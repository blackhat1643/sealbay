<?php
/**
 * "Send a photo for a custom quote" form. Vars: $state (from handle_enquiry('quote')), optional $prefill
 */
defined('ST_APP') || exit;

$state['values'] += ($prefill ?? []);
$maxBytes = (int) config('uploads.max_bytes');
?>
<form class="form-panel" method="post" action="<?= e(url('custom-quote.php')) ?>#form" enctype="multipart/form-data" id="form" data-enquiry-form novalidate>
  <h2 class="form-panel__title">Send a photo for a custom quote</h2>
  <p class="form-panel__intro">A clear photo with a ruler beside the seal is usually enough. Add any measurements or markings you have.</p>

  <?= form_alert($state, 'Thanks — we have your request. We will look at it and email you a quote.') ?>
  <?= csrf_field() ?>
  <?= spam_trap_fields('quote') ?>
  <input type="hidden" name="MAX_FILE_SIZE" value="<?= $maxBytes ?>">

  <div class="form-grid">
    <div class="field field--full<?= isset($state['errors']['attachment']) ? ' has-error' : '' ?>">
      <label for="f-attachment">Photo of the seal <span class="opt">(with a ruler or coin for scale)</span></label>
      <div class="upload">
        <?= icon('camera', 24) ?>
        <input type="file" id="f-attachment" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*" data-max-bytes="<?= $maxBytes ?>">
      </div>
      <p class="field__hint">JPG, PNG, WebP or PDF — up to <?= e(format_bytes($maxBytes)) ?>.</p>
      <p class="field__error" data-file-note><?= e($state['errors']['attachment'] ?? '') ?></p>
    </div>

    <?= form_input('name', 'Your name', $state, ['required' => true, 'autocomplete' => 'name', 'maxlength' => 120]) ?>
    <?= form_input('email', 'Email', $state, ['required' => true, 'type' => 'email', 'autocomplete' => 'email', 'inputmode' => 'email', 'maxlength' => 190]) ?>
    <?= form_input('phone', 'Phone', $state, ['type' => 'tel', 'autocomplete' => 'tel', 'inputmode' => 'tel', 'maxlength' => 25]) ?>
    <?= form_input('postcode', 'Postcode', $state, ['autocomplete' => 'postal-code', 'inputmode' => 'numeric', 'maxlength' => 4, 'hint' => 'So we can quote delivery.']) ?>
  </div>

  <fieldset class="form-section">
    <legend>What you know about the seal</legend>
    <div class="form-grid form-grid--3">
      <?= form_input('inner_diameter', 'Inner diameter (mm)', $state, ['inputmode' => 'decimal', 'maxlength' => 60]) ?>
      <?= form_input('outer_diameter', 'Outer diameter (mm)', $state, ['inputmode' => 'decimal', 'maxlength' => 60]) ?>
      <?= form_input('width', 'Width (mm)', $state, ['inputmode' => 'decimal', 'maxlength' => 60]) ?>
    </div>
    <div class="form-grid" style="margin-top:18px">
      <?= form_select('seal_type', 'Seal type', array_merge(array_values(SEAL_TYPES), ['Not sure']), $state, ['placeholder' => 'Select if you know']) ?>
      <?= form_input('quantity', 'How many do you need?', $state, ['maxlength' => 60]) ?>
      <?= form_input('machine', 'Machine it came from', $state, ['maxlength' => 250, 'full' => true, 'placeholder' => 'Make, model and where the seal sits']) ?>
      <?= form_textarea('message', 'Markings on the seal, or anything else', $state, ['rows' => 4]) ?>
    </div>
  </fieldset>

  <div class="form-foot">
    <button class="btn btn--primary btn--lg" type="submit">Send for a quote <?= icon('arrow-right', 20) ?></button>
    <p>We only use your details to reply to this request. See our <a href="<?= e(url('privacy-policy.php')) ?>">Privacy Policy</a>.</p>
  </div>
</form>
