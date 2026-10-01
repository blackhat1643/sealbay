<?php
/**
 * Shop listing: all seals, a category, or size-finder results.
 * Query: category, type, material, id, od, w (mm), q, sort, page
 */
require __DIR__ . '/includes/bootstrap.php';

$str = static fn (string $key): string => isset($_GET[$key]) && is_string($_GET[$key]) ? trim(mb_substr($_GET[$key], 0, 80)) : '';

$categorySlug = slugify($str('category'));
$category     = $categorySlug !== '' ? category($categorySlug) : null;
if ($categorySlug !== '' && !$category) {
    render_404();
}

$type     = isset(SEAL_TYPES[$str('type')]) ? $str('type') : '';
$material = in_array($str('material'), catalogue_materials(), true) ? $str('material') : '';
$query    = $str('q');
$sort     = in_array($str('sort'), ['size', 'price-low', 'price-high'], true) ? $str('sort') : '';
$raw      = ['id' => $str('id'), 'od' => $str('od'), 'w' => $str('w')];
$dims     = array_map('parse_mm', $raw);
$invalid  = array_keys(array_filter($raw, static fn ($v, $k) => $v !== '' && $dims[$k] === null, ARRAY_FILTER_USE_BOTH));
$finder   = (bool) array_filter($dims, static fn ($v) => $v !== null);

$results = find_products(['category' => $categorySlug, 'type' => $type, 'material' => $material, 'q' => $query] + $dims);
if ($sort === 'price-low') {
    usort($results, static fn ($a, $b) => $a['price_cents'] <=> $b['price_cents']);
} elseif ($sort === 'price-high') {
    usort($results, static fn ($a, $b) => $b['price_cents'] <=> $a['price_cents']);
} elseif ($sort === 'size') {
    usort($results, static fn ($a, $b) => [$a['inner_diameter'] ?? 9999, $a['outer_diameter'], $a['width']] <=> [$b['inner_diameter'] ?? 9999, $b['outer_diameter'], $b['width']]);
}

$perPage = 24;
$total   = count($results);
$pages   = max(1, (int) ceil($total / $perPage));
$pageNo  = min($pages, max(1, (int) ($_GET['page'] ?? 1)));
$shown   = array_slice($results, ($pageNo - 1) * $perPage, $perPage);

// Current filters, used to build links that change one thing at a time.
$current = ['type' => $type, 'material' => $material, 'q' => $query, 'sort' => $sort, 'id' => $raw['id'], 'od' => $raw['od'], 'w' => $raw['w']];
$link    = static fn (array $change): string => shop_url(array_merge($current, $change), $categorySlug);

$sizeText = implode(' × ', array_map(static fn ($k, $label) => $dims[$k] !== null ? mm($dims[$k]) : $label, ['id', 'od', 'w'], ['any', 'any', 'any']));
if ($finder) {
    $heading = 'Seals matching ' . $sizeText . ' mm';
} elseif ($query !== '') {
    $heading = 'Search: “' . $query . '”';
} else {
    $heading = $category ? $category['headline'] : 'All seals';
}

$baseUrl = $category ? category_url($categorySlug) : url('shop.php');
$page    = [
    'title'         => $category ? $category['name'] . ' — Buy Online by Size' : 'Shop Seals by Size',
    'description'   => $category ? excerpt($category['summary'] . ' Find yours by size in millimetres. Prices in AUD including GST, shipped from Australian stock.', 158)
                                 : 'Rotary shaft seals, hydraulic rod and piston seals, wipers and seal kits. Find yours by size in millimetres. Prices in AUD including GST.',
    'path'          => 'shop.php',
    'canonical_url' => $baseUrl . ($pageNo > 1 && !$finder && $query === '' ? (str_contains($baseUrl, '?') ? '&' : '?') . 'page=' . $pageNo : ''),
    'noindex'       => $finder || $query !== '' || $type !== '' || $material !== '' || $sort !== '',
    'breadcrumbs'   => $category ? [['Home', 'index.php'], ['Shop', 'shop.php'], [$category['name'], null]] : [['Home', 'index.php'], ['Shop', null]],
];
$catPhoto = $category ? photo('category-' . $categorySlug) : null;

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact bg-grid<?= $catPhoto ? ' page-hero--photo' : '' ?>">
  <?php if ($catPhoto): ?><div class="page-hero__bg"><?= img_tag($catPhoto, ['loading' => 'eager', 'alt' => '']) ?></div><?php endif; ?>
  <div class="container page-hero__inner">
    <div class="page-hero__content">
      <?php component('breadcrumb', ['crumbs' => $page['breadcrumbs']]); ?>
      <h1 class="page-hero__title"><?= e($heading) ?></h1>
      <?php if ($category && !$finder && $query === ''): ?><p class="page-hero__lead"><?= e($category['summary']) ?></p><?php endif; ?>
    </div>
  </div>
</section>

<section class="section section--tight section--light">
  <div class="container">
    <div class="finder-bar">
      <?php component('finder', ['values' => $raw + ['type' => $type], 'action' => $baseUrl]); ?>
    </div>

    <?php if ($invalid): ?>
    <div class="alert alert--error" role="alert"><strong>Please enter sizes as numbers in millimetres</strong> — for example 35 or 47.5.</div>
    <?php endif; ?>

    <div class="shop-bar">
      <p class="shop-bar__count"><strong><?= $total ?></strong> seal<?= $total === 1 ? '' : 's' ?><?= $finder ? ' within ' . e(mm(shop('size_tolerance_mm', '0.5'))) . ' mm of your size' : '' ?></p>
      <ul class="chip-nav" aria-label="Filter by seal type">
        <li><a class="<?= $type === '' ? 'is-active' : '' ?>" href="<?= e($link(['type' => ''])) ?>">All types</a></li>
        <?php foreach (SEAL_TYPES as $key => $label): if ($category && !array_filter(all_products(), static fn ($p) => $p['category'] === $categorySlug && $p['seal_type'] === $key)) { continue; } ?>
        <li><a class="<?= $type === $key ? 'is-active' : '' ?>" href="<?= e($link(['type' => $key])) ?>"><?= e($label) ?></a></li>
        <?php endforeach; ?>
      </ul>
      <form class="shop-bar__sort" method="get" action="<?= e(strtok($baseUrl, '?')) ?>" data-autosubmit>
        <?php foreach (array_filter($current + ($category && !config('app.pretty_urls', true) ? ['category' => $categorySlug] : []), 'strlen') as $name => $value): if (in_array($name, ['sort', 'material'], true)) { continue; } ?>
        <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
        <?php endforeach; ?>
        <label class="visually-hidden" for="filter-material">Material</label>
        <select class="select select--sm" id="filter-material" name="material">
          <option value="">Any material</option>
          <?php foreach (catalogue_materials() as $mat): ?><option<?= $material === $mat ? ' selected' : '' ?>><?= e($mat) ?></option><?php endforeach; ?>
        </select>
        <label class="visually-hidden" for="filter-sort">Sort by</label>
        <select class="select select--sm" id="filter-sort" name="sort">
          <option value=""><?= $finder ? 'Closest match first' : 'Default order' ?></option>
          <option value="size"<?= $sort === 'size' ? ' selected' : '' ?>>Size, small to large</option>
          <option value="price-low"<?= $sort === 'price-low' ? ' selected' : '' ?>>Price, low to high</option>
          <option value="price-high"<?= $sort === 'price-high' ? ' selected' : '' ?>>Price, high to low</option>
        </select>
        <button class="btn btn--outline btn--sm" type="submit">Apply</button>
      </form>
    </div>

    <?php if ($shown): ?>
    <div class="p-grid">
      <?php foreach ($shown as $product): ?>
        <?php component('product-card', ['product' => $product]); ?>
      <?php endforeach; ?>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Pages">
      <?php for ($p = 1; $p <= $pages; $p++): ?>
      <a class="<?= $p === $pageNo ? 'is-active' : '' ?>" href="<?= e($link(['page' => $p > 1 ? (string) $p : ''])) ?>"<?= $p === $pageNo ? ' aria-current="page"' : '' ?>><?= $p ?></a>
      <?php endfor; ?>
    </nav>
    <?php endif; ?>

    <?php else: ?>
    <div class="no-match">
      <h2>Nothing matches <?= $finder ? 'that size' : 'that search' ?> yet</h2>
      <p class="lead">That does not mean we cannot get it. Try one of these:</p>
      <ul class="check-list">
        <?php if ($finder): ?>
        <li><?= icon('check', 18) ?><span><strong>Clear a field.</strong>
          <?php if ($dims['w'] !== null): ?>Width varies most between makers — <a href="<?= e($link(['w' => ''])) ?>">search again without the width</a>.
          <?php elseif ($type !== ''): ?><a href="<?= e($link(['type' => ''])) ?>">Search again across all seal types</a>.
          <?php else: ?>Search with just the inner diameter, or just the outer diameter, to see what is close.<?php endif; ?></span></li>
        <li><?= icon('check', 18) ?><span><strong>Check the measurement.</strong> Inner diameter is the shaft or rod; outer diameter is the housing bore. <a href="<?= e(url('measure-your-seal.php')) ?>">See the measuring guide</a>.</span></li>
        <?php else: ?>
        <li><?= icon('check', 18) ?><span><strong>Search by size instead.</strong> Enter the inner diameter, outer diameter and width in the size finder above.</span></li>
        <?php endif; ?>
        <li><?= icon('check', 18) ?><span><strong>Send a photo for a custom quote.</strong> Put a ruler beside the old seal, take a photo and <a href="<?= e(url('custom-quote.php') . ($finder ? '?' . http_build_query(array_filter(['inner_diameter' => $raw['id'], 'outer_diameter' => $raw['od'], 'width' => $raw['w']], 'strlen')) : '')) ?>">send it to us</a>.</span></li>
      </ul>
      <div class="btn-row">
        <a class="btn btn--dark" href="<?= e($baseUrl) ?>">Clear all filters</a>
        <a class="btn btn--primary" href="<?= e(url('custom-quote.php')) ?>"><?= icon('camera', 18) ?> Send a photo for a quote</a>
      </div>
    </div>
    <?php endif; ?>
  </div>
</section>

<?php if ($category && !$finder && $query === ''): ?>
<section class="section">
  <div class="container intro">
    <div data-reveal>
      <p class="eyebrow">About this range</p>
      <h2 class="sec-head__title" style="margin-bottom:24px"><?= e($category['name']) ?></h2>
      <?= paragraphs($category['description']) ?>
    </div>
    <aside class="intro__aside" data-reveal style="--d:120ms">
      <h3>Not sure which one?</h3>
      <ul class="check-list" style="margin:0 0 20px">
        <li><?= icon('ruler', 18) ?><span><a href="<?= e(url('measure-your-seal.php')) ?>">Measure your old seal</a> in six steps</span></li>
        <li><?= icon('layers', 18) ?><span><a href="<?= e(url('materials.php')) ?>">Choose a material</a> for your oil and temperature</span></li>
        <li><?= icon('camera', 18) ?><span><a href="<?= e(url('custom-quote.php')) ?>">Send a photo</a> and we will identify it</span></li>
      </ul>
    </aside>
  </div>
</section>
<?php if ($categorySlug === 'hydraulic-seals' || $categorySlug === 'seal-kits'): ?>
<section class="section section--light">
  <div class="container">
    <?php section_heading('Hydraulic cylinders', 'Which seal goes where?'); ?>
    <?php component('cylinder-figure', ['theme' => 'light']); ?>
  </div>
</section>
<?php endif; ?>
<?php endif; ?>

<?php
component('cta-band');
require __DIR__ . '/includes/footer.php';
