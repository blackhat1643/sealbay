<?php
/**
 * Shop logic: cart (session), delivery options, orders, stock and order emails.
 * All prices are AUD cents, GST inclusive. Prices and stock are always re-read from
 * the catalogue on the server — nothing sent by the browser is trusted for totals.
 */
defined('ST_APP') || exit;

const AU_STATES = [
    'NSW' => 'New South Wales', 'VIC' => 'Victoria', 'QLD' => 'Queensland', 'WA' => 'Western Australia',
    'SA'  => 'South Australia', 'TAS' => 'Tasmania', 'ACT' => 'Australian Capital Territory', 'NT' => 'Northern Territory',
];

const ORDER_STATUSES = [
    'awaiting_payment' => 'Awaiting payment',
    'paid'             => 'Paid — to be shipped',
    'shipped'          => 'Shipped',
    'cancelled'        => 'Cancelled',
];

/* ---------- Cart ---------- */

/** Only open a session for visitors who already have one (keeps anonymous browsing cookie-free). */
function resume_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE && isset($_COOKIE['sb_session'])) {
        start_session();
    }
}

/** [slug => quantity] */
function cart_raw(): array
{
    resume_session();
    $cart = $_SESSION['cart'] ?? [];
    return is_array($cart) ? $cart : [];
}

function cart_count(): int
{
    return (int) array_sum(cart_raw());
}

function cart_set(string $slug, int $qty): void
{
    start_session();
    $max = (int) config('shop.max_qty_per_line', 500);
    if ($qty <= 0 || !product($slug)) {
        unset($_SESSION['cart'][$slug]);
        return;
    }
    $_SESSION['cart'][$slug] = min($qty, $max);
}

function cart_add(string $slug, int $qty): void
{
    cart_set($slug, (cart_raw()[$slug] ?? 0) + max(1, $qty));
}

/**
 * A buyer sent to Stripe may never come back to the return page (the webhook then
 * confirms the payment). When they next open the cart, empty it if that order was paid.
 */
function cart_sync_pending(): void
{
    resume_session();
    $number = $_SESSION['pending_order'] ?? null;
    if (!is_string($number)) {
        return;
    }
    $order = order_by_number($number);
    if (!$order || in_array($order['status'], ['paid', 'shipped'], true)) {
        if ($order) {
            unset($_SESSION['cart']);
        }
        unset($_SESSION['pending_order']);
    } elseif ($order['status'] === 'cancelled') {
        unset($_SESSION['pending_order']);
    }
}

function cart_clear(): void
{
    start_session();
    unset($_SESSION['cart'], $_SESSION['pending_order']);
}

/**
 * Cart lines with current catalogue data. Products that no longer exist are dropped.
 * Each line: product, qty, line_cents, backorder_qty, problem (string|null).
 */
function cart_lines(): array
{
    $lines = [];
    foreach (cart_raw() as $slug => $qty) {
        $product = product((string) $slug);
        $qty     = (int) $qty;
        if (!$product || $qty <= 0) {
            continue;
        }
        $available = $product['stock_qty'];
        $problem   = null;
        if ($qty > $available && !$product['allow_backorder']) {
            $problem = $available > 0 ? 'Only ' . $available . ' in stock.' : 'This item is out of stock.';
        }
        $lines[] = [
            'product'       => $product,
            'qty'           => $qty,
            'line_cents'    => $qty * $product['price_cents'],
            'backorder_qty' => $product['allow_backorder'] ? max(0, $qty - $available) : 0,
            'problem'       => $problem,
        ];
    }
    return $lines;
}

function cart_subtotal(array $lines): int
{
    return (int) array_sum(array_column($lines, 'line_cents'));
}

/* ---------- Delivery ---------- */

/** Delivery options for an order value: [key => ['label', 'cents', 'note']]. */
function shipping_options(int $subtotalCents): array
{
    $standard  = to_cents(shop('shipping_standard', '0')) ?? 0;
    $express   = to_cents(shop('shipping_express', '0')) ?? 0;
    $threshold = to_cents(shop('free_shipping_over', ''));
    $free      = $threshold !== null && $subtotalCents >= $threshold;

    return [
        'standard' => ['label' => 'Standard parcel', 'cents' => $free ? 0 : $standard, 'note' => $free ? 'Free on this order' : ''],
        'express'  => ['label' => 'Express', 'cents' => $express, 'note' => ''],
    ];
}

/** Amount still needed for free standard delivery (0 when reached, null when not offered). */
function free_shipping_gap(int $subtotalCents): ?int
{
    $threshold = to_cents(shop('free_shipping_over', ''));
    return $threshold === null ? null : max(0, $threshold - $subtotalCents);
}

/** Australian state / territory for a postcode, or null if the postcode is not valid. */
function postcode_state(string $postcode): ?string
{
    if (!preg_match('/^\d{4}$/', $postcode)) {
        return null;
    }
    $n      = (int) $postcode;
    $ranges = [
        'ACT' => [[200, 299], [2600, 2618], [2900, 2920]],
        'NSW' => [[1000, 2599], [2619, 2899], [2921, 2999]],
        'VIC' => [[3000, 3999], [8000, 8999]],
        'QLD' => [[4000, 4999], [9000, 9999]],
        'SA'  => [[5000, 5999]],
        'WA'  => [[6000, 6999]],
        'TAS' => [[7000, 7999]],
        'NT'  => [[800, 999]],
    ];
    foreach ($ranges as $state => $list) {
        foreach ($list as [$from, $to]) {
            if ($n >= $from && $n <= $to) {
                return $state;
            }
        }
    }
    return null;
}

/** Delivery estimate text for a method and state ('' when none is set). */
function delivery_estimate(string $method, string $state): string
{
    if (!isset(AU_STATES[$state]) || !in_array($method, ['standard', 'express'], true)) {
        return '';
    }
    $saved = settings()['shop_estimate_' . $method . '_' . $state] ?? '';
    if (trim($saved) !== '') {
        return trim($saved);
    }
    return (string) config('shop.estimate_' . $method . '.' . $state, '');
}

/** [state => ['standard' => …, 'express' => …]] for the checkout page script. */
function delivery_estimate_table(): array
{
    $table = [];
    foreach (array_keys(AU_STATES) as $state) {
        $table[$state] = ['standard' => delivery_estimate('standard', $state), 'express' => delivery_estimate('express', $state)];
    }
    return $table;
}

/* ---------- Payments available ---------- */

function bank_details(): array
{
    $details = [
        'name'   => (string) config('payments.bank_account_name', ''),
        'bsb'    => (string) config('payments.bank_bsb', ''),
        'number' => (string) config('payments.bank_account_number', ''),
    ];
    return ($details['name'] !== '' && $details['bsb'] !== '' && $details['number'] !== '') ? $details : [];
}

/** Payment methods the buyer can choose: [key => label]. */
function payment_methods(): array
{
    $methods = [];
    if (stripe_enabled()) {
        $methods['card'] = 'Credit or debit card';
    }
    if (bank_details()) {
        $methods['bank'] = 'Bank transfer';
    }
    if (is_dev()) {
        $methods['test'] = 'Test payment (development mode only)';
    }
    return $methods;
}

/** GST included in a GST-inclusive amount (one eleventh). */
function gst_component(int $totalCents): int
{
    return (int) round($totalCents / 11);
}

/* ---------- Orders ---------- */

/**
 * Create an order from the current cart and reserve stock.
 * Returns ['ok' => true, 'order' => row] or ['ok' => false, 'error' => message].
 */
function create_order(array $customer, string $shippingMethod, string $paymentMethod): array
{
    if (!db_ready()) {
        return ['ok' => false, 'error' => 'Ordering is not available yet.'];
    }
    $lines = cart_lines();
    if (!$lines) {
        return ['ok' => false, 'error' => 'Your cart is empty.'];
    }
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $lock     = db_driver() === 'mysql' ? ' FOR UPDATE' : '';
        $items    = [];
        $subtotal = 0;

        foreach ($lines as $line) {
            $row = db_one('SELECT id, sku, name, price_cents, stock_qty, allow_backorder, is_active FROM products WHERE slug = ?' . $lock, [$line['product']['slug']]);
            if (!$row || !$row['is_active']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => $line['product']['name'] . ' is no longer available. Please remove it from your cart.'];
            }
            $qty       = $line['qty'];
            $available = max(0, (int) $row['stock_qty']);
            if ($qty > $available && !$row['allow_backorder']) {
                $pdo->rollBack();
                return ['ok' => false, 'error' => 'Only ' . $available . ' of ' . $row['name'] . ' in stock. Please update your cart.'];
            }
            $fromStock = min($qty, $available);
            db_run('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?', [$fromStock, $row['id']]);

            $lineTotal = $qty * (int) $row['price_cents'];
            $subtotal += $lineTotal;
            $items[]   = [
                'product_id' => (int) $row['id'], 'sku' => $row['sku'], 'name' => $row['name'],
                'unit_price_cents' => (int) $row['price_cents'], 'quantity' => $qty,
                'line_total_cents' => $lineTotal, 'backorder_qty' => $qty - $fromStock,
            ];
        }

        $options = shipping_options($subtotal);
        if (!isset($options[$shippingMethod])) {
            $shippingMethod = 'standard';
        }
        $shipping = $options[$shippingMethod]['cents'];
        $total    = $subtotal + $shipping;

        do {
            $number = 'SB' . date('ymd') . '-' . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (db_value('SELECT COUNT(*) FROM orders WHERE order_number = ?', [$number]) > 0);

        $order = [
            'order_number'    => $number,
            'access_token'    => bin2hex(random_bytes(24)),
            'status'          => 'awaiting_payment',
            'payment_method'  => $paymentMethod,
            'first_name'      => $customer['first_name'],
            'last_name'       => $customer['last_name'],
            'company'         => $customer['company'] !== '' ? $customer['company'] : null,
            'email'           => $customer['email'],
            'phone'           => $customer['phone'],
            'address_line1'   => $customer['address_line1'],
            'address_line2'   => $customer['address_line2'] !== '' ? $customer['address_line2'] : null,
            'suburb'          => $customer['suburb'],
            'state'           => $customer['state'],
            'postcode'        => $customer['postcode'],
            'shipping_method' => $shippingMethod,
            'shipping_cents'  => $shipping,
            'subtotal_cents'  => $subtotal,
            'total_cents'     => $total,
            'gst_cents'       => gst_component($total),
            'customer_notes'  => $customer['notes'] !== '' ? $customer['notes'] : null,
            'ip_address'      => client_ip(),
            'created_at'      => db_now(),
        ];
        db_run('INSERT INTO orders (' . implode(', ', array_keys($order)) . ') VALUES (' . implode(', ', array_fill(0, count($order), '?')) . ')', array_values($order));
        $order['id'] = (int) $pdo->lastInsertId();

        foreach ($items as $item) {
            $item = ['order_id' => $order['id']] + $item;
            db_run('INSERT INTO order_items (' . implode(', ', array_keys($item)) . ') VALUES (' . implode(', ', array_fill(0, count($item), '?')) . ')', array_values($item));
        }
        $pdo->commit();
        return ['ok' => true, 'order' => $order];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log('create_order(): ' . $ex->getMessage());
        return ['ok' => false, 'error' => 'Sorry, your order could not be placed because of a technical problem. Please try again.'];
    }
}

function order_by_number(string $number): ?array
{
    if (!db_ready() || !preg_match('/^SB\d{6}-\d{4}$/', $number)) {
        return null;
    }
    return db_one('SELECT * FROM orders WHERE order_number = ?', [$number]);
}

/** Order for a buyer-facing page: number and secret token must both match. */
function order_for_customer(mixed $number, mixed $token): ?array
{
    if (!is_string($number) || !is_string($token)) {
        return null;
    }
    $order = order_by_number($number);
    return ($order && hash_equals($order['access_token'], $token)) ? $order : null;
}

function order_items(int $orderId): array
{
    return db_all('SELECT * FROM order_items WHERE order_id = ? ORDER BY id', [$orderId]);
}

function order_url(array $order, bool $absolute = false): string
{
    $path = 'order.php?n=' . rawurlencode($order['order_number']) . '&t=' . rawurlencode($order['access_token']);
    return $absolute ? abs_url($path) : url($path);
}

/** Mark an order paid (idempotent). Returns true only when the status actually changed. */
function order_mark_paid(int $orderId, ?string $reference = null): bool
{
    $changed = db_run(
        "UPDATE orders SET status = 'paid', paid_at = ?, payment_ref = COALESCE(?, payment_ref) WHERE id = ? AND status = 'awaiting_payment'",
        [db_now(), $reference, $orderId]
    )->rowCount();
    return $changed > 0;
}

/** Cancel an unshipped order and return its reserved stock. Returns true when cancelled. */
function order_cancel(int $orderId): bool
{
    $pdo = db();
    try {
        $pdo->beginTransaction();
        $changed = db_run("UPDATE orders SET status = 'cancelled' WHERE id = ? AND status IN ('awaiting_payment', 'paid')", [$orderId])->rowCount();
        if ($changed > 0) {
            foreach (order_items($orderId) as $item) {
                $restock = (int) $item['quantity'] - (int) $item['backorder_qty'];
                if ($restock > 0 && $item['product_id']) {
                    db_run('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?', [$restock, $item['product_id']]);
                }
            }
        }
        $pdo->commit();
        return $changed > 0;
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        app_log('order_cancel(): ' . $ex->getMessage());
        return false;
    }
}

function order_mark_shipped(int $orderId, string $tracking): bool
{
    return db_run(
        "UPDATE orders SET status = 'shipped', shipped_at = ?, tracking_number = ? WHERE id = ? AND status = 'paid'",
        [db_now(), $tracking !== '' ? $tracking : null, $orderId]
    )->rowCount() > 0;
}

/* ---------- Email ---------- */

/** Send a plain-text email with PHP mail(). Returns false when mail is not configured. */
function send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    if (!config('mail.enabled', true) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    $strip = static fn (string $v): string => trim(preg_replace('/[\r\n]+/', ' ', $v) ?? '');
    $host  = preg_replace('/^www\./', '', (string) parse_url(site_origin(), PHP_URL_HOST));
    $from  = (string) config('mail.from', '');
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $from = 'no-reply@' . $host;
    }
    $headers = [
        'From: ' . mb_encode_mimeheader($strip((string) config('mail.from_name', company('name')))) . ' <' . $from . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $headers[] = 'Reply-To: ' . $replyTo;
    }
    if ((string) config('mail.resend_api_key', '') !== '') {
        return send_mail_resend($to, $strip($subject), $body, $strip((string) config('mail.from_name', company('name'))) . ' <' . $from . '>', $replyTo);
    }
    $sent = @mail($to, mb_encode_mimeheader($strip($subject)), $body, implode("\r\n", $headers), '-f' . $from);
    if (!$sent) {
        app_log('Email could not be sent: ' . $strip($subject));
    }
    return $sent;
}

/**
 * Send through the Resend HTTP API (https://resend.com) — for hosts such as Vercel
 * where PHP mail() is not available. The "from" domain must be verified in Resend.
 */
function send_mail_resend(string $to, string $subject, string $body, string $from, ?string $replyTo): bool
{
    if (!function_exists('curl_init')) {
        return false;
    }
    $payload = ['from' => $from, 'to' => [$to], 'subject' => $subject, 'text' => $body];
    if ($replyTo !== null && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
        $payload['reply_to'] = $replyTo;
    }
    $ch = curl_init((string) (getenv('RESEND_API_BASE') ?: 'https://api.resend.com') . '/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . config('mail.resend_api_key'), 'Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
    ]);
    $response = curl_exec($ch);
    $status   = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if ($status < 200 || $status >= 300) {
        app_log('Resend email failed (' . $status . '): ' . mb_substr((string) $response, 0, 200));
        return false;
    }
    return true;
}

/** Plain-text summary of an order (used in both emails). */
function order_text(array $order, array $items): string
{
    $text = '';
    foreach ($items as $item) {
        $text .= sprintf("%d x %s (%s) — %s\n", $item['quantity'], $item['name'], $item['sku'], money((int) $item['line_total_cents']));
        if ((int) $item['backorder_qty'] > 0) {
            $text .= '    ' . (int) $item['backorder_qty'] . " on backorder — sent as soon as stock arrives\n";
        }
    }
    $text .= "\n" . str_pad('Subtotal:', 22) . money((int) $order['subtotal_cents']) . "\n";
    $text .= str_pad('Delivery (' . $order['shipping_method'] . '):', 22) . ((int) $order['shipping_cents'] > 0 ? money((int) $order['shipping_cents']) : 'Free') . "\n";
    $text .= str_pad('Total (AUD):', 22) . money((int) $order['total_cents']) . "\n";
    $text .= str_pad('Includes GST of:', 22) . money((int) $order['gst_cents']) . "\n\n";
    $text .= "Deliver to:\n" . $order['first_name'] . ' ' . $order['last_name'] . "\n";
    if (!empty($order['company'])) {
        $text .= $order['company'] . "\n";
    }
    $text .= $order['address_line1'] . "\n" . (!empty($order['address_line2']) ? $order['address_line2'] . "\n" : '');
    $text .= $order['suburb'] . ' ' . $order['state'] . ' ' . $order['postcode'] . "\n";
    return $text;
}

/** Email the buyer and the shop when an order is placed (bank transfer) or paid (card). */
function send_order_emails(array $order): void
{
    $items = order_items((int) $order['id']);
    $name  = company('name');
    $body  = 'Hi ' . $order['first_name'] . ",\n\nThanks for your order " . $order['order_number'] . ".\n\n";

    if ($order['payment_method'] === 'bank' && $order['status'] === 'awaiting_payment' && ($bank = bank_details())) {
        $body .= "To complete it, please transfer " . money((int) $order['total_cents']) . " to:\n"
              . '  Account name: ' . $bank['name'] . "\n  BSB: " . $bank['bsb'] . "\n  Account number: " . $bank['number'] . "\n"
              . '  Reference: ' . $order['order_number'] . "\n\nYour order is sent once the payment arrives.\n\n";
    } else {
        $body .= "We have received your payment and are getting your order ready.\n\n";
    }
    $body .= order_text($order, $items) . "\nView your order: " . order_url($order, true) . "\n\n" . $name . "\n";
    if (company('abn') !== '') {
        $body .= 'ABN ' . company('abn') . "\n";
    }
    send_mail($order['email'], 'Order ' . $order['order_number'] . ' — ' . $name, $body, (string) config('mail.to', '') ?: null);

    $shop = "A new order has been placed on the website.\n\nOrder: " . $order['order_number'] . "\nStatus: " . (ORDER_STATUSES[$order['status']] ?? $order['status'])
          . "\nPayment: " . $order['payment_method'] . "\nCustomer: " . $order['first_name'] . ' ' . $order['last_name'] . ' <' . $order['email'] . '>, ' . $order['phone'] . "\n\n"
          . order_text($order, $items) . (!empty($order['customer_notes']) ? "\nCustomer notes:\n" . $order['customer_notes'] . "\n" : '')
          . "\nManage it in the admin panel: " . abs_url('admin/order-view.php?id=' . (int) $order['id']) . "\n";
    send_mail((string) config('mail.to', ''), '[' . $order['order_number'] . '] New order — ' . money((int) $order['total_cents']), $shop, $order['email']);
}

function send_shipped_email(array $order): void
{
    $body = 'Hi ' . $order['first_name'] . ",\n\nYour order " . $order['order_number'] . " is on its way.\n";
    if (!empty($order['tracking_number'])) {
        $body .= 'Tracking number: ' . $order['tracking_number'] . "\n";
    }
    $body .= "\nView your order: " . order_url($order, true) . "\n\n" . company('name') . "\n";
    send_mail($order['email'], 'Your order ' . $order['order_number'] . ' has shipped', $body, (string) config('mail.to', '') ?: null);
}
