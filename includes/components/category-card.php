<?php
/**
 * Shop category card. Vars: $category, optional $count (products in it)
 */
defined('ST_APP') || exit;

$href  = category_url($category['slug']);
$image = uploaded_image($category['image'], $category['name']);
?>
<article class="cat-card" data-reveal>
  <a class="cat-card__media bg-grid" href="<?= e($href) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($image): ?>
      <?= img_tag($image, ['class' => 'cat-card__photo', 'alt' => '']) ?>
    <?php else: ?>
      <?= illustration($category['illustration'], ['theme' => 'dark']) ?>
    <?php endif; ?>
  </a>
  <div class="cat-card__body">
    <h3 class="cat-card__title"><a href="<?= e($href) ?>"><?= e($category['name']) ?></a></h3>
    <p class="cat-card__text"><?= e($category['summary']) ?></p>
    <a class="link-arrow" href="<?= e($href) ?>">Shop <?= e(strtolower($category['name'])) ?><?= isset($count) ? ' (' . (int) $count . ')' : '' ?> <?= icon('arrow-right', 18) ?></a>
  </div>
</article>
