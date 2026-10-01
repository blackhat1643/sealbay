<?php
/**
 * Change the signed-in administrator's email and password.
 */
require __DIR__ . '/includes/admin.php';
$user   = admin_require_login();
$admin  = db_one('SELECT * FROM admins WHERE id = ?', [$user['id']]);
$errors = [];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    admin_require_post();
    $email   = a_post('email');
    $current = is_string($_POST['current_password'] ?? null) ? $_POST['current_password'] : '';
    $new     = is_string($_POST['new_password'] ?? null) ? $_POST['new_password'] : '';
    $confirm = is_string($_POST['confirm_password'] ?? null) ? $_POST['confirm_password'] : '';

    if (!password_verify($current, $admin['password_hash'])) {
        $errors[] = 'Your current password is not correct.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Enter a valid email address.';
    }
    if ($new !== '') {
        if (strlen($new) < 10) {
            $errors[] = 'The new password must be at least 10 characters long.';
        } elseif ($new !== $confirm) {
            $errors[] = 'The new passwords do not match.';
        }
    }
    if (!$errors) {
        db_run('UPDATE admins SET email = ? WHERE id = ?', [$email, $admin['id']]);
        if ($new !== '') {
            db_run('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $admin['id']]);
            session_regenerate_id(true);
        }
        flash('admin_ok', 'Account updated.');
        redirect(admin_url('account.php'));
    }
}

admin_header('My Account', 'account');
?>
<?php if ($errors): ?><div class="a-alert a-alert--err"><?= e(implode(' ', $errors)) ?></div><?php endif; ?>
<form class="a-panel a-form" method="post" autocomplete="off">
  <?= csrf_field() ?>
  <div class="a-field"><label>Username</label><p class="a-static"><?= e($admin['username']) ?></p></div>
  <?= a_field('email', 'Email', isset($_POST['email']) ? a_post('email') : $admin['email'], ['type' => 'email', 'required' => true, 'maxlength' => 190]) ?>
  <?= a_field('current_password', 'Current password', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'wide' => true, 'hint' => 'Required to save any change.']) ?>
  <?= a_field('new_password', 'New password', '', ['type' => 'password', 'autocomplete' => 'new-password', 'hint' => 'Leave empty to keep the current password. Minimum 10 characters.']) ?>
  <?= a_field('confirm_password', 'Repeat new password', '', ['type' => 'password', 'autocomplete' => 'new-password']) ?>
  <div class="a-form__foot"><button class="a-btn a-btn--primary" type="submit">Save</button></div>
</form>
<?php admin_footer(); ?>
