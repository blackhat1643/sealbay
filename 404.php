<?php
/**
 * Page not found.
 */
if (!defined('ST_APP')) {
    require __DIR__ . '/includes/bootstrap.php';
}
http_response_code(404);

$page = [
    'title'       => 'Page Not Found',
    'description' => 'The page you are looking for could not be found.',
    'path'        => '404.php',
    'noindex'     => true,
];
require ST_ROOT . '/includes/header.php';
?>
<section class="section section--light">
  <div class="container notice">
    <p class="error-code" aria-hidden="true">404</p>
    <h1>We could not find that page</h1>
    <p class="lead">The seal may have been removed from the range, or the address may be wrong. Try the size finder instead.</p>
    <div class="notice__finder"><?php component('finder', []); ?></div>
    <div class="btn-row">
      <a class="btn btn--dark" href="<?= e(url('')) ?>">Back to home</a>
      <a class="btn btn--outline" href="<?= e(url('custom-quote.php')) ?>">Send a photo for a quote</a>
    </div>
  </div>
</section>
<?php require ST_ROOT . '/includes/footer.php'; ?>
