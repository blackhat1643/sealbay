<?php
/**
 * Business details shown across the website.
 */
require __DIR__ . '/includes/admin.php';
admin_require_login();

$fields = [
    'Business' => [
        'name'    => ['Business name', 120, 'text', 'Shown in the header, footer, emails and page titles.'],
        'tagline' => ['Footer tagline', 160, 'text', ''],
        'abn'     => ['ABN', 20, 'text', 'Shown in the footer, on order confirmations and on the contact page.'],
    ],
    'Address' => [
        'address_line1' => ['Address line 1', 160, 'text', 'Empty fields are not shown on the website.'],
        'address_line2' => ['Address line 2', 160, 'text', ''],
        'suburb'        => ['Suburb', 80, 'text', ''],
        'state'         => ['State', 3, 'text', 'e.g. NSW'],
        'postcode'      => ['Postcode', 4, 'text', ''],
    ],
    'Contact' => [
        'phone'          => ['Phone', 40, 'text', ''],
        'email'          => ['Email', 190, 'email', 'Shown on the website. The address that receives orders is set in the configuration file (mail.to).'],
        'business_hours' => ['Business hours', 120, 'text', 'e.g. Mon–Fri, 8:30 am – 5 pm AEST'],
        'map_embed_url'  => ['Map embed URL', 2000, 'text', 'Google Maps → Share → Embed a map → copy only the address inside src="…". Use it if you have a shopfront or pickup point.'],
    ],
    'Social media' => [
        'facebook'  => ['Facebook URL', 250, 'url', ''],
        'instagram' => ['Instagram URL', 250, 'url', ''],
        'youtube'   => ['YouTube URL', 250, 'url', ''],
    ],
];
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $values = [];
    foreach ($fields as $group) {
        foreach ($group as $key => [$label, $max, $type]) {
            $value = mb_substr(a_post($key), 0, $max);
            if ($value !== '' && $type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[] = $label . ' is not a valid email address.';
            }
            if ($value !== '' && $type === 'url' && !preg_match('#^https://#i', $value)) {
                $errors[] = $label . ' must start with https://';
            }
            $values[$key] = $value;
        }
    }
    if ($values['name'] === '') {
        $errors[] = 'Business name cannot be empty.';
    }
    $values['state'] = strtoupper($values['state']);
    if ($values['state'] !== '' && !isset(AU_STATES[$values['state']])) {
        $errors[] = 'State must be one of ' . implode(', ', array_keys(AU_STATES)) . '.';
    }
    if ($values['postcode'] !== '' && postcode_state($values['postcode']) === null) {
        $errors[] = 'Postcode must be a valid 4-digit Australian postcode.';
    }
    if ($values['abn'] !== '' && !preg_match('/^\d{2} ?\d{3} ?\d{3} ?\d{3}$/', $values['abn'])) {
        $errors[] = 'ABN must be 11 digits, e.g. 12 345 678 901.';
    }
    if ($values['map_embed_url'] !== '' && !preg_match('#^https://(www\.google\.com|maps\.google\.com)/maps#', $values['map_embed_url'])) {
        $errors[] = 'The map embed URL must be a Google Maps embed address starting with https://www.google.com/maps';
    }
    if (!$errors) {
        foreach ($values as $key => $value) {
            db_run('DELETE FROM settings WHERE setting_key = ?', [$key]);
            db_run('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)', [$key, $value]);
        }
        flash('admin_ok', 'Business details saved.');
        redirect(admin_url('settings.php'));
    }
}

admin_header('Business Details', 'settings');
?>
<?php if ($errors): ?><div class="a-alert a-alert--err"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form method="post">
  <?= csrf_field() ?>
  <?php foreach ($fields as $groupName => $group): ?>
  <section class="a-panel a-form">
    <h2 class="a-field--wide"><?= e($groupName) ?></h2>
    <?php foreach ($group as $key => [$label, $max, $type, $hint]): ?>
      <?= a_field($key, $label, isset($_POST[$key]) ? a_post($key) : company($key), ['maxlength' => $max, 'hint' => $hint, 'wide' => $key === 'map_embed_url']) ?>
    <?php endforeach; ?>
  </section>
  <?php endforeach; ?>
  <p><button class="a-btn a-btn--primary" type="submit">Save business details</button></p>
</form>
<?php admin_footer(); ?>
