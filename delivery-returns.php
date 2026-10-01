<?php
/**
 * Delivery and returns. Rates and estimates come from Admin → Shop Settings.
 * Draft policy wording — have a professional confirm your obligations before launch.
 */
require __DIR__ . '/includes/bootstrap.php';

$options   = shipping_options(0);
$threshold = to_cents(shop('free_shipping_over', ''));
$cutoff    = shop('dispatch_cutoff', '2 pm');
$days      = shop('returns_days', '30');
$estimates = delivery_estimate_table();

$page = [
    'title'       => 'Delivery & Returns',
    'description' => 'Same-day dispatch on in-stock orders placed before ' . $cutoff . ' on business days. ' . $days . '-day returns on unused seals. Delivery within Australia.',
    'path'        => 'delivery-returns.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Delivery & Returns', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'Delivery & returns',
    'title'   => 'Delivery and returns',
    'lead'    => 'Orders placed before ' . $cutoff . ' on a business day ship the same day. Unused seals can be returned within ' . $days . ' days.',
]);
?>
<section class="section">
  <div class="container intro">
    <div class="prose">
      <h2 id="delivery">Delivery</h2>
      <ul>
        <li>In-stock orders placed before <?= e($cutoff) ?> on a business day ship the same day. Orders placed later, or on a weekend or public holiday, ship the next business day.</li>
        <li>We deliver within Australia.</li>
        <li>Items on backorder are sent as soon as stock arrives. If your order has both in-stock and backordered items, we will contact you about sending it in parts.</li>
        <li>You will get an email when your order ships, with a tracking number where one is available.</li>
      </ul>

      <h3>Delivery options</h3>
      <table>
        <thead><tr><th>Option</th><th>Cost</th></tr></thead>
        <tbody>
          <tr><td>Standard parcel</td><td><?= e(money($options['standard']['cents'])) ?><?= $threshold !== null ? ' — free on orders of ' . e(money($threshold)) . ' or more' : '' ?></td></tr>
          <tr><td>Express</td><td><?= e(money($options['express']['cents'])) ?></td></tr>
        </tbody>
      </table>

      <h3>Estimated delivery times</h3>
      <p>Estimates are counted from dispatch and are a guide only. The estimate for your postcode is shown at checkout.</p>
      <table>
        <thead><tr><th>Delivering to</th><th>Standard</th><th>Express</th></tr></thead>
        <tbody>
          <?php foreach (AU_STATES as $code => $stateName): ?>
          <tr><td><?= e($stateName) ?></td><td><?= e($estimates[$code]['standard'] ?: '—') ?></td><td><?= e($estimates[$code]['express'] ?: '—') ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <h2 id="returns">Returns</h2>
      <ul>
        <li>Unused seals in their original packaging can be returned within <?= e($days) ?> days.</li>
        <li>Contact us first with your order number so we can tell you where to send the return.</li>
        <li>Seals that have been fitted, cut or damaged cannot be returned as a change of mind.</li>
      </ul>

      <h2 id="faulty">Faulty or wrong items</h2>
      <p>Faulty items are handled under the Australian Consumer Law, which gives you rights that cannot be removed by a store policy. If something arrives faulty, damaged or not as described, contact us with your order number and a photo and we will put it right.</p>

      <h2 id="wrong-size">Ordered the wrong size?</h2>
      <p>It happens. As long as the seal is unused and in its packaging, send it back within <?= e($days) ?> days. To avoid it, <a href="<?= e(url('measure-your-seal.php')) ?>">measure the old seal</a> and the shaft before ordering, or <a href="<?= e(url('custom-quote.php')) ?>">send us a photo</a>.</p>
    </div>

    <aside class="intro__aside">
      <h3>In short</h3>
      <ul class="check-list" style="margin:0">
        <li><?= icon('truck', 18) ?><span>Same-day dispatch before <?= e($cutoff) ?> on business days</span></li>
        <li><?= icon('box', 18) ?><span>Shipped from Australian stock</span></li>
        <li><?= icon('returns', 18) ?><span><?= e($days) ?>-day returns on unused seals</span></li>
        <li><?= icon('card', 18) ?><span>Prices in AUD, GST included</span></li>
      </ul>
      <p style="margin-top:24px"><a class="btn btn--dark btn--block" href="<?= e(url('contact.php')) ?>">Contact us about an order</a></p>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
