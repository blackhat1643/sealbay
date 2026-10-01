<?php
/**
 * One-time installer: creates the database tables, loads the sample catalogue and
 * guides from /data and creates the first administrator.
 *
 * It locks itself after a successful run (storage/installed.lock).
 * DELETE THE /install FOLDER FROM THE SERVER AFTERWARDS.
 */
require __DIR__ . '/../includes/bootstrap.php';
start_session();
header('Cache-Control: no-store, private');
header('X-Robots-Tag: noindex, nofollow');

$lockFile  = ST_STORAGE . '/installed.lock';
$inDb      = storage_in_db();   // no persistent disk (e.g. Vercel): the database is the "installed" marker
$installed = is_file($lockFile);
if ($inDb && db()) {
    try {
        $installed = (int) db()->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0;
    } catch (PDOException $ex) {
        $installed = false;   // tables do not exist yet
    }
}
$isLocal   = is_local_request();
$setupKey  = (string) config('app.setup_key', '');
$errors    = [];
$done      = false;

/* ---------- Environment checks ---------- */
$driver = db_driver();
$checks = [
    ['PHP 8.0 or newer (running ' . PHP_VERSION . ')', version_compare(PHP_VERSION, '8.0.0', '>=')],
    ['PDO extension for ' . ($driver === 'sqlite' ? 'SQLite' : 'MySQL'), extension_loaded($driver === 'sqlite' ? 'pdo_sqlite' : 'pdo_mysql')],
    ['Fileinfo extension (upload validation)', extension_loaded('fileinfo')],
    ['mbstring extension', extension_loaded('mbstring')],
    ['Working folder is writable', is_writable(ST_STORAGE)],
    ['Uploads folder is writable', is_writable(ST_STORAGE . '/uploads')],
    [$inDb ? 'Uploaded images are kept in the database (no persistent disk)' : '/assets/uploads folder is writable', $inDb || is_writable(ST_ROOT . '/assets/uploads')],
    ['cURL extension (card payments)', extension_loaded('curl')],
    ['Database details are configured', db_configured()],
    ['Database connection works', db_configured() && db() !== null],
];
if (!$isLocal) {
    $checks[] = ['Setup key is set in the configuration file (app.setup_key)', $setupKey !== ''];
}
$ready = !in_array(false, array_column($checks, 1), true);

/* ---------- Install ---------- */
if (!$installed && $ready && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
    $email    = trim((string) ($_POST['email'] ?? ''));
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $confirm  = is_string($_POST['confirm'] ?? null) ? $_POST['confirm'] : '';

    if (!csrf_valid()) {
        $errors[] = 'The form expired. Please try again.';
    }
    if (!$isLocal && !hash_equals($setupKey, (string) ($_POST['setup_key'] ?? ''))) {
        $errors[] = 'The setup key is not correct.';
    }
    if (!preg_match('/^[a-z0-9._-]{3,60}$/', $username)) {
        $errors[] = 'Username: 3–60 characters, letters, numbers, dot, dash or underscore.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 10) {
        $errors[] = 'The password must be at least 10 characters long.';
    } elseif ($password !== $confirm) {
        $errors[] = 'The passwords do not match.';
    }

    if (!$errors) {
        try {
            $pdo = db();
            foreach (schema_statements() as $sql) {
                try {
                    $pdo->exec($sql);
                } catch (PDOException $ex) {
                    // Re-running after a partial install: an index may already exist.
                    if (!str_starts_with($sql, 'CREATE INDEX')) {
                        throw $ex;
                    }
                }
            }
            seed_default_content($pdo);

            if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
                db_run('INSERT INTO admins (username, email, password_hash, created_at) VALUES (?, ?, ?, ?)',
                    [$username, $email, password_hash($password, PASSWORD_DEFAULT), db_now()]);
            } else {
                $errors[] = 'An administrator already exists in this database — the existing account was kept.';
            }
            if (!$inDb) {
                file_put_contents($lockFile, 'Installed ' . date('c') . PHP_EOL, LOCK_EX);
            }
            $done = true;
        } catch (Throwable $ex) {
            app_log('Installer: ' . $ex->getMessage());
            $errors[] = 'Installation failed: ' . $ex->getMessage();
        }
    }
}

/** Copy the sample content from /data into empty tables. */
function seed_default_content(PDO $pdo): void
{
    $now = db_now();

    if ((int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn() === 0) {
        $ids = [];
        foreach (data_file('categories') as $i => $c) {
            db_run(
                'INSERT INTO categories (slug, name, headline, summary, description, illustration, sort_order, is_active, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)',
                [$c['slug'], $c['name'], $c['headline'], $c['summary'], $c['description'], $c['illustration'], ($i + 1) * 10, $now]
            );
            $ids[$c['slug']] = (int) $pdo->lastInsertId();
        }
        foreach (data_file('products') as $p) {
            if (!isset($ids[$p['category']])) {
                continue;
            }
            db_run(
                'INSERT INTO products (category_id, slug, sku, name, seal_type, style, material, inner_diameter, outer_diameter, width, temp_range,
                    price_cents, stock_qty, allow_backorder, summary, fitment, kit_contents, illustration, is_featured, sort_order, is_active, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, ?)',
                [$ids[$p['category']], $p['slug'], $p['sku'], $p['name'], $p['seal_type'], $p['style'], $p['material'],
                 $p['inner_diameter'], $p['outer_diameter'], $p['width'], $p['temp_range'], (int) $p['price_cents'], (int) $p['stock_qty'],
                 !empty($p['allow_backorder']) ? 1 : 0, $p['summary'], $p['fitment'], implode("\n", $p['kit_contents'] ?? []), $p['illustration'],
                 !empty($p['is_featured']) ? 1 : 0, $now]
            );
        }
    }

    if ((int) $pdo->query('SELECT COUNT(*) FROM articles')->fetchColumn() === 0) {
        foreach (data_file('articles') as $a) {
            db_run(
                'INSERT INTO articles (slug, title, topic, excerpt, body, faqs, reading_minutes, meta_title, meta_description, is_published, published_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)',
                [$a['slug'], $a['title'], $a['topic'], $a['excerpt'], $a['body'], faqs_to_text($a['faqs'] ?? []), (int) $a['reading_minutes'],
                 $a['meta_title'], $a['meta_description'], $a['published_at'] . ' 00:00:00', $a['published_at'] . ' 00:00:00']
            );
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Install — <?= e(company('name')) ?></title>
<link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
</head>
<body class="a-guest a-guest--wide">
<main class="a-main">
  <div class="a-install">
    <div class="a-login__brand"><?= logo_mark(44) ?><strong><?= e(company('name')) ?></strong><span>Website setup</span></div>

    <?php if ($done): ?>
      <div class="a-alert a-alert--ok">Installation complete. The database tables were created and the sample catalogue and guides were loaded.</div>
      <?php foreach ($errors as $error): ?><div class="a-alert a-alert--warn"><?= e($error) ?></div><?php endforeach; ?>
      <?php if ($inDb): ?>
      <p>The installer is now locked: it cannot be run again while an administrator exists in the database.</p>
      <?php else: ?>
      <p><strong>Now delete the <code>/install</code> folder from the server.</strong></p>
      <?php endif; ?>
      <p><a class="a-btn a-btn--primary" href="<?= e(url('admin/login.php')) ?>">Go to admin sign-in</a></p>

    <?php elseif ($installed): ?>
      <div class="a-alert a-alert--warn">The website is already installed.<?= $inDb ? '' : ' For security, delete the <code>/install</code> folder from the server.' ?></div>
      <p><a class="a-btn" href="<?= e(url('admin/login.php')) ?>">Go to admin sign-in</a></p>

    <?php else: ?>
      <h2>1. Server check</h2>
      <ul class="a-checks">
        <?php foreach ($checks as [$label, $ok]): ?><li class="<?= $ok ? '' : 'is-bad' ?>"><?= e($label) ?></li><?php endforeach; ?>
      </ul>

      <?php if (!$ready): ?>
        <div class="a-alert a-alert--err">Fix the items marked ✕, then reload this page. <?php if (getenv('VERCEL')): ?>On Vercel, set <code>DATABASE_URL</code> (or <code>DB_HOST</code>, <code>DB_NAME</code>, <code>DB_USER</code>, <code>DB_PASSWORD</code>) and <code>SETUP_KEY</code> under Project → Settings → Environment Variables, then redeploy.<?php else: ?>Database details and the setup key are set in <code>includes/config.local.php</code> (or <code>sealbay-config.php</code> above the web root) — see <code>includes/config.local.example.php</code>.<?php endif; ?></div>
      <?php else: ?>
        <h2>2. Create the administrator</h2>
        <?php foreach ($errors as $error): ?><div class="a-alert a-alert--err"><?= e($error) ?></div><?php endforeach; ?>
        <form method="post" class="a-form" autocomplete="off">
          <?= csrf_field() ?>
          <?php if (!$isLocal): ?>
          <div class="a-field a-field--wide"><label for="i-key">Setup key</label><input id="i-key" type="password" name="setup_key" required><small>The value of <code>app.setup_key</code> in your configuration file.</small></div>
          <?php endif; ?>
          <div class="a-field"><label for="i-user">Username</label><input id="i-user" type="text" name="username" value="<?= e($_POST['username'] ?? '') ?>" required maxlength="60"></div>
          <div class="a-field"><label for="i-email">Email</label><input id="i-email" type="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required maxlength="190"></div>
          <div class="a-field"><label for="i-pass">Password</label><input id="i-pass" type="password" name="password" required minlength="10" autocomplete="new-password"><small>At least 10 characters.</small></div>
          <div class="a-field"><label for="i-confirm">Repeat password</label><input id="i-confirm" type="password" name="confirm" required minlength="10" autocomplete="new-password"></div>
          <div class="a-form__foot"><button class="a-btn a-btn--primary" type="submit">Install</button></div>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
