<?php
/**
 * Document head + site header. Pages set $page (see includes/seo.php) and include this file.
 */
defined('ST_APP') || exit;

resume_session();
$page = page_defaults($page ?? []);
?>
<!DOCTYPE html>
<html lang="en-AU">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<script><?= ST_BOOT_JS ?></script>
<?= render_seo($page) ?>
<meta name="theme-color" content="#0B1F33">
<meta name="format-detection" content="telephone=no">
<link rel="icon" href="<?= e(asset('img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(asset('img/apple-touch-icon.png')) ?>">
<link rel="preload" href="<?= e(url('assets/fonts/manrope-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?= e(url('assets/fonts/inter-latin-wght-normal.woff2')) ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</head>
<body class="<?= e(trim($page['body_class'])) ?>">
<a class="skip-link" href="#main">Skip to content</a>
<?= illustration_defs() ?>
<?php require __DIR__ . '/navbar.php'; ?>
<main id="main">
