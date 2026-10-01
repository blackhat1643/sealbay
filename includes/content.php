<?php
/**
 * Catalogue and content repository.
 *
 * Categories, products, guides and settings are read from the database once it has
 * been installed (so they can be managed in the admin panel). Until then the sample
 * content in /data is shown, so the catalogue can be browsed before setup — but
 * orders can only be placed once the database is installed.
 */
defined('ST_APP') || exit;

/** Seal types offered in the size finder (key => label). */
const SEAL_TYPES = [
    'rotary' => 'Rotary shaft',
    'rod'    => 'Hydraulic rod',
    'piston' => 'Hydraulic piston',
    'wiper'  => 'Wiper',
    'kit'    => 'Seal kit',
];

function data_file(string $name): array
{
    static $cache = [];
    if (!isset($cache[$name])) {
        $file         = ST_ROOT . '/data/' . $name . '.php';
        $cache[$name] = is_file($file) ? (array) require $file : [];
    }
    return $cache[$name];
}

/** Settings saved through the admin panel (key => value). */
function settings(): array
{
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    $settings = [];
    if (db_ready()) {
        try {
            foreach (db_all('SELECT setting_key, setting_value FROM settings') as $row) {
                $settings[$row['setting_key']] = (string) $row['setting_value'];
            }
        } catch (PDOException $ex) {
            app_log('settings(): ' . $ex->getMessage());
        }
    }
    return $settings;
}

/* ---------- Categories ---------- */

function categories(): array
{
    static $list = null;
    if ($list !== null) {
        return $list;
    }
    if (db_ready()) {
        try {
            return $list = array_map('normalise_category', db_all('SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, id'));
        } catch (PDOException $ex) {
            app_log('categories(): ' . $ex->getMessage());
        }
    }
    return $list = array_map('normalise_category', data_file('categories'));
}

function normalise_category(array $row): array
{
    return [
        'id'           => (int) ($row['id'] ?? 0),
        'slug'         => (string) $row['slug'],
        'name'         => (string) $row['name'],
        'headline'     => (string) (($row['headline'] ?? '') ?: $row['name']),
        'summary'      => (string) ($row['summary'] ?? ''),
        'description'  => (string) ($row['description'] ?? ''),
        'illustration' => (string) (($row['illustration'] ?? '') ?: 'reference'),
        'image'        => (string) ($row['image'] ?? ''),
    ];
}

function category(string $slug): ?array
{
    foreach (categories() as $category) {
        if ($category['slug'] === $slug) {
            return $category;
        }
    }
    return null;
}

function category_url(string $slug): string
{
    return config('app.pretty_urls', true)
        ? url('shop/' . rawurlencode($slug))
        : url('shop.php?category=' . rawurlencode($slug));
}

/* ---------- Products ---------- */

/** Every active product, normalised. Cached for the request. */
function all_products(): array
{
    static $list = null;
    if ($list !== null) {
        return $list;
    }
    if (db_ready()) {
        try {
            $rows = db_all(
                'SELECT p.*, c.slug AS category, c.name AS category_name FROM products p
                 JOIN categories c ON c.id = p.category_id
                 WHERE p.is_active = 1 AND c.is_active = 1
                 ORDER BY c.sort_order, p.sort_order, p.inner_diameter, p.outer_diameter, p.id'
            );
            return $list = array_map('normalise_product', $rows);
        } catch (PDOException $ex) {
            app_log('all_products(): ' . $ex->getMessage());
        }
    }
    $names = array_column(data_file('categories'), 'name', 'slug');
    $list  = [];
    foreach (data_file('products') as $row) {
        $row['category_name'] = $names[$row['category']] ?? '';
        $list[]               = normalise_product($row);
    }
    return $list;
}

function normalise_product(array $row): array
{
    $kit   = $row['kit_contents'] ?? [];
    $float = static fn ($v): ?float => ($v === null || $v === '') ? null : (float) $v;
    return [
        'id'              => (int) ($row['id'] ?? 0),
        'slug'            => (string) $row['slug'],
        'sku'             => (string) $row['sku'],
        'name'            => (string) $row['name'],
        'category'        => (string) ($row['category'] ?? ''),
        'category_name'   => (string) ($row['category_name'] ?? ''),
        'seal_type'       => isset(SEAL_TYPES[$row['seal_type'] ?? '']) ? (string) $row['seal_type'] : 'rotary',
        'style'           => (string) ($row['style'] ?? ''),
        'material'        => (string) ($row['material'] ?? ''),
        'inner_diameter'  => $float($row['inner_diameter'] ?? null),
        'outer_diameter'  => $float($row['outer_diameter'] ?? null),
        'width'           => $float($row['width'] ?? null),
        'temp_range'      => (string) ($row['temp_range'] ?? ''),
        'price_cents'     => (int) ($row['price_cents'] ?? 0),
        'stock_qty'       => max(0, (int) ($row['stock_qty'] ?? 0)),
        'allow_backorder' => (bool) ($row['allow_backorder'] ?? true),
        'summary'         => (string) ($row['summary'] ?? ''),
        'fitment'         => (string) ($row['fitment'] ?? ''),
        'kit_contents'    => is_array($kit) ? $kit : str_lines((string) $kit),
        'illustration'    => (string) (($row['illustration'] ?? '') ?: 'reference'),
        'image'           => (string) ($row['image'] ?? ''),
        'is_featured'     => (bool) ($row['is_featured'] ?? false),
    ];
}

function product(string $slug): ?array
{
    foreach (all_products() as $product) {
        if ($product['slug'] === $slug) {
            return $product;
        }
    }
    return null;
}

function product_url(string $slug): string
{
    return config('app.pretty_urls', true)
        ? url('seal/' . rawurlencode($slug))
        : url('product.php?slug=' . rawurlencode($slug));
}

/** "35 × 52 × 7 mm" (inner × outer × width), or '' for products without a single size (kits). */
function size_label(array $product): string
{
    if ($product['inner_diameter'] === null || $product['outer_diameter'] === null || $product['width'] === null) {
        return '';
    }
    return mm($product['inner_diameter']) . ' × ' . mm($product['outer_diameter']) . ' × ' . mm($product['width']) . ' mm';
}

/** Stock indicator: ['key' => in_stock|low_stock|backorder|out_of_stock, 'label' => …, 'can_buy' => bool]. */
function stock_status(array $product): array
{
    $qty = $product['stock_qty'];
    if ($qty <= 0) {
        return $product['allow_backorder']
            ? ['key' => 'backorder', 'label' => 'On backorder', 'can_buy' => true]
            : ['key' => 'out_of_stock', 'label' => 'Out of stock', 'can_buy' => false];
    }
    if ($qty <= (int) shop('low_stock_threshold', '5')) {
        return ['key' => 'low_stock', 'label' => 'Low stock — ' . $qty . ' left', 'can_buy' => true];
    }
    return ['key' => 'in_stock', 'label' => 'In stock', 'can_buy' => true];
}

/** Parse a millimetre value typed into the size finder; null when empty or invalid. */
function parse_mm(mixed $value): ?float
{
    if (!is_string($value)) {
        return null;
    }
    $value = str_replace([',', 'mm', ' '], ['.', '', ''], strtolower(trim($value)));
    if ($value === '' || !preg_match('/^\d{1,4}(\.\d{1,2})?$/', $value)) {
        return null;
    }
    $mm = (float) $value;
    return $mm > 0 ? $mm : null;
}

/**
 * Filter the catalogue.
 * $filters: category (slug), type (SEAL_TYPES key), material, q (text), id / od / w (floats, mm).
 * When any dimension is given the result is a size-finder search: products match when
 * every given dimension is within the tolerance, and are ordered by closeness.
 * Each returned product gains 'match' => ['exact' => bool, 'notes' => [...]] in finder mode.
 */
function find_products(array $filters): array
{
    $tolerance = (float) shop('size_tolerance_mm', '0.5');
    $dims      = ['id' => 'inner_diameter', 'od' => 'outer_diameter', 'w' => 'width'];
    $labels    = ['id' => 'Inner', 'od' => 'Outer', 'w' => 'Width'];
    $wanted    = [];
    foreach ($dims as $key => $column) {
        if (isset($filters[$key]) && $filters[$key] !== null) {
            $wanted[$key] = (float) $filters[$key];
        }
    }
    $query = mb_strtolower(trim((string) ($filters['q'] ?? '')));
    // "35x52x7" typed into search is treated as text against the name / SKU.
    $queryCompact = str_replace([' ', '×', '*'], ['', 'x', 'x'], $query);

    $results = [];
    foreach (all_products() as $product) {
        if (!empty($filters['category']) && $product['category'] !== $filters['category']) {
            continue;
        }
        if (!empty($filters['type']) && $product['seal_type'] !== $filters['type']) {
            continue;
        }
        if (!empty($filters['material']) && strcasecmp($product['material'], (string) $filters['material']) !== 0) {
            continue;
        }
        if ($query !== '') {
            $haystack = mb_strtolower($product['name'] . ' ' . $product['sku'] . ' ' . $product['material'] . ' ' . $product['style']);
            if (!str_contains($haystack, $query) && !str_contains(str_replace(' ', '', $haystack), $queryCompact)) {
                continue;
            }
        }
        if ($wanted) {
            $deviation = 0.0;
            $notes     = [];
            foreach ($wanted as $key => $value) {
                $actual = $product[$dims[$key]];
                if ($actual === null || abs($actual - $value) > $tolerance + 1e-9) {
                    continue 2;
                }
                $diff = round($actual - $value, 2);
                if (abs($diff) > 1e-9) {
                    $notes[] = $labels[$key] . ' ' . ($diff > 0 ? '+' : '−') . mm(abs($diff)) . ' mm';
                }
                $deviation += abs($diff);
            }
            $product['match'] = ['exact' => $deviation < 1e-9, 'notes' => $notes, 'deviation' => $deviation];
        }
        $results[] = $product;
    }

    if ($wanted) {
        usort($results, static fn ($a, $b) => $a['match']['deviation'] <=> $b['match']['deviation']);
    }
    return $results;
}

/** Distinct materials in the active catalogue (for the filter). */
function catalogue_materials(): array
{
    $materials = array_unique(array_filter(array_column(all_products(), 'material')));
    sort($materials);
    return $materials;
}

/* ---------- Guides (articles) ---------- */

function articles(): array
{
    static $list = null;
    if ($list !== null) {
        return $list;
    }
    if (db_ready()) {
        try {
            return $list = array_map('normalise_article', db_all('SELECT * FROM articles WHERE is_published = 1 ORDER BY published_at DESC, id'));
        } catch (PDOException $ex) {
            app_log('articles(): ' . $ex->getMessage());
        }
    }
    return $list = array_map('normalise_article', data_file('articles'));
}

function normalise_article(array $row): array
{
    $faqs = $row['faqs'] ?? [];
    if (!is_array($faqs)) {
        $faqs = parse_faqs((string) $faqs);
    }
    return [
        'id'               => (int) ($row['id'] ?? 0),
        'slug'             => (string) $row['slug'],
        'title'            => (string) $row['title'],
        'topic'            => (string) (($row['topic'] ?? '') ?: 'How-to'),
        'excerpt'          => (string) ($row['excerpt'] ?? ''),
        'body'             => (string) ($row['body'] ?? ''),
        'faqs'             => $faqs,
        'reading_minutes'  => max(1, (int) ($row['reading_minutes'] ?? 4)),
        'meta_title'       => (string) (($row['meta_title'] ?? '') ?: $row['title']),
        'meta_description' => (string) (($row['meta_description'] ?? '') ?: ($row['excerpt'] ?? '')),
        'published_at'     => (string) ($row['published_at'] ?? ''),
        'updated_at'       => (string) ($row['updated_at'] ?? ($row['published_at'] ?? '')),
    ];
}

/** Admin textarea format: one "Question? | Answer" per line. */
function parse_faqs(string $text): array
{
    $faqs = [];
    foreach (str_lines($text) as $line) {
        [$q, $a] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
        if ($q !== '' && $a !== '') {
            $faqs[] = ['q' => $q, 'a' => $a];
        }
    }
    return $faqs;
}

function faqs_to_text(array $faqs): string
{
    return implode("\n", array_map(static fn ($f) => $f['q'] . ' | ' . $f['a'], $faqs));
}

function article(string $slug): ?array
{
    foreach (articles() as $article) {
        if ($article['slug'] === $slug) {
            return $article;
        }
    }
    return null;
}

function article_url(string $slug): string
{
    return config('app.pretty_urls', true)
        ? url('guides/' . rawurlencode($slug))
        : url('guide.php?slug=' . rawurlencode($slug));
}

/* ---------- Company profile (file-based) ---------- */

/** The company behind the shop (data/company.php). */
function company_profile(): array
{
    return data_file('company');
}

/**
 * Logo for a client, if a file has been supplied in assets/img/clients.
 * Logos are never fetched from elsewhere: no file means the name is shown as text.
 */
function client_logo(string $slug): ?string
{
    foreach (['svg', 'png', 'webp', 'jpg'] as $ext) {
        $rel = 'img/clients/' . $slug . '.' . $ext;
        if (preg_match('/^[a-z0-9-]+$/', $slug) && is_file(ST_ROOT . '/assets/' . $rel)) {
            return asset($rel);
        }
    }
    return null;
}

/* ---------- Materials (file-based) ---------- */

function materials(): array
{
    return data_file('materials');
}
