<?php
/**
 * Serves product / category images that are stored in the database
 * (hosts without a persistent disk). On normal hosting images are plain
 * files in assets/uploads and this script is not used.
 */
require __DIR__ . '/includes/bootstrap.php';

$name = isset($_GET['f']) && is_string($_GET['f']) ? $_GET['f'] : '';
$file = preg_match('/^[a-f0-9]{32}\.(jpg|png|webp)$/', $name) ? stored_file_get($name, 'image') : null;

while (ob_get_level() > 0) {
    ob_end_clean();
}
if (!$file || !in_array($file['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)) {
    http_response_code(404);
    exit;
}
// Names are random and never reused, so the response can be cached for a long time.
header('Content-Type: ' . $file['mime']);
header('Content-Length: ' . strlen($file['data']));
header('Cache-Control: public, max-age=31536000, immutable');
header('X-Content-Type-Options: nosniff');
echo $file['data'];
