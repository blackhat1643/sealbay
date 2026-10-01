<?php
/**
 * Sessions, CSRF protection, response headers, rate limiting and upload validation.
 */
defined('ST_APP') || exit;

/** Inline snippet that flags JS support before first paint (whitelisted in the CSP by hash). */
const ST_BOOT_JS = "document.documentElement.className+=' js'";

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    $bootHash = base64_encode(hash('sha256', ST_BOOT_JS, true));
    $csp = [
        "default-src 'self'",
        "img-src 'self' data:",
        "style-src 'self' 'unsafe-inline'",
        "script-src 'self' 'sha256-$bootHash'",
        "font-src 'self'",
        'frame-src https://www.google.com https://maps.google.com',
        "base-uri 'self'",
        "form-action 'self' https://checkout.stripe.com",
        "frame-ancestors 'self'",
        "object-src 'none'",
    ];
    header('Content-Security-Policy: ' . implode('; ', $csp));
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), camera=(), microphone=()');
    header_remove('X-Powered-By');
}

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name('sb_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() . '/',
        'secure'   => is_https(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    start_session();
    $sent = $_POST['csrf_token'] ?? '';
    return is_string($sent) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

/**
 * Light protection for cart actions, which do not carry a CSRF token so that
 * browsing visitors need no session: the POST must come from this site.
 */
function same_origin_post(): bool
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        return false;
    }
    $fetchSite = (string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? '');
    if ($fetchSite !== '' && !in_array($fetchSite, ['same-origin', 'none'], true)) {
        return false;
    }
    $origin = (string) ($_SERVER['HTTP_ORIGIN'] ?? '');
    if ($origin !== '' && $origin !== 'null') {
        return parse_url($origin, PHP_URL_HOST) === parse_url(site_origin(), PHP_URL_HOST)
            || parse_url($origin, PHP_URL_HOST) === preg_replace('/:\d+$/', '', (string) ($_SERVER['HTTP_HOST'] ?? ''));
    }
    return true;
}

/* ---------- Flash messages ---------- */

function flash(string $key, ?string $message = null): ?string
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $value;
}

/* ---------- Spam protection for public forms ---------- */

/** Hidden fields: a honeypot that humans leave empty and a render timestamp. */
function spam_trap_fields(string $form): string
{
    start_session();
    $_SESSION['form_time'][$form] = time();
    return '<div class="hp-field" aria-hidden="true">'
        . '<label for="' . e($form) . '-website">Website</label>'
        . '<input type="text" id="' . e($form) . '-website" name="website" tabindex="-1" autocomplete="off">'
        . '</div>';
}

/** Returns true when the submission looks automated. */
function spam_trap_triggered(string $form): bool
{
    start_session();
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        return true;
    }
    $rendered = (int) ($_SESSION['form_time'][$form] ?? 0);
    if ($rendered === 0) {
        return true; // form was never rendered in this session
    }
    return (time() - $rendered) < (int) config('security.form_min_seconds', 3);
}

/**
 * File-based sliding-window rate limiter (works without a database).
 * Returns true if the action is allowed and records the hit.
 */
function rate_limit_allow(string $bucket, int $max, int $windowSeconds): bool
{
    $dir = ST_STORAGE . '/cache';
    if (!is_dir($dir) || !is_writable($dir)) {
        return true;
    }
    $file = $dir . '/rl_' . hash('sha256', $bucket) . '.json';
    $now  = time();
    $fh   = @fopen($file, 'c+');
    if (!$fh) {
        return true;
    }
    flock($fh, LOCK_EX);
    $hits = json_decode((string) stream_get_contents($fh), true);
    $hits = is_array($hits) ? array_values(array_filter($hits, static fn ($t) => is_int($t) && $t > $now - $windowSeconds)) : [];
    $allowed = count($hits) < $max;
    if ($allowed) {
        $hits[] = $now;
    }
    ftruncate($fh, 0);
    rewind($fh);
    fwrite($fh, json_encode($hits));
    flock($fh, LOCK_UN);
    fclose($fh);

    // Occasionally clear out stale limiter files.
    if (random_int(1, 50) === 1) {
        foreach (glob($dir . '/rl_*.json') ?: [] as $old) {
            if (filemtime($old) < $now - 86400) {
                @unlink($old);
            }
        }
    }
    return $allowed;
}

/* ---------- Upload validation ---------- */

/**
 * Validate an uploaded file against an extension => MIME allow-list and move it
 * to $targetDir under a random name. Returns ['ok'=>bool, 'error'=>?string,
 * 'file'=>?string (stored name), 'original'=>?string].
 * A missing optional upload returns ok with file = null.
 */
function handle_upload(array $file, array $allowed, int $maxBytes, string $targetDir, int $mode = 0640): array
{
    $result = ['ok' => true, 'error' => null, 'file' => null, 'original' => null];

    $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
    if (is_array($error)) {
        return ['ok' => false, 'error' => 'Please upload a single file.'] + $result;
    }
    if ($error === UPLOAD_ERR_NO_FILE) {
        return $result;
    }
    if ($error === UPLOAD_ERR_INI_SIZE || $error === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'error' => 'The file is larger than the allowed size.'] + $result;
    }
    if ($error !== UPLOAD_ERR_OK || !is_uploaded_file((string) $file['tmp_name'])) {
        return ['ok' => false, 'error' => 'The file could not be uploaded. Please try again.'] + $result;
    }
    $size = (int) filesize($file['tmp_name']);
    if ($size <= 0 || $size > $maxBytes) {
        return ['ok' => false, 'error' => 'The file must be smaller than ' . format_bytes($maxBytes) . '.'] + $result;
    }

    $original = (string) $file['name'];
    $ext      = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return ['ok' => false, 'error' => 'Allowed file types: ' . strtoupper(implode(', ', array_keys($allowed))) . '.'] + $result;
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: '';
    if (!in_array($mime, $allowed[$ext], true)) {
        return ['ok' => false, 'error' => 'The file contents do not match its type.'] + $result;
    }
    if (str_starts_with($mime, 'image/') && @getimagesize($file['tmp_name']) === false) {
        return ['ok' => false, 'error' => 'The image file appears to be damaged.'] + $result;
    }
    if (!is_dir($targetDir) && !@mkdir($targetDir, 0750, true)) {
        return ['ok' => false, 'error' => 'Upload folder is not available.'] + $result;
    }

    $stored = bin2hex(random_bytes(16)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!move_uploaded_file($file['tmp_name'], $targetDir . '/' . $stored)) {
        return ['ok' => false, 'error' => 'The file could not be saved.'] + $result;
    }
    @chmod($targetDir . '/' . $stored, $mode);

    $cleanName = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($original)) ?? 'attachment';
    return ['ok' => true, 'error' => null, 'file' => $stored, 'original' => mb_substr($cleanName, 0, 150)];
}

function format_bytes(int $bytes): string
{
    if ($bytes >= 1048576) {
        return rtrim(rtrim(number_format($bytes / 1048576, 1), '0'), '.') . ' MB';
    }
    return max(1, (int) round($bytes / 1024)) . ' KB';
}
