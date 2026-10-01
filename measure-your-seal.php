<?php
/**
 * Measuring guide.
 */
require __DIR__ . '/includes/bootstrap.php';

$steps = [
    ['Take the old seal out carefully', 'Remove the old seal carefully so you do not damage the housing. A scored bore will leak past a new seal.'],
    ['Measure the inner diameter', 'Measure the inner diameter — the shaft or rod the seal runs on — with calipers.'],
    ['Measure the outer diameter', 'Measure the outer diameter — the housing bore the seal presses into.'],
    ['Measure the width', 'Measure the width at the thickest point.'],
    ['Note the material and markings', 'Note the material and any markings on the seal. The three size numbers are often moulded on the face.'],
    ['Not sure? Send a photo', 'Take a photo with a ruler beside the seal and send it to us.'],
];
$faqs = [
    ['q' => 'What order are seal sizes written in?', 'a' => 'Inner diameter × outer diameter × width, in millimetres. A seal marked 35x52x7 fits a 35 mm shaft and a 52 mm housing bore, and is 7 mm wide.'],
    ['q' => 'My measurement is slightly different from the sizes listed. What should I do?', 'a' => 'Old seals wear and swell, so measure the shaft and the housing bore as well if you can. The size finder shows seals within ' . mm(shop('size_tolerance_mm', '0.5')) . ' mm of what you enter.'],
    ['q' => 'I do not have calipers. Can you still help?', 'a' => 'Yes. Put a ruler beside the seal, take a clear photo from straight above and send it to us with anything you know about the machine.'],
];

$page = [
    'title'       => 'How to Measure a Seal',
    'description' => 'Six steps to measure an oil seal or hydraulic seal: inner diameter, outer diameter and width in millimetres. Then find the size that fits.',
    'path'        => 'measure-your-seal.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Measure Your Seal', null]],
    'faqs'        => $faqs,
];
$page['schema'] = [[
    '@type' => 'HowTo',
    'name'  => 'How to measure a seal',
    'step'  => array_map(static fn ($s, $i) => ['@type' => 'HowToStep', 'position' => $i + 1, 'name' => $s[0], 'text' => $s[1]], $steps, array_keys($steps)),
]];
require __DIR__ . '/includes/header.php';

component('page-hero', [
    'crumbs'  => $page['breadcrumbs'],
    'eyebrow' => 'Measuring guide',
    'title'   => 'Measure your seal',
    'lead'    => 'Three measurements identify almost any seal: inner diameter, outer diameter and width. Here is how to take them.',
    'figure'  => illustration_measure(),
    'caption' => 'Where to measure · drawing not to scale',
]);
?>

<section class="section">
  <div class="container intro">
    <div>
      <p class="eyebrow">Step by step</p>
      <h2 class="sec-head__title" style="margin-bottom:28px">Six steps</h2>
      <ol class="steps">
        <?php foreach ($steps as [$title, $text]): ?>
        <li data-reveal><h3><?= e($title) ?></h3><p><?= e($text) ?></p></li>
        <?php endforeach; ?>
      </ol>
    </div>
    <aside class="intro__aside intro__aside--sticky" data-reveal>
      <h3>Got your three numbers?</h3>
      <?php component('finder', []); ?>
    </aside>
  </div>
</section>

<section class="section section--light">
  <div class="container">
    <?php section_heading('Good to know', 'Tips for a measurement you can trust'); ?>
    <ul class="factor-grid" data-reveal>
      <li><strong>Measure the hardware too</strong>If you can, measure the shaft and the housing bore as well as the old seal. A worn seal can read small or large.</li>
      <li><strong>Use millimetres</strong>All sizes on this site are metric. If your reading is an odd figure such as 31.75, the seal may be an inch size — send us a photo.</li>
      <li><strong>Hydraulic seals</strong>For a rod seal the inner diameter is the rod. For a piston seal the outer diameter is the cylinder bore.</li>
      <li><strong>Check the lip direction</strong>Note which way the seal faced before you remove it, so the new one goes in the same way.</li>
    </ul>
  </div>
</section>

<section class="section">
  <div class="container">
    <?php section_heading('Questions', 'Measuring questions'); ?>
    <?php component('faq', ['faqs' => $faqs]); ?>
  </div>
</section>

<?php
component('cta-band', ['title' => 'Rather send a photo?', 'secondary' => ['Open the size finder', url('shop.php')]]);
require __DIR__ . '/includes/footer.php';
