<?php
/**
 * robots.txt — served as /robots.txt (rewrite in .htaccess).
 */
require __DIR__ . '/includes/bootstrap.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: text/plain; charset=UTF-8');
$base = base_path();
echo "User-agent: *\n";
foreach (['admin/', 'install/', 'cart.php', 'checkout.php', 'checkout-return.php', 'order.php', 'stripe-webhook.php'] as $path) {
    echo "Disallow: {$base}/{$path}\n";
}
echo "\nSitemap: " . abs_url('sitemap.xml') . "\n";
