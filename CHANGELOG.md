# Changelog

All notable changes to this project will be documented in this file.
 
## v1

### v1.0.0

Initial release.

- Automatic image renaming on upload using Groq Vision API
- Configurable AI model selection (Llama 4 Scout)
- Optional alt text population for accessibility
- Encrypted API key storage (database or `wp-config.php` constant)
- Configurable file types (JPEG, PNG, WebP, GIF)
- Batch image processing of up to 5 images at a time
- Configurable keyword limits for filename generation
- Modern tabbed settings page with Twig templates
- Translation-ready (`.pot` file, custom `{% trans %}` Twig tag)
- System diagnostics panel
- Extension hooks for Pro add-on plugin
