<?php
/**
 * Orders: list, filter, search and CSV export.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$status = isset(ORDER_STATUSES[$_GET['status'] ?? '']) ? $_GET['status'] : '';
$search = is_string($_GET['q'] ?? null) ? trim(mb_substr($_GET['q'], 0, 80)) : '';
$pageNo = max(1, (int) ($_GET['page'] ?? 1));
$per    = 25;

$where  = [];
$params = [];
if ($status !== '') {
    $where[]  = 'status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(order_number LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR postcode LIKE ? OR company LIKE ?)';
    $like    = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like, $like);
}
$sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';

if (isset($_GET['export'])) {
    $rows = db_all('SELECT * FROM orders' . $sqlWhere . ' ORDER BY created_at DESC, id DESC', $params);
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="orders-' . date('Ymd-His') . '.csv"');
    $out     = fopen('php://output', 'w');
    $columns = ['order_number', 'created_at', 'status', 'payment_method', 'first_name', 'last_name', 'company', 'email', 'phone',
        'address_line1', 'address_line2', 'suburb', 'state', 'postcode', 'shipping_method', 'subtotal_cents', 'shipping_cents',
        'total_cents', 'gst_cents', 'tracking_number', 'paid_at', 'shipped_at', 'customer_notes'];
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, $columns, ',', '"', '');
    foreach ($rows as $row) {
        $line = [];
        foreach ($columns as $column) {
            $cell = (string) ($row[$column] ?? '');
            if (str_ends_with($column, '_cents')) {
                $cell = number_format(((int) $cell) / 100, 2, '.', '');
            } elseif ($cell !== '' && in_array($cell[0], ['=', '+', '-', '@', "\t", "\r"], true) && !preg_match('/^\+?[0-9 ()\-]+$/', $cell)) {
                $cell = "'" . $cell; // neutralise spreadsheet formulas in customer-supplied text
            }
            $line[] = $cell;
        }
        fputcsv($out, $line, ',', '"', '');
    }
    fclose($out);
    exit;
}

$total  = (int) db_value('SELECT COUNT(*) FROM orders' . $sqlWhere, $params);
$pages  = max(1, (int) ceil($total / $per));
$pageNo = min($pageNo, $pages);
$rows   = db_all('SELECT * FROM orders' . $sqlWhere . ' ORDER BY created_at DESC, id DESC LIMIT ' . $per . ' OFFSET ' . (($pageNo - 1) * $per), $params);
$query  = static fn (array $extra = []): string => http_build_query(array_filter($extra + ['status' => $status, 'q' => $search], 'strlen'));

admin_header('Orders', 'orders');
?>
<form class="a-filter" method="get">
  <input type="search" name="q" value="<?= e($search) ?>" placeholder="Search order number, name, email, postcode…" aria-label="Search orders">
  <select name="status" aria-label="Status">
    <option value="">All statuses</option>
    <?php foreach (ORDER_STATUSES as $key => $label): ?><option value="<?= e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?>
  </select>
  <button class="a-btn" type="submit">Filter</button>
  <a class="a-btn a-btn--ghost" href="<?= e(admin_url('orders.php?' . $query(['export' => '1']))) ?>">Export CSV</a>
</form>

<section class="a-panel">
  <p class="a-count"><?= $total ?> order<?= $total === 1 ? '' : 's' ?></p>
  <?php if (!$rows): ?>
  <p class="a-empty">No orders match this filter.</p>
  <?php else: ?>
  <div class="a-table-wrap">
    <table class="a-table">
      <thead><tr><th>Order</th><th>Placed</th><th>Customer</th><th>Deliver to</th><th>Delivery</th><th>Total</th><th>Payment</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($rows as $row): ?>
        <tr<?= $row['status'] === 'paid' ? ' class="is-new"' : '' ?>>
          <td><a href="<?= e(admin_url('order-view.php?id=' . (int) $row['id'])) ?>"><?= e($row['order_number']) ?></a></td>
          <td><?= e(format_date($row['created_at'], 'j M Y, g:i a')) ?></td>
          <td><?= e($row['first_name'] . ' ' . $row['last_name']) ?></td>
          <td><?= e($row['suburb'] . ' ' . $row['state'] . ' ' . $row['postcode']) ?></td>
          <td><?= e($row['shipping_method']) ?></td>
          <td><?= e(money((int) $row['total_cents'])) ?></td>
          <td><?= e($row['payment_method']) ?></td>
          <td><?= a_status_label($row['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($pages > 1): ?>
  <nav class="a-pages" aria-label="Pages">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
    <a class="<?= $p === $pageNo ? 'is-active' : '' ?>" href="<?= e(admin_url('orders.php?' . $query(['page' => (string) $p]))) ?>"><?= $p ?></a>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
  <?php endif; ?>
</section>
<?php admin_footer(); ?>
