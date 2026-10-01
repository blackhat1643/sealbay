<?php
/**
 * Hydraulic cylinder cut-away with numbered legend. Optional var: $theme ('dark'|'light')
 */
defined('ST_APP') || exit;

$theme = $theme ?? 'dark';
$parts = [
    1 => ['Piston seal', 'Seals the piston against the cylinder bore.'],
    2 => ['Wear rings', 'Keep the piston centred and carry side load.'],
    3 => ['Guide ring', 'Guides the rod in the gland.'],
    4 => ['Buffer seal', 'Absorbs pressure peaks ahead of the rod seal.'],
    5 => ['Rod seal', 'Retains fluid at the moving rod.'],
    6 => ['Wiper seal', 'Keeps dirt and moisture out.'],
    7 => ['Static seals', 'Seal the joints between fixed parts.'],
];
?>
<figure class="cyl cyl--<?= e($theme) ?>" data-cylinder data-reveal>
  <div class="cyl__scroll" tabindex="0" role="group" aria-label="Hydraulic cylinder drawing — scroll sideways to see the full cylinder">
    <?= illustration_cylinder($theme) ?>
  </div>
  <ol class="cyl__legend">
    <?php foreach ($parts as $n => [$name, $text]): ?>
    <li data-part="<?= $n ?>" tabindex="0"><span class="cyl__no"><?= $n ?></span><span><strong><?= e($name) ?></strong><?= e($text) ?></span></li>
    <?php endforeach; ?>
    <li class="cyl__kit"><span class="cyl__no">+</span><span><strong>Seal kits</strong>All of the above for one cylinder, in one bag.</span></li>
  </ol>
  <figcaption class="sheet__caption">Fig. — Hydraulic cylinder sealing system · schematic, not to scale</figcaption>
</figure>
