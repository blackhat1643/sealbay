<?php
/**
 * Contact page. Business details come from configuration / Admin → Business Details;
 * blocks without a configured value are not shown.
 */
require __DIR__ . '/includes/bootstrap.php';
start_session();

$state   = handle_enquiry('contact');
$phone   = company('phone');
$email   = company('email');
$hours   = company('business_hours');
$abn     = company('abn');
$address = company_address_lines();
$mapUrl  = company('map_embed_url');
$mapOk   = (bool) preg_match('#^https://(www\.google\.com|maps\.google\.com)/maps#', $mapUrl);

$page = [
    'title'       => 'Contact Us',
    'description' => 'Contact ' . company('name') . ' about an order, a seal size or a custom quote.',
    'path'        => 'contact.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Contact', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'Contact',
    'title'   => 'Get in touch',
    'lead'    => 'Questions about an order, a size or a material? Send us a message and we will get back to you.',
]);
?>
<section class="section">
  <div class="container contact-grid">
    <div>
      <p class="eyebrow">Business details</p>
      <h2 class="sec-head__title" style="margin-bottom:28px"><?= e(company('name')) ?></h2>
      <div class="info-list">
        <?php if ($phone !== ''): ?>
        <div class="info-item"><span class="info-item__icon"><?= icon('phone', 22) ?></span><div><h3>Phone</h3><p><a href="<?= e(tel_href($phone)) ?>"><?= e($phone) ?></a></p></div></div>
        <?php endif; ?>
        <?php if ($email !== ''): ?>
        <div class="info-item"><span class="info-item__icon"><?= icon('mail', 22) ?></span><div><h3>Email</h3><p><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p></div></div>
        <?php endif; ?>
        <?php if ($address): ?>
        <div class="info-item"><span class="info-item__icon"><?= icon('map-pin', 22) ?></span><div><h3>Address</h3><address><?= implode('<br>', array_map('e', $address)) ?></address></div></div>
        <?php endif; ?>
        <?php if ($hours !== ''): ?>
        <div class="info-item"><span class="info-item__icon"><?= icon('clock', 22) ?></span><div><h3>Hours</h3><p><?= e($hours) ?></p></div></div>
        <?php endif; ?>
        <?php if ($abn !== ''): ?>
        <div class="info-item"><span class="info-item__icon"><?= icon('b2b', 22) ?></span><div><h3>ABN</h3><p><?= e($abn) ?></p></div></div>
        <?php endif; ?>
        <div class="info-item"><span class="info-item__icon"><?= icon('camera', 22) ?></span><div><h3>Identify a seal</h3><p><a href="<?= e(url('custom-quote.php')) ?>">Send a photo for a custom quote</a><small>The quickest way if you do not know the size.</small></p></div></div>
      </div>
      <?php if (is_dev() && ($phone === '' || $email === '' || $abn === '' || !$address)): ?>
      <p class="fine-print"><strong>Setup note (development mode only):</strong> phone, email, address and ABN appear here once they are entered in Admin → Business Details.</p>
      <?php endif; ?>
    </div>
    <div><?php component('contact-form', ['state' => $state]); ?></div>
  </div>
</section>

<?php if ($mapOk): ?>
<section class="section section--light section--tight">
  <div class="container">
    <div class="map-embed"><iframe src="<?= e($mapUrl) ?>" title="Map showing the location of <?= e(company('name')) ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
  </div>
</section>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
