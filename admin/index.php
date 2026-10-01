<?php
/**
 * Admin dashboard.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$fallbackFile = ST_STORAGE . '/enquiries/enquiries.jsonl';

// Import messages that were written to the file fallback while the database was unavailable.
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $imported = 0;
    if (is_file($fallbackFile)) {
        $allowed = array_flip(['reference', 'type', 'name', 'email', 'phone', 'postcode', 'seal_type', 'inner_diameter', 'outer_diameter', 'width',
            'quantity', 'machine', 'message', 'attachment_path', 'attachment_name', 'ip_address', 'user_agent', 'created_at']);
        foreach (file($fallbackFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $row = json_decode($line, true);
            if (is_array($row) && !empty($row['name']) && !empty($row['email'])) {
                $columns = array_intersect_key($row, $allowed) + ['reference' => 'IMPORT', 'type' => 'quote', 'created_at' => db_now()];
                db_run('INSERT INTO enquiries (' . implode(', ', array_keys($columns)) . ", status) VALUES (" . implode(', ', array_fill(0, count($columns), '?')) . ", 'new')", array_values($columns));
                $imported++;
            }
        }
        rename($fallbackFile, $fallbackFile . '.imported-' . date('Ymd-His'));
    }
    flash('admin_ok', $imported . ' message' . ($imported === 1 ? '' : 's') . ' imported.');
    redirect(admin_url());
}

$threshold = (int) shop('low_stock_threshold', '5');
$counts = [
    'Orders to ship'    => [(int) db_value("SELECT COUNT(*) FROM orders WHERE status = 'paid'"), 'orders.php?status=paid'],
    'Awaiting payment'  => [(int) db_value("SELECT COUNT(*) FROM orders WHERE status = 'awaiting_payment'"), 'orders.php?status=awaiting_payment'],
    'Low or no stock'   => [(int) db_value('SELECT COUNT(*) FROM products WHERE is_active = 1 AND stock_qty <= ?', [$threshold]), 'products.php?stock=low'],
    'New messages'      => [(int) db_value("SELECT COUNT(*) FROM enquiries WHERE status = 'new'"), 'enquiries.php?status=new'],
    'Products'          => [(int) db_value('SELECT COUNT(*) FROM products'), 'products.php'],
];
$latest  = db_all('SELECT id, order_number, first_name, last_name, total_cents, payment_method, status, created_at FROM orders ORDER BY created_at DESC, id DESC LIMIT 8');
$pending = is_file($fallbackFile) ? count(file($fallbackFile, FILE_SKIP_EMPTY_LINES) ?: []) : 0;

$todo = [];
if (company('name') === 'SealBay Australia') {
    $todo[] = ['The business name is still the placeholder “SealBay Australia”. Set your own name.', 'settings.php'];
}
if (company('abn') === '') {
    $todo[] = ['Add your ABN — it must be shown on the site.', 'settings.php'];
}
if (company('phone') === '' || company('email') === '' || company('address_line1') === '') {
    $todo[] = ['Add your real contact details: phone, email and address.', 'settings.php'];
}
if (db_value("SELECT COUNT(*) FROM products WHERE sku = 'TC-35-52-7-NBR' AND price_cents = 890") > 0) {
    $todo[] = ['The catalogue still contains the sample products and prices. Replace them with your own range.', 'products.php'];
}
if (!isset(settings()['shop_shipping_standard'])) {
    $todo[] = ['Shipping rates and delivery estimates are still the sample values. Set your own.', 'shop-settings.php'];
}
if (!stripe_enabled() && !bank_details()) {
    $todo[] = ['No payment method is configured. Add Stripe keys or bank details to the configuration (file or environment variables).', null];
} elseif (stripe_enabled() && (string) config('payments.stripe_webhook_secret', '') === '') {
    $todo[] = ['Add the Stripe webhook secret so payments are confirmed even if a buyer closes the browser.', null];
}
if ((string) config('mail.to', '') === '') {
    $todo[] = ['Set mail.to in the configuration file so new orders and messages are emailed to you.', null];
}
if ((string) config('app.site_url', '') === '') {
    $todo[] = ['Set app.site_url in the configuration file (used in emails, canonical URLs and the sitemap).', null];
}
if (!storage_in_db() && is_dir(ST_ROOT . '/install')) {
    $todo[] = ['Delete the /install folder from the server.', null];
}
if (storage_in_db() && (string) config('mail.resend_api_key', '') === '') {
    $todo[] = ['This host cannot send email by itself. Add a Resend API key (RESEND_API_KEY) so order and dispatch emails are sent.', null];
}

admin_header('Dashboard', 'dashboard');
?>
<div class="a-cards">
  <?php foreach ($counts as $label => [$value, $href]): ?>
  <a class="a-card" href="<?= e(admin_url($href)) ?>"><strong><?= $value ?></strong><span><?= e($label) ?></span></a>
  <?php endforeach; ?>
</div>

<?php if ($pending > 0): ?>
<form class="a-alert a-alert--warn" method="post">
  <?= csrf_field() ?>
  <?= $pending ?> message<?= $pending === 1 ? ' was' : 's were' ?> saved to the file fallback while the database was not available.
  <button class="a-btn a-btn--sm" type="submit">Import into database</button>
</form>
<?php endif; ?>

<?php if ($todo): ?>
<section class="a-panel">
  <h2>Before you launch</h2>
  <ul class="a-todo">
    <?php foreach ($todo as [$text, $href]): ?>
    <li><?= e($text) ?><?php if ($href): ?> <a href="<?= e(admin_url($href)) ?>">Open →</a><?php endif; ?></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<section class="a-panel">
  <h2>Latest orders</h2>
  <?php if (!$latest): ?>
  <p class="a-empty">No orders yet.</p>
  <?php else: ?>
  <div class="a-table-wrap">
    <table class="a-table">
      <thead><tr><th>Order</th><th>Placed</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($latest as $row): ?>
        <tr<?= $row['status'] === 'paid' ? ' class="is-new"' : '' ?>>
          <td><a href="<?= e(admin_url('order-view.php?id=' . (int) $row['id'])) ?>"><?= e($row['order_number']) ?></a></td>
          <td><?= e(format_date($row['created_at'], 'j M Y, g:i a')) ?></td>
          <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
          <td><?= e(money((int) $row['total_cents'])) ?></td>
          <td><?= e($row['payment_method']) ?></td>
          <td><?= a_status_label($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php admin_footer(); ?>
