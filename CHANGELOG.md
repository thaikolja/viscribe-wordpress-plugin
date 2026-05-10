# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-05-10

### Added

- Initial release of AI Image Renamer.
- Integration with Groq Vision API for automated image renaming.
- Support for JPEG, PNG, and WebP image formats.
- Options to customize AI prompt and maximum keywords.
- Support for automatically setting image Alt Text based on AI description.
- Encryption service for secure API key storage.
- Twig-based template engine for admin views.
- PSR-4 autoloading via Composer.

### Fixed

- Fixed fatal error where classes were not loading due to missing Composer autoloader.
- Fixed dashboard menu item not appearing in "Media" menu by correcting hook timing (moved from `admin_init` to
  `plugins_loaded`).
- Corrected text domain from `ai-image` to `ai-image-renamer` for better translation support.
- Improved plugin initialization using the Singleton pattern.
