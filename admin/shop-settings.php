<?php
/**
 * Shop settings: delivery rates and estimates, dispatch cut-off, returns period,
 * stock threshold and size-finder tolerance.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$money  = ['shipping_standard' => 'Standard parcel rate (AUD)', 'shipping_express' => 'Express rate (AUD)', 'free_shipping_over' => 'Free standard delivery on orders of (AUD)'];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $values = [];
    foreach ($money as $key => $label) {
        $value = a_post($key);
        if ($value === '' && $key === 'free_shipping_over') {
            $values[$key] = '';
            continue;
        }
        $cents = to_cents($value);
        if ($cents === null) {
            $errors[] = $label . ' must be an amount such as 9.95.';
        }
        $values[$key] = $cents === null ? $value : number_format($cents / 100, 2, '.', '');
    }
    $values['dispatch_cutoff']     = mb_substr(a_post('dispatch_cutoff'), 0, 20);
    $values['returns_days']        = (string) max(0, min(365, (int) a_post('returns_days')));
    $values['low_stock_threshold'] = (string) max(0, min(1000, (int) a_post('low_stock_threshold')));
    $tolerance                     = parse_mm(a_post('size_tolerance_mm'));
    if ($tolerance === null || $tolerance > 5) {
        $errors[] = 'Size finder tolerance must be a number of millimetres between 0.01 and 5.';
    }
    $values['size_tolerance_mm'] = $tolerance === null ? a_post('size_tolerance_mm') : mm($tolerance);
    if ($values['dispatch_cutoff'] === '') {
        $errors[] = 'Dispatch cut-off time cannot be empty.';
    }
    foreach (['standard', 'express'] as $method) {
        foreach (array_keys(AU_STATES) as $state) {
            $values['estimate_' . $method . '_' . $state] = mb_substr(a_post('estimate_' . $method . '_' . $state), 0, 40);
        }
    }
    if (!$errors) {
        foreach ($values as $key => $value) {
            db_run('DELETE FROM settings WHERE setting_key = ?', ['shop_' . $key]);
            db_run('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)', ['shop_' . $key, $value]);
        }
        flash('admin_ok', 'Shop settings saved.');
        redirect(admin_url('shop-settings.php'));
    }
}

$current = static fn (string $key): string => isset($_POST[$key]) ? a_post($key) : shop($key);
$sample  = !isset(settings()['shop_shipping_standard']);

admin_header('Shop Settings', 'shop');
?>
<?php if ($errors): ?><div class="a-alert a-alert--err"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<?php if ($sample): ?><div class="a-alert a-alert--warn">These are the sample values that came with the website. Review them and press Save to make them yours.</div><?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <section class="a-panel a-form a-form--3">
    <h2 class="a-field--wide">Delivery rates</h2>
    <?php foreach ($money as $key => $label): ?>
      <?= a_field($key, $label, $current($key), ['hint' => $key === 'free_shipping_over' ? 'Leave empty to never offer free delivery.' : 'Including GST.']) ?>
    <?php endforeach; ?>
  </section>

  <section class="a-panel">
    <h2>Delivery estimates by state</h2>
    <p class="a-empty">Shown at checkout for the buyer’s postcode and on the Delivery &amp; Returns page. Counted from dispatch.</p>
    <div class="a-table-wrap">
      <table class="a-table">
        <thead><tr><th>Delivering to</th><th>Standard parcel</th><th>Express</th></tr></thead>
        <tbody>
          <?php foreach (AU_STATES as $code => $name): ?>
          <tr>
            <td><strong><?= e($code) ?></strong> — <?= e($name) ?></td>
            <?php foreach (['standard', 'express'] as $method): $key = 'estimate_' . $method . '_' . $code; ?>
            <td><input type="text" name="<?= e($key) ?>" value="<?= e(isset($_POST[$key]) ? a_post($key) : delivery_estimate($method, $code)) ?>" maxlength="40" aria-label="<?= e(ucfirst($method) . ' estimate for ' . $code) ?>"></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="a-panel a-form">
    <h2 class="a-field--wide">Dispatch, returns and stock</h2>
    <?= a_field('dispatch_cutoff', 'Same-day dispatch cut-off', $current('dispatch_cutoff'), ['maxlength' => 20, 'hint' => 'Shown as “Order before … for same-day dispatch”, e.g. 2 pm.']) ?>
    <?= a_field('returns_days', 'Returns period (days)', $current('returns_days'), ['type' => 'number']) ?>
    <?= a_field('low_stock_threshold', '“Low stock” at or below', $current('low_stock_threshold'), ['type' => 'number']) ?>
    <?= a_field('size_tolerance_mm', 'Size finder tolerance (mm)', $current('size_tolerance_mm'), ['hint' => 'The size finder shows seals within this many millimetres of each measurement.']) ?>
  </section>
  <p><button class="a-btn a-btn--primary" type="submit">Save shop settings</button></p>
</form>
<p class="a-empty">Payment methods (Stripe keys, bank account details) are set in the configuration file, not here.</p>
<?php admin_footer(); ?>
