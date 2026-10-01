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
        // Where sessions, uploaded photos and rate limits are kept:
        //   'disk' — files on the server (normal PHP hosting)
        //   'db'   — in the database, for hosts without a persistent disk (Vercel and other serverless hosts)
        //   null   — choose automatically ('db' on Vercel, otherwise 'disk')
        'storage'     => null,
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
        'ssl'         => null,             // true = require TLS (cloud databases); null = automatic (on when not localhost on Vercel)
    ],

    'mail' => [
        'enabled'   => true,
        'to'        => '',                 // where order and enquiry notifications go
        'cc'        => '',
        'from'      => '',                 // must be a mailbox on your own domain, e.g. orders@yourdomain.com.au
        'from_name' => 'SealBay Australia',
        // Hosts without PHP mail() (e.g. Vercel): set a Resend API key and email is sent through resend.com instead.
        'resend_api_key' => '',
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

/*
 * Environment variables (used on Vercel and other platforms where settings are not
 * kept in a file). They override everything above.
 */
$env = static function (string ...$names): ?string {
    foreach ($names as $name) {
        $value = getenv($name);
        if ($value !== false && $value !== '') {
            return $value;
        }
    }
    return null;
};
$envMap = [
    'app.env'                        => ['APP_ENV'],
    'app.site_url'                   => ['SITE_URL'],
    'app.setup_key'                  => ['SETUP_KEY'],
    'app.storage'                    => ['APP_STORAGE'],
    'db.driver'                      => ['DB_DRIVER'],
    'db.host'                        => ['DB_HOST', 'TIDB_HOST', 'MYSQL_HOST'],
    'db.port'                        => ['DB_PORT', 'TIDB_PORT', 'MYSQL_PORT'],
    'db.name'                        => ['DB_NAME', 'TIDB_DATABASE', 'MYSQL_DATABASE'],
    'db.user'                        => ['DB_USER', 'TIDB_USER', 'MYSQL_USER'],
    'db.pass'                        => ['DB_PASSWORD', 'TIDB_PASSWORD', 'MYSQL_PASSWORD'],
    'mail.to'                        => ['MAIL_TO'],
    'mail.from'                      => ['MAIL_FROM'],
    'mail.from_name'                 => ['MAIL_FROM_NAME'],
    'mail.resend_api_key'            => ['RESEND_API_KEY'],
    'payments.stripe_secret_key'     => ['STRIPE_SECRET_KEY'],
    'payments.stripe_webhook_secret' => ['STRIPE_WEBHOOK_SECRET'],
    'payments.bank_account_name'     => ['BANK_ACCOUNT_NAME'],
    'payments.bank_bsb'              => ['BANK_BSB'],
    'payments.bank_account_number'   => ['BANK_ACCOUNT_NUMBER'],
];
foreach ($envMap as $key => $names) {
    $value = $env(...$names);
    if ($value !== null) {
        [$section, $name]        = explode('.', $key);
        $config[$section][$name] = $value;
    }
}
// DATABASE_URL=mysql://user:password@host:3306/dbname
if (($url = $env('DATABASE_URL', 'MYSQL_URL')) !== null && str_starts_with($url, 'mysql')) {
    $parts = parse_url($url);
    if (!empty($parts['host'])) {
        $config['db'] = array_merge($config['db'], [
            'driver' => 'mysql',
            'host'   => $parts['host'],
            'port'   => (int) ($parts['port'] ?? 3306),
            'name'   => ltrim((string) ($parts['path'] ?? ''), '/'),
            'user'   => rawurldecode((string) ($parts['user'] ?? '')),
            'pass'   => rawurldecode((string) ($parts['pass'] ?? '')),
        ]);
    }
}
if (($ssl = $env('DB_SSL')) !== null) {
    $config['db']['ssl'] = in_array(strtolower($ssl), ['1', 'true', 'on', 'yes'], true);
}

$onVercel = (bool) ($env('VERCEL', 'VERCEL_URL', 'VERCEL_ENV') ?? false);
if ($onVercel) {
    // Canonical URLs, emails and payment return links use the production domain.
    if ($config['app']['site_url'] === '' && ($host = $env('VERCEL_PROJECT_PRODUCTION_URL')) !== null) {
        $config['app']['site_url'] = 'https://' . $host;
    }
    // Vercel accepts request bodies up to about 4.5 MB.
    $config['uploads']['max_bytes']       = min($config['uploads']['max_bytes'], 4 * 1024 * 1024);
    $config['uploads']['image_max_bytes'] = min($config['uploads']['image_max_bytes'], 3 * 1024 * 1024);
}
if (!in_array($config['app']['storage'], ['disk', 'db'], true)) {
    // Serverless hosts have a read-only project folder: keep sessions and uploads in the database there.
    $config['app']['storage'] = ($onVercel || !is_writable(ST_ROOT . '/storage')) ? 'db' : 'disk';
}
if ($config['db']['ssl'] === null) {
    $config['db']['ssl'] = $onVercel && !in_array($config['db']['host'], ['localhost', '127.0.0.1'], true);
}

return $config;
