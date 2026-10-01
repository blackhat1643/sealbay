<?php
/**
 * Admin panel bootstrap: authentication, CSRF enforcement and page layout.
 * Every admin page starts with:  require __DIR__ . '/includes/admin.php';
 */
require __DIR__ . '/../../includes/bootstrap.php';

start_session();
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');

function admin_url(string $path = ''): string
{
    return url('admin/' . ltrim($path, '/'));
}

/** Currently signed-in admin (or null). Signs out after a period of inactivity. */
function admin_user(): ?array
{
    $user = $_SESSION['admin'] ?? null;
    if (!is_array($user)) {
        return null;
    }
    $idle = (int) config('security.admin_idle_seconds', 1800);
    if (time() - (int) ($_SESSION['admin_seen'] ?? 0) > $idle) {
        unset($_SESSION['admin'], $_SESSION['admin_seen']);
        flash('admin_err', 'You were signed out after a period of inactivity.');
        return null;
    }
    $_SESSION['admin_seen'] = time();
    return $user;
}

function admin_require_login(): array
{
    if (!db_ready()) {
        redirect(url('install/'));
    }
    $user = admin_user();
    if (!$user) {
        redirect(admin_url('login.php'));
    }
    return $user;
}

/** Abort unless the request is a POST carrying a valid CSRF token. */
function admin_require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !csrf_valid()) {
        http_response_code(400);
        admin_header('Request expired', '');
        echo '<div class="a-alert a-alert--err">The form has expired or the request was not valid. Please go back and try again.</div>';
        admin_footer();
        exit;
    }
}

/** Too many failed sign-in attempts from this IP address recently? */
function admin_login_locked(): bool
{
    $since = date('Y-m-d H:i:s', time() - (int) config('security.login_lock_seconds', 900));
    $count = (int) db_value('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?', [client_ip(), $since]);
    return $count >= (int) config('security.login_max_attempts', 5);
}

/** Verify credentials; returns true on success. */
function admin_attempt_login(string $username, string $password): bool
{
    $row = db_one('SELECT * FROM admins WHERE username = ?', [$username]);
    // Verify against a dummy hash when the user does not exist so timing does not reveal valid usernames.
    $hash = $row['password_hash'] ?? '$2y$12$/LX6MtdVNh6ZmE03cxl3.ucGCcQqEgHmt0i6vQc8hFWLGK2qCckSi';
    $ok   = password_verify($password, $hash) && $row !== null;

    if (!$ok) {
        db_run('INSERT INTO login_attempts (ip_address, username, attempted_at) VALUES (?, ?, ?)', [client_ip(), mb_substr($username, 0, 60), db_now()]);
        return false;
    }

    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db_run('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    db_run('DELETE FROM login_attempts WHERE ip_address = ? OR attempted_at < ?', [client_ip(), date('Y-m-d H:i:s', time() - 86400)]);
    db_run('UPDATE admins SET last_login_at = ? WHERE id = ?', [db_now(), $row['id']]);

    session_regenerate_id(true);
    $_SESSION['admin']      = ['id' => (int) $row['id'], 'username' => $row['username']];
    $_SESSION['admin_seen'] = time();
    unset($_SESSION['csrf_token']);
    return true;
}

/* ---------- Layout ---------- */

function admin_header(string $title, string $active): void
{
    $user   = $_SESSION['admin'] ?? null;
    $badges = [];
    if ($user && db_ready()) {
        $badges['orders']    = (int) db_value("SELECT COUNT(*) FROM orders WHERE status = 'paid'");
        $badges['enquiries'] = (int) db_value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'");
    }
    $nav = [
        'dashboard'  => ['Dashboard', 'index.php'],
        'orders'     => ['Orders', 'orders.php'],
        'products'   => ['Products', 'products.php'],
        'categories' => ['Categories', 'categories.php'],
        'articles'   => ['Guides', 'articles.php'],
        'enquiries'  => ['Messages & quotes', 'enquiries.php'],
        'shop'       => ['Shop Settings', 'shop-settings.php'],
        'settings'   => ['Business Details', 'settings.php'],
        'account'    => ['My Account', 'account.php'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — <?= e(company('name')) ?> Admin</title>
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="<?= $user ? 'a-app' : 'a-guest' ?>">
<?php if ($user): ?>
<aside class="a-side">
  <a class="a-brand" href="<?= e(admin_url()) ?>"><?= logo_mark(32) ?><span><?= e(company('name')) ?><small>Admin</small></span></a>
  <nav aria-label="Admin">
    <?php foreach ($nav as $key => [$label, $href]): ?>
    <a class="<?= $active === $key ? 'is-active' : '' ?>" href="<?= e(admin_url($href)) ?>"><?= e($label) ?><?php if (!empty($badges[$key])): ?><b><?= (int) $badges[$key] ?></b><?php endif; ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="a-side__foot">
    <a href="<?= e(url('')) ?>" target="_blank" rel="noopener">View website ↗</a>
    <form method="post" action="<?= e(admin_url('logout.php')) ?>">
      <?= csrf_field() ?>
      <button type="submit">Sign out (<?= e($user['username']) ?>)</button>
    </form>
  </div>
</aside>
<?php endif; ?>
<main class="a-main">
  <?php if ($user): ?><h1 class="a-title"><?= e($title) ?></h1><?php endif; ?>
  <?php if ($msg = flash('admin_ok')): ?><div class="a-alert a-alert--ok" role="status"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($msg = flash('admin_err')): ?><div class="a-alert a-alert--err" role="alert"><?= e($msg) ?></div><?php endif; ?>
    <?php
}

function admin_footer(): void
{
    echo "</main>\n" . '<script src="' . e(asset('js/admin.js')) . '" defer></script>' . "\n</body>\n</html>";
}

/* ---------- Form helpers ---------- */

function a_field(string $name, string $label, ?string $value, array $opt = []): string
{
    $type  = $opt['type'] ?? 'text';
    $hint  = !empty($opt['hint']) ? '<small>' . e($opt['hint']) . '</small>' : '';
    $req   = !empty($opt['required']) ? ' required' : '';
    $max   = isset($opt['maxlength']) ? ' maxlength="' . (int) $opt['maxlength'] . '"' : '';
    $id    = 'a-' . $name;
    $html  = '<div class="a-field' . (!empty($opt['wide']) ? ' a-field--wide' : '') . '"><label for="' . $id . '">' . e($label) . '</label>';
    if ($type === 'textarea') {
        $html .= '<textarea id="' . $id . '" name="' . e($name) . '" rows="' . (int) ($opt['rows'] ?? 4) . '"' . $req . '>' . e($value ?? '') . '</textarea>';
    } elseif ($type === 'select') {
        $html .= '<select id="' . $id . '" name="' . e($name) . '"' . $req . '>';
        foreach ($opt['options'] as $optValue => $optLabel) {
            $html .= '<option value="' . e($optValue) . '"' . ((string) $optValue === (string) $value ? ' selected' : '') . '>' . e($optLabel) . '</option>';
        }
        $html .= '</select>';
    } else {
        $html .= '<input id="' . $id . '" type="' . e($type) . '" name="' . e($name) . '" value="' . e($value ?? '') . '"' . $req . $max
            . (isset($opt['autocomplete']) ? ' autocomplete="' . e($opt['autocomplete']) . '"' : '') . '>';
    }
    return $html . $hint . '</div>';
}

/** Trimmed POST value. */
function a_post(string $name, bool $multiline = false): string
{
    $value = $_POST[$name] ?? '';
    if (!is_string($value)) {
        return '';
    }
    $value = str_replace("\0", '', $value);
    return $multiline ? trim(str_replace(["\r\n", "\r"], "\n", $value)) : trim(preg_replace('/\s+/u', ' ', $value) ?? '');
}

/** Handle an optional image upload for products / categories. Returns ['path' => ?string, 'error' => ?string]. */
function a_image_upload(string $field): array
{
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['path' => null, 'error' => null];
    }
    $allowed = ['jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'], 'webp' => ['image/webp']];
    $inDb    = storage_in_db();
    $result  = handle_upload($_FILES[$field], $allowed, (int) config('uploads.image_max_bytes'), $inDb ? ST_STORAGE . '/uploads' : ST_ROOT . '/assets/uploads', 0644);
    if (!$result['ok']) {
        return ['path' => null, 'error' => $result['error']];
    }
    if ($result['file'] !== null && $inDb && !stored_file_put($result['file'], ST_STORAGE . '/uploads/' . $result['file'], 'image')) {
        return ['path' => null, 'error' => 'The image could not be saved.'];
    }
    return ['path' => 'uploads/' . $result['file'], 'error' => null];
}

function a_delete_image(?string $relPath): void
{
    if ($relPath && preg_match('#^uploads/[a-z0-9]+\.(?:webp|jpe?g|png)$#', $relPath)) {
        @unlink(ST_ROOT . '/assets/' . $relPath);
        stored_file_delete(basename($relPath));
    }
}

/** Make a slug unique within a table (appends -2, -3 … if needed). */
function a_unique_slug(string $table, string $slug, int $ignoreId = 0): string
{
    $table = in_array($table, ['categories', 'products', 'articles'], true) ? $table : 'products';
    $slug  = $slug !== '' ? $slug : 'item';
    $base  = $slug;
    $n     = 1;
    while (db_value("SELECT COUNT(*) FROM $table WHERE slug = ? AND id <> ?", [$slug, $ignoreId]) > 0) {
        $slug = $base . '-' . (++$n);
    }
    return $slug;
}

function a_status_label(string $status): string
{
    $labels = a_statuses() + ORDER_STATUSES;
    return '<span class="a-badge a-badge--' . e($status) . '">' . e($labels[$status] ?? $status) . '</span>';
}

function a_statuses(): array
{
    return ['new' => 'New', 'in_progress' => 'In progress', 'quoted' => 'Quoted', 'closed' => 'Closed'];
}
