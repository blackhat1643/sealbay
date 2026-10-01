<?php
/**
 * How-to guides — listing.
 */
require __DIR__ . '/includes/bootstrap.php';

$articles = articles();
$topics   = array_values(array_unique(array_map(static fn ($a) => $a['topic'], $articles)));

$page = [
    'title'       => 'How-to Guides for Seals',
    'description' => 'Short, practical guides: how to measure an oil seal, replace a PTO shaft seal, read seal markings and choose between NBR and FKM.',
    'path'        => 'guides.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Guides', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'How-to guides',
    'title'   => 'Help with the job',
    'lead'    => 'Short, practical guides for measuring, choosing and fitting seals.',
]);
?>
<section class="section section--light">
  <div class="container">
    <?php if ($articles): ?>
    <?php if (count($topics) > 1): ?>
    <div class="filter" data-filter role="group" aria-label="Filter guides by topic">
      <button type="button" data-topic-filter="all" aria-pressed="true">All</button>
      <?php foreach ($topics as $topic): ?>
      <button type="button" data-topic-filter="<?= e($topic) ?>" aria-pressed="false"><?= e($topic) ?></button>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div class="art-grid">
      <?php foreach ($articles as $article): ?>
        <?php component('article-card', ['article' => $article]); ?>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="lead">Guides will be published here soon.</p>
    <?php endif; ?>
  </div>
</section>
<?php
component('cta-band');
require __DIR__ . '/includes/footer.php';
