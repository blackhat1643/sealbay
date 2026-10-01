<?php
/**
 * FAQ accordion (native <details>). Vars: $faqs = [['q' => …, 'a' => …]]
 */
defined('ST_APP') || exit;

if (!$faqs) {
    return;
}
?>
<div class="faq" data-reveal>
  <?php foreach ($faqs as $faq): ?>
  <details class="faq__item">
    <summary><span><?= e($faq['q']) ?></span><?= icon('chevron-down', 20) ?></summary>
    <div class="faq__answer"><p><?= e($faq['a']) ?></p></div>
  </details>
  <?php endforeach; ?>
</div>
