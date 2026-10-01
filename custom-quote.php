<?php
/**
 * Send a photo for a custom quote.
 * Optional query string to pre-fill sizes: ?inner_diameter=…&outer_diameter=…&width=…
 */
require __DIR__ . '/includes/bootstrap.php';
start_session();

$state   = handle_enquiry('quote');
$prefill = [];
foreach (['inner_diameter', 'outer_diameter', 'width'] as $key) {
    $value = clean_input($_GET[$key] ?? '');
    if ($value !== '' && parse_mm($value) !== null) {
        $prefill[$key] = $value;
    }
}

$page = [
    'title'       => 'Send a Photo for a Custom Quote',
    'description' => 'Cannot find your seal size? Send a photo of the old seal with a ruler beside it and we will identify it and send you a quote.',
    'path'        => 'custom-quote.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Custom Quote', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'Custom quote',
    'title'   => 'Can’t find it? Send us a photo.',
    'lead'    => 'Put a ruler or a coin beside the old seal, take a clear photo and send it with anything you know. We will work out what it is and email you a quote.',
]);
?>
<section class="section section--light">
  <div class="container enquiry-layout">
    <?php component('quote-form', ['state' => $state, 'prefill' => $prefill]); ?>
    <aside class="enquiry-aside">
      <div class="aside-box aside-box--dark">
        <h2>A good photo shows</h2>
        <ol class="step-list">
          <li><strong>The whole seal</strong>From straight above, in good light.</li>
          <li><strong>Something for scale</strong>A ruler or tape beside it, or a coin.</li>
          <li><strong>Any markings</strong>Numbers and letters moulded on the face.</li>
        </ol>
      </div>
      <div class="aside-box">
        <h2>Have the measurements?</h2>
        <p>The size finder is quicker.</p>
        <a class="btn btn--dark btn--block" href="<?= e(url('shop.php')) ?>"><?= icon('search', 18) ?> Open the size finder</a>
      </div>
    </aside>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
