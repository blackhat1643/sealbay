<?php
/**
 * Cart: add / update / remove (POST) and the cart page.
 */
require __DIR__ . '/includes/bootstrap.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!same_origin_post()) {
        http_response_code(400);
        exit('Bad request');
    }
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    $slug   = is_string($_POST['slug'] ?? null) ? strtolower(trim($_POST['slug'])) : '';

    if ($action === 'add' && ($product = product($slug)) && stock_status($product)['can_buy']) {
        cart_add($slug, max(1, (int) ($_POST['qty'] ?? 1)));
        if (($_POST['return'] ?? '') === 'product') {
            flash('cart_added', 'Added to your cart.');
            redirect(product_url($slug));
        }
        flash('cart_notice', $product['name'] . ' was added to your cart.');
    } elseif ($action === 'update' && is_array($_POST['qty'] ?? null)) {
        foreach ($_POST['qty'] as $itemSlug => $qty) {
            if (is_string($itemSlug) && is_scalar($qty)) {
                cart_set($itemSlug, (int) $qty);
            }
        }
        flash('cart_notice', 'Cart updated.');
    } elseif ($action === 'remove') {
        cart_set($slug, 0);
        flash('cart_notice', 'Item removed.');
    }
    redirect(url('cart.php'));
}

cart_sync_pending();
$lines    = cart_lines();
$subtotal = cart_subtotal($lines);
$options  = shipping_options($subtotal);
$gap      = free_shipping_gap($subtotal);
$problems = array_filter(array_column($lines, 'problem'));
$notice   = flash('cart_notice');

$page = [
    'title'       => 'Your Cart',
    'description' => 'Review the seals in your cart before checkout.',
    'path'        => 'cart.php',
    'noindex'     => true,
    'breadcrumbs' => [['Home', 'index.php'], ['Cart', null]],
];
require __DIR__ . '/includes/header.php';
?>
<section class="section section--tight section--light">
  <div class="container">
    <h1 class="page-title">Your cart</h1>
    <?php if ($notice): ?><div class="alert alert--success" role="status"><?= e($notice) ?></div><?php endif; ?>

    <?php if (!$lines): ?>
    <div class="no-match">
      <h2>Your cart is empty</h2>
      <p class="lead">Find your seal by size, or browse the range.</p>
      <div class="btn-row"><a class="btn btn--primary" href="<?= e(url('shop.php')) ?>"><?= icon('search', 18) ?> Find a seal</a></div>
    </div>
    <?php else: ?>
    <div class="cart-layout">
      <form class="cart" method="post" action="<?= e(url('cart.php')) ?>" id="cart-form">
        <input type="hidden" name="action" value="update">
        <ul class="cart__lines">
          <?php foreach ($lines as $line): $p = $line['product']; ?>
          <li class="cart-line">
            <a class="cart-line__media" href="<?= e(product_url($p['slug'])) ?>" tabindex="-1" aria-hidden="true"><?= product_visual($p, 'light', ['alt' => '']) ?></a>
            <div class="cart-line__info">
              <a class="cart-line__name" href="<?= e(product_url($p['slug'])) ?>"><?= e($p['name']) ?></a>
              <p class="cart-line__meta"><?= e($p['sku']) ?> · <?= e(money($p['price_cents'])) ?> each</p>
              <?php if ($line['problem']): ?><p class="field__error"><?= e($line['problem']) ?></p>
              <?php elseif ($line['backorder_qty'] > 0): ?><p class="cart-line__note"><?= (int) $line['backorder_qty'] ?> on backorder — sent as soon as stock arrives</p><?php endif; ?>
            </div>
            <div class="cart-line__qty">
              <label class="visually-hidden" for="qty-<?= e($p['slug']) ?>">Quantity of <?= e($p['name']) ?></label>
              <input class="input qty" id="qty-<?= e($p['slug']) ?>" type="number" name="qty[<?= e($p['slug']) ?>]" value="<?= (int) $line['qty'] ?>" min="0" max="<?= (int) config('shop.max_qty_per_line', 500) ?>" inputmode="numeric">
            </div>
            <p class="cart-line__total"><?= e(money($line['line_cents'])) ?></p>
            <button class="cart-line__remove" type="submit" form="remove-<?= e($p['slug']) ?>" aria-label="Remove <?= e($p['name']) ?>"><?= icon('trash', 18) ?></button>
          </li>
          <?php endforeach; ?>
        </ul>
        <div class="cart__actions">
          <a class="link-arrow" href="<?= e(url('shop.php')) ?>">Keep shopping <?= icon('arrow-right', 18) ?></a>
          <button class="btn btn--outline btn--sm" type="submit">Update quantities</button>
        </div>
      </form>
      <?php foreach ($lines as $line): ?>
      <form method="post" action="<?= e(url('cart.php')) ?>" id="remove-<?= e($line['product']['slug']) ?>" hidden>
        <input type="hidden" name="action" value="remove"><input type="hidden" name="slug" value="<?= e($line['product']['slug']) ?>">
      </form>
      <?php endforeach; ?>

      <aside class="summary">
        <h2>Order summary</h2>
        <dl class="summary__rows">
          <div><dt>Subtotal</dt><dd><?= e(money($subtotal)) ?></dd></div>
          <div><dt>Standard delivery</dt><dd><?= $options['standard']['cents'] === 0 ? 'Free' : e(money($options['standard']['cents'])) ?></dd></div>
          <div class="summary__total"><dt>Estimated total</dt><dd><?= e(money($subtotal + $options['standard']['cents'])) ?></dd></div>
        </dl>
        <p class="summary__note">Prices include GST. Express delivery (<?= e(money($options['express']['cents'])) ?>) can be chosen at checkout.</p>
        <?php if ($gap !== null && $gap > 0): ?>
        <p class="summary__gap"><?= icon('truck', 16) ?> Add <?= e(money($gap)) ?> more for free standard delivery.</p>
        <?php endif; ?>
        <?php if ($problems): ?>
        <p class="field__error">Please fix the highlighted items before checkout.</p>
        <?php else: ?>
        <a class="btn btn--primary btn--lg btn--block" href="<?= e(url('checkout.php')) ?>"><?= icon('lock', 18) ?> Checkout</a>
        <?php endif; ?>
      </aside>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
