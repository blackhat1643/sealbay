<?php
/**
 * SEO helpers: meta tags, canonical URLs, Open Graph and JSON-LD structured data.
 *
 * Each page defines a $page array before including header.php:
 *   'title'        => page title (brand is appended automatically)
 *   'description'  => meta description
 *   'path'         => canonical path, e.g. 'products/rotary-seals.php' ('' = home)
 *   'breadcrumbs'  => [['Home', 'index.php'], ['Products', 'products.php'], ['Rotary Seals', null]]
 *   'faqs'         => [['q' => ..., 'a' => ...]]  (adds FAQPage schema)
 *   'schema'       => extra JSON-LD arrays
 *   'og_type', 'og_image', 'noindex', 'body_class'
 */
defined('ST_APP') || exit;

function page_defaults(array $page): array
{
    $name = company('name', 'SealBay Australia');
    $page += [
        'title'       => '',
        'description' => 'Rotary shaft seals and hydraulic seals shipped from Australian stock. Enter your measurements in millimetres and find the seal that fits.',
        'path'        => current_path(),
        'breadcrumbs' => [],
        'faqs'        => [],
        'schema'      => [],
        'og_type'     => 'website',
        'og_image'    => 'assets/img/og-default.jpg',
        'noindex'     => false,
        'body_class'  => '',
    ];
    // Long titles are shown without the brand suffix so they are not cut off in search results.
    $withBrand          = $page['title'] !== '' ? $page['title'] . ' | ' . $name : $name;
    $page['full_title'] = $page['title_full'] ?? (mb_strlen($withBrand) > 68 ? $page['title'] : $withBrand);
    $path               = $page['path'] === 'index.php' ? '' : $page['path'];
    // Pages with a pretty URL pass the finished root-relative address in 'canonical_url'.
    $page['canonical']  = isset($page['canonical_url']) ? site_origin() . $page['canonical_url'] : abs_url($path);
    return $page;
}

function json_ld(array $data): string
{
    $json = json_encode(
        ['@context' => 'https://schema.org'] + $data,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
    );
    return '<script type="application/ld+json">' . $json . '</script>' . "\n";
}

/** Online store schema — only includes contact details that have been configured. */
function schema_organization(): array
{
    $org = [
        '@type'       => 'OnlineStore',
        '@id'         => abs_url('') . '#organization',
        'name'        => company('name'),
        'url'         => abs_url(''),
        'logo'        => site_origin() . url('assets/img/logo-mark.svg'),
        'description' => 'Online shop for rotary shaft seals, hydraulic seals and seal kits, shipped within Australia.',
        'areaServed'  => ['@type' => 'Country', 'name' => 'Australia'],
    ];
    $address = array_filter([
        'streetAddress'   => trim(company('address_line1') . ' ' . company('address_line2')),
        'addressLocality' => company('suburb'),
        'addressRegion'   => company('state'),
        'postalCode'      => company('postcode'),
    ]);
    if ($address) {
        $org['address'] = ['@type' => 'PostalAddress'] + $address + ['addressCountry' => 'AU'];
    }
    if (company('phone') !== '') {
        $org['telephone'] = company('phone');
    }
    if (company('email') !== '') {
        $org['email'] = company('email');
    }
    if (company('abn') !== '') {
        $org['taxID'] = company('abn');
    }
    $same = array_values(array_filter([company('facebook'), company('instagram'), company('youtube')]));
    if ($same) {
        $org['sameAs'] = $same;
    }
    return $org;
}

/** Product schema with price and availability (no ratings or reviews are invented). */
function schema_product(array $product, string $canonical): array
{
    $availability = [
        'in_stock' => 'https://schema.org/InStock', 'low_stock' => 'https://schema.org/LimitedAvailability',
        'backorder' => 'https://schema.org/BackOrder', 'out_of_stock' => 'https://schema.org/OutOfStock',
    ][stock_status($product)['key']];
    $image = uploaded_image($product['image']);

    return array_filter([
        '@type'       => 'Product',
        'name'        => $product['name'],
        'sku'         => $product['sku'],
        'description' => $product['summary'],
        'category'    => $product['category_name'],
        'material'    => $product['material'],
        'image'       => $image ? site_origin() . strtok($image['src'], '?') : null,
        'offers'      => [
            '@type'         => 'Offer',
            'url'           => $canonical,
            'priceCurrency' => 'AUD',
            'price'         => number_format($product['price_cents'] / 100, 2, '.', ''),
            'availability'  => $availability,
            'itemCondition' => 'https://schema.org/NewCondition',
            'seller'        => ['@id' => abs_url('') . '#organization'],
        ],
    ]);
}

function schema_website(): array
{
    return [
        '@type'     => 'WebSite',
        '@id'       => abs_url('') . '#website',
        'url'       => abs_url(''),
        'name'      => company('name'),
        'publisher' => ['@id' => abs_url('') . '#organization'],
        'inLanguage' => 'en-AU',
    ];
}

function schema_breadcrumbs(array $crumbs, string $canonical): ?array
{
    if (count($crumbs) < 2) {
        return null;
    }
    $items = [];
    foreach (array_values($crumbs) as $i => [$label, $path]) {
        $items[] = [
            '@type'    => 'ListItem',
            'position' => $i + 1,
            'name'     => $label,
            'item'     => $path === null ? $canonical : (str_starts_with($path, '/') ? site_origin() . $path : abs_url($path === 'index.php' ? '' : $path)),
        ];
    }
    return ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
}

function schema_faq(array $faqs): ?array
{
    if (!$faqs) {
        return null;
    }
    return [
        '@type'      => 'FAQPage',
        'mainEntity' => array_map(static fn ($f) => [
            '@type'          => 'Question',
            'name'           => $f['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f['a']],
        ], $faqs),
    ];
}

function schema_article(array $article, string $canonical): array
{
    return array_filter([
        '@type'            => 'Article',
        'headline'         => $article['title'],
        'description'      => $article['excerpt'],
        'mainEntityOfPage' => $canonical,
        'datePublished'    => $article['published_at'] ? date('c', strtotime($article['published_at'])) : null,
        'dateModified'     => $article['updated_at'] ? date('c', strtotime($article['updated_at'])) : null,
        'articleSection'   => $article['topic'],
        'author'           => ['@type' => 'Organization', 'name' => company('name'), 'url' => abs_url('')],
        'publisher'        => ['@id' => abs_url('') . '#organization'],
        'inLanguage'       => 'en-AU',
    ]);
}

/** All <head> SEO tags for the page. */
function render_seo(array $page): string
{
    $out  = '<title>' . e($page['full_title']) . "</title>\n";
    $out .= '<meta name="description" content="' . e($page['description']) . "\">\n";
    $out .= '<link rel="canonical" href="' . e($page['canonical']) . "\">\n";
    if ($page['noindex']) {
        $out .= "<meta name=\"robots\" content=\"noindex, nofollow\">\n";
    }
    $og = [
        'og:type'        => $page['og_type'],
        'og:site_name'   => company('name'),
        'og:title'       => $page['full_title'],
        'og:description' => $page['description'],
        'og:url'         => $page['canonical'],
        'og:locale'      => 'en_AU',
    ];
    if (is_file(ST_ROOT . '/' . $page['og_image'])) {
        $og['og:image'] = site_origin() . url($page['og_image']);
    }
    foreach ($og as $property => $content) {
        $out .= '<meta property="' . $property . '" content="' . e($content) . "\">\n";
    }
    $out .= '<meta name="twitter:card" content="' . (isset($og['og:image']) ? 'summary_large_image' : 'summary') . "\">\n";

    if (!$page['noindex']) {
        $out .= json_ld(schema_organization());
        if ($page['path'] === '' || $page['path'] === 'index.php') {
            $out .= json_ld(schema_website());
        }
        foreach (array_filter([
            schema_breadcrumbs($page['breadcrumbs'], $page['canonical']),
            schema_faq($page['faqs']),
            ...$page['schema'],
        ]) as $schema) {
            $out .= json_ld($schema);
        }
    }
    return $out;
}
