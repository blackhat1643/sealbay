<?php
/**
 * Authenticated download of an enquiry attachment (files live outside the web-accessible folders).
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$row  = db_one('SELECT attachment_path, attachment_name FROM enquiries WHERE id = ?', [(int) ($_GET['id'] ?? 0)]);
$name = (string) ($row['attachment_path'] ?? '');
$file = ST_STORAGE . '/uploads/' . $name;

if (!$row || !preg_match('/^[a-f0-9]{32}\.(pdf|jpg|png|webp)$/', $name, $m) || !is_file($file)) {
    http_response_code(404);
    exit('File not found.');
}

$types    = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
$download = preg_replace('/[^A-Za-z0-9._ -]/', '_', (string) ($row['attachment_name'] ?: 'attachment.' . $m[1]));

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: ' . $types[$m[1]]);
header('Content-Length: ' . filesize($file));
// Images may be shown inline on the enquiry page; everything else is always a download.
$inline = isset($_GET['inline']) && $m[1] !== 'pdf';
header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $download . '"');
header('X-Content-Type-Options: nosniff');
readfile($file);
exit;
