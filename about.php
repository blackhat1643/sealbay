<?php
/**
 * About the shop. Deliberately says only what the shop does — add your own story.
 */
require __DIR__ . '/includes/bootstrap.php';

$name = company('name');
$page = [
    'title'       => 'About Us',
    'description' => $name . ' is an online shop for rotary shaft seals and hydraulic seals, shipped from Australian stock to buyers across Australia.',
    'path'        => 'about.php',
    'breadcrumbs' => [['Home', 'index.php'], ['About', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'About ' . $name,
    'title'   => 'Replacement seals, without the runaround',
    'lead'    => $name . ' is an online shop for rotary shaft seals and hydraulic seals, sold directly to the people who fit them.',
]);
?>
<section class="section">
  <div class="container split">
    <div class="split__media" data-reveal>
      <?php component('media', ['photo' => photo('about'), 'fallback' => illustration('tc', ['theme' => 'dark']), 'class' => 'media--tall media--tick', 'parallax' => true]); ?>
    </div>
    <div class="split__content" data-reveal style="--d:120ms">
      <p class="eyebrow">What we do</p>
      <h2 class="sec-head__title">The right seal, found by its size</h2>
      <p class="lead">If you know your seal’s measurements — or have the old one in your hand — you should be able to find the replacement in under a minute and have it on its way the same day.</p>
      <p>That is what this shop is built around. Every seal is listed by inner diameter, outer diameter and width in millimetres. The size finder shows what fits. Each product page tells you the material, the temperature range and whether it is in stock, with the price in Australian dollars including GST.</p>
      <p>When you are not sure, there is a plain <a href="<?= e(url('measure-your-seal.php')) ?>">measuring guide</a>, a <a href="<?= e(url('materials.php')) ?>">materials guide</a>, and a way to <a href="<?= e(url('custom-quote.php')) ?>">send us a photo</a> so a person can look at it.</p>
      <div class="btn-row">
        <a class="btn btn--primary" href="<?= e(url('shop.php')) ?>"><?= icon('search', 18) ?> Find a seal</a>
        <a class="btn btn--outline" href="<?= e(url('contact.php')) ?>">Contact us</a>
      </div>
    </div>
  </div>
</section>

<section class="section section--light section--tight">
  <div class="container">
    <?php section_heading('How we work', 'Three things we stick to'); ?>
    <?php component('reasons'); ?>
  </div>
</section>

<?php
component('cta-band');
require __DIR__ . '/includes/footer.php';
