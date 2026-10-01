<?php
/**
 * Single order: items, customer, and the actions that move it along
 * (mark paid → mark shipped, or cancel and restock).
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$id    = (int) ($_GET['id'] ?? 0);
$order = db_one('SELECT * FROM orders WHERE id = ?', [$id]);
if (!$order) {
    flash('admin_err', 'That order could not be found.');
    redirect(admin_url('orders.php'));
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $action = a_post('action');

    if ($action === 'paid' && $order['status'] === 'awaiting_payment') {
        if (order_mark_paid($id, a_post('payment_ref') ?: null)) {
            send_order_emails(db_one('SELECT * FROM orders WHERE id = ?', [$id]));
            flash('admin_ok', 'Order marked as paid. The customer has been emailed.');
        }
    } elseif ($action === 'shipped' && $order['status'] === 'paid') {
        if (order_mark_shipped($id, mb_substr(a_post('tracking_number'), 0, 80))) {
            send_shipped_email(db_one('SELECT * FROM orders WHERE id = ?', [$id]));
            flash('admin_ok', 'Order marked as shipped. The customer has been emailed.');
        }
    } elseif ($action === 'cancel' && in_array($order['status'], ['awaiting_payment', 'paid'], true)) {
        if (order_cancel($id)) {
            flash('admin_ok', 'Order cancelled and stock returned.' . ($order['status'] === 'paid' ? ' Remember to refund the payment.' : ''));
        }
    } elseif ($action === 'notes') {
        db_run('UPDATE orders SET admin_notes = ? WHERE id = ?', [mb_substr(a_post('admin_notes', true), 0, 5000), $id]);
        flash('admin_ok', 'Notes saved.');
    }
    redirect(admin_url('order-view.php?id=' . $id));
}

$items = order_items($id);
admin_header('Order ' . $order['order_number'], 'orders');
?>
<p><a href="<?= e(admin_url('orders.php')) ?>">← All orders</a></p>
<div class="a-cols">
  <div>
    <section class="a-panel">
      <h2>Items <?= a_status_label($order['status']) ?></h2>
      <div class="a-table-wrap">
        <table class="a-table">
          <thead><tr><th>SKU</th><th>Item</th><th>Qty</th><th>Each</th><th>Total</th></tr></thead>
          <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
              <td><?= e($item['sku']) ?></td>
              <td><?= e($item['name']) ?><?php if ((int) $item['backorder_qty'] > 0): ?><br><span class="a-badge a-badge--new"><?= (int) $item['backorder_qty'] ?> on backorder</span><?php endif; ?></td>
              <td><?= (int) $item['quantity'] ?></td>
              <td><?= e(money((int) $item['unit_price_cents'])) ?></td>
              <td><?= e(money((int) $item['line_total_cents'])) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <dl class="a-dl" style="margin-top:16px">
        <dt>Subtotal</dt><dd><?= e(money((int) $order['subtotal_cents'])) ?></dd>
        <dt>Delivery (<?= e($order['shipping_method']) ?>)</dt><dd><?= e(money((int) $order['shipping_cents'])) ?></dd>
        <dt>Total (incl. GST)</dt><dd><strong><?= e(money((int) $order['total_cents'])) ?></strong> — GST <?= e(money((int) $order['gst_cents'])) ?></dd>
        <dt>Payment</dt><dd><?= e($order['payment_method']) ?><?= $order['payment_ref'] ? ' · ' . e($order['payment_ref']) : '' ?><?= $order['paid_at'] ? ' · paid ' . e(format_date($order['paid_at'], 'j M Y, g:i a')) : '' ?></dd>
        <dt>Placed</dt><dd><?= e(format_date($order['created_at'], 'j M Y, g:i a')) ?></dd>
        <?php if ($order['shipped_at']): ?><dt>Shipped</dt><dd><?= e(format_date($order['shipped_at'], 'j M Y, g:i a')) ?><?= $order['tracking_number'] ? ' · tracking ' . e($order['tracking_number']) : '' ?></dd><?php endif; ?>
      </dl>
    </section>

    <section class="a-panel">
      <h2>Customer</h2>
      <dl class="a-dl">
        <dt>Name</dt><dd><?= e($order['first_name'] . ' ' . $order['last_name']) ?><?= $order['company'] ? ' · ' . e($order['company']) : '' ?></dd>
        <dt>Email</dt><dd><a href="mailto:<?= e($order['email']) ?>"><?= e($order['email']) ?></a></dd>
        <dt>Phone</dt><dd><a href="<?= e(tel_href($order['phone'])) ?>"><?= e($order['phone']) ?></a></dd>
        <dt>Deliver to</dt><dd><?= e($order['address_line1']) ?><?= $order['address_line2'] ? '<br>' . e($order['address_line2']) : '' ?><br><?= e($order['suburb'] . ' ' . $order['state'] . ' ' . $order['postcode']) ?></dd>
        <?php if ($order['customer_notes']): ?><dt>Delivery notes</dt><dd><?= nl2br(e($order['customer_notes'])) ?></dd><?php endif; ?>
        <dt>Customer’s order page</dt><dd><a href="<?= e(order_url($order)) ?>" target="_blank" rel="noopener">Open ↗</a></dd>
      </dl>
    </section>
  </div>

  <div>
    <?php if ($order['status'] === 'awaiting_payment'): ?>
    <form class="a-panel" method="post">
      <h2>Payment received?</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="paid">
      <?= a_field('payment_ref', 'Payment reference', '', ['maxlength' => 120, 'hint' => 'Optional — e.g. the bank transfer reference.']) ?>
      <button class="a-btn a-btn--primary" type="submit">Mark as paid</button>
    </form>
    <?php endif; ?>

    <?php if ($order['status'] === 'paid'): ?>
    <form class="a-panel" method="post">
      <h2>Ship this order</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="shipped">
      <?= a_field('tracking_number', 'Tracking number', '', ['maxlength' => 80, 'hint' => 'Optional. Included in the dispatch email to the customer.']) ?>
      <button class="a-btn a-btn--primary" type="submit">Mark as shipped &amp; email customer</button>
    </form>
    <?php endif; ?>

    <form class="a-panel" method="post">
      <h2>Internal notes</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="notes">
      <?= a_field('admin_notes', 'Notes', $order['admin_notes'], ['type' => 'textarea', 'rows' => 5, 'hint' => 'Not shown to the customer.']) ?>
      <button class="a-btn" type="submit">Save notes</button>
    </form>

    <?php if (in_array($order['status'], ['awaiting_payment', 'paid'], true)): ?>
    <form class="a-panel a-panel--danger" method="post" data-confirm="Cancel this order and return its items to stock?">
      <h2>Cancel order</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="cancel">
      <p>Returns the reserved items to stock. Refunds are made separately in your payment provider or bank.</p>
      <button class="a-btn a-btn--danger" type="submit">Cancel order</button>
    </form>
    <?php endif; ?>
  </div>
</div>
<?php admin_footer(); ?>
