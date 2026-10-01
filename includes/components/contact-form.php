<?php
/**
 * Contact form. Vars: $state (result of handle_enquiry('contact'))
 */
defined('ST_APP') || exit;
?>
<form class="form-panel" method="post" action="<?= e(url('contact.php')) ?>#form" id="form" data-enquiry-form novalidate>
  <h2 class="form-panel__title">Send us a message</h2>
  <p class="form-panel__intro">Trying to identify a seal? The <a class="link-arrow" href="<?= e(url('custom-quote.php')) ?>">photo quote form</a> lets you attach a picture.</p>

  <?= form_alert($state) ?>
  <?= csrf_field() ?>
  <?= spam_trap_fields('contact') ?>

  <div class="form-grid">
    <?= form_input('name', 'Your name', $state, ['required' => true, 'autocomplete' => 'name', 'maxlength' => 120]) ?>
    <?= form_input('email', 'Email', $state, ['required' => true, 'type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'maxlength' => 190]) ?>
    <?= form_input('phone', 'Phone', $state, ['type' => 'tel', 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 25, 'full' => true]) ?>
    <?= form_textarea('message', 'Message', $state, ['required' => true, 'placeholder' => 'Order number, question about a seal, or anything else']) ?>
  </div>

  <div class="form-foot">
    <button class="btn btn--primary btn--lg" type="submit">Send message <?= icon('arrow-right', 20) ?></button>
    <p>We only use your details to reply to your message. See our <a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a>.</p>
  </div>
</form>
