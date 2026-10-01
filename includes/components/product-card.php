<?php
/**
 * Product card for listings. Vars: $product (optionally with 'match' from the size finder)
 */
defined('ST_APP') || exit;

$href   = product_url($product['slug']);
$status = stock_status($product);
$size   = size_label($product);
?>
<article class="p-card">
  <a class="p-card__media" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
    <?= product_visual($product, 'light', ['alt' => '']) ?>
    <?php if ($size !== ''): ?><span class="p-card__dim"><?= e(str_replace(' mm', '', $size)) ?></span><?php endif; ?>
  </a>
  <div class="p-card__body">
    <p class="p-card__meta"><?= e(SEAL_TYPES[$product['seal_type']]) ?><?= $product['material'] !== '' ? ' · ' . e($product['material']) : '' ?></p>
    <h3 class="p-card__title"><a href="<?= e($href) ?>"><?= e($product['name']) ?></a></h3>
    <?php if (isset($product['match'])): ?>
    <p class="p-card__match<?= $product['match']['exact'] ? ' is-exact' : '' ?>"><?= $product['match']['exact'] ? icon('check', 14) . ' Exact match' : 'Close match: ' . e(implode(', ', $product['match']['notes'])) ?></p>
    <?php endif; ?>
    <div class="p-card__foot">
      <p class="price"><?= e(money($product['price_cents'])) ?> <small>incl. GST</small></p>
      <?= stock_badge($product) ?>
    </div>
    <?php if ($status['can_buy']): ?>
    <form class="p-card__buy" method="post" action="<?= e(url('cart.php')) ?>">
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="slug" value="<?= e($product['slug']) ?>">
      <input type="hidden" name="qty" value="1">
      <button class="btn btn--dark btn--sm btn--block" type="submit"><?= icon('cart', 16) ?> Add to cart<span class="visually-hidden"> — <?= e($product['name']) ?></span></button>
    </form>
    <?php else: ?>
    <a class="btn btn--outline btn--sm btn--block" href="<?= e($href) ?>">View details</a>
    <?php endif; ?>
  </div>
</article>
