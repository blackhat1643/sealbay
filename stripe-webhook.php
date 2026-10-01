<?php
/**
 * Stripe webhook endpoint. Add it in the Stripe Dashboard (Developers → Webhooks):
 *   URL:    https://your-domain/stripe-webhook.php
 *   Events: checkout.session.completed, checkout.session.async_payment_succeeded, checkout.session.expired
 * and put the signing secret in payments.stripe_webhook_secret.
 */
require __DIR__ . '/includes/bootstrap.php';

while (ob_get_level() > 0) {
    ob_end_clean();
}
header('Content-Type: application/json');

$payload = (string) file_get_contents('php://input');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !stripe_verify_webhook($payload, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''))) {
    http_response_code(400);
    exit('{"error":"invalid signature"}');
}

$event   = json_decode($payload, true);
$session = $event['data']['object'] ?? null;
$type    = (string) ($event['type'] ?? '');
$order   = is_array($session) ? order_by_number((string) ($session['client_reference_id'] ?? '')) : null;

if ($order && hash_equals((string) $order['payment_ref'], (string) ($session['id'] ?? ''))) {
    if (in_array($type, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)
        && stripe_session_matches($session, $order)) {
        if (order_mark_paid((int) $order['id'], (string) $session['id'])) {
            send_order_emails(order_by_number($order['order_number']));
        }
    } elseif ($type === 'checkout.session.expired' && $order['status'] === 'awaiting_payment') {
        order_cancel((int) $order['id']);
    }
}

echo '{"received":true}';
