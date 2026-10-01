<?php
/**
 * Inner-page hero. Vars: $crumbs, $eyebrow, $title, $lead,
 * optional $actions = [[label, href, 'primary'|'ghost'], …], $figure (HTML), $caption
 */
defined('ST_APP') || exit;

$actions = $actions ?? [];
$figure  = $figure ?? '';
$caption = $caption ?? '';
?>
<section class="page-hero bg-grid<?= $figure !== '' ? ' page-hero--figure' : '' ?>">
  <div class="container page-hero__inner">
    <div class="page-hero__content">
      <?php component('breadcrumb', ['crumbs' => $crumbs]); ?>
      <?php if (!empty($eyebrow)): ?><p class="eyebrow eyebrow--light"><?= e($eyebrow) ?></p><?php endif; ?>
      <h1 class="page-hero__title"><?= e($title) ?></h1>
      <?php if (!empty($lead)): ?><p class="page-hero__lead"><?= e($lead) ?></p><?php endif; ?>
      <?php if ($actions): ?>
      <div class="btn-row">
        <?php foreach ($actions as [$label, $href, $style]): ?>
        <a class="btn <?= $style === 'primary' ? 'btn--primary' : 'btn--ghost-light' ?>" href="<?= e($href) ?>"><?= e($label) ?><?= $style === 'primary' ? ' ' . icon('arrow-right', 18) : '' ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($figure !== ''): ?>
    <figure class="page-hero__figure sheet sheet--dark" data-reveal>
      <?= $figure ?>
      <?php if ($caption !== ''): ?><figcaption class="sheet__caption"><?= e($caption) ?></figcaption><?php endif; ?>
    </figure>
    <?php endif; ?>
  </div>
</section>
