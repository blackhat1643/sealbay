<?php
/**
 * Framed image: shows a photograph when one exists in assets/img/photos,
 * otherwise a technical illustration on a drawing-sheet panel.
 * Vars: $photo (array|null from photo()), $fallback (HTML), optional $caption, $class, $parallax
 */
defined('ST_APP') || exit;

$class = $class ?? '';
?>
<figure class="media <?= e($class) ?><?= $photo ? '' : ' media--drawn bg-grid' ?>">
  <?php if ($photo): ?>
    <div class="media__img"<?= !empty($parallax) ? ' data-parallax' : '' ?>><?= img_tag($photo) ?></div>
  <?php else: ?>
    <div class="media__drawing"><?= $fallback ?></div>
  <?php endif; ?>
  <?php if (!empty($caption)): ?><figcaption class="sheet__caption"><?= e($caption) ?></figcaption><?php endif; ?>
</figure>
