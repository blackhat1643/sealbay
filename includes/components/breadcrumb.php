<?php
/**
 * Breadcrumb trail. Vars: $crumbs = [[label, path|null], …] (last item = current page)
 */
defined('ST_APP') || exit;

if (count($crumbs) < 2) {
    return;
}
?>
<nav class="breadcrumb" aria-label="Breadcrumb">
  <ol>
    <?php foreach ($crumbs as [$label, $path]): ?>
      <?php if ($path === null): ?>
      <li aria-current="page"><?= e($label) ?></li>
      <?php else: ?>
      <li><a href="<?= e(str_starts_with($path, '/') ? $path : url($path === 'index.php' ? '' : $path)) ?>"><?= e($label) ?></a></li>
      <?php endif; ?>
    <?php endforeach; ?>
  </ol>
</nav>
