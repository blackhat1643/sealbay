<?php
/**
 * Section heading. Vars: $eyebrow, $title, $lead, $opt ['no', 'light', 'center', 'tag', 'action' => [label, href]]
 */
defined('ST_APP') || exit;

$tag    = $opt['tag'] ?? 'h2';
$light  = !empty($opt['light']);
$class  = 'sec-head' . ($lead === '' && empty($opt['action']) ? ' sec-head--single' : '') . (!empty($opt['center']) ? ' sec-head--center' : '');
?>
<div class="<?= $class ?>" data-reveal>
  <div class="sec-head__main">
    <p class="eyebrow<?= $light ? ' eyebrow--light' : '' ?>"><?php if (!empty($opt['no'])): ?><span class="eyebrow__no"><?= e($opt['no']) ?></span><?php endif; ?><?= e($eyebrow) ?></p>
    <<?= $tag ?> class="sec-head__title"><?= e($title) ?></<?= $tag ?>>
  </div>
  <?php if ($lead !== '' || !empty($opt['action'])): ?>
  <div class="sec-head__side">
    <?php if ($lead !== ''): ?><p class="sec-head__lead"><?= e($lead) ?></p><?php endif; ?>
    <?php if (!empty($opt['action'])): ?>
    <a class="link-arrow<?= $light ? ' link-arrow--light' : '' ?>" href="<?= e($opt['action'][1]) ?>"><?= e($opt['action'][0]) ?> <?= icon('arrow-right', 18) ?></a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>
