# 24os Mail Sy – WordPress Plugin with Angular front-ends

A WordPress plugin for managing and tracking customer orders for **official-document services** and **mail forwarding**.
The plugin exposes a custom REST API (`/wp-json/24os-mail-sy/v1/…`) and serves **three independent Angular apps** on top of it:

| App | Folder | Used by | Where it appears |
|-----|--------|---------|------------------|
| **Orders dashboard** | `orders/` | Staff (WP admin) | WP admin menu **Mail Sy → Orders List** (shortcode `[mail_sy_orders_list_app]` rendered inside the admin page) |
| **Order form** | `app/` | Customers | Shortcode `[mail_sy_form_app lang="en"]` (rendered in an `<iframe>`) |
| **Order tracker** | `tracker/` | Customers | Shortcode `[mail_sy_tracker_app lang="en"]` (rendered in an `<iframe>`) |

The UI is multilingual: **Arabic, English, German** (`assets/lang/*.json`).

## Features

- Customers submit orders for *official documents* (with optional embassy attestation / translation categories) or *mail forwarding*, optionally uploading ID images.
- Orders get a number (`D<yy><id>` for documents, `M<yy><id>` for mail), and a confirmation e-mail with payment details is sent to the customer; staff are notified by e-mail.
- Staff update an order's status (received, paid, sent, attested, delivered, …); each status change can notify the customer.
- Customers track their order with the order code (+ e-mail).
- Prices, durations, categories and statuses are stored per language (AR/EN/DE) in custom tables.

## Architecture

```
┌──────────────┐   iframe    ┌───────────────────────────┐
│ WP page      │────────────▶│ app/  (Angular order form)│──┐
│ [shortcodes] │────────────▶│ tracker/ (Angular tracker)│──┤  HTTPS / JSON
└──────────────┘             └───────────────────────────┘  │
┌──────────────┐                                            ▼
│ WP admin     │──▶ orders/ (Angular dashboard) ──▶  /wp-json/24os-mail-sy/v1/*
└──────────────┘                                            │
                                                    24os-mail-sy.php
                                                    (REST routes, e-mails, DB)
                                                            │
                                                         MySQL
```

### Database tables (created on activation, prefix = `$wpdb->prefix`)

`24os_mail_sy_item`, `24os_mail_sy_item_translate`, `24os_mail_sy_category`, `24os_mail_sy_category_translate`,
`24os_mail_sy_order`, `24os_mail_sy_status`, `24os_mail_sy_status_translate`, `24os_mail_sy_order_status`.
The activation hook also seeds default items, categories and statuses. **Uninstalling the plugin drops all tables.**

### REST API (`/wp-json/24os-mail-sy/v1`)

| Method | Route | Purpose |
|--------|-------|---------|
| GET | `/items/{lang}`, `/category/{lang}` | list services / categories |
| POST | `/items`, `/type-items`, `/category` | resolve selected items / categories (prices, durations) |
| POST | `/order` | create order |
| GET | `/order`, `/order/{id}` | list / get orders (**staff only**) |
| GET | `/status`, `/status/{order_id}` | list statuses / statuses of an order (**staff only**) |
| POST | `/status/{order_id}` | add status to an order (**staff only**) |
| GET | `/track/{order_code}?email=…` | public order tracking (rate-limited) |
| GET/POST | `/lang` | get / set the admin UI language (**staff only**) |

"Staff only" routes require a logged-in user with the capability `OS24_MAIL_SY_CAPABILITY` and the `X-WP-Nonce` header
(added automatically to the admin dashboard by `api-rewrite.js`). Public order creation is limited to 10 orders / IP / hour,
tracking to 30 attempts / IP / 10 min. Uploaded ID images are validated (real PNG/JPEG, max ~5 MB) and stored with random file names.

## Repository layout

```
plugin/24os-mail-sy/        ← the installable plugin (zip THIS folder)
├── 24os-mail-sy.php        # plugin bootstrap, DB, shortcodes, REST API, e-mails
├── config.sample.php       # copy to config.php and fill in (git-ignored)
├── api-rewrite.js          # points the prebuilt Angular apps to this site's REST API + sends the REST nonce
├── orders/                 # built Angular admin dashboard
├── app/                    # built Angular order form (index.php adds cache-busting ?dev=<mtime>)
└── tracker/                # built Angular tracker
frontend/                   ← Angular sources (orders / app / tracker)
```

## Installation

### Requirements
WordPress 5.8+, PHP 7.4+, MySQL/MariaDB, HTTPS. For e-mails install an SMTP plugin (e.g. *WP Mail SMTP*) – `wp_mail()` alone is unreliable on most hosts.

### Steps
1. Download this repo and make a zip of the **`plugin/24os-mail-sy`** folder (the zip must contain the `24os-mail-sy` folder at its root).
   The folder name must stay `24os-mail-sy` – the plugin builds asset URLs from it.
2. In WordPress: **Plugins → Add New → Upload Plugin**, upload the zip, **Activate**.
   (Alternatively copy the folder to `wp-content/plugins/`.)
3. Configure private settings:
   ```bash
   cd wp-content/plugins/24os-mail-sy
   cp config.sample.php config.php
   # edit config.php: company name, IBAN, BIC, address, notification e-mails
   ```
4. Install/configure an SMTP plugin and send a test mail.
5. Create two pages and add the shortcodes:
   - Order form page: `[mail_sy_form_app lang="de"]` (`lang` = `ar` | `en` | `de`)
   - Tracking page:   `[mail_sy_tracker_app lang="de"]`
   Give the iframes enough height (the shortcodes use class `fix-heigt` – style it in your theme, e.g. `iframe.fix-heigt{min-height:900px;border:0}`).
6. Staff open **Mail Sy → Orders List** in the WP admin.
7. If you use a cache plugin (LiteSpeed etc.), purge JS/CSS caches after updating the Angular builds.

### API base URL
The prebuilt Angular apps contain a hard-coded API URL. `api-rewrite.js` (loaded automatically) redirects those calls to the
REST API of the WordPress site the plugin is installed on, so the prebuilt files work on any domain / sub-folder, with pretty or plain permalinks.
When rebuilding the Angular apps you may set your own URL in `environment.ts` (`prodApiEndpointUrl`).

## Local testing with XAMPP (Windows)
1. Install XAMPP (PHP 8.x), start **Apache** and **MySQL**. Create a database `wordpress` in phpMyAdmin (`http://localhost/phpmyadmin`).
2. Download WordPress, extract to `C:\xampp\htdocs\wordpress`, open `http://localhost/wordpress` and finish the installer (DB user `root`, empty password, host `localhost`).
3. Copy the folder `plugin\24os-mail-sy` to `C:\xampp\htdocs\wordpress\wp-content\plugins\`.
4. Copy `config.sample.php` → `config.php` in that folder and edit (use fake values locally).
5. WordPress admin → **Settings → Permalinks → Post name → Save**.
6. **Plugins → activate "24os Mail Sy"**. Tables are created (check phpMyAdmin: `wp_24os_mail_sy_*`).
7. Create pages with `[mail_sy_form_app lang="de"]` and `[mail_sy_tracker_app lang="de"]`; add `iframe.fix-heigt{min-height:900px;border:0}` under *Appearance → Customize → Additional CSS*.
8. E-mails: XAMPP can't send mail by default. Install **WP Mail SMTP** (or **WP Mail Logging** to just log mails) – otherwise order creation works but no mail is sent.

### Quick test checklist
- Admin: **Mail Sy → Orders List** opens and shows the (empty) list.
- Anonymous check (private window, not logged in): `http://localhost/wordpress/wp-json/24os-mail-sy/v1/order` → must return **401/403** (`rest_forbidden`). `/items/en` → must return JSON (public).
- Submit an order on the form page → it appears in the dashboard; open it and add a status.
- Tracker page: enter the order code + e-mail (or first name) → statuses appear.
- Upload an ID image (PNG/JPG) → the file in `wp-content/uploads/…` has a random name (`os24ms_…`).

## Development / rebuilding the Angular apps

Sources are in `frontend/` (`app`, `orders`, `tracker`; see `frontend/README.md` for versions).
For each project:

```bash
cd frontend/<project>
npm install
npx ng build --configuration production
```

Then copy the build output (the folder under `dist/`; with Angular 18 the files may be inside a `browser/` sub-folder) into the plugin:

| Project | Copy build to | Notes |
|---------|---------------|-------|
| `orders` | `plugin/24os-mail-sy/orders/` | Files are hashed. After each build **update the file names** in `os24_mail_sy_load_scripts()` inside `24os-mail-sy.php` (`main.*.js`, `polyfills.*.js`, `runtime.*.js`, `styles.*.css`; `icon.css` is a separate static file). |
| `tracker` | `plugin/24os-mail-sy/tracker/` | Loaded by its own `index.html` inside the iframe. Re-add `<script src="../api-rewrite.js"></script>` right after `<body>`. |
| `app` | `plugin/24os-mail-sy/app/` | Built **without hashing**. Rename `index.html` → `index.php`, then re-apply the small edits of the current `app/index.php`: the `add_edit_time()` helper (cache busting `?dev=<filemtime>`), `$os24_app_url` for the asset URLs, and the `api-rewrite.js` script tag. |

## Configuration reference (`config.php`)

| Constant | Meaning |
|----------|---------|
| `OS24_MAIL_SY_COMPANY_NAME` / `_IBAN` / `_BIC` / `_ADDRESS` | Payment details shown in confirmation e-mails |
| `OS24_MAIL_SY_ADMIN_EMAIL_DOCS` | Receives "official documents" orders |
| `OS24_MAIL_SY_ADMIN_EMAIL_MAIL` | Receives "mail forwarding" orders |

If `config.php` is missing, notification mails go to the WordPress admin e-mail and payment fields are empty.

## Privacy

Orders contain personal data (names, addresses, phone numbers, ID images). Run the site over HTTPS, restrict access to the API/dashboard, keep uploaded ID images private, and follow GDPR requirements for your jurisdiction.

## License

Add a license of your choice (e.g. MIT) as `LICENSE`.
