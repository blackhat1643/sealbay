<?php
/**
 * Router for PHP's built-in web server (local development only):
 *
 *     php -S localhost:8000 router.php
 *
 * It mirrors the rules in .htaccess so local behaviour matches production.
 * Apache / LiteSpeed never use this file. On Vercel the same routing is used
 * through api/index.php (the PHP runtime there is also the built-in server).
 */
if (PHP_SAPI !== 'cli-server') {
    http_response_code(404);
    exit;
}

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

// Private folders and files are never served.
if (preg_match('#^/(includes|data|database|storage)(/|$)#', $path) || preg_match('#/\.|\.(md|sql|lock|log|sqlite|jsonl)$|^/router\.php$#i', $path)) {
    http_response_code(403);
    echo '403 Forbidden';
    return true;
}

// No PHP execution inside the uploads folder.
if (preg_match('#^/assets/uploads/.*\.php#i', $path)) {
    http_response_code(403);
    return true;
}

$serve = static function (string $file, array $query = []): bool {
    $_GET = $query + $_GET;
    chdir(dirname($file));
    $_SERVER['SCRIPT_NAME'] = str_replace(__DIR__, '', $file);
    require $file;
    return true;
};

if ($path === '/sitemap.xml' || $path === '/robots.txt') {
    return $serve(__DIR__ . ($path === '/sitemap.xml' ? '/sitemap.php' : '/robots.php'));
}

// Pretty URLs (same as the RewriteRules in .htaccess).
if (preg_match('#^/shop/([a-z0-9-]+)/?$#', $path, $m)) {
    return $serve(__DIR__ . '/shop.php', ['category' => $m[1]]);
}
if (preg_match('#^/seal/([a-z0-9-]+)/?$#', $path, $m)) {
    return $serve(__DIR__ . '/product.php', ['slug' => $m[1]]);
}
if (preg_match('#^/guides/([a-z0-9-]+)/?$#', $path, $m)) {
    return $serve(__DIR__ . '/guide.php', ['slug' => $m[1]]);
}

// Static files: only what lives in /assets, plus the favicon.
if ($path === '/favicon.ico' || preg_match('#^/assets/[A-Za-z0-9/_.-]+\.(css|js|woff2|webp|jpe?g|png|svg|ico)$#', $path)) {
    $static = __DIR__ . $path;
    if (!str_contains($path, '..') && is_file($static)) {
        if (isset($_SERVER['VERCEL']) || getenv('VERCEL')) {
            // Normally answered by Vercel's CDN (see vercel.json); this is only a fallback.
            $types = ['css' => 'text/css', 'js' => 'application/javascript', 'woff2' => 'font/woff2', 'webp' => 'image/webp', 'jpg' => 'image/jpeg',
                      'jpeg' => 'image/jpeg', 'png' => 'image/png', 'svg' => 'image/svg+xml', 'ico' => 'image/x-icon'];
            header('Content-Type: ' . $types[strtolower(pathinfo($static, PATHINFO_EXTENSION))]);
            header('Cache-Control: public, max-age=31536000, immutable');
            readfile($static);
            return true;
        }
        return false; // let the built-in server send the file
    }
}

// PHP pages: files in the site root, /admin and /install only.
if (preg_match('#^/(?:(admin|install)/)?([a-z0-9-]+\.php)?$#', $path, $m) || in_array($path, ['/admin', '/install'], true)) {
    $dir  = $m[1] ?? trim($path, '/');
    $name = $m[2] ?? 'index.php';
    $file = __DIR__ . ($dir !== '' ? '/' . $dir : '') . '/' . $name;
    if ($name !== 'router.php' && is_file($file)) {
        return $serve($file);
    }
}

http_response_code(404);
require __DIR__ . '/404.php';
return true;
