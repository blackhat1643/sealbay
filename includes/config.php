<?php
/**
 * SealBay Australia — site configuration (defaults).
 *
 * Do NOT put real credentials in this file. Override any value in ONE of:
 *
 *   1. <one level above the web root>/sealbay-config.php   (recommended on cPanel:
 *      /home/<user>/sealbay-config.php — not reachable from the web)
 *   2. includes/config.local.php                            (blocked by .htaccess)
 *
 * Both files simply `return [ ... ];` with the keys you want to change
 * (see includes/config.local.example.php).
 *
 * Business details and shipping rates can also be edited in the admin panel once the
 * database is installed; values saved there take priority over this file.
 */
defined('ST_APP') || exit;

$config = [

    'app' => [
        // 'production' hides errors and unfinished placeholders. 'development' shows them,
        // but only when browsing from the same machine; 'staging' shows them everywhere.
        'env'         => 'production',
        // Full public URL without trailing slash, e.g. 'https://www.example.com.au'.
        // Leave empty to auto-detect (set it in production so canonical URLs are fixed).
        'site_url'    => '',
        // Sub-folder the site lives in, e.g. '/shop'. null = auto-detect.
        'base_path'   => null,
        'timezone'    => 'Australia/Sydney',
        // Secret required by /install when it is run on a live server (not needed on localhost).
        'setup_key'   => '',
        // /seal/<slug>, /shop/<category>, /guides/<slug> (needs mod_rewrite). false = query-string URLs.
        'pretty_urls' => true,
    ],

    /*
     * Business details shown across the website. The name is a PLACEHOLDER from the
     * website plan. ABN, phone, email and address are intentionally EMPTY — the site
     * hides those blocks until real values are entered.
     */
    'company' => [
        'name'           => 'SealBay Australia',
        'tagline'        => 'Rotary and hydraulic seals, shipped from Australian stock',
        'abn'            => '',
        'address_line1'  => '',
        'address_line2'  => '',
        'suburb'         => '',
        'state'          => '',
        'postcode'       => '',
        'country'        => 'Australia',
        'phone'          => '',
        'email'          => '',
        'business_hours' => '',     // e.g. 'Mon–Fri, 8:30 am – 5 pm AEST'
        'map_embed_url'  => '',     // Google Maps → Share → Embed a map → the src="" URL only
        'facebook'       => '',
        'instagram'      => '',
        'youtube'        => '',
    ],

    /*
     * Shop settings. The shipping rates and delivery estimates below are SAMPLE
     * values so the checkout can be demonstrated — set your own in
     * Admin → Shop Settings before launch.
     */
    'shop' => [
        'currency'            => 'AUD',     // prices are stored and shown in AUD, GST inclusive
        'size_tolerance_mm'   => 0.5,       // size finder matches within this many millimetres
        'low_stock_threshold' => 5,         // "low stock" at or below this quantity
        'max_qty_per_line'    => 500,
        'dispatch_cutoff'     => '2 pm',    // "orders placed before … on a business day ship the same day"
        'returns_days'        => 30,
        'shipping_standard'   => '9.95',
        'shipping_express'    => '14.95',
        'free_shipping_over'  => '99.00',   // free standard delivery at or above this order value; '' = never
        // Delivery estimate text per state, shown at checkout for the buyer's postcode.
        'estimate_standard'   => [
            'NSW' => '2–6 business days', 'ACT' => '2–6 business days', 'VIC' => '2–6 business days', 'QLD' => '2–7 business days',
            'SA'  => '3–7 business days', 'TAS' => '4–8 business days', 'WA'  => '5–9 business days', 'NT'  => '6–10 business days',
        ],
        'estimate_express'    => [
            'NSW' => '1–3 business days', 'ACT' => '1–3 business days', 'VIC' => '1–3 business days', 'QLD' => '1–3 business days',
            'SA'  => '1–4 business days', 'TAS' => '2–4 business days', 'WA'  => '2–4 business days', 'NT'  => '2–5 business days',
        ],
    ],

    /*
     * Payments. Card payments use Stripe Checkout (the buyer pays on Stripe's hosted
     * page, so no card data touches this server). Bank transfer is offered when the
     * account details are filled in. With neither configured, checkout is unavailable
     * (a "test payment" option appears in development mode only).
     */
    'payments' => [
        'stripe_secret_key'     => '',      // sk_live_… / sk_test_…
        'stripe_webhook_secret' => '',      // whsec_… for /stripe-webhook.php
        'stripe_api_base'       => 'https://api.stripe.com',
        'bank_account_name'     => '',
        'bank_bsb'              => '',
        'bank_account_number'   => '',
    ],

    /*
     * Database — required for orders, stock and the admin panel.
     * Until it is installed the catalogue is shown from the sample files in /data.
     */
    'db' => [
        'driver'      => 'mysql',          // 'mysql' (production) or 'sqlite' (quick local setup)
        'host'        => 'localhost',
        'port'        => 3306,
        'name'        => '',
        'user'        => '',
        'pass'        => '',
        'charset'     => 'utf8mb4',
        'sqlite_path' => null,             // null = storage/database.sqlite
    ],

    'mail' => [
        'enabled'   => true,
        'to'        => '',                 // where order and enquiry notifications go
        'cc'        => '',
        'from'      => '',                 // must be a mailbox on your own domain, e.g. orders@yourdomain.com.au
        'from_name' => 'SealBay Australia',
    ],

    'uploads' => [
        'max_bytes'  => 5 * 1024 * 1024,   // custom-quote photos
        // extension => allowed MIME types (checked against the real file contents)
        'enquiry_types' => [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'webp' => ['image/webp'],
            'pdf'  => ['application/pdf'],
        ],
        'image_max_bytes' => 3 * 1024 * 1024, // admin product / category images
    ],

    'security' => [
        'form_min_seconds'    => 3,     // submissions faster than this are treated as bots
        'rate_limit_max'      => 5,     // enquiries allowed per IP …
        'rate_limit_window'   => 600,   // … within this many seconds
        'order_limit_max'     => 6,     // orders allowed per IP within the same window
        'login_max_attempts'  => 5,
        'login_lock_seconds'  => 900,
        'admin_idle_seconds'  => 1800,
    ],
];

// Later files win: a private file above the web root overrides config.local.php.
$overrides = [
    __DIR__ . '/config.local.php',
    dirname(ST_ROOT) . '/sealbay-config.php',
];
foreach ($overrides as $file) {
    if (is_file($file)) {
        $local = require $file;
        if (is_array($local)) {
            $config = array_replace_recursive($config, $local);
        }
    }
}

return $config;
