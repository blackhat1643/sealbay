<?php
/**
 * Card payments with Stripe Checkout, using Stripe's REST API directly (no SDK).
 * The buyer pays on Stripe's hosted page, so card details never reach this server.
 *
 * Flow: checkout.php creates the order → stripe_create_checkout() → buyer is sent to
 * Stripe → returns to checkout-return.php (verified with stripe_session_paid()).
 * stripe-webhook.php confirms the same payment server-to-server in case the buyer
 * closes the browser before returning.
 */
defined('ST_APP') || exit;

function stripe_enabled(): bool
{
    return (string) config('payments.stripe_secret_key', '') !== '' && function_exists('curl_init');
}

/** Call the Stripe API. Returns the decoded JSON, or null on a transport / API error. */
function stripe_request(string $method, string $path, array $params = []): ?array
{
    $url = rtrim((string) config('payments.stripe_api_base', 'https://api.stripe.com'), '/') . $path;
    $ch  = curl_init();
    $opt = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . config('payments.stripe_secret_key')],
    ];
    if ($method === 'POST') {
        $opt[CURLOPT_POST]       = true;
        $opt[CURLOPT_POSTFIELDS] = http_build_query($params);
    } elseif ($params) {
        $url .= '?' . http_build_query($params);
    }
    $opt[CURLOPT_URL] = $url;
    curl_setopt_array($ch, $opt);
    $body   = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $error  = curl_error($ch);

    $data = is_string($body) ? json_decode($body, true) : null;
    if ($status < 200 || $status >= 300 || !is_array($data)) {
        app_log('Stripe ' . $method . ' ' . $path . ' failed (' . $status . '): ' . ($data['error']['message'] ?? $error ?: 'no response'));
        return null;
    }
    return $data;
}

/** Create a Checkout Session for an order. Returns ['id' => …, 'url' => …] or null. */
function stripe_create_checkout(array $order, array $items): ?array
{
    $params = [
        'mode'                => 'payment',
        'client_reference_id' => $order['order_number'],
        'customer_email'      => $order['email'],
        'success_url'         => abs_url('checkout-return.php') . '?n=' . rawurlencode($order['order_number']) . '&t=' . rawurlencode($order['access_token']) . '&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url'          => abs_url('checkout-return.php') . '?n=' . rawurlencode($order['order_number']) . '&t=' . rawurlencode($order['access_token']) . '&cancelled=1',
        'metadata'            => ['order_number' => $order['order_number']],
        'line_items'          => [],
    ];
    foreach ($items as $item) {
        $params['line_items'][] = [
            'quantity'   => (int) $item['quantity'],
            'price_data' => [
                'currency'     => 'aud',
                'unit_amount'  => (int) $item['unit_price_cents'],
                'product_data' => ['name' => $item['name']],
            ],
        ];
    }
    if ((int) $order['shipping_cents'] > 0) {
        $params['line_items'][] = [
            'quantity'   => 1,
            'price_data' => [
                'currency'     => 'aud',
                'unit_amount'  => (int) $order['shipping_cents'],
                'product_data' => ['name' => 'Delivery (' . $order['shipping_method'] . ')'],
            ],
        ];
    }
    $session = stripe_request('POST', '/v1/checkout/sessions', $params);
    if (!$session || empty($session['id']) || empty($session['url'])) {
        return null;
    }
    // Stripe always returns an https URL; anything else is only accepted from a local test API.
    if (!str_starts_with((string) $session['url'], 'https://') && !is_dev()) {
        return null;
    }
    return ['id' => (string) $session['id'], 'url' => (string) $session['url']];
}

/** True when the session belongs to the order, is paid, and the amount matches. */
function stripe_session_matches(array $session, array $order): bool
{
    return ($session['client_reference_id'] ?? '') === $order['order_number']
        && ($session['payment_status'] ?? '') === 'paid'
        && (int) ($session['amount_total'] ?? -1) === (int) $order['total_cents']
        && strtolower((string) ($session['currency'] ?? '')) === 'aud';
}

/** Ask Stripe whether a Checkout Session has been paid for this order. */
function stripe_session_paid(string $sessionId, array $order): bool
{
    if (!preg_match('/^cs_[A-Za-z0-9_]+$/', $sessionId)) {
        return false;
    }
    $session = stripe_request('GET', '/v1/checkout/sessions/' . $sessionId);
    return $session !== null && stripe_session_matches($session, $order);
}

/** Verify the Stripe-Signature header of a webhook request. */
function stripe_verify_webhook(string $payload, string $header, int $tolerance = 300): bool
{
    $secret = (string) config('payments.stripe_webhook_secret', '');
    if ($secret === '' || $header === '') {
        return false;
    }
    $timestamp  = null;
    $signatures = [];
    foreach (explode(',', $header) as $part) {
        [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
        if ($key === 't') {
            $timestamp = $value;
        } elseif ($key === 'v1') {
            $signatures[] = $value;
        }
    }
    if ($timestamp === null || !ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $tolerance) {
        return false;
    }
    $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
    foreach ($signatures as $signature) {
        if (hash_equals($expected, $signature)) {
            return true;
        }
    }
    return false;
}
