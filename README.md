# SealBay Australia — online seal shop

B2C online shop for rotary shaft seals, hydraulic seals and seal kits, sold to buyers across Australia. Plain PHP, HTML, CSS and vanilla JavaScript with MySQL. No framework, no build step, no Composer — upload the folder to any PHP + MySQL host.

> **"SealBay Australia", every product, every price, the temperature ranges, the shipping rates and the delivery estimates are placeholders** from the website plan. Replace them with your own before launch (see §9).

- PHP 8.0 or newer with `pdo_mysql`, `curl`, `fileinfo`, `mbstring` (standard on cPanel)
- MySQL 5.7+ / MariaDB 10.3+ (SQLite is supported for local work only)
- Apache or LiteSpeed with `.htaccess` and `mod_rewrite`

---

## 1. Run it locally

```bash
cd /path/to/this/folder
php -S localhost:8000 router.php
```

Open <http://localhost:8000>. The catalogue can be browsed straight away from the sample files in `/data`.

To place orders and use the admin panel, open <http://localhost:8000/install/>, create an administrator, then sign in at `/admin/`. For a zero-setup local database, create `includes/config.local.php` (it is not committed to git) with:

```php
<?php
defined('ST_APP') || exit;
return ['app' => ['env' => 'development'], 'db' => ['driver' => 'sqlite']];
```

That uses a SQLite file in `storage/`, so no MySQL server is needed on your machine. In this development mode the checkout offers a **Test payment** option that marks an order as paid without taking money.

## 2. Deploy on cPanel / shared PHP hosting

1. **Database** — cPanel → *MySQL Databases*: create a database and a user with *All Privileges* on it.
2. **Upload** the contents of this folder into `public_html`, including the hidden `.htaccess` files.
3. **Configuration** — copy `includes/config.local.example.php` to **`/home/<cpanel-user>/sealbay-config.php`** (one level above `public_html`) and fill in `app.site_url`, `app.setup_key`, the `db.*` details, `mail.to` / `mail.from`, and your payment details (§5). Then delete `includes/config.local.php` from the server — it only holds the local development settings.
4. **Permissions** — `storage/` (and sub-folders) and `assets/uploads/` must be writable by PHP.
5. **Install** — open `https://your-domain/install/`, enter the setup key and create the administrator. This creates the tables and loads the sample catalogue and guides.
6. **Delete the `/install` folder**, sign in at `/admin/` and work through *Before you launch* on the dashboard.
7. **HTTPS** — once SSL is active, uncomment the two "Force HTTPS" lines in `.htaccess`. Card payments require HTTPS.
8. Submit `https://your-domain/sitemap.xml` in Google Search Console.

If `mod_rewrite` is not available, set `app.pretty_urls` to `false`; product, category and guide addresses then use `product.php?slug=…` style URLs.

## 3. Site structure

| URL | Page |
| --- | --- |
| `/` | Home — size finder, three reasons to buy, categories, popular sizes, measuring help |
| `/shop.php` | All seals, with the size finder and filters |
| `/shop/<category>` | Rotary Shaft Seals · Hydraulic Seals · Seal Kits |
| `/shop.php?id=35&od=52&w=7&type=rotary` | Size-finder results (match within 0.5 mm) |
| `/seal/<product>` | Product page — one per size, with the size in the title |
| `/cart.php`, `/checkout.php` | Cart and checkout |
| `/order.php?n=…&t=…` | Order confirmation / status (private link) |
| `/measure-your-seal.php` | Measuring guide |
| `/materials.php` | Materials guide |
| `/guides.php`, `/guides/<guide>` | How-to guides (6 to start) |
| `/custom-quote.php` | Send a photo for a custom quote |
| `/delivery-returns.php`, `/terms-of-sale.php`, `/privacy-policy.php` | Policies |
| `/contact.php`, `/about.php` | Contact and about |
| `/admin/` | Admin panel |

```
├── index.php  shop.php  product.php  cart.php  checkout.php  order.php
├── checkout-return.php  stripe-webhook.php
├── measure-your-seal.php  materials.php  guides.php  guide.php  custom-quote.php
├── delivery-returns.php  terms-of-sale.php  privacy-policy.php  contact.php  about.php  404.php
├── sitemap.php  robots.php  router.php (local only)  .htaccess
├── admin/        Orders, products, categories, guides, messages, shop settings, business details
├── install/      One-time installer — delete after use
├── includes/     bootstrap, config, db, security, content (catalogue + size finder), shop (cart,
│                 delivery, orders, stock, email), payments (Stripe), enquiry (forms), seo, render,
│                 icons, illustrations, header / navbar / footer, components/
├── data/         Sample categories, products, materials guide, how-to guides
├── database/schema.sql
├── storage/      Private: uploads, logs, cache, SQLite file, install lock
└── assets/       css, js, fonts, img (photos, share image), uploads (product photos)
```

`includes/`, `data/`, `database/` and `storage/` are blocked from the web.

## 4. How the shop works

**Size finder** — the buyer enters inner diameter, outer diameter, width (any or all) and an optional seal type. Products whose dimensions are all within the tolerance (0.5 mm, adjustable) are shown, closest first, each marked *Exact match* or *Close match: Inner +0.5 mm*. If nothing matches, the page explains how to clear a field or send a photo for a custom quote.

**Product page** — name including size, drawing or photo, inner / outer / width, specification table (material, temperature range, seal style), price including GST, stock indicator (*In stock*, *Low stock*, *On backorder*, or *Out of stock* when backorders are switched off for that product), fitment notes, Add to cart, and a link to *Measure your seal*.

**Cart and checkout** — the cart lives in the visitor's session. Checkout is a single page for guests: contact, Australian delivery address, delivery method with an estimate for the buyer's postcode, payment method, and acceptance of the terms. Totals are always recalculated on the server from the catalogue.

**Stock** — stock is reserved when an order is placed and returned if the order is cancelled or the card payment is abandoned. Quantities beyond the stock on hand are recorded as backordered.

**Orders** — statuses are *Awaiting payment → Paid → Shipped*, or *Cancelled*. The buyer gets a confirmation email, and a dispatch email with the tracking number when you mark the order shipped.

## 5. Payments

Set in the configuration file (`payments.*`):

- **Card — Stripe Checkout.** Add `stripe_secret_key`. The buyer pays on Stripe's hosted page, so card details never touch this server. In the Stripe Dashboard add a webhook to `https://your-domain/stripe-webhook.php` for `checkout.session.completed`, `checkout.session.async_payment_succeeded` and `checkout.session.expired`, and put its signing secret in `stripe_webhook_secret`.
- **Bank transfer.** Fill in `bank_account_name`, `bank_bsb`, `bank_account_number`. The buyer is shown and emailed the details; you mark the order paid in the admin panel when the money arrives.

With neither configured, checkout cannot take orders on the live site.

> The Stripe integration was tested against a local stand-in for the Stripe API (session creation, verified return, webhook signature, amount check, expiry). **Run a real test-mode payment with your own Stripe test keys before going live.**

## 6. Admin panel

| Section | What you do there |
| --- | --- |
| Dashboard | Orders to ship, awaiting payment, low stock, new messages, and the launch checklist |
| Orders | Search / filter, CSV export, mark paid, mark shipped (with tracking), cancel and restock, notes |
| Products | Name, SKU, category, seal type, size, material, temperature range, price, stock, backorder, fitment notes, photo |
| Categories | Name, headline, descriptions, order |
| Guides | How-to articles (HTML with a safe tag list) |
| Messages & quotes | Contact messages and photo quote requests, with the photo |
| Shop Settings | Delivery rates, free-delivery threshold, delivery estimates by state, dispatch cut-off, returns period, low-stock threshold, finder tolerance |
| Business Details | Business name, ABN, address, phone, email, hours, map, social links |

## 7. Configuration reference

| Key | Meaning |
| --- | --- |
| `app.env` | `production` (default); `development` shows errors and hints on your own machine only; `staging` shows them everywhere |
| `app.site_url` | Public URL — used in emails, canonical links, the sitemap and Stripe return URLs |
| `app.setup_key` | Secret asked for by `/install` on a live server |
| `app.pretty_urls` | `/seal/…` style URLs (default `true`) |
| `db.*` | Database connection |
| `mail.to`, `mail.from`, `mail.from_name`, `mail.cc` | Where order and message notifications go, and the sender shown to buyers |
| `payments.*` | Stripe keys and bank details |
| `company.*` | Business details (admin values override these) |
| `shop.*` | Shipping rates, estimates, cut-off, tolerance and so on (admin values override these) |
| `uploads.*`, `security.*` | Upload limits, spam and rate limits, login lock-out |

Email uses PHP `mail()`, which works on most cPanel hosts when `mail.from` is a mailbox on the same domain. If your host needs authenticated SMTP, replace the body of `send_mail()` in `includes/shop.php` with PHPMailer.

## 8. Database

Tables: `admins`, `login_attempts`, `settings`, `categories`, `products`, `orders`, `order_items`, `articles`, `enquiries` — see `database/schema.sql`. Money is stored as integer cents in AUD, GST inclusive. All queries are prepared statements.

## 9. Before launch

- [ ] Business name, ABN and real contact details entered (Admin → Business Details) — ABN and contact details must be shown on the site
- [ ] Sample products replaced with your range: sizes, prices, stock, **temperature ranges from your supplier's data sheets**, and fitment notes you have confirmed
- [ ] Product photos uploaded (with a ruler or coin for scale); until then each product shows a schematic drawing
- [ ] Shipping rates and delivery estimates set (Admin → Shop Settings)
- [ ] Payment configured and a real test order placed and refunded
- [ ] `mail.to` / `mail.from` set and the order emails received
- [ ] Materials guide (`data/materials.php`) checked — its temperature ranges are typical published figures, not your supplier's
- [ ] Delivery & returns, terms of sale and privacy policy reviewed by a professional — they are drafts, not legal advice
- [ ] `assets/img/og-default.jpg` (share image, has the placeholder name on it) and the logo mark replaced
- [ ] `app.site_url` set, `app.env` is `production`, `/install` deleted, HTTPS forced

Not built yet, from the plan's marketing ideas: customer reviews after delivery, and a Google Business Profile (set that up directly with Google if you have a shopfront or pickup point).

## 10. Security summary

- Prepared statements everywhere; output escaped; guide HTML passed through an allow-list sanitiser
- CSRF tokens on checkout, forms and every admin action; cart actions are restricted to same-site requests
- Prices, stock, delivery charges and totals are computed on the server; card payments are verified with Stripe (session, amount and currency) before an order is marked paid; webhooks are signature-checked
- Order pages need the order number plus a random token
- Admin: hashed passwords, session regeneration, idle timeout, lock-out after repeated failed sign-ins
- Content-Security-Policy and related headers; no inline JavaScript apart from one hashed line
- Uploads validated by extension, content type and size; customer photos are stored outside the web-accessible folders
- Credentials never live in a web-served file; the installer locks itself after use

## 11. Images

Technical drawings (seal cross-sections, the measuring diagram, the hydraulic cylinder) are inline SVG generated in `includes/illustrations.php`; they are schematic and not to scale.

`assets/img/photos/` holds five stock placeholder photographs from Unsplash (hero, about, three category banners) — see `CREDITS.md` there. Replace any of them by saving a file with the same name; update `alt.php` to match.
