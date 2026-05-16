# Viscribe – WordPress Plugin

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-0073aa)](https://wordpress.org/) [![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](https://www.php.net/) [![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-3da639)](https://www.gnu.org/licenses/gpl-2.0.html)

**Viscribe** is a WordPress plugin that leverages Groq's free Vision API to analyze newly uploaded images, extracting descriptive keywords and automatically renaming the files. It can also optionally generate alt text for accessibility.

**Example:**

`DSC_3246.jpg` → `man-with-beard-in-car.jpg`

**Read the plugin's [comprehensive documentation](https://docs.kolja-nolte.com/viscribe/usage/quick-start) for more thorough guides, examples, and references.**

---

## For WordPress Users

See [`readme.txt`](./readme.txt) or the [WordPress.org plugin page](https://wordpress.org/plugins/viscribe) for installation instructions. To see how far you can go with *Viscribe*, check out the [official documentation](https://docs.kolja-nolte.com/viscribe/usage/quick-start).

---

## For Developers

### Requirements

- WordPress 6.0+
- PHP 8.2+
- Node.js 20+ and `npm`/`bun`
- Composer 2.x
- A free [Groq API key](https://console.groq.com/keys)

### Setup

```bash
# Change to plugin directory
cd /path/to/wordpress/wp-content/plugins

# Clone
git clone https://gitlab.com/thaikolja/viscribe-wordpress-plugin.git viscribe

# Change into directory
cd viscribe

# Installer Composer and Node dependencies
composer install && npm i
```

### Architecture

```
viscribe/
├── viscribe.php              # Plugin bootstrap
├── includes/
│   ├── Plugin.php            # Service container & init
│   ├── Admin/
│   │   └── Settings_Page.php # Admin settings UI
│   ├── Hooks/
│   │   └── Image_Uploader.php# wp_handle_upload_prefilter hook
│   ├── Services/
│   │   ├── Groq_Service.php  # Groq API integration
│   │   ├── Encryption_Service.php # defuse/php-encryption wrapper
│   │   ├── Template_Engine.php    # Twig renderer
│   │   └── Twig/             # {% trans %} custom token parser
│   └── Utils/
│       ├── File_Sanitizer.php
│       ├── API_Key_Validator.php
│       ├── Rate_Limiter.php
│       └── SVG_Sanitizer.php
├── views/                    # Twig templates
├── assets/
│   ├── css/                  # Source (styles.css) + built (styles.min.css)
│   ├── js/                   # Source (main.js, admin.js) + built (scripts.min.js)
│   └── icons/                # SVG icons
├── languages/                # .pot, .po, .mo
└── scripts/                  # Build tooling
```

- **PSR-4 autoloading**: `Viscribe\` → `includes/`
- **Twig 3.24+** for admin templates with custom `{% trans %}` tag compiled to `__()` at compile time
- **Hook-driven** upload pipeline: `wp_handle_upload_prefilter` → `Image_Uploader` → `Groq_Service` → `File_Sanitizer`

### Extension Hooks

The plugin fires actions and filters for Pro add-on integration:

| Hook | Type | Purpose |
|------|------|---------|
| `viscribe_services_loaded` | action | After core services init |
| `viscribe_loaded` | action | After full plugin init |
| `viscribe_should_process_upload` | filter | Skip specific uploads |
| `viscribe_generated_description` | filter | Modify AI description |
| `viscribe_new_filename` | filter | Modify final filename |
| `viscribe_alt_text` | filter | Modify alt text |
| `viscribe_prompt` | filter | Customize AI prompt |
| `viscribe_api_payload` | filter | Modify API request body |
| `viscribe_api_request_args` | filter | Modify HTTP request args |
| `viscribe_template_paths` | filter | Add custom Twig paths |
| `viscribe_available_models` | filter | Add AI models |
| `viscribe_available_file_types` | filter | Add MIME types |
| `viscribe_sanitize_settings` | filter | Extend setting sanitization |
| `viscribe_settings_defaults` | filter | Extend default values |
| `viscribe_mime_to_ext` | filter | Custom MIME→ext mapping |
| `viscribe_max_file_size` | filter | Override API file size limit |

### Development commands

```bash
# PHP
composer lint                    # Syntax check
composer phpcs                   # WordPress Coding Standards
composer phpcbf                  # Auto-fix CS issues
composer phpstan                 # Static analysis

# Frontend
npm run start                    # Vite dev server
npm run lint:js                  # ESLint
npm run lint:css                 # Stylelint

# Production
npm run build                    # Minify assets + create viscribe.zip
```

### Build process

`npm run build` runs:

1. `NODE_ENV=production vite build` — bundles JS into `assets/js/scripts.min.js` + sourcemap, omits CSS (handled below)
2. `node scripts/generate-asset-file.mjs` — generates `assets/js/index.asset.php` and minifies `assets/css/styles.css` → `assets/css/styles.min.js`
3. `composer install --no-dev -o --prefer-dist` — production Composer deps
4. `node scripts/plugin-zip.mjs` — creates `viscribe.zip` with explicit allowlist (PHP sources, built assets, vendor, views, translations, readme.txt, composer metadata)
5. `composer install` — restores dev Composer deps

The zip ships **no** source JS/CSS files (`main.js`, `admin.js`, `admin-tabs.js`, `styles.css`), no config files, no markdown, and no `node_modules` / `vendor` dev packages.

### Asset loading

PHP enqueues assets based on `WP_DEBUG`:

| Mode | CSS | JS |
|------|-----|-----|
| `WP_DEBUG=true` | `styles.css` (readable source) | `scripts.js` (non-minified bundle) |
| `WP_DEBUG` off | `styles.min.css` (minified) | `scripts.min.js` (minified bundle) |

### Validation before release

```bash
composer lint
composer phpcs
composer phpstan
npm run build
wp plugin check viscribe --skip-plugins=secondary-title
```

### Configuration in `wp-config.php`

```php
define( 'VISCRIBE_API_KEY', 'gsk_your_api_key_here' );
define( 'VISCRIBE_ENCRYPTION_KEY', 'def00000_your_defuse_key_here' );
```

- `VISCRIBE_API_KEY` bypasses database storage.
- `VISCRIBE_ENCRYPTION_KEY` keeps the encryption key out of the DB.

---

## Authors and Collaborators

* **Kolja Nolte** (kolja.nolte@gmail.com)

## Links

- [Official documentation](https://docs.kolja-nolte.com/viscribe)
- [Viscribe on WordPress.org](https://wordpress.org/plugins/viscribe)
- [Official support forum](https://wordpress.org/support/plugin/viscribe/)
- [GitLab repository](https://gitlab.com/thaikolja/viscribe-wordpress-plugin)
- [GitHub repository](https://gitlab.com/thaikolja/viscribe-wordpress-plugin) (mirror of *GitLab*)

## License

GPL-2.0-or-later. See [`LICENSE`](./LICENSE).
