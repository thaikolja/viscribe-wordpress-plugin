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

Give your uploads meaningful names — automatically. Viscribe uses AI to rename images as you upload them, so your media library is filled with descriptive, SEO-friendly filenames instead of `IMG_1234.jpg`.

== Description ==

Every time you upload an image, Viscribe sends it to Groq's Vision API, generates a short description of what's in the photo, and turns that into a clean, readable filename. If the API is unavailable, your upload continues as normal — no disruption.

The plugin can also save a cleaned version of the generated description as the image's alt text, improving accessibility without extra work.

You stay in control: choose which file types to process, which AI model to use, and how many keywords go into each filename. Everything is managed from **Media → Viscribe**.

= What makes it different =

* **Automatic, not extra work** — no buttons to click, no extra dialogs. It just runs during upload.
* **Safe fallback** — if the API call fails, your image keeps its original name.
* **Privacy-aware** — you choose your own Groq API key. Nothing is sent to third parties beyond what you configure.
* **Alt text done for you** — optionally save descriptive alt text with no additional effort.

= Example =

* Original filename: `IMG_1234.jpg`
* Possible generated filename: `golden-retriever-playing-fetch-park.jpg`

= Supported image types =

* JPEG / JPG
* PNG
* WebP
* GIF

= Security notes =

* The plugin supports storing the Groq API key directly in `wp-config.php` via `VISCRIBE_API_KEY`.
* Saved API keys are encrypted at rest using `defuse/php-encryption`.
* The plugin warns administrators when the encryption key is still stored in the database instead of `wp-config.php`.
* All AJAX requests are protected by capability checks and nonces.

= Developer hooks =

The plugin includes actions and filters for advanced integrations, including upload processing, prompt customization, model lists, file types, and generated filenames. See the `README.md` for a full reference.

== Installation ==

= From WordPress.org =

1. Install and activate **Viscribe** from your WordPress admin.
2. Go to **Media → Viscribe**.
3. Enter your Groq API key.
4. Click **Test Connection**.
5. Save your settings.
6. Done. The next images you upload will be renamed automatically.

= Manual installation =

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin in WordPress.
3. Open **Media → Viscribe**.
4. Add your Groq API key and save the settings.

== Frequently Asked Questions ==

= Does this rename existing images? =

No. Viscribe only processes images uploaded after the plugin is active. Existing filenames stay as they are.

= What happens if Groq is unavailable? =

The upload continues with the original filename — no errors, no interruption.

= Can I store the API key outside the database? =

Yes. Define `VISCRIBE_API_KEY` in `wp-config.php` and the plugin will use that value instead of the saved key.

= Can I also keep the encryption key out of the database? =

Yes. Define `VISCRIBE_ENCRYPTION_KEY` in `wp-config.php`.

= Which file types can be processed? =

JPEG, PNG, WebP, and GIF are supported. You can enable or disable each type in the settings.

= Does the plugin create alt text? =

It can. When enabled, a cleaned version of the AI-generated description is saved as the attachment's alt text — improving accessibility with no extra effort.

== Screenshots ==

1. General settings and quick start information
2. API key configuration with connection test
3. Choose between available Groq vision models
4. Select which file types to process
5. Advanced settings and system diagnostics

== Changelog ==

= 1.0.0 =
* Initial public release
* AI-based image renaming on upload
* Optional alt text generation
* Groq API connection test
* Support for JPEG, PNG, WebP, and GIF
* Encrypted API key storage with `wp-config.php` support
* Diagnostics and model limits in the admin UI

== Upgrade Notice ==

= 1.0.0 =
Initial release.

== Third-Party Services ==

This plugin connects to the Groq API to analyze images and generate filenames.

= Groq =

Viscribe uses the Groq API to analyze uploaded images and generate descriptive filenames.

**What is sent:**

* the uploaded image,
* a short text instruction,
* the selected model identifier.

**When it is sent:**

* only when the plugin is enabled,
* only during supported image uploads,
* only when a valid Groq API key is configured.

**Service links:**

* Groq: https://groq.com/
* Groq Console: https://console.groq.com/keys
* Terms of Use: https://groq.com/terms-of-use/
* Privacy Policy: https://groq.com/privacy-policy/

== Support ==

* Documentation:      https://docs.kolja-nolte.com/viscribe
* Support forum:      https://wordpress.org/support/plugin/viscribe/
* Source repository:  https://gitlab.com/thaikolja/viscribe-wordpress-plugin
