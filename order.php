<?php
/**
 * Order confirmation / status page. Reached with the order number and its secret token
 * (the link is shown after checkout and included in the confirmation email).
 */
require __DIR__ . '/includes/bootstrap.php';

$order = order_for_customer($_GET['n'] ?? null, $_GET['t'] ?? null);
if (!$order) {
    render_404();
}
$items = order_items((int) $order['id']);
$bank  = bank_details();

$page = [
    'title'       => 'Order ' . $order['order_number'],
    'description' => 'Your order details.',
    'path'        => 'order.php',
    'noindex'     => true,
];
require __DIR__ . '/includes/header.php';

$headline = [
    'awaiting_payment' => $order['payment_method'] === 'bank' ? 'Thanks — your order is reserved' : 'Your payment has not come through yet',
    'paid'             => 'Thanks — your order is confirmed',
    'shipped'          => 'Your order is on its way',
    'cancelled'        => 'This order was cancelled',
][$order['status']] ?? 'Your order';
?>
<section class="section section--tight section--light">
  <div class="container container--narrow">
    <div class="order">
      <span class="notice__icon"><?= icon($order['status'] === 'cancelled' ? 'close' : ($order['status'] === 'shipped' ? 'truck' : 'check'), 32) ?></span>
      <h1 class="page-title"><?= e($headline) ?></h1>
      <p class="order__ref">Order <strong><?= e($order['order_number']) ?></strong> · placed <?= e(format_date($order['created_at'], 'j M Y, g:i a')) ?> · <span class="stock stock--<?= $order['status'] === 'cancelled' ? 'out_of_stock' : ($order['status'] === 'awaiting_payment' ? 'low_stock' : 'in_stock') ?>"><?= e(ORDER_STATUSES[$order['status']] ?? $order['status']) ?></span></p>

      <?php if ($order['status'] === 'awaiting_payment' && $order['payment_method'] === 'bank' && $bank): ?>
      <div class="form-panel order__pay">
        <h2>Pay by bank transfer</h2>
        <p>Please transfer <strong><?= e(money((int) $order['total_cents'])) ?></strong> to the account below. Your order ships once the payment arrives.</p>
        <table class="spec-table">
          <tbody>
            <tr><th scope="row">Account name</th><td><?= e($bank['name']) ?></td></tr>
            <tr><th scope="row">BSB</th><td><?= e($bank['bsb']) ?></td></tr>
            <tr><th scope="row">Account number</th><td><?= e($bank['number']) ?></td></tr>
            <tr><th scope="row">Reference</th><td><strong><?= e($order['order_number']) ?></strong></td></tr>
          </tbody>
        </table>
      </div>
      <?php elseif ($order['status'] === 'awaiting_payment'): ?>
      <div class="alert alert--error">We have not received confirmation of your card payment. If you completed the payment, this page will update shortly — otherwise your cart is still saved and you can <a href="<?= e(url('checkout.php')) ?>">try again</a>.</div>
      <?php elseif ($order['status'] === 'paid'): ?>
      <p class="lead">We have emailed a confirmation to <?= e($order['email']) ?>. In-stock items ordered before <?= e(shop('dispatch_cutoff', '2 pm')) ?> on a business day ship the same day.</p>
      <?php elseif ($order['status'] === 'shipped'): ?>
      <p class="lead">Shipped <?= e(format_date($order['shipped_at'])) ?><?= !empty($order['tracking_number']) ? ' · Tracking number ' . e($order['tracking_number']) : '' ?>.</p>
      <?php endif; ?>

      <div class="form-panel">
        <h2>Items</h2>
        <ul class="summary__items">
          <?php foreach ($items as $item): ?>
          <li><span><?= (int) $item['quantity'] ?> × <?= e($item['name']) ?><small><?= e($item['sku']) ?><?= (int) $item['backorder_qty'] > 0 ? ' · ' . (int) $item['backorder_qty'] . ' on backorder' : '' ?></small></span><span><?= e(money((int) $item['line_total_cents'])) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <dl class="summary__rows">
          <div><dt>Subtotal</dt><dd><?= e(money((int) $order['subtotal_cents'])) ?></dd></div>
          <div><dt>Delivery (<?= e($order['shipping_method']) ?>)</dt><dd><?= (int) $order['shipping_cents'] === 0 ? 'Free' : e(money((int) $order['shipping_cents'])) ?></dd></div>
          <div class="summary__total"><dt>Total (AUD)</dt><dd><?= e(money((int) $order['total_cents'])) ?></dd></div>
        </dl>
        <p class="summary__note">Includes GST of <?= e(money((int) $order['gst_cents'])) ?>.<?= company('abn') !== '' ? ' ' . e(company('name')) . ', ABN ' . e(company('abn')) . '.' : '' ?></p>
      </div>

      <div class="form-panel">
        <h2>Delivering to</h2>
        <address>
          <?= e($order['first_name'] . ' ' . $order['last_name']) ?><br>
          <?php if (!empty($order['company'])): ?><?= e($order['company']) ?><br><?php endif; ?>
          <?= e($order['address_line1']) ?><br>
          <?php if (!empty($order['address_line2'])): ?><?= e($order['address_line2']) ?><br><?php endif; ?>
          <?= e($order['suburb'] . ' ' . $order['state'] . ' ' . $order['postcode']) ?>
        </address>
      </div>

      <div class="btn-row">
        <a class="btn btn--dark" href="<?= e(url('shop.php')) ?>">Keep shopping</a>
        <a class="btn btn--outline" href="<?= e(url('contact.php')) ?>">Question about this order?</a>
      </div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
