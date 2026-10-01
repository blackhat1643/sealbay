<?php
/**
 * Privacy Policy. Draft template — have it reviewed before launch.
 */
require __DIR__ . '/includes/bootstrap.php';

$name  = company('name');
$email = company('email');

$page = [
    'title'       => 'Privacy Policy',
    'description' => 'How ' . $name . ' collects, uses and protects your personal information.',
    'path'        => 'privacy-policy.php',
    'breadcrumbs' => [['Home', 'index.php'], ['Privacy Policy', null]],
];
require __DIR__ . '/includes/header.php';

component('page-hero', ['crumbs' => $page['breadcrumbs'], 'eyebrow' => 'Policies', 'title' => 'Privacy policy', 'lead' => 'What we collect when you shop with us, and what we do with it.']);
?>
<section class="section">
  <div class="container container--narrow">
    <div class="prose prose--legal">
      <p>This policy explains what personal information <?= e($name) ?> (“we”, “us”) collects through this website and how it is used.</p>

      <h2>What we collect</h2>
      <ul>
        <li><strong>Order details</strong> — your name, email address, phone number, delivery address and the items you order.</li>
        <li><strong>Messages and quote requests</strong> — what you write in our forms, and any photo you choose to attach.</li>
        <li><strong>Technical information</strong> — your IP address and browser details, recorded with orders and form submissions to protect the website against misuse.</li>
      </ul>
      <p>We do not receive or store your card number. Card payments are made on the secure page of our payment provider, Stripe, which handles your card details under its own privacy policy.</p>

      <h2>How we use it</h2>
      <ul>
        <li>To process, deliver and support your order, including sending order and dispatch emails.</li>
        <li>To reply to your messages and quote requests.</li>
        <li>To keep the records we are required to keep as a business.</li>
        <li>To keep the website secure.</li>
      </ul>
      <p>We do not sell your information, and we do not send marketing emails unless you have asked for them.</p>

      <h2>Who we share it with</h2>
      <p>Your delivery details are given to the carrier delivering your parcel. Payment is handled by our payment provider. The companies that host this website and deliver our email process information on our behalf. We may disclose information where the law requires it.</p>

      <h2>Cookies</h2>
      <p>This website uses one session cookie, which remembers your cart and keeps the forms secure. We do not use advertising or tracking cookies.</p>

      <h2>Keeping and securing your information</h2>
      <p>We keep order records for as long as we need them for our business and legal obligations, and take reasonable steps to protect the information we hold.</p>

      <h2>Access and correction</h2>
      <p>You can ask to see or correct the personal information we hold about you, or raise a privacy concern, by <?php if ($email !== ''): ?>writing to <a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php else: ?>using our <a href="<?= e(url('contact.php')) ?>">contact form</a><?php endif; ?>.</p>

      <h2>Changes</h2>
      <p>We may update this policy from time to time. The current version is always on this page.</p>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
