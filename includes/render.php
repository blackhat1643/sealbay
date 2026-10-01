<?php
/**
 * Rendering helpers: reusable components and small formatting functions.
 */
defined('ST_APP') || exit;

/** Include a reusable component from includes/components with local variables. */
function component(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    include ST_ROOT . '/includes/components/' . $name . '.php';
}

/** Section heading block: eyebrow label, title and optional lead. */
function section_heading(string $eyebrow, string $title, string $lead = '', array $opt = []): void
{
    component('section-heading', ['eyebrow' => $eyebrow, 'title' => $title, 'lead' => $lead, 'opt' => $opt]);
}

/** Text with blank-line separated paragraphs → <p> tags. */
function paragraphs(string $text, string $class = ''): string
{
    $out = '';
    foreach (preg_split('/\R{2,}/', trim($text)) ?: [] as $para) {
        if (trim($para) !== '') {
            $out .= '<p' . ($class !== '' ? ' class="' . e($class) . '"' : '') . '>' . e(trim($para)) . '</p>';
        }
    }
    return $out;
}

/** Shop listing URL with filters, e.g. shop_url(['type' => 'rod', 'id' => '35']). */
function shop_url(array $params = [], ?string $categorySlug = null): string
{
    $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
    $base   = $categorySlug !== null && $categorySlug !== '' ? category_url($categorySlug) : url('shop.php');
    if (!$params) {
        return $base;
    }
    return $base . (str_contains($base, '?') ? '&' : '?') . http_build_query($params);
}

/** Stock badge markup for a product. */
function stock_badge(array $product): string
{
    $status = stock_status($product);
    return '<span class="stock stock--' . e($status['key']) . '">' . e($status['label']) . '</span>';
}

/** Product picture: uploaded photo if there is one, otherwise the technical drawing. */
function product_visual(array $product, string $theme = 'light', array $imgAttrs = []): string
{
    $image = uploaded_image($product['image'], $product['name']);
    if ($image) {
        return img_tag($image, $imgAttrs);
    }
    return illustration($product['illustration'], ['theme' => $theme, 'label' => $product['name'] . ' — schematic drawing']);
}

function render_404(): never
{
    http_response_code(404);
    require ST_ROOT . '/404.php';
    exit;
}
