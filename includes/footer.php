<?php
/**
 * Site footer and closing markup.
 */
defined('ST_APP') || exit;

$phone      = company('phone');
$email      = company('email');
$address    = company_address_lines();
$abn        = company('abn');
$socials    = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube'];
$brandParts = explode(' ', company('name'), 2);
?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="site-footer__top">
      <div class="site-footer__brand">
        <a class="brand brand--light" href="<?= e(url('')) ?>" aria-label="<?= e(company('name')) ?> — home">
          <?= logo_mark(40) ?>
          <span class="brand__text"><strong><?= e($brandParts[0]) ?></strong><?php if (!empty($brandParts[1])): ?><span><?= e($brandParts[1]) ?></span><?php endif; ?></span>
        </a>
        <p class="site-footer__tagline"><?= e(company('tagline')) ?></p>
        <p class="site-footer__about">Find a replacement seal by its measurements and have it sent to your door. Prices are in Australian dollars and include GST.</p>
        <?php if ($parentCo = company_profile()): ?>
        <p class="site-footer__about">The online shop of <a href="<?= e(url('about.php')) ?>"><?= e($parentCo['name']) ?></a>, <?= e($parentCo['location']) ?> — <?= e(lcfirst($parentCo['principal']['status'])) ?>.</p>
        <?php endif; ?>
        <?php if ($abn !== ''): ?><p class="site-footer__abn">ABN <?= e($abn) ?></p><?php elseif (is_dev()): ?><p class="site-footer__abn">ABN — not set (add it in Admin → Business Details)</p><?php endif; ?>
        <ul class="social" aria-label="Social media">
          <?php foreach ($socials as $key => $label): $link = company($key); ?>
            <?php if ($link !== ''): ?>
            <li><a href="<?= e($link) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($label) ?>"><?= icon($key, 18) ?></a></li>
            <?php endif; ?>
          <?php endforeach; ?>
        </ul>
      </div>

      <nav class="site-footer__col" aria-label="Shop">
        <h2 class="site-footer__title">Shop</h2>
        <ul>
          <?php foreach (categories() as $cat): ?>
          <li><a href="<?= e(category_url($cat['slug'])) ?>"><?= e($cat['name']) ?></a></li>
          <?php endforeach; ?>
          <li><a href="<?= e(url('shop.php')) ?>">All seals</a></li>
          <li><a href="<?= e(url('cart.php')) ?>">Cart</a></li>
        </ul>
      </nav>

      <nav class="site-footer__col" aria-label="Help">
        <h2 class="site-footer__title">Help</h2>
        <ul>
          <li><a href="<?= e(url('measure-your-seal.php')) ?>">Measure your seal</a></li>
          <li><a href="<?= e(url('materials.php')) ?>">Materials guide</a></li>
          <li><a href="<?= e(url('guides.php')) ?>">How-to guides</a></li>
          <li><a href="<?= e(url('custom-quote.php')) ?>">Send a photo for a quote</a></li>
          <li><a href="<?= e(url('delivery-returns.php')) ?>">Delivery &amp; returns</a></li>
        </ul>
      </nav>

      <nav class="site-footer__col" aria-label="Company">
        <h2 class="site-footer__title">Company</h2>
        <ul>
          <li><a href="<?= e(url('about.php')) ?>">About us</a></li>
          <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
          <li><a href="<?= e(url('terms-of-sale.php')) ?>">Terms of sale</a></li>
          <li><a href="<?= e(url('privacy-policy.php')) ?>">Privacy policy</a></li>
        </ul>
      </nav>

      <div class="site-footer__col site-footer__contact">
        <h2 class="site-footer__title">Contact</h2>
        <ul>
          <?php if ($address): ?>
          <li><?= icon('map-pin', 16) ?><address><?= implode('<br>', array_map('e', $address)) ?></address></li>
          <?php endif; ?>
          <?php if ($phone !== ''): ?>
          <li><?= icon('phone', 16) ?><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></li>
          <?php endif; ?>
          <?php if ($email !== ''): ?>
          <li><?= icon('mail', 16) ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></li>
          <?php endif; ?>
          <li><?= icon('camera', 16) ?><a href="<?= e(url('custom-quote.php')) ?>">Send us a photo of your seal</a></li>
        </ul>
      </div>
    </div>

    <div class="site-footer__bottom">
      <p>&copy; <?= date('Y') ?> <?= e(company('name')) ?>. All prices in AUD and include GST.</p>
      <ul>
        <li><a href="<?= e(url('privacy-policy.php')) ?>">Privacy</a></li>
        <li><a href="<?= e(url('terms-of-sale.php')) ?>">Terms of sale</a></li>
        <li><a href="<?= e(url('delivery-returns.php')) ?>">Delivery &amp; returns</a></li>
      </ul>
    </div>
  </div>
</footer>
</body>
</html>
