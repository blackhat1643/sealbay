<?php
/**
 * Checkout: delivery details, delivery method, payment method → places the order.
 */
require __DIR__ . '/includes/bootstrap.php';
start_session();
cart_sync_pending();

$lines    = cart_lines();
$subtotal = cart_subtotal($lines);
$options  = shipping_options($subtotal);
$methods  = payment_methods();
$problems = array_filter(array_column($lines, 'problem'));

if (!$lines || $problems) {
    redirect(url('cart.php'));
}

$fields = [
    // name => [label, max length, required]
    'email'         => ['Email', 190, true],
    'phone'         => ['Phone', 25, true],
    'first_name'    => ['First name', 80, true],
    'last_name'     => ['Last name', 80, true],
    'company'       => ['Company', 160, false],
    'address_line1' => ['Street address', 160, true],
    'address_line2' => ['Unit, shed, property name', 160, false],
    'suburb'        => ['Suburb or town', 100, true],
    'state'         => ['State', 3, true],
    'postcode'      => ['Postcode', 4, true],
    'notes'         => ['Delivery notes', 500, false],
];
$state = ['errors' => [], 'general' => flash('checkout_notice'), 'values' => (array) ($_SESSION['checkout'] ?? [])];
$state['values'] += ['shipping_method' => 'standard', 'payment_method' => (string) array_key_first($methods)];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $values = [];
    foreach ($fields as $name => [$label, $max, $required]) {
        $values[$name] = clean_input($_POST[$name] ?? '', $name === 'notes');
    }
    $values['state']           = strtoupper($values['state']);
    $values['shipping_method'] = isset($options[$_POST['shipping_method'] ?? '']) ? (string) $_POST['shipping_method'] : '';
    $values['payment_method']  = isset($methods[$_POST['payment_method'] ?? '']) ? (string) $_POST['payment_method'] : '';
    $state['values']           = $values;
    $_SESSION['checkout']      = array_diff_key($values, ['notes' => 1]);

    $errors = [];
    foreach ($fields as $name => [$label, $max, $required]) {
        if ($required && $values[$name] === '') {
            $errors[$name] = $label . ' is required.';
        } elseif (mb_strlen($values[$name]) > $max) {
            $errors[$name] = $label . ' must be ' . $max . ' characters or fewer.';
        }
    }
    if (!isset($errors['email']) && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    }
    if (!isset($errors['phone']) && !preg_match('/^\+?[0-9][0-9 ()\-]{6,24}$/', $values['phone'])) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }
    if (!isset($errors['state']) && !isset(AU_STATES[$values['state']])) {
        $errors['state'] = 'Please choose a state or territory.';
    }
    if (!isset($errors['postcode']) && postcode_state($values['postcode']) === null) {
        $errors['postcode'] = 'Please enter a valid 4-digit Australian postcode.';
    }
    if ($values['shipping_method'] === '') {
        $errors['shipping_method'] = 'Please choose a delivery method.';
    }
    if ($values['payment_method'] === '') {
        $errors['payment_method'] = $methods ? 'Please choose a payment method.' : 'Online payment is not available yet.';
    }
    if (empty($_POST['terms'])) {
        $errors['terms'] = 'Please confirm that you accept the terms of sale.';
    }

    if (!csrf_valid()) {
        $state['general'] = 'Your session has expired. Please check your details and place the order again.';
    } elseif ($errors) {
        $state['errors'] = $errors;
    } elseif (!rate_limit_allow('order:' . client_ip(), (int) config('security.order_limit_max', 6), (int) config('security.rate_limit_window', 600))) {
        $state['general'] = 'Several orders have been placed from your connection in a short time. Please wait a few minutes and try again.';
    } else {
        $result = create_order($values, $values['shipping_method'], $values['payment_method']);
        if (!$result['ok']) {
            $state['general'] = $result['error'];
        } else {
            $order = $result['order'];
            if ($values['payment_method'] === 'card') {
                $session = stripe_create_checkout($order, order_items($order['id']));
                if (!$session) {
                    order_cancel($order['id']);
                    $state['general'] = 'We could not start the card payment. Nothing has been charged — please try again, or choose another payment method.';
                } else {
                    db_run('UPDATE orders SET payment_ref = ? WHERE id = ?', [$session['id'], $order['id']]);
                    $_SESSION['pending_order'] = $order['order_number'];
                    redirect($session['url']);
                }
            } else {
                if ($values['payment_method'] === 'test') {
                    order_mark_paid($order['id'], 'test');
                }
                send_order_emails(order_by_number($order['order_number']));
                cart_clear();
                redirect(order_url($order));
            }
        }
    }
}

$v        = $state['values'];
$pcState  = postcode_state((string) ($v['postcode'] ?? ''));
$selected = isset($options[$v['shipping_method'] ?? '']) ? $v['shipping_method'] : 'standard';

$page = [
    'title'       => 'Checkout',
    'description' => 'Enter your delivery details and place your order.',
    'path'        => 'checkout.php',
    'noindex'     => true,
];
require __DIR__ . '/includes/header.php';
?>
<section class="section section--tight section--light">
  <div class="container">
    <h1 class="page-title">Checkout</h1>

    <?php if (!db_ready()): ?>
    <div class="alert alert--error" role="alert"><strong>Ordering is not switched on yet.</strong> <?= is_dev() ? 'Run the installer at /install/ to create the database, then place a test order.' : 'Please contact us to place your order.' ?></div>
    <?php endif; ?>
    <?= form_alert($state) ?>

    <form class="checkout" method="post" action="<?= e(url('checkout.php')) ?>" novalidate data-checkout
          data-estimates="<?= e(json_encode(delivery_estimate_table())) ?>">
      <?= csrf_field() ?>
      <div class="checkout__main">
        <fieldset class="form-panel form-section">
          <legend><span>1</span>Contact</legend>
          <div class="form-grid">
            <?= form_input('email', 'Email', $state, ['required' => true, 'type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'maxlength' => 190, 'hint' => 'Your order confirmation is sent here.']) ?>
            <?= form_input('phone', 'Phone', $state, ['required' => true, 'type' => 'tel', 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 25, 'hint' => 'For the courier, if there is a delivery problem.']) ?>
          </div>
        </fieldset>

        <fieldset class="form-panel form-section">
          <legend><span>2</span>Delivery address</legend>
          <div class="form-grid">
            <?= form_input('first_name', 'First name', $state, ['required' => true, 'autocomplete' => 'given-name', 'maxlength' => 80]) ?>
            <?= form_input('last_name', 'Last name', $state, ['required' => true, 'autocomplete' => 'family-name', 'maxlength' => 80]) ?>
            <?= form_input('company', 'Company', $state, ['autocomplete' => 'organization', 'maxlength' => 160, 'full' => true]) ?>
            <?= form_input('address_line1', 'Street address', $state, ['required' => true, 'autocomplete' => 'address-line1', 'maxlength' => 160, 'full' => true]) ?>
            <?= form_input('address_line2', 'Unit, shed or property name', $state, ['autocomplete' => 'address-line2', 'maxlength' => 160, 'full' => true]) ?>
            <?= form_input('suburb', 'Suburb or town', $state, ['required' => true, 'autocomplete' => 'address-level2', 'maxlength' => 100]) ?>
            <?= form_select('state', 'State', array_combine(array_keys(AU_STATES), array_keys(AU_STATES)), $state, ['required' => true, 'autocomplete' => 'address-level1']) ?>
            <?= form_input('postcode', 'Postcode', $state, ['required' => true, 'autocomplete' => 'postal-code', 'inputmode' => 'numeric', 'maxlength' => 4, 'pattern' => '[0-9]{4}']) ?>
            <?= form_input('notes', 'Delivery notes', $state, ['maxlength' => 500, 'placeholder' => 'e.g. leave at the front office']) ?>
          </div>
          <p class="field__hint">We deliver within Australia only.</p>
        </fieldset>

        <fieldset class="form-panel form-section">
          <legend><span>3</span>Delivery method</legend>
          <div class="choice-list">
            <?php foreach ($options as $key => $option): $estimate = $pcState ? delivery_estimate($key, $pcState) : ''; ?>
            <label class="choice">
              <input type="radio" name="shipping_method" value="<?= e($key) ?>" data-cents="<?= (int) $option['cents'] ?>"<?= $selected === $key ? ' checked' : '' ?>>
              <span class="choice__body">
                <strong><?= e($option['label']) ?></strong>
                <span class="choice__note" data-estimate="<?= e($key) ?>"><?= $estimate !== '' ? 'Estimated ' . e($estimate) . ' to ' . e($pcState) : 'Enter your postcode for a delivery estimate' ?></span>
              </span>
              <span class="choice__price"><?= $option['cents'] === 0 ? 'Free' : e(money($option['cents'])) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <?php if (isset($state['errors']['shipping_method'])): ?><p class="field__error"><?= e($state['errors']['shipping_method']) ?></p><?php endif; ?>
          <p class="field__hint">Estimates are from dispatch and are a guide only. In-stock orders placed before <?= e(shop('dispatch_cutoff', '2 pm')) ?> on a business day ship the same day.</p>
        </fieldset>

        <fieldset class="form-panel form-section">
          <legend><span>4</span>Payment</legend>
          <?php if (!$methods): ?>
          <p class="field__error">Online payment is not available yet. Please contact us to place your order.</p>
          <?php else: ?>
          <div class="choice-list">
            <?php foreach ($methods as $key => $label): ?>
            <label class="choice">
              <input type="radio" name="payment_method" value="<?= e($key) ?>"<?= ($v['payment_method'] ?? '') === $key ? ' checked' : '' ?>>
              <span class="choice__body">
                <strong><?= e($label) ?></strong>
                <span class="choice__note"><?= [
                    'card' => 'You will pay on Stripe’s secure page. We never see your card number.',
                    'bank' => 'We email you our account details. Your order ships once the payment arrives.',
                    'test' => 'Marks the order as paid without taking money. Not shown on the live site.',
                ][$key] ?></span>
              </span>
              <span class="choice__price"><?= icon(['card' => 'card', 'bank' => 'bank', 'test' => 'check'][$key], 22) ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <?php if (isset($state['errors']['payment_method'])): ?><p class="field__error"><?= e($state['errors']['payment_method']) ?></p><?php endif; ?>
          <?php endif; ?>
        </fieldset>
      </div>

      <aside class="summary summary--sticky">
        <h2>Your order</h2>
        <ul class="summary__items">
          <?php foreach ($lines as $line): ?>
          <li><span><?= (int) $line['qty'] ?> × <?= e($line['product']['name']) ?><?php if ($line['backorder_qty'] > 0): ?><small><?= (int) $line['backorder_qty'] ?> on backorder</small><?php endif; ?></span><span><?= e(money($line['line_cents'])) ?></span></li>
          <?php endforeach; ?>
        </ul>
        <dl class="summary__rows">
          <div><dt>Subtotal</dt><dd><?= e(money($subtotal)) ?></dd></div>
          <div><dt>Delivery</dt><dd data-shipping><?= $options[$selected]['cents'] === 0 ? 'Free' : e(money($options[$selected]['cents'])) ?></dd></div>
          <div class="summary__total"><dt>Total (AUD)</dt><dd data-total data-subtotal="<?= $subtotal ?>"><?= e(money($subtotal + $options[$selected]['cents'])) ?></dd></div>
        </dl>
        <p class="summary__note">Includes GST of <span data-gst><?= e(money(gst_component($subtotal + $options[$selected]['cents']))) ?></span>.</p>

        <label class="check<?= isset($state['errors']['terms']) ? ' has-error' : '' ?>">
          <input type="checkbox" name="terms" value="1"<?= !empty($_POST['terms']) ? ' checked' : '' ?> required>
          <span>I accept the <a href="<?= e(url('terms-of-sale.php')) ?>" target="_blank" rel="noopener">terms of sale</a> and the <a href="<?= e(url('delivery-returns.php')) ?>" target="_blank" rel="noopener">delivery and returns policy</a>.</span>
        </label>
        <?php if (isset($state['errors']['terms'])): ?><p class="field__error"><?= e($state['errors']['terms']) ?></p><?php endif; ?>

        <button class="btn btn--primary btn--lg btn--block" type="submit"<?= (!$methods || !db_ready()) ? ' disabled' : '' ?>><?= icon('lock', 18) ?> Place order</button>
        <p class="summary__note"><a href="<?= e(url('cart.php')) ?>">Edit cart</a></p>
      </aside>
    </form>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
