<?php
/**
 * General helpers: config access, escaping, URLs, assets, company details.
 */
defined('ST_APP') || exit;

/** Read a config value using dot notation, e.g. config('db.host'). */
function config(string $key, mixed $default = null): mixed
{
    $value = $GLOBALS['st_config'] ?? [];
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }
    return $value;
}

/** True for a genuine loopback request (your own machine), not a live site behind a same-host proxy. */
function is_local_request(): bool
{
    if (PHP_SAPI === 'cli') {
        return true;
    }
    if (getenv('VERCEL') || getenv('VERCEL_URL')) {
        return false;
    }
    return in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true)
        && empty($_SERVER['HTTP_X_FORWARDED_FOR'])
        && preg_match('/^(localhost|127\.0\.0\.1|\[::1\])(:\d+)?$/', (string) ($_SERVER['HTTP_HOST'] ?? '')) === 1;
}

/**
 * Development mode shows PHP errors and setup hints. As a safety net it only
 * applies on your own machine, so a development config uploaded by mistake
 * cannot expose errors on a live server (set app.env to 'staging' to force it).
 */
function is_dev(): bool
{
    $env = config('app.env');
    if ($env === 'staging') {
        return true;
    }
    return $env === 'development' && is_local_request();
}

/** Escape for HTML output. */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Sub-folder the site is served from ('' when installed at the domain root). */
function base_path(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configured = config('app.base_path');
    if (is_string($configured)) {
        return $base = rtrim($configured, '/');
    }
    $docRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath((string) $_SERVER['DOCUMENT_ROOT']) : false;
    $root    = realpath(ST_ROOT);
    if ($docRoot && $root && str_starts_with($root, $docRoot)) {
        return $base = rtrim(str_replace('\\', '/', substr($root, strlen($docRoot))), '/');
    }
    return $base = '';
}

/** Root-relative URL for an internal path: url('products/rotary-seals.php'). */
function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

/** Scheme + host of the site, from config or (validated) request headers. */
function site_origin(): string
{
    static $origin = null;
    if ($origin !== null) {
        return $origin;
    }
    $configured = (string) config('app.site_url', '');
    if ($configured !== '') {
        $parts = parse_url($configured);
        if (!empty($parts['scheme']) && !empty($parts['host'])) {
            return $origin = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        }
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    if (!preg_match('/^[a-z0-9.\-]+(:\d{1,5})?$/i', $host)) {
        $host = 'localhost';
    }
    return $origin = (is_https() ? 'https' : 'http') . '://' . $host;
}

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') == 443)
        || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');
}

/** Absolute URL for an internal path. */
function abs_url(string $path = ''): string
{
    return site_origin() . url($path);
}

/** Versioned URL for a file in /assets (cache-busted by modification time). */
function asset(string $path): string
{
    $path = ltrim($path, '/');
    $file = ST_ROOT . '/assets/' . $path;
    $v    = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . $path) . $v;
}

/** Path of the current request relative to the site root, e.g. 'products/rotary-seals.php'. */
function current_path(): string
{
    $uri  = (string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH);
    $base = base_path();
    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }
    $uri = ltrim($uri, '/');
    return $uri === '' ? 'index.php' : $uri;
}

/** True when the current page matches one of the given path prefixes. */
function nav_active(string ...$prefixes): bool
{
    $path = current_path();
    foreach ($prefixes as $prefix) {
        if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/') . '/')) {
            return true;
        }
    }
    return false;
}

function redirect(string $to, int $status = 302): never
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Location: ' . $to, true, $status);
    exit;
}

/**
 * Company detail: a value saved in Admin → Company Information wins,
 * otherwise the value from config.php is used.
 */
function company(string $key, string $default = ''): string
{
    $settings = settings();
    if (isset($settings[$key]) && trim((string) $settings[$key]) !== '') {
        return trim((string) $settings[$key]);
    }
    $value = config('company.' . $key, $default);
    return is_scalar($value) ? trim((string) $value) : $default;
}

/** Suburb / state line, or just the country when no address has been entered. */
function company_location(): string
{
    $parts = array_filter([company('suburb'), company('state')]);
    return $parts ? implode(' ', $parts) : company('country', 'Australia');
}

/** Full postal address lines (only parts that have been configured). */
function company_address_lines(): array
{
    $suburbLine = trim(implode(' ', array_filter([company('suburb'), company('state'), company('postcode')])));
    $lines      = array_values(array_filter([company('address_line1'), company('address_line2'), $suburbLine]));
    return $lines ? [...$lines, company('country', 'Australia')] : [];
}

/** Shop setting: a value saved in Admin → Shop Settings wins over config('shop.*'). */
function shop(string $key, string $default = ''): string
{
    $settings = settings();
    if (isset($settings['shop_' . $key]) && trim((string) $settings['shop_' . $key]) !== '') {
        return trim((string) $settings['shop_' . $key]);
    }
    $value = config('shop.' . $key, $default);
    return is_scalar($value) ? trim((string) $value) : $default;
}

/** Format an amount held in cents as Australian dollars: 1295 → "$12.95". */
function money(int $cents): string
{
    return ($cents < 0 ? '-' : '') . '$' . number_format(abs($cents) / 100, 2);
}

/** Parse a dollar amount typed by a person ("12.5", "$12.50") into cents; null if invalid. */
function to_cents(string $amount): ?int
{
    $amount = str_replace(['$', ',', ' '], '', $amount);
    if ($amount === '' || !preg_match('/^\d{1,7}(\.\d{1,2})?$/', $amount)) {
        return null;
    }
    return (int) round(((float) $amount) * 100);
}

/** Millimetre value without trailing zeros: 35.00 → "35", 6.50 → "6.5". */
function mm(float|string|null $value): string
{
    if ($value === null || $value === '') {
        return '';
    }
    return rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
}

function tel_href(string $phone): string
{
    return 'tel:' . preg_replace('/[^0-9+]/', '', $phone);
}

/** Split a textarea value into trimmed, non-empty lines. */
function str_lines(?string $text): array
{
    if ($text === null || trim($text) === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), 'strlen'));
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/**
 * Look up an optional photograph in assets/img/photos (or an uploaded image path).
 * Returns ['src','alt','w','h'] or null when the file has not been supplied —
 * components then fall back to a technical illustration.
 */
function photo(string $key, string $alt = ''): ?array
{
    static $altMap = null;
    if ($altMap === null) {
        $file   = ST_ROOT . '/assets/img/photos/alt.php';
        $altMap = is_file($file) ? (array) require $file : [];
    }
    foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
        $rel  = 'img/photos/' . $key . '.' . $ext;
        $file = ST_ROOT . '/assets/' . $rel;
        if (is_file($file)) {
            $size = @getimagesize($file) ?: [0, 0];
            return [
                'src' => asset($rel),
                'alt' => $alt !== '' ? $alt : (string) ($altMap[$key] ?? ''),
                'w'   => (int) $size[0],
                'h'   => (int) $size[1],
            ];
        }
    }
    return null;
}

/** An image uploaded through the admin panel (stored under assets/uploads). */
function uploaded_image(?string $relPath, string $alt = ''): ?array
{
    if (!$relPath || !preg_match('#^uploads/[a-z0-9]+\.(?:webp|jpe?g|png)$#', $relPath)) {
        return null;
    }
    $file = ST_ROOT . '/assets/' . $relPath;
    if (!is_file($file)) {
        // Hosts without a persistent disk keep uploaded images in the database (served by media.php).
        $name = basename($relPath);
        $meta = stored_images_meta()[$name] ?? null;
        return $meta ? ['src' => url('media.php?f=' . $name), 'alt' => $alt, 'w' => $meta[0], 'h' => $meta[1]] : null;
    }
    $size = @getimagesize($file) ?: [0, 0];
    return ['src' => asset($relPath), 'alt' => $alt, 'w' => (int) $size[0], 'h' => (int) $size[1]];
}

/** <img> tag for a photo() result. */
function img_tag(array $photo, array $attrs = []): string
{
    $attrs = array_merge([
        'src'      => $photo['src'],
        'alt'      => $photo['alt'],
        'width'    => $photo['w'] ?: null,
        'height'   => $photo['h'] ?: null,
        'loading'  => 'lazy',
        'decoding' => 'async',
    ], $attrs);
    $html = '<img';
    foreach ($attrs as $name => $value) {
        if ($value === null || $value === false) {
            continue;
        }
        $html .= ' ' . $name . '="' . e($value) . '"';
    }
    return $html . '>';
}

/**
 * Sanitise editor HTML (article bodies). Keeps a small set of structural tags,
 * removes every attribute except safe href values on links.
 */
function safe_html(string $html): string
{
    $allowed = '<p><h2><h3><h4><ul><ol><li><strong><b><em><i><a><blockquote><table><thead><tbody><tr><th><td><br>';
    $html    = strip_tags($html, $allowed);

    return preg_replace_callback('/<([a-z0-9]+)(\s[^>]*)?>/i', static function (array $m): string {
        $tag = strtolower($m[1]);
        if ($tag !== 'a') {
            return '<' . $tag . '>';
        }
        $href = '';
        if (isset($m[2]) && preg_match('/href\s*=\s*("([^"]*)"|\'([^\']*)\')/i', $m[2], $h)) {
            $href = html_entity_decode($h[2] !== '' ? $h[2] : ($h[3] ?? ''), ENT_QUOTES, 'UTF-8');
        }
        if ($href === '' || !preg_match('#^(https?://|/|\#|mailto:|tel:)#i', $href)) {
            return '<a>';
        }
        if ($href[0] === '/' && !str_starts_with($href, '//')) {
            return '<a href="' . e(internal_link($href)) . '">';
        }
        return '<a href="' . e($href) . '" rel="noopener noreferrer">';
    }, $html) ?? '';
}

/**
 * Root-relative link written in content (e.g. "/shop/hydraulic-seals", "/guides/…")
 * → real URL, honouring the base path and the pretty_urls setting.
 */
function internal_link(string $href): string
{
    if (!config('app.pretty_urls', true)) {
        $map = ['shop' => 'shop.php?category=', 'seal' => 'product.php?slug=', 'guides' => 'guide.php?slug='];
        if (preg_match('#^/(shop|seal|guides)/([a-z0-9-]+)/?$#', $href, $m)) {
            return url($map[$m[1]] . $m[2]);
        }
    }
    return url($href);
}

function format_date(?string $date, string $format = 'j M Y'): string
{
    if (!$date) {
        return '';
    }
    $ts = strtotime($date);
    return $ts ? date($format, $ts) : '';
}

/** Truncate plain text on a word boundary. */
function excerpt(string $text, int $limit = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($text)) ?? '');
    if (mb_strlen($text) <= $limit) {
        return $text;
    }
    $cut = mb_substr($text, 0, $limit);
    $pos = mb_strrpos($cut, ' ');
    return rtrim($pos ? mb_substr($cut, 0, $pos) : $cut, ' ,.;:') . '…';
}

function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    // On Vercel PHP sits behind the platform's proxy, which sets these headers itself
    // (a value sent by the visitor is overwritten), so they can be trusted there.
    if (getenv('VERCEL') || getenv('VERCEL_URL')) {
        $forwarded = (string) ($_SERVER['HTTP_X_REAL_IP'] ?? explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''))[0]);
        if (filter_var(trim($forwarded), FILTER_VALIDATE_IP)) {
            return trim($forwarded);
        }
    }
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function app_log(string $message): void
{
    $dir = ST_STORAGE . '/logs';
    if (is_dir($dir) && is_writable($dir)) {
        @file_put_contents($dir . '/app.log', '[' . date('c') . '] ' . $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    } else {
        error_log('[sealing-technologies] ' . $message);
    }
}
