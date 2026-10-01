<?php
/**
 * Storage that works without a persistent disk.
 *
 * With app.storage = 'db' (automatic on Vercel) three things that normally live in
 * files are kept in the database instead:
 *   - PHP sessions (cart, CSRF tokens, admin sign-in)      → table `sessions`
 *   - uploaded files (customer photos, product images)     → table `uploads`
 *   - rate-limit counters                                  → table `rate_hits`
 * On normal PHP hosting (app.storage = 'disk') none of this is used.
 */
defined('ST_APP') || exit;

function storage_in_db(): bool
{
    return config('app.storage') === 'db';
}

/** Sessions stored in the database. Data is base64-encoded so it is safe in a text column. */
final class DbSessionHandler implements SessionHandlerInterface, SessionUpdateTimestampHandlerInterface
{
    private const LIFETIME = 172800; // two days — long enough to come back to a cart

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $data = db_value('SELECT data FROM sessions WHERE id = ? AND expires_at > ?', [$id, time()]);
        return is_string($data) ? (string) base64_decode($data, true) : '';
    }

    public function write(string $id, string $data): bool
    {
        db_run('REPLACE INTO sessions (id, data, expires_at) VALUES (?, ?, ?)', [$id, base64_encode($data), time() + self::LIFETIME]);
        if (random_int(1, 50) === 1) {
            $this->gc(0);
        }
        return true;
    }

    public function destroy(string $id): bool
    {
        db_run('DELETE FROM sessions WHERE id = ?', [$id]);
        return true;
    }

    public function gc(int $max_lifetime): int|false
    {
        return db_run('DELETE FROM sessions WHERE expires_at < ?', [time()])->rowCount();
    }

    public function validateId(string $id): bool
    {
        return (int) db_value('SELECT COUNT(*) FROM sessions WHERE id = ? AND expires_at > ?', [$id, time()]) > 0;
    }

    public function updateTimestamp(string $id, string $data): bool
    {
        return $this->write($id, $data);
    }
}

/* ---------- Uploaded files in the database ---------- */

/**
 * Move a file that handle_upload() saved to disk into the `uploads` table.
 * $kind: 'image' (public product / category pictures) or 'enquiry' (private customer photos).
 */
function stored_file_put(string $name, string $path, string $kind): bool
{
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path) ?: 'application/octet-stream';
    $size = str_starts_with($mime, 'image/') ? (@getimagesize($path) ?: [0, 0]) : [0, 0];
    try {
        $stmt = db()->prepare('INSERT INTO uploads (name, kind, mime, width, height, data, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->bindValue(1, $name);
        $stmt->bindValue(2, $kind);
        $stmt->bindValue(3, $mime);
        $stmt->bindValue(4, (int) $size[0], PDO::PARAM_INT);
        $stmt->bindValue(5, (int) $size[1], PDO::PARAM_INT);
        $stmt->bindValue(6, file_get_contents($path), PDO::PARAM_LOB);
        $stmt->bindValue(7, db_now());
        $stmt->execute();
        @unlink($path);
        return true;
    } catch (Throwable $ex) {
        app_log('stored_file_put(): ' . $ex->getMessage());
        return false;
    }
}

/** ['mime' => …, 'data' => binary] or null. */
function stored_file_get(string $name, string $kind): ?array
{
    if (!preg_match('/^[a-f0-9]{32}\.[a-z0-9]{3,4}$/', $name) || !db_ready()) {
        return null;
    }
    $row = db_one('SELECT mime, data FROM uploads WHERE name = ? AND kind = ?', [$name, $kind]);
    if (!$row) {
        return null;
    }
    $data = is_resource($row['data']) ? stream_get_contents($row['data']) : $row['data'];
    return ['mime' => (string) $row['mime'], 'data' => (string) $data];
}

function stored_file_delete(string $name): void
{
    if (preg_match('/^[a-f0-9]{32}\.[a-z0-9]{3,4}$/', $name) && db_ready()) {
        db_run('DELETE FROM uploads WHERE name = ?', [$name]);
    }
}

/** Sizes of all public images held in the database: [name => [width, height]] (one query per request). */
function stored_images_meta(): array
{
    static $meta = null;
    if ($meta !== null) {
        return $meta;
    }
    $meta = [];
    if (storage_in_db() && db_ready()) {
        try {
            foreach (db_all("SELECT name, width, height FROM uploads WHERE kind = 'image'") as $row) {
                $meta[$row['name']] = [(int) $row['width'], (int) $row['height']];
            }
        } catch (PDOException $ex) {
            app_log('stored_images_meta(): ' . $ex->getMessage());
        }
    }
    return $meta;
}
