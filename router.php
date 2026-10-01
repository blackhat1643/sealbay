<?php
/**
 * Router for PHP's built-in web server (local development only):
 *
 *     php -S localhost:8000 router.php
 *
 * It mirrors the rules in .htaccess so local behaviour matches production.
 * Apache / LiteSpeed never use this file.
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

$file = __DIR__ . $path;
if (is_dir($file)) {
    $file = rtrim($file, '/') . '/index.php';
}
if (is_file($file)) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        return $serve($file);
    }
    return false; // let the built-in server send static files
}

http_response_code(404);
require __DIR__ . '/404.php';
return true;
