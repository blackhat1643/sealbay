<?php
/**
 * Return from Stripe Checkout (success or cancel). The payment is verified with
 * Stripe before the order is marked paid — the browser is never trusted.
 */
require __DIR__ . '/includes/bootstrap.php';
start_session();

$order = order_for_customer($_GET['n'] ?? null, $_GET['t'] ?? null);
if (!$order) {
    render_404();
}

if (isset($_GET['cancelled'])) {
    if ($order['status'] === 'awaiting_payment' && $order['payment_method'] === 'card') {
        order_cancel((int) $order['id']);
    }
    flash('checkout_notice', 'The card payment was cancelled and nothing was charged. Your cart is still here.');
    redirect(url('checkout.php'));
}

$sessionId = is_string($_GET['session_id'] ?? null) ? $_GET['session_id'] : '';
if ($order['status'] === 'awaiting_payment' && $order['payment_method'] === 'card'
    && $sessionId !== '' && hash_equals((string) $order['payment_ref'], $sessionId)
    && stripe_session_paid($sessionId, $order)) {
    if (order_mark_paid((int) $order['id'], $sessionId)) {
        send_order_emails(order_by_number($order['order_number']));
    }
    $order['status'] = 'paid';
}
if ($order['status'] !== 'awaiting_payment' || $order['payment_method'] !== 'card') {
    cart_clear();
}
redirect(order_url($order));
