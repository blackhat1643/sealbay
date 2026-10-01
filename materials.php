<?php
/**
 * Materials guide (draft copy — see data/materials.php).
 */
require __DIR__ . '/includes/bootstrap.php';

$materials = materials();
$faqs = [
    ['q' => 'Should I replace an NBR seal with FKM?', 'a' => 'Only if the old seal failed from heat or the fluid calls for it. FKM handles more heat and more chemicals but costs more, and it is not better for every fluid. If the NBR seal lasted well, fit NBR again.'],
    ['q' => 'How do I tell what my old seal is made of?', 'a' => 'Colour is only a hint: NBR is usually black and FKM is often brown. Markings on the seal or the machine’s parts list are more reliable. If in doubt, send us a photo.'],
    ['q' => 'Where is the temperature range for a particular seal?', 'a' => 'In the specification table on each product page. The ranges on this page are typical for the material; the product page is the figure that applies to that seal.'],
];

$page = [
    'title'       => 'Seal Materials Guide — NBR, FKM, Polyurethane, PTFE',
    'description' => 'Which seal material do you need? A plain guide to NBR, FKM (Viton™), polyurethane and PTFE: what each is good for and what to watch out for.',
    'path'        => 'materials.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Materials', null]],
    'faqs'        => $faqs,
];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'Materials guide',
    'title'   => 'Which seal material do you need?',
    'lead'    => 'Match the material to the oil and the heat. If the old seal lasted well, the same material is usually the right call.',
]);
?>

<section class="section">
  <div class="container">
    <nav class="filter" aria-label="Materials on this page">
      <?php foreach ($materials as $m): ?>
      <a class="btn btn--outline btn--sm" href="#<?= e($m['slug']) ?>"><?= e($m['code']) ?></a>
      <?php endforeach; ?>
      <a class="btn btn--outline btn--sm" href="#comparison">Side by side</a>
    </nav>

    <?php foreach ($materials as $m): ?>
    <article class="mat-block" id="<?= e($m['slug']) ?>" data-reveal>
      <header>
        <p class="mat-block__code"><?= e($m['code']) ?></p>
        <h2 class="mat-block__name"><?= e($m['name']) ?></h2>
        <p class="mat-block__aka"><?= e($m['aka']) ?></p>
      </header>
      <div>
        <p class="lead"><?= e($m['summary']) ?></p>
        <div class="mat-block__cols">
          <section>
            <h3>Good for</h3>
            <ul class="dash-list"><?php foreach ($m['good_for'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
          </section>
          <section>
            <h3>Watch out for</h3>
            <ul class="dash-list"><?php foreach ($m['watch_out'] as $item): ?><li><?= e($item) ?></li><?php endforeach; ?></ul>
          </section>
        </div>
        <p class="mat-block__choose"><strong>Choose it if:</strong> <?= e($m['choose_if']) ?></p>
        <p class="fine-print">Typical temperature range: <?= e($m['temp_range']) ?>. Check the product page for the seal you are buying.</p>
        <p><a class="link-arrow" href="<?= e(shop_url(['q' => $m['code'] === 'PU' ? 'Polyurethane' : $m['code']])) ?>">Shop <?= e($m['code']) ?> seals <?= icon('arrow-right', 18) ?></a></p>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<section class="section section--light" id="comparison">
  <div class="container">
    <?php section_heading('Side by side', 'Materials at a glance', 'A quick comparison for first orientation. The product page has the figures for each seal.'); ?>
    <div class="table-wrap" data-reveal>
      <table class="data-table">
        <thead><tr><th scope="col">Material</th><th scope="col">Typical temperature range</th><th scope="col">Most used for</th><th scope="col">Choose it if</th></tr></thead>
        <tbody>
          <?php foreach ($materials as $m): ?>
          <tr>
            <th scope="row"><?= e($m['code']) ?></th>
            <td><?= e($m['temp_range']) ?></td>
            <td><?= e($m['good_for'][0]) ?></td>
            <td><?= e($m['choose_if']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php section_heading('Questions', 'Material questions'); ?>
    <?php component('faq', ['faqs' => $faqs]); ?>
  </div>
</section>

<?php
component('cta-band', ['title' => 'Not sure what your seal is made of?', 'text' => 'Send us a photo of the old seal and tell us what it came from. We will suggest a material and send you a quote.']);
require __DIR__ . '/includes/footer.php';
