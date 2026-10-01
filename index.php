<?php
/**
 * Homepage: size finder, reasons to buy, categories, popular sizes, measuring help.
 */
require __DIR__ . '/includes/bootstrap.php';

$name = company('name');
$page = [
    'title_full'  => $name . ' — Rotary Shaft Seals & Hydraulic Seals Online',
    'description' => 'Rotary shaft seals and hydraulic seals, shipped from Australian stock. Enter your measurements in millimetres and see what fits.',
    'path'        => '',
    'body_class'  => 'page-home',
];

$categories = categories();
$products   = all_products();
$featured   = array_values(array_filter($products, static fn ($p) => $p['is_featured']));
$counts     = array_count_values(array_column($products, 'category'));
$materials  = materials();
$guides     = array_slice(articles(), 0, 3);
$heroPhoto  = photo('hero');
$profile    = company_profile();

require __DIR__ . '/includes/header.php';
?>

<section class="hero<?= $heroPhoto ? ' hero--photo' : '' ?>">
  <?php if ($heroPhoto): ?>
  <div class="hero__bg" data-parallax><?= img_tag($heroPhoto, ['loading' => 'eager', 'fetchpriority' => 'high', 'alt' => '']) ?></div>
  <?php endif; ?>
  <div class="hero__grid bg-grid" aria-hidden="true"></div>

  <div class="container hero__inner">
    <div class="hero__content">
      <p class="eyebrow eyebrow--light">Rotary &amp; hydraulic seals · Shipped Australia-wide</p>
      <h1 class="hero__title">Find the seal that fits <span>your shaft.</span></h1>
      <p class="hero__lead">Rotary shaft seals and hydraulic seals, shipped from Australian stock. Enter your measurements in millimetres and see what fits.</p>
      <ul class="hero__points">
        <li><?= icon('check', 18) ?> Order before <?= e(shop('dispatch_cutoff', '2 pm')) ?> for same-day dispatch</li>
        <li><?= icon('check', 18) ?> Prices in AUD, GST included</li>
        <li><?= icon('check', 18) ?> <?= e(shop('returns_days', '30')) ?>-day returns on unused seals</li>
      </ul>
    </div>

    <div class="hero__finder">
      <?php component('finder', ['title' => 'Size finder']); ?>
      <p class="hero__alt"><?= icon('camera', 18) ?><span>No calipers? <a href="<?= e(url('custom-quote.php')) ?>">Send us a photo of the old seal</a></span></p>
    </div>
  </div>
</section>

<section class="section section--tight">
  <div class="container">
    <h2 class="visually-hidden">Why buy from <?= e($name) ?></h2>
    <?php component('reasons'); ?>
  </div>
</section>

<section class="section section--light" id="categories">
  <div class="container">
    <?php section_heading('Shop by type', 'What are you replacing?', 'Three ranges cover most repairs: seals for rotating shafts, seals for hydraulic cylinders, and complete kits.', ['action' => ['All seals', url('shop.php')]]); ?>
    <div class="cat-grid">
      <?php foreach ($categories as $cat): ?>
        <?php component('category-card', ['category' => $cat, 'count' => $counts[$cat['slug']] ?? 0]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($featured): ?>
<section class="section" id="popular">
  <div class="container">
    <?php section_heading('Popular sizes', 'Seals people order most', '', ['action' => ['Browse all sizes', url('shop.php')]]); ?>
    <div class="p-grid">
      <?php foreach (array_slice($featured, 0, 8) as $product): ?>
        <?php component('product-card', ['product' => $product]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="section section--navy bg-grid feature" id="measure">
  <div class="container feature__inner">
    <div class="feature__content" data-reveal>
      <p class="eyebrow eyebrow--light">Measure your seal</p>
      <h2 class="sec-head__title">Three measurements are all you need</h2>
      <p class="lead">Every seal on this site is listed as inner diameter × outer diameter × width, in millimetres.</p>
      <ol class="num-list num-list--light">
        <li><strong>Inner diameter</strong> — the shaft or rod the seal runs on.</li>
        <li><strong>Outer diameter</strong> — the housing bore it presses into.</li>
        <li><strong>Width</strong> — measured at the thickest point.</li>
      </ol>
      <div class="btn-row">
        <a class="btn btn--primary" href="<?= e(url('measure-your-seal.php')) ?>">Full measuring guide <?= icon('arrow-right', 18) ?></a>
        <a class="btn btn--ghost-light" href="<?= e(url('custom-quote.php')) ?>"><?= icon('camera', 18) ?> Send a photo instead</a>
      </div>
    </div>
    <figure class="sheet sheet--dark" data-reveal style="--d:120ms">
      <?= illustration_measure() ?>
      <figcaption class="sheet__caption">Where to measure a rotary shaft seal · drawing not to scale</figcaption>
    </figure>
  </div>
</section>

<section class="section" id="hydraulic">
  <div class="container">
    <?php section_heading('Hydraulic cylinders', 'Which seal goes where?', 'A cylinder uses several seals. Find the one you need by its position, or reseal the lot with a kit.', ['action' => ['Shop hydraulic seals', category_url('hydraulic-seals')]]); ?>
    <?php component('cylinder-figure', ['theme' => 'light']); ?>
  </div>
</section>

<section class="section section--light" id="materials">
  <div class="container">
    <?php section_heading('Materials', 'Which material do you need?', 'Match the material to the oil and the heat. If the old seal lasted well, the same material is usually the right call.', ['action' => ['Materials guide', url('materials.php')]]); ?>
    <div class="mat-strip mat-strip--4" data-reveal>
      <?php foreach ($materials as $m): ?>
      <a class="mat-cell" href="<?= e(url('materials.php#' . $m['slug'])) ?>">
        <span class="mat-cell__code"><?= e($m['code']) ?></span>
        <span class="mat-cell__name"><?= e($m['name']) ?></span>
        <span class="mat-cell__text"><?= e($m['summary']) ?></span>
        <span class="mat-cell__more">Details <?= icon('arrow-right', 16) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php if ($profile): ?>
<section class="section section--navy bg-grid" id="company">
  <div class="container">
    <div class="intro intro--center">
      <div data-reveal>
        <p class="eyebrow eyebrow--light">The company behind the shop</p>
        <h2 class="sec-head__title" style="margin-bottom:24px">Backed by <?= e($profile['name']) ?></h2>
        <p class="lead"><?= e($name) ?> is the online shop of <?= e($profile['name']) ?>, an industrial sealing distributor founded in <?= e($profile['founded']) ?> in <?= e($profile['location']) ?>.</p>
        <p><?= e($profile['principal']['status']) ?>, and distributor for <?= e(implode(', ', array_map(static fn ($b) => $b[0] . ' (' . $b[1] . ')', $profile['brands']))) ?>.</p>
        <div class="btn-row">
          <a class="btn btn--primary" href="<?= e(url('about.php')) ?>">About the company <?= icon('arrow-right', 18) ?></a>
        </div>
      </div>
      <ul class="fact-row fact-row--dark fact-row--2" data-reveal style="--d:120ms">
        <?php foreach ($profile['facts'] as [$value, $label, $note]): ?>
        <li class="fact">
          <p class="fact__value"><?= e($value) ?></p>
          <p class="fact__label"><?= e($label) ?></p>
          <p class="fact__note"><?= e($note) ?></p>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="client-strip">
      <p class="client-strip__label">Clients of <?= e($profile['name']) ?> include</p>
      <?php component('clients', ['compact' => true]); ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($guides): ?>
<section class="section" id="guides">
  <div class="container">
    <?php section_heading('How-to guides', 'Help with the job', '', ['action' => ['All guides', url('guides.php')]]); ?>
    <div class="art-grid">
      <?php foreach ($guides as $guide): ?>
        <?php component('article-card', ['article' => $guide]); ?>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php component('cta-band'); ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
