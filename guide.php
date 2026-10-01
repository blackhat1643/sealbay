<?php
/**
 * A single how-to guide.  /guides/<slug>  (or guide.php?slug=<slug>)
 */
require __DIR__ . '/includes/bootstrap.php';

$slug    = isset($_GET['slug']) && is_string($_GET['slug']) ? slugify($_GET['slug']) : '';
$article = $slug !== '' ? article($slug) : null;
if (!$article) {
    render_404();
}

$related = array_slice(array_values(array_filter(articles(), static fn ($a) => $a['slug'] !== $article['slug'])), 0, 4);

$canonical = article_url($article['slug']);
$page      = [
    'title'         => $article['meta_title'],
    'description'   => $article['meta_description'],
    'path'          => 'guide.php?slug=' . $article['slug'],
    'canonical_url' => $canonical,
    'breadcrumbs'   => [['Home', 'index.php'], ['Guides', 'guides.php'], [$article['title'], null]],
    'og_type'       => 'article',
    'faqs'          => $article['faqs'],
];
$page['schema'] = [schema_article($article, site_origin() . $canonical)];

require __DIR__ . '/includes/header.php';
?>
<section class="page-hero bg-grid">
  <div class="container page-hero__inner">
    <div class="page-hero__content">
      <?php component('breadcrumb', ['crumbs' => [['Home', 'index.php'], ['Guides', 'guides.php'], [$article['topic'], null]]]); ?>
      <h1 class="page-hero__title"><?= e($article['title']) ?></h1>
      <p class="page-hero__lead"><?= e($article['excerpt']) ?></p>
      <p class="article-meta"><span><?= e($article['topic']) ?></span><span><?= (int) $article['reading_minutes'] ?> min read</span></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container article-layout">
    <article class="prose">
      <?= safe_html($article['body']) ?>
    </article>
    <aside class="article-aside">
      <div class="aside-box aside-box--dark">
        <h2>Find your seal</h2>
        <p>Got the three measurements? See what fits.</p>
        <a class="btn btn--primary btn--block" href="<?= e(url('shop.php')) ?>"><?= icon('search', 18) ?> Open the size finder</a>
      </div>
      <?php if ($related): ?>
      <div class="aside-box">
        <h2>More guides</h2>
        <ul class="aside-links">
          <?php foreach ($related as $rel): ?>
          <li><a href="<?= e(article_url($rel['slug'])) ?>"><?= e($rel['title']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </aside>
  </div>
</section>

<?php if ($article['faqs']): ?>
<section class="section section--light">
  <div class="container">
    <?php section_heading('Questions', 'Quick answers'); ?>
    <?php component('faq', ['faqs' => $article['faqs']]); ?>
  </div>
</section>
<?php endif; ?>
<?php
component('cta-band');
require __DIR__ . '/includes/footer.php';
