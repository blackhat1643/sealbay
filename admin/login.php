<?php
/**
 * Admin sign-in.
 */
require __DIR__ . '/includes/admin.php';

if (!db_ready()) {
    redirect(url('install/'));
}
if (admin_user()) {
    redirect(admin_url());
}

$error = null;
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!csrf_valid()) {
        $error = 'The form expired. Please try again.';
    } elseif (admin_login_locked()) {
        $error = 'Too many failed sign-in attempts. Please wait ' . (int) ceil(config('security.login_lock_seconds', 900) / 60) . ' minutes and try again.';
    } elseif (admin_attempt_login(a_post('username'), is_string($_POST['password'] ?? null) ? $_POST['password'] : '')) {
        redirect(admin_url());
    } else {
        $error = 'Incorrect username or password.';
    }
}

admin_header('Sign in', '');
?>
<form class="a-login" method="post" action="<?= e(admin_url('login.php')) ?>" autocomplete="off">
  <div class="a-login__brand"><?= logo_mark(44) ?><strong><?= e(company('name')) ?></strong><span>Website administration</span></div>
  <?php if ($error): ?><div class="a-alert a-alert--err" role="alert"><?= e($error) ?></div><?php endif; ?>
  <?= csrf_field() ?>
  <?= a_field('username', 'Username', a_post('username'), ['required' => true, 'maxlength' => 60, 'autocomplete' => 'username']) ?>
  <?= a_field('password', 'Password', '', ['type' => 'password', 'required' => true, 'autocomplete' => 'current-password']) ?>
  <button class="a-btn a-btn--primary a-btn--block" type="submit">Sign in</button>
  <p class="a-login__back"><a href="<?= e(url('')) ?>">← Back to website</a></p>
</form>
<?php admin_footer(); ?>
