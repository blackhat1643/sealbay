<?php
/**
 * Knowledge Centre article card. Vars: $article, optional $featured (bool)
 */
defined('ST_APP') || exit;

$featured = !empty($featured);
?>
<article class="art-card<?= $featured ? ' art-card--featured' : '' ?>" data-reveal data-topic="<?= e($article['topic']) ?>">
  <p class="art-card__meta"><span><?= e($article['topic']) ?></span><span><?= (int) $article['reading_minutes'] ?> min read</span></p>
  <h3 class="art-card__title"><a class="stretched" href="<?= e(article_url($article['slug'])) ?>"><?= e($article['title']) ?></a></h3>
  <p class="art-card__text"><?= e($article['excerpt']) ?></p>
  <span class="link-arrow">Read guide <?= icon('arrow-right', 18) ?></span>
</article>
