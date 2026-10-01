<?php
/**
 * Closing call-to-action band: custom quote from a photo.
 * Optional vars: $title, $text, $primary = [label, href], $secondary = [label, href]
 */
defined('ST_APP') || exit;

$title     = $title ?? 'Can’t find your size?';
$text      = $text ?? 'Take a photo of the old seal with a ruler beside it and send it to us. We will work out what it is and send you a quote.';
$primary   = $primary ?? ['Send a photo for a quote', url('custom-quote.php')];
$secondary = $secondary ?? ['How to measure a seal', url('measure-your-seal.php')];
?>
<section class="cta-band bg-grid<?= ($ctaPhoto = photo('hero')) ? ' cta-band--photo' : '' ?>">
  <?php if ($ctaPhoto): ?><div class="cta-band__bg"><?= img_tag($ctaPhoto, ['alt' => '']) ?></div><?php endif; ?>
  <div class="container cta-band__inner" data-reveal>
    <div>
      <p class="eyebrow eyebrow--light">Custom quote</p>
      <h2 class="cta-band__title"><?= e($title) ?></h2>
      <p class="cta-band__text"><?= e($text) ?></p>
    </div>
    <div class="cta-band__actions">
      <a class="btn btn--primary btn--lg" href="<?= e($primary[1]) ?>"><?= icon('camera', 20) ?> <?= e($primary[0]) ?></a>
      <a class="btn btn--ghost-light btn--lg" href="<?= e($secondary[1]) ?>"><?= e($secondary[0]) ?></a>
    </div>
  </div>
</section>
