<?php
/**
 * Client list from data/company.php. Shows a logo where one has been supplied in
 * assets/img/clients, otherwise the company name.
 * Optional vars: $compact (bool) — one flat row without group headings
 */
defined('ST_APP') || exit;

$profile = company_profile();
$groups  = $profile['clients'] ?? [];
if (!$groups) {
    return;
}
if (!empty($compact)) {
    $groups = ['' => array_merge(...array_values($groups))];
}
?>
<?php foreach ($groups as $group => $clients): ?>
<?php if ($group !== ''): ?><h3 class="client-group"><?= e($group) ?></h3><?php endif; ?>
<ul class="client-grid" data-reveal>
  <?php foreach ($clients as [$slug, $name]): $logo = client_logo($slug); ?>
  <li class="client">
    <?php if ($logo): ?>
    <img src="<?= e($logo) ?>" alt="<?= e($name) ?>" loading="lazy" decoding="async">
    <?php else: ?>
    <span class="client__name"><?= e($name) ?></span>
    <?php endif; ?>
  </li>
  <?php endforeach; ?>
</ul>
<?php endforeach; ?>
