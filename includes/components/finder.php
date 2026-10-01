<?php
/**
 * Size finder form. Submits to the shop listing with id / od / w / type.
 * Optional vars: $values (current id, od, w, type), $action (URL), $title, $light (bool: on a dark background)
 */
defined('ST_APP') || exit;

$values = $values ?? [];
$action = $action ?? url('shop.php');
$idBase = 'finder-' . substr(md5($action . ($title ?? '')), 0, 5);
$types  = array_diff_key(SEAL_TYPES, ['kit' => 1]);
// Keep the category when the form posts to a query-string category URL.
$hidden = [];
parse_str((string) parse_url($action, PHP_URL_QUERY), $hidden);
?>
<form class="finder<?= !empty($light) ? ' finder--on-dark' : '' ?>" method="get" action="<?= e(strtok($action, '?')) ?>" role="search" aria-label="Find a seal by size">
  <?php foreach ($hidden as $name => $value): if (is_string($value)): ?>
  <input type="hidden" name="<?= e($name) ?>" value="<?= e($value) ?>">
  <?php endif; endforeach; ?>
  <?php if (!empty($title)): ?><p class="finder__title"><?= icon('ruler', 20) ?> <?= e($title) ?></p><?php endif; ?>
  <div class="finder__fields">
    <div class="finder__field">
      <label for="<?= $idBase ?>-id">Inner diameter <span>(mm)</span></label>
      <input class="input" id="<?= $idBase ?>-id" name="id" type="text" inputmode="decimal" autocomplete="off" placeholder="e.g. 35" value="<?= e($values['id'] ?? '') ?>" pattern="[0-9]{1,4}([.,][0-9]{1,2})?" title="Millimetres, for example 35 or 47.5">
    </div>
    <div class="finder__field">
      <label for="<?= $idBase ?>-od">Outer diameter <span>(mm)</span></label>
      <input class="input" id="<?= $idBase ?>-od" name="od" type="text" inputmode="decimal" autocomplete="off" placeholder="e.g. 52" value="<?= e($values['od'] ?? '') ?>" pattern="[0-9]{1,4}([.,][0-9]{1,2})?" title="Millimetres, for example 52">
    </div>
    <div class="finder__field">
      <label for="<?= $idBase ?>-w">Width <span>(mm)</span></label>
      <input class="input" id="<?= $idBase ?>-w" name="w" type="text" inputmode="decimal" autocomplete="off" placeholder="e.g. 7" value="<?= e($values['w'] ?? '') ?>" pattern="[0-9]{1,4}([.,][0-9]{1,2})?" title="Millimetres, for example 7">
    </div>
    <div class="finder__field">
      <label for="<?= $idBase ?>-type">Seal type</label>
      <select class="select" id="<?= $idBase ?>-type" name="type">
        <option value="">All types</option>
        <?php foreach ($types as $key => $label): ?>
        <option value="<?= e($key) ?>"<?= ($values['type'] ?? '') === $key ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn--primary finder__submit" type="submit"><?= icon('search', 18) ?> See what fits</button>
  </div>
  <p class="finder__hint">Results match within <?= e(mm(shop('size_tolerance_mm', '0.5'))) ?> mm. You can leave a field empty. <a href="<?= e(url('measure-your-seal.php')) ?>">How to measure</a></p>
</form>
