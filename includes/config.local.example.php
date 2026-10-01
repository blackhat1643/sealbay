<?php
/**
 * Copy this file to ONE of the following and fill in real values:
 *
 *   - /home/<cpanel-user>/sealbay-config.php   (one level above public_html — recommended)
 *   - includes/config.local.php
 *
 * Only include the keys you want to override.
 */
defined('ST_APP') || exit;

return [
    'app' => [
        'env'       => 'production',
        'site_url'  => 'https://www.your-domain.com.au',
        'setup_key' => 'choose-a-long-random-phrase',   // asked for once by /install
    ],

    'db' => [
        'driver' => 'mysql',
        'host'   => 'localhost',
        'name'   => 'cpaneluser_shop',
        'user'   => 'cpaneluser_shop',
        'pass'   => 'change-me',
    ],

    'mail' => [
        'to'        => 'orders@your-domain.com.au',
        'from'      => 'orders@your-domain.com.au',
        'from_name' => 'Your Business Name',
    ],

    'payments' => [
        // Stripe Dashboard → Developers → API keys / Webhooks
        'stripe_secret_key'     => '',
        'stripe_webhook_secret' => '',
        // Leave empty to hide the bank-transfer option
        'bank_account_name'     => '',
        'bank_bsb'              => '',
        'bank_account_number'   => '',
    ],

    // Business details can be set here or in Admin → Business Details.
    'company' => [
        'name'  => 'Your Business Name',
        'abn'   => '',
        'phone' => '',
        'email' => '',
    ],
];
