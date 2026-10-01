<?php
/**
 * XML sitemap — served as /sitemap.xml (rewrite in .htaccess) or /sitemap.php.
 */
require __DIR__ . '/includes/bootstrap.php';

$origin = site_origin();
$urls   = array_map(static fn ($p) => abs_url($p), ['', 'shop.php', 'measure-your-seal.php', 'materials.php', 'guides.php', 'delivery-returns.php', 'custom-quote.php', 'contact.php', 'about.php', 'privacy-policy.php', 'terms-of-sale.php']);
foreach (categories() as $category) {
    $urls[] = $origin . category_url($category['slug']);
}
foreach (all_products() as $product) {
    $urls[] = $origin . product_url($product['slug']);
}
foreach (articles() as $article) {
    $urls[] = $origin . article_url($article['slug']);
}

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $loc) {
    echo '  <url><loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc></url>\n";
}
echo '</urlset>' . "\n";
