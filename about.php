<?php
/**
 * About: the shop and the company behind it (content: data/company.php).
 */
require __DIR__ . '/includes/bootstrap.php';

$name    = company('name');
$profile = company_profile();
$parent  = $profile['name'];

$page = [
    'title'       => 'About Us — Backed by ' . $parent,
    'description' => $name . ' is the online shop of ' . $parent . ', an industrial sealing distributor founded in ' . $profile['founded'] . ' and a national channel partner for Parker Hannifin in India.',
    'path'        => 'about.php',
    'breadcrumbs' => [['Home', 'index.php'], ['About', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'About us',
    'title'   => 'The seal specialists behind ' . explode(' ', $name)[0],
    'lead'    => $name . ' is the online shop of ' . $parent . ', a trading and distribution company specialising in high-performance industrial seals. Founded in ' . $profile['founded'] . ' in ' . $profile['location'] . '.',
    'actions' => [['Find a seal', url('shop.php'), 'primary'], ['Contact us', url('contact.php'), 'ghost']],
]);
?>

<section class="facts" aria-label="<?= e($parent) ?> at a glance">
  <div class="container">
    <ul class="fact-row">
      <?php foreach ($profile['facts'] as $i => [$value, $label, $note]): ?>
      <li class="fact" data-reveal style="--d:<?= $i * 70 ?>ms">
        <p class="fact__value"><?= e($value) ?></p>
        <p class="fact__label"><?= e($label) ?></p>
        <p class="fact__note"><?= e($note) ?></p>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="section" id="who-we-are">
  <div class="container split">
    <div class="split__media" data-reveal>
      <?php component('media', ['photo' => photo('about'), 'fallback' => illustration('tc', ['theme' => 'dark']), 'class' => 'media--tall media--tick', 'parallax' => true]); ?>
    </div>
    <div class="split__content" data-reveal style="--d:120ms">
      <p class="eyebrow">Who we are</p>
      <h2 class="sec-head__title"><?= e($parent) ?></h2>
      <p class="lead"><?= e($profile['intro']) ?></p>
      <h3>Our mission</h3>
      <p><?= e($profile['mission']) ?></p>
      <h3>Where the shop fits</h3>
      <p><?= e($name) ?> brings that experience to individual buyers online. Every seal is listed by inner diameter, outer diameter and width in millimetres, with its material, temperature range, stock level and price shown up front. If the size you need is not listed, <a href="<?= e(url('custom-quote.php')) ?>">send us a photo or the measurements</a> and we will quote it.</p>
    </div>
  </div>
</section>

<section class="section section--navy bg-grid" id="brands">
  <div class="container intro intro--center">
    <div data-reveal>
      <p class="eyebrow eyebrow--light">Authorised distribution</p>
      <h2 class="sec-head__title" style="margin-bottom:24px"><?= e($profile['principal']['status']) ?></h2>
      <p class="lead"><?= e($profile['principal']['text']) ?></p>
      <ul class="check-list check-list--light">
        <?php foreach ($profile['principal']['points'] as [$title, $text]): ?>
        <li><?= icon('check', 18) ?><span><strong><?= e($title) ?>.</strong> <?= e($text) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="brand-panel" data-reveal style="--d:120ms">
      <p class="brand-panel__label">Channel partner for</p>
      <p class="brand-panel__main"><?= e($profile['principal']['name']) ?> <span><?= e($profile['principal']['country']) ?></span></p>
      <p class="brand-panel__label">Also distributing</p>
      <ul class="brand-panel__list">
        <?php foreach ($profile['brands'] as [$brand, $country]): ?>
        <li><?= e($brand) ?> <span><?= e($country) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section class="section section--light" id="range">
  <div class="container">
    <?php section_heading('What we supply', 'Sealing products for rotating shafts and hydraulic cylinders', 'The shop lists the sizes people order most. The wider range below is available on request.'); ?>
    <div class="link-grid cols-3">
      <?php foreach ($profile['supply'] as [$title, $text, $href]): ?>
      <a class="link-tile" href="<?= e(internal_link($href)) ?>" data-reveal>
        <strong><?= e($title) ?></strong>
        <span><?= e($text) ?></span>
        <?= icon('arrow-right', 18) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section" id="industries">
  <div class="container">
    <?php section_heading('Industries', 'Sealing components for heavy industry', 'From standard sizes to severe temperature and pressure duty, across a wide range of sectors.'); ?>
    <ul class="factor-grid cols-3" data-reveal>
      <?php foreach ($profile['industries'] as $industry): ?>
      <li><strong><?= e($industry) ?></strong></li>
      <?php endforeach; ?>
    </ul>
    <div class="sector-notes">
      <?php foreach ($profile['sectors'] as [$sector, $heading, $text]): ?>
      <article class="sector-note" data-reveal>
        <p class="sector-note__label"><?= e($sector) ?></p>
        <h3><?= e($heading) ?></h3>
        <p><?= e($text) ?></p>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section section--light" id="clients">
  <div class="container">
    <?php section_heading('Clients', 'Trusted by industry leaders', $profile['clients_intro']); ?>
    <?php component('clients'); ?>
  </div>
</section>

<section class="section" id="team">
  <div class="container intro intro--center">
    <div class="big-figure" data-reveal>
      <p class="big-figure__value"><?= e($profile['sales_force']['count']) ?></p>
      <p class="big-figure__label">Skilled sales engineers</p>
    </div>
    <div data-reveal style="--d:120ms">
      <p class="eyebrow">Technical sales force</p>
      <h2 class="sec-head__title" style="margin-bottom:24px">Technical expertise in the field</h2>
      <p class="lead"><?= e($profile['sales_force']['text']) ?></p>
      <ul class="check-list">
        <?php foreach ($profile['sales_force']['points'] as [$title, $text]): ?>
        <li><?= icon('check', 18) ?><span><strong><?= e($title) ?>.</strong> <?= e($text) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section class="section section--navy bg-grid" id="inventory">
  <div class="container intro">
    <div data-reveal>
      <p class="eyebrow eyebrow--light">Inventory</p>
      <h2 class="sec-head__title" style="margin-bottom:24px"><?= e($profile['inventory']['range']) ?></h2>
      <p class="lead"><?= e($profile['inventory']['text']) ?></p>
    </div>
    <div data-reveal style="--d:120ms">
      <p class="eyebrow eyebrow--light">Why customers choose us</p>
      <ul class="check-list check-list--light" style="margin-top:0">
        <?php foreach ($profile['advantages'] as [$title, $text]): ?>
        <li><?= icon('check', 18) ?><span><strong><?= e($title) ?>.</strong> <?= e($text) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</section>

<section class="section section--light section--tight">
  <div class="container">
    <?php section_heading('Buying online', 'How the shop works'); ?>
    <?php component('reasons'); ?>
  </div>
</section>

<?php
component('cta-band');
require __DIR__ . '/includes/footer.php';
