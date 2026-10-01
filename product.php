<?php
/**
 * Product page.  /seal/<slug>  (or product.php?slug=<slug>)
 */
require __DIR__ . '/includes/bootstrap.php';

$slug    = isset($_GET['slug']) && is_string($_GET['slug']) ? strtolower(trim($_GET['slug'])) : '';
$product = preg_match('/^[a-z0-9-]{1,160}$/', $slug) ? product($slug) : null;
if (!$product) {
    render_404();
}

$status   = stock_status($product);
$size     = size_label($product);
$isKit    = $product['seal_type'] === 'kit';
$category = category($product['category']);
$material = null;
foreach (materials() as $m) {
    if (stripos($product['material'], $m['code']) !== false || stripos($product['material'], $m['name']) !== false) {
        $material = $m;
        break;
    }
}

// Related: same seal type, nearest inner diameter first.
$related = array_values(array_filter(all_products(), static fn ($p) => $p['slug'] !== $product['slug'] && $p['seal_type'] === $product['seal_type']));
usort($related, static fn ($a, $b) => abs(($a['inner_diameter'] ?? 0) - ($product['inner_diameter'] ?? 0)) <=> abs(($b['inner_diameter'] ?? 0) - ($product['inner_diameter'] ?? 0)));
$related = array_slice($related, 0, 4);

$specs = array_filter([
    'Inner diameter'    => $product['inner_diameter'] !== null ? mm($product['inner_diameter']) . ' mm' : '',
    'Outer diameter'    => $product['outer_diameter'] !== null ? mm($product['outer_diameter']) . ' mm' : '',
    'Width'             => $product['width'] !== null ? mm($product['width']) . ' mm' : '',
    'Material'          => $product['material'],
    'Temperature range' => $product['temp_range'],
    'Seal style'        => $product['style'],
    'Seal type'         => SEAL_TYPES[$product['seal_type']],
    'SKU'               => $product['sku'],
], 'strlen');

$canonical = product_url($product['slug']);
$page      = [
    'title'         => $product['name'],
    'description'   => excerpt('Buy ' . $product['name'] . ' online' . ($size !== '' ? ': ' . $size : '') . ($product['material'] !== '' ? ', ' . $product['material'] : '')
                        . '. ' . money($product['price_cents']) . ' including GST. Shipped from Australian stock.', 158),
    'path'          => 'product.php?slug=' . $product['slug'],
    'canonical_url' => $canonical,
    'og_type'       => 'product',
    'breadcrumbs'   => array_values(array_filter([
        ['Home', 'index.php'], ['Shop', 'shop.php'],
        $category ? [$category['name'], category_url($category['slug'])] : null,
        [$product['name'], null],
    ])),
];
$page['schema'] = [schema_product($product, site_origin() . $canonical)];

$added = flash('cart_added');
require __DIR__ . '/includes/header.php';
?>

<section class="section section--tight product">
  <div class="container">
    <div class="product__crumbs"><?php component('breadcrumb', ['crumbs' => $page['breadcrumbs']]); ?></div>

    <div class="product__layout">
      <div class="product__media">
        <figure class="sheet sheet--light">
          <?= product_visual($product, 'light', ['loading' => 'eager']) ?>
          <?php if (!uploaded_image($product['image'])): ?>
          <figcaption class="sheet__caption">Drawing for identification · not to scale</figcaption>
          <?php endif; ?>
        </figure>
        <?php if ($size !== ''): ?>
        <dl class="dims">
          <div><dt>Inner</dt><dd><?= e(mm($product['inner_diameter'])) ?><small>mm</small></dd></div>
          <div><dt>Outer</dt><dd><?= e(mm($product['outer_diameter'])) ?><small>mm</small></dd></div>
          <div><dt>Width</dt><dd><?= e(mm($product['width'])) ?><small>mm</small></dd></div>
        </dl>
        <?php endif; ?>
      </div>

      <div class="product__info">
        <p class="p-card__meta"><?= e(SEAL_TYPES[$product['seal_type']]) ?><?= $product['material'] !== '' ? ' · ' . e($product['material']) : '' ?> · SKU <?= e($product['sku']) ?></p>
        <h1 class="product__title"><?= e($product['name']) ?></h1>
        <p class="product__summary"><?= e($product['summary']) ?></p>

        <div class="buy-box">
          <div class="buy-box__price">
            <p class="price price--lg"><?= e(money($product['price_cents'])) ?> <small>each, incl. GST</small></p>
            <?= stock_badge($product) ?>
          </div>
          <?php if ($status['key'] === 'backorder'): ?>
          <p class="buy-box__note"><?= icon('info', 16) ?><span>This size is on backorder. You can order now and it will be sent as soon as stock arrives.</span></p>
          <?php elseif ($status['can_buy']): ?>
          <p class="buy-box__note"><?= icon('truck', 16) ?><span>Order before <?= e(shop('dispatch_cutoff', '2 pm')) ?> on a business day and it ships the same day.</span></p>
          <?php endif; ?>

          <?php if ($added): ?><div class="alert alert--success" role="status"><?= e($added) ?> <a href="<?= e(url('cart.php')) ?>">View cart</a></div><?php endif; ?>

          <?php if ($status['can_buy']): ?>
          <form class="buy-box__form" method="post" action="<?= e(url('cart.php')) ?>">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">
            <input type="hidden" name="return" value="product">
            <label for="qty">Quantity</label>
            <input class="input qty" id="qty" name="qty" type="number" min="1" max="<?= $product['allow_backorder'] ? (int) config('shop.max_qty_per_line', 500) : $product['stock_qty'] ?>" value="1" inputmode="numeric">
            <button class="btn btn--primary btn--lg" type="submit"><?= icon('cart', 20) ?> Add to cart</button>
          </form>
          <?php else: ?>
          <p class="buy-box__note">This item cannot be ordered at the moment. <a href="<?= e(url('contact.php')) ?>">Ask us when it is due back</a>.</p>
          <?php endif; ?>
          <p class="buy-box__measure"><?= icon('ruler', 16) ?><span>Not sure it is the right size? <a href="<?= e(url('measure-your-seal.php')) ?>">Measure your seal</a></span></p>
        </div>

        <?php if ($product['fitment'] !== ''): ?>
        <div class="product__block">
          <h2>Fitment notes</h2>
          <p><?= e($product['fitment']) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($isKit && $product['kit_contents']): ?>
        <div class="product__block">
          <h2>In the kit</h2>
          <ul class="dash-list">
            <?php foreach ($product['kit_contents'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?>
          </ul>
        </div>
        <?php endif; ?>

        <div class="product__block">
          <h2>Specifications</h2>
          <table class="spec-table">
            <tbody>
              <?php foreach ($specs as $label => $value): ?>
              <tr><th scope="row"><?= e($label) ?></th><td><?= e($value) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <?php if ($material): ?>
        <div class="product__block">
          <h2>About <?= e($material['code']) ?></h2>
          <p><?= e($material['summary']) ?> Choose it if <?= e(lcfirst($material['choose_if'])) ?> <a href="<?= e(url('materials.php#' . $material['slug'])) ?>">Materials guide</a></p>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php if ($related): ?>
<section class="section section--light section--tight">
  <div class="container">
    <?php section_heading('Similar sizes', 'Other ' . strtolower(SEAL_TYPES[$product['seal_type']]) . ' seals close to this size'); ?>
    <div class="p-grid">
      <?php foreach ($related as $item): ?>
        <?php component('product-card', ['product' => $item]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php
component('cta-band');
require __DIR__ . '/includes/footer.php';
