# Viscribe

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-0073aa)](https://wordpress.org/) [![PHP](https://img.shields.io/badge/PHP-8.2%2B-777bb4)](https://www.php.net/) [![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-3da639)](https://www.gnu.org/licenses/gpl-2.0.html)

`Viscribe` is a WordPress plugin that renames newly uploaded images with AI-generated, descriptive filenames and can optionally store matching alt text.

## What the plugin does

When a supported image is uploaded, the plugin can:

1. send the image to Groq's Vision API,
2. generate a short descriptive text,
3. convert that output into a safe filename,
4. keep the original upload flow intact if the API request fails,
5. optionally save cleaned alt text for the attachment.

The settings page lives in **Media → Viscribe**.

## Current feature set

- Automatic renaming for new uploads only
- Optional attachment alt text population
- Groq API key support via database or `wp-config.php`
- Encrypted API key storage using `defuse/php-encryption`
- Configurable file types
- Configurable keyword limit
- Model selection for supported Groq vision models
- Diagnostics and model limit overview in the admin UI
- WordPress.org-ready production ZIP via `npm run production`

## Supported image types

The plugin can work with these image types:

- JPEG / JPG
- PNG
- WebP
- GIF

## Requirements

- WordPress **6.0+**
- PHP **8.2+**
- A Groq API key: <https://console.groq.com/keys>

## Installation for development

```bash
cd /path/to/wordpress/wp-content/plugins
git clone https://gitlab.com/thaikolja/wp-viscribe.git viscribe
cd viscribe
composer install
npm install
```

## Development commands

```bash
composer lint
composer phpcs
composer phpstan
npm run build
npm run bundle
```

## Production build

The production archive is created with:

```bash
npm run production
```

That process currently:

1. builds frontend assets,
2. installs Composer dependencies without dev packages,
3. creates `viscribe.zip`,
4. restores development dependencies locally afterwards.

The shipped ZIP includes only runtime-relevant files such as PHP source, assets, views, `readme.txt`, `composer.json`, translations, and runtime Composer dependencies.

## Security notes

### Recommended: store secrets in `wp-config.php`

For the strongest setup, define both constants in `wp-config.php`:

```php
define( 'VISCRIBE_API_KEY', 'gsk_your_api_key_here' );
define( 'VISCRIBE_ENCRYPTION_KEY', 'def00000_your_defuse_key_here' );
```

Notes:

- `VISCRIBE_API_KEY` bypasses database storage for the Groq API key.
- `VISCRIBE_ENCRYPTION_KEY` keeps the encryption key out of the database.
- If `VISCRIBE_API_KEY` is present, it takes priority over any saved key in the settings UI.

## Architecture notes

- Main bootstrap: `viscribe.php`
- Service container/bootstrap: `includes/Plugin.php`
- Admin UI: `includes/Admin/Settings_Page.php`
- Upload hook: `includes/Hooks/Image_Uploader.php`
- API integration: `includes/Services/Groq_Service.php`
- Encryption: `includes/Services/Encryption_Service.php`
- Twig rendering: `includes/Services/Template_Engine.php`

## Release validation performed locally

Before release, validate at least:

```bash
npm run production
wp plugin check viscribe --skip-plugins=secondary-title
composer lint
```

For the real shipped contents, check the extracted ZIP instead of the repository folder whenever review tooling would otherwise flag development-only files.

## Links

- Documentation: <https://docs.kolja-nolte.com/viscribe>
- WordPress.org: <https://wordpress.org/plugins/viscribe>
- Support forum: <https://wordpress.org/support/plugin/viscribe/>
- Source repository: <https://gitlab.com/thaikolja/wp-viscribe>

## License

GPL-2.0-or-later. See `LICENSE`.
