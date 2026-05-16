=== Viscribe ===
Contributors:         thaikolja
Tags:                 images, media, seo, accessibility, ai
Requires at least:    6.0
Tested up to:         6.9
Requires PHP:         8.2
Stable tag:           1.0.0
License:              GPLv2 or later
License URI:          https://www.gnu.org/licenses/gpl-2.0.html
Donate link:          https://www.paypal.com/paypalme/thaikolja/10/

Viscribe uses AI to automatically rename meaningless image uploads and generate alt text, boosting your SEO & accessibility without lifting a finger.

== Description ==

Because life is too short to manually rename `IMG_20250315_143022.jpg` to something search engines actually care about.

Every time you upload an image, **Viscribe** intercepts it, sends the file to an AI Vision API, and translates the visual content into a clean, [SEO-friendly filename](https://developers.google.com/search/docs/appearance/google-images#filenames). It then optionally uses this generated description directly in the image's `alt="…"` image attribute, solving [accessibility compliance](https://www.w3.org/WAI/tutorials/images/informative/) instantly.

If the API rate-limits you or the network drops, Viscribe gracefully steps aside. Your upload continues with its original filename. No broken media library, no interrupted workflows.

[Fully documented](https://docs.kolja-nolte.com/viscribe). [Fully open-sourced](https://github.com/thaikolja/viscribe-wordpress-plugin).

= Why You Need This =

* **Free means Free**: The core plugin is 100% free and fully functional. No locked vital features, no nags, no "upgrade to Pro" popups. Just install, add your API key, and let the plugin do the work.
* **Batch Uploads**: Upload up to 5 images at the same time (< 10 MB).
* **Zero-Friction SEO**: Works (almost) out of the box and runs silently during native WordPress uploads. No extra buttons, no popups.
* **Bulletproof Fallbacks**: API failure? The upload still succeeds using the original filename.
* **Instant Accessibility**: Automatically generates and assigns a unique, descriptive [alt text](https://developer.wordpress.org/plugins/accessibility/alt-text-manual/).
* **Security First**: API keys are encrypted at rest. For the paranoid (and professionals), you can bypass the database entirely and define keys in `wp-config.php`.

Like it? [Leave a review](https://wordpress.org/support/plugin/viscribe/reviews/#new-post)!

= How It Works =

**Out of the box:**

1. Upload `screenshot_2022-05-27_e2fgy3u7l60h1.jpg`,
2. AI analyzes the image content,
3. Saved as `snowy-sunny-mountain-landscape.png`.

Set the number of keywords and more on the *Viscribe* settings page ("Media" <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span> "Viscribe")

= Free vs. Pro =

This free version is fully functional and uses **Groq's lightning-fast Llama 4 Vision models**. You simply provide your own [free Groq API key](https://console.groq.com/keys), and it works.

If you require enterprise-grade models, the **Viscribe Pro** add-on unlocks OpenAI (ChatGPT), Anthropic (Claude), Google Gemini, and custom OpenAI-compatible endpoints via BYOK (bring your own key). Batch renaming, one-click reversibility, and much more.

= For Developers =

This isn't spaghetti code. Built on a modern Service Container architecture, utilizing PSR-4 autoloading and Twig templating for the admin UI. It exposes over a dozen hooks (`filters` and `actions`), allowing you to modify AI prompts, intercept filenames, or inject your own custom vision models. See the [source repository](https://docs.kolja-nolte.com/viscribe) for the full API signature list.

== Installation ==

= From WordPress.org =

1. Install and activate **Viscribe** from your WordPress admin.
2. Navigate to **Media <span aria-hidden="true" class="wp-exclude-emoji"><span aria-hidden="true" class="wp-exclude-emoji">→</span></span> Viscribe**.
3. Enable "Enable Auto-Rename" and/or "Populate Alt Text."
4. Enter your [Groq API key](https://console.groq.com/keys).
5. Click **Test Connection**.
6. Save your settings. The next image you upload will be analyzed and renamed.

= Hardened Installation (Recommended) =

Keep secrets out of your database. Add this to your `wp-config.php` file:

`define( 'VISCRIBE_API_KEY', 'gsk_your_groq_api_key_here' );`
`define( 'VISCRIBE_ENCRYPTION_KEY', 'your_defuse_encryption_key' );`

== Frequently Asked Questions ==

= Does this rename my existing images? =

No. Processing existing images requires batch processing and risks breaking existing front-end URLs. Viscribe only hooks into `wp_handle_upload_prefilter` for newly uploaded files, keeping your files safe.

= What happens if the AI API is down or I hit a rate limit? =

The upload proceeds normally using the original filename. *Viscribe* is designed to never block a user's workflow.

= Is this plugin vibe-coded? =

Absolutely not. It's built with clean, maintainable code and follows [WordPress coding standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/). No unnecessary dependencies, no bloat.

= Which file types are supported? =

JPEG, PNG, WebP, and GIF. You can toggle processing for each format individually in the settings.

= Is my image data sent to third parties? =

Yes. The image and a short prompt are sent to the AI provider (Groq in the free version) for analysis. Once the API returns the description, the transaction is done.

= Can I use OpenAI or Anthropic instead of Groq? =

The free version strictly uses Groq (Llama 4 Scout). Support for OpenAI, Anthropic, and Google Gemini is available via the **Viscribe Pro**.

== Screenshots ==

1. **Dashboard:** General settings and quick start diagnostics.
2. **API Configuration:** Key management with secure storage warnings and connection testing.
3. **Model Selection:** Choose between available Groq vision models.
4. **File Types:** Granular control over which image mime-types trigger AI analysis.
5. **Advanced:** Keyword limits and deep system diagnostics.

== Changelog ==

= 1.0.0 =

* Initial public release.
* Added AI-based image renaming on upload via Groq (Llama 4).
* Added optional alt text generation.
* Implemented AES-256 encryption for API keys via `defuse/php-encryption`.
* Added `wp-config.php` constant overrides for maximum security.
* Added UI diagnostics, API connection testing, and model limits.
* Added developer hooks for extending providers and modifying prompts.

== Upgrade Notice ==

= 1.0.0 =
Initial release. Automate your media library.

== Third-Party Services ==

This plugin relies on an external API to analyze images and generate text.

= Groq API =

*Viscribe* transmits uploaded images to the Groq API to determine the visual content. Your data will not be saved other than on your website.
