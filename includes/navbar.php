<?php
/**
 * Announcement bar + main navigation with cart.
 */
defined('ST_APP') || exit;

$navCategories = categories();
$phone         = company('phone');
$cartCount     = cart_count();
$brandParts    = explode(' ', company('name'), 2);
?>
<?php if (is_dev()): ?>
<div class="devbar">Development preview — sample products, prices and shipping rates. Replace them before launch.</div>
<?php endif; ?>
<div class="topbar">
  <div class="container topbar__inner">
    <p class="topbar__msg"><span class="topbar__dot" aria-hidden="true"></span>Order before <?= e(shop('dispatch_cutoff', '2 pm')) ?> on a business day for same-day dispatch</p>
    <ul class="topbar__meta">
      <li><?= icon('box', 14) ?><span>Shipped from Australian stock</span></li>
      <li><span>Prices in AUD, GST included</span></li>
      <?php if ($phone !== ''): ?>
      <li><?= icon('phone', 14) ?><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<header class="site-header" data-header>
  <div class="container site-header__inner">
    <a class="brand" href="<?= e(url('')) ?>" aria-label="<?= e(company('name')) ?> — home">
      <?= logo_mark(40) ?>
      <span class="brand__text"><strong><?= e($brandParts[0]) ?></strong><?php if (!empty($brandParts[1])): ?><span><?= e($brandParts[1]) ?></span><?php endif; ?></span>
    </a>

    <nav class="nav" id="site-nav" aria-label="Main navigation">
      <ul class="nav__list">
        <li class="nav__item has-menu">
          <a class="nav__link<?= nav_active('shop.php', 'shop', 'product.php', 'seal') ? ' is-active' : '' ?>" href="<?= e(url('shop.php')) ?>">Shop</a>
          <button class="nav__toggle" type="button" aria-expanded="false" aria-controls="menu-shop" aria-label="Show shop menu"><?= icon('chevron-down', 16) ?></button>
          <div class="nav__menu" id="menu-shop">
            <ul>
              <?php foreach ($navCategories as $cat): ?>
              <li><a href="<?= e(category_url($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
              <?php endforeach; ?>
              <li class="nav__all"><a href="<?= e(url('shop.php')) ?>">All seals &amp; size finder <?= icon('arrow-right', 14) ?></a></li>
            </ul>
          </div>
        </li>
        <li class="nav__item"><a class="nav__link<?= nav_active('measure-your-seal.php') ? ' is-active' : '' ?>" href="<?= e(url('measure-your-seal.php')) ?>">Measure Your Seal</a></li>
        <li class="nav__item"><a class="nav__link<?= nav_active('materials.php') ? ' is-active' : '' ?>" href="<?= e(url('materials.php')) ?>">Materials</a></li>
        <li class="nav__item"><a class="nav__link<?= nav_active('guides.php', 'guide.php', 'guides') ? ' is-active' : '' ?>" href="<?= e(url('guides.php')) ?>">Guides</a></li>
        <li class="nav__item"><a class="nav__link<?= nav_active('delivery-returns.php') ? ' is-active' : '' ?>" href="<?= e(url('delivery-returns.php')) ?>">Delivery &amp; Returns</a></li>
        <li class="nav__item"><a class="nav__link<?= nav_active('contact.php') ? ' is-active' : '' ?>" href="<?= e(url('contact.php')) ?>">Contact</a></li>
      </ul>

      <div class="nav__foot">
        <a class="btn btn--primary btn--block" href="<?= e(url('custom-quote.php')) ?>"><?= icon('camera', 18) ?> Send a photo for a quote</a>
        <?php if ($phone !== ''): ?>
        <a class="btn btn--ghost-light btn--block" href="<?= e(tel_href($phone)) ?>"><?= icon('phone', 18) ?> <?= e($phone) ?></a>
        <?php endif; ?>
      </div>
    </nav>

    <a class="cart-link" href="<?= e(url('cart.php')) ?>" aria-label="Cart, <?= $cartCount ?> item<?= $cartCount === 1 ? '' : 's' ?>">
      <?= icon('cart', 22) ?><span class="cart-link__label">Cart</span><?php if ($cartCount > 0): ?><span class="cart-link__count"><?= $cartCount ?></span><?php endif; ?>
    </a>

    <button class="nav-burger" type="button" aria-controls="site-nav" aria-expanded="false" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>
