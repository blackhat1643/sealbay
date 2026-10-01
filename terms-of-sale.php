<?php
/**
 * Terms of sale. Draft template — have a professional confirm your obligations before launch.
 */
require __DIR__ . '/includes/bootstrap.php';

$name = company('name');
$abn  = company('abn');
$days = shop('returns_days', '30');

$page = [
    'title'       => 'Terms of Sale',
    'description' => 'The terms that apply when you buy from ' . $name . '.',
    'path'        => 'terms-of-sale.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Terms of Sale', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', ['crumbs' => $page['breadcrumbs'], 'eyebrow' => 'Policies', 'title' => 'Terms of sale', 'lead' => 'The terms that apply when you place an order on this website.']);
?>
<section class="section">
  <div class="container container--narrow">
    <div class="prose prose--legal">
      <p>These terms apply to orders placed on this website with <?= e($name) ?><?= $abn !== '' ? ' (ABN ' . e($abn) . ')' : '' ?>. By placing an order you agree to them.</p>

      <h2>Prices and payment</h2>
      <ul>
        <li>All prices are in Australian dollars (AUD) and include GST.</li>
        <li>Delivery charges are shown at checkout before you pay.</li>
        <li>Payment is taken when you place your order, or is due before dispatch if you choose bank transfer.</li>
      </ul>

      <h2>Orders</h2>
      <p>Your order is accepted when we send you an order confirmation. If we cannot supply an item — for example because of a pricing or stock error — we will tell you and refund anything you have paid for it.</p>

      <h2>Choosing the right seal</h2>
      <p>Sizes, materials and specifications are shown on each product page. Drawings are for identification and are not to scale. Fitment notes and guides are general information; it is your responsibility to check that a seal’s size and material suit your machine. If you are unsure, <a href="<?= e(url('custom-quote.php')) ?>">send us a photo</a> before ordering.</p>

      <h2>Delivery</h2>
      <p>We deliver within Australia. Dispatch and delivery times are set out on our <a href="<?= e(url('delivery-returns.php')) ?>">delivery and returns</a> page. Delivery estimates are a guide, not a guaranteed date.</p>

      <h2>Returns</h2>
      <p>Unused seals in their original packaging can be returned within <?= e($days) ?> days. See <a href="<?= e(url('delivery-returns.php') . '#returns') ?>">delivery and returns</a> for how.</p>

      <h2>Your rights under the Australian Consumer Law</h2>
      <p>Faulty items are handled under the Australian Consumer Law, which gives you rights that cannot be removed by a store policy. Nothing in these terms limits those rights.</p>

      <h2>Contact</h2>
      <p>Questions about an order or these terms? <a href="<?= e(url('contact.php')) ?>">Contact us</a>.</p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
