<?php
/**
 * Three reasons to buy (from the shop's stated goals).
 */
defined('ST_APP') || exit;

$reasons = [
    ['ruler', 'Find it by size', 'Type in the inner diameter, outer diameter and width in millimetres and see what fits — usually in under a minute.'],
    ['truck', 'Same-day dispatch', 'In-stock orders placed before ' . shop('dispatch_cutoff', '2 pm') . ' on a business day leave our Australian warehouse the same day.'],
    ['returns', 'Easy ' . shop('returns_days', '30') . '-day returns', 'Ordered the wrong size? Unused seals in their original packaging can come back within ' . shop('returns_days', '30') . ' days.'],
];
?>
<ul class="reasons">
  <?php foreach ($reasons as $i => [$ico, $title, $text]): ?>
  <li class="reason" data-reveal style="--d:<?= $i * 80 ?>ms">
    <span class="reason__icon"><?= icon($ico, 26) ?></span>
    <h3 class="reason__title"><?= e($title) ?></h3>
    <p><?= e($text) ?></p>
  </li>
  <?php endforeach; ?>
</ul>
