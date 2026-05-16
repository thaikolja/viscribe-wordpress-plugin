<?php

/*
 * @name:           Viscribe
 * @description     Uses AI to rename images during upload for SEO-friendly filenames.
 * @author          Kolja Nolte <kolja.nolte@gmail.com>
 * @copyright       2025-2026 (C) Kolja Nolte
 * @see             https://docs.kolja-nolte.com/viscribe-wordpress-plugin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Released under the GNU General Public License v2 or later.
 * See: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Viscribe
 * @license GPL-2.0-or-later
 */

/**
 * Admin Settings Page.
 *
 * @package Viscribe\Admin
 */

declare( strict_types=1 );

namespace Viscribe\Admin;

use Viscribe\Utils\Rate_Limiter;
use Viscribe\Utils\SVG_Sanitizer;
use Viscribe\Services\Groq_Service;
use Viscribe\Utils\API_Key_Validator;
use Viscribe\Services\Template_Engine;
use Viscribe\Services\Encryption_Service;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings_Page
 *
 * Handles the plugin settings page under Settings menu.
 */
class Settings_Page {
	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	public const PAGE_SLUG = 'viscribe';

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	public const OPTION_GROUP = 'viscribe_settings';

	/**
	 * Option name for storing settings.
	 *
	 * @var string
	 */
	public const OPTION_NAME = 'viscribe_options';

	/**
	 * Template engine instance.
	 *
	 * @var Template_Engine
	 */
	private Template_Engine $template_engine;

	/**
	 * Encryption service instance.
	 *
	 * @var Encryption_Service
	 */
	private Encryption_Service $encryption_service;

	/**
	 * Groq service instance.
	 *
	 * @var Groq_Service
	 */
	private Groq_Service $groq_service;

	/**
	 * Constructor.
	 *
	 * @param Template_Engine    $template_engine    Template engine instance.
	 * @param Encryption_Service $encryption_service Encryption service instance.
	 * @param Groq_Service       $groq_service       Groq service instance.
	 */
	public function __construct(
		Template_Engine $template_engine, Encryption_Service $encryption_service, Groq_Service $groq_service
	) {
		$this->template_engine    = $template_engine;
		$this->encryption_service = $encryption_service;
		$this->groq_service       = $groq_service;
	}

	/**
	 * Initialize the settings page.
	 *
	 * @return void
	 */
	final public function init(): void {
		\add_action( 'admin_menu', [ $this, 'add_settings_page' ] );
		\add_action( 'admin_init', [ $this, 'register_settings' ] );
		\add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		\add_action( 'wp_ajax_viscribe_test_connection', [ $this, 'ajax_test_connection' ] );
		\add_action( 'wp_ajax_viscribe_delete_api_key', [ $this, 'ajax_delete_api_key' ] );
	}

	/**
	 * Add the settings page to the admin menu.
	 *
	 * @return void
	 */
	final public function add_settings_page(): void {
		\add_submenu_page( 'upload.php', 'Viscribe', 'Viscribe', 'manage_options', self::PAGE_SLUG, [
			$this,
			'render_settings_page',
		] );
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	final public function register_settings(): void {
		\register_setting( self::OPTION_GROUP, self::OPTION_NAME, [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_settings' ],
		] );

		// Main settings section.
		\add_settings_section( 'viscribe_main_section', '<span class="dashicons dashicons-admin-settings"></span> ' . \__( 'API Configuration', 'viscribe' ), function () {
			echo $this->template_engine->render( 'admin/sections/hero.twig' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		},                     self::PAGE_SLUG );

		// Enable/Disable toggle.
		\add_settings_field( 'enabled', '<label for="air_enabled"><span class="dashicons dashicons-lightbulb"></span> ' . \__( 'Enable Auto-Rename', 'viscribe' ) . '</label>', [
			$this,
			'render_enabled_field',
		],                   self::PAGE_SLUG, 'viscribe_main_section' );

		// API Key field.
		\add_settings_field( 'api_key', '<label for="air_api_key"><span class="dashicons dashicons-admin-network"></span> ' . \__( 'Groq API Key', 'viscribe' ) . '</label>', [
			$this,
			'render_api_key_field',
		],                   self::PAGE_SLUG, 'viscribe_main_section' );

		// Model Selection.
		\add_settings_field( 'model', '<label for="air_model"><span class="dashicons dashicons-products"></span> ' . \__( 'AI Model', 'viscribe' ) . '</label>', [
			$this,
			'render_model_field',
		],                   self::PAGE_SLUG, 'viscribe_main_section' );

		// Alt text toggle.
		\add_settings_field( 'set_alt_text', '<label for="air_set_alt_text"><span class="dashicons dashicons-text"></span> ' . \__( 'Use as <code>alt=""</code>', 'viscribe' ) . '</label>', [
			$this,
			'render_alt_text_field',
		],                   self::PAGE_SLUG, 'viscribe_main_section' );

		// File types section.
		\add_settings_section( 'viscribe_file_types_section', '<span class="dashicons dashicons-format-image"></span> ' . \__( 'File Types', 'viscribe' ), function () {
			echo '<p>' . \esc_html__( 'Select which image types to process.', 'viscribe' ) . '</p>';
		},                     self::PAGE_SLUG );

		\add_settings_field( 'file_types', '<span class="dashicons dashicons-images-alt2"></span> ' . \__( 'Allowed Types', 'viscribe' ), [
			$this,
			'render_file_types_field',
		],                   self::PAGE_SLUG, 'viscribe_file_types_section' );

		// Advanced section.
		\add_settings_section( 'viscribe_advanced_section', '<span class="dashicons dashicons-admin-tools"></span> ' . \__( 'Advanced Settings', 'viscribe' ), function () {
			echo '<p>' . \esc_html__( 'Customize the AI prompt and keyword settings.', 'viscribe' ) . '</p>';
		},                     self::PAGE_SLUG );

		\add_settings_field( 'max_keywords', '<label for="air_max_keywords"><span class="dashicons dashicons-editor-ol"></span> ' . \__( 'Max Keywords', 'viscribe' ) . '</label>', [
			$this,
			'render_max_keywords_field',
		],                   self::PAGE_SLUG, 'viscribe_advanced_section' );

		/**
		 * Fires after all core settings fields are registered.
		 * Pro can add additional sections and fields here.
		 *
		 * @param string $page_slug The settings page slug.
		 *
		 * @since 1.0.0
		 */
		\do_action( 'viscribe_register_settings_fields', self::PAGE_SLUG );
	}

	/**
	 * Get default settings.
	 *
	 * @return array Default settings.
	 */
	private function get_defaults(): array {
		$defaults = [
			'api_key'      => '',
			'enabled'      => false,
			'file_types'   => [ 'image/jpeg', 'image/png', 'image/webp' ],
			'max_keywords' => 5,
			'set_alt_text' => false,
			'model'        => 'meta-llama/llama-4-scout-17b-16e-instruct',
		];

		/**
		 * Filter the default settings values.
		 * Pro can add its own defaults here.
		 *
		 * @param array $defaults The default settings.
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'viscribe_settings_defaults', $defaults );
	}

	/**
	 * Normalize checkbox-like settings values to a boolean.
	 *
	 * @param mixed $value Raw submitted or stored value.
	 *
	 * @return bool
	 */
	private function normalize_checkbox_value( mixed $value ): bool {
		return true === $value || 1 === $value || '1' === (string) $value;
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input The input settings.
	 *
	 * @return array Sanitized settings.
	 */
	final public function sanitize_settings( array $input ): array {
		$sanitized = $this->get_defaults();
		$old       = \get_option( self::OPTION_NAME, $this->get_defaults() );

		if ( isset( $input['api_key'] ) ) {
			$plaintext = \trim( $input['api_key'] );

			if ( empty( $plaintext ) ) {
				$sanitized['api_key'] = '';
			} else if ( API_Key_Validator::is_masked( $plaintext ) ) {
				// If the key is masked (user didn't change it), keep the old encrypted key.
				$sanitized['api_key'] = $old['api_key'] ?? '';
			} else {
				// Use strict API key validation.
				$validation = API_Key_Validator::validate_groq_key( $plaintext );

				if ( ! $validation['valid'] ) {
					\add_settings_error( self::OPTION_GROUP, 'invalid_key', $validation['message'] );
					// Keep old key if validation failed.
					$sanitized['api_key'] = $old['api_key'] ?? '';
				} else {
					$encrypted = $this->encryption_service->encrypt( $plaintext );
					if ( false !== $encrypted ) {
						$sanitized['api_key'] = $encrypted;
					} else {
						\add_settings_error( self::OPTION_GROUP, 'encryption_failed', \__( 'Failed to encrypt API key. Please try again.', 'viscribe' ) );
						// Keep old key if encryption failed.
						$sanitized['api_key'] = $old['api_key'] ?? '';
					}
				}
			}
		}

		// Enabled toggle.
		$sanitized['enabled'] = $this->normalize_checkbox_value( $input['enabled'] ?? false );

		// File types.
		if ( isset( $input['file_types'] ) && is_array( $input['file_types'] ) ) {
			$allowed_types = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];

			/**
			 * Filter the allowed file types for validation.
			 * Pro can add additional file types (e.g., HEIC).
			 *
			 * @param array $allowed_types The allowed MIME types.
			 *
			 * @since 1.0.0
			 */
			$allowed_types = \apply_filters( 'viscribe_allowed_file_types_for_validation', $allowed_types );

			$sanitized['file_types'] = array_intersect( $input['file_types'], $allowed_types );
		}

		// Max keywords.
		if ( isset( $input['max_keywords'] ) ) {
			$sanitized['max_keywords'] = \absint( $input['max_keywords'] );
			$sanitized['max_keywords'] = max( 1, min( 10, $sanitized['max_keywords'] ) );
		}

		// Alt text toggle.
		// Note: The UI says "Add to alt Attribute", key is set_alt_text.
		$sanitized['set_alt_text'] = $this->normalize_checkbox_value( $input['set_alt_text'] ?? false );

		// Model selection.
		if ( isset( $input['model'] ) ) {
			$valid_models = [
				'meta-llama/llama-4-maverick-17b-128e-instruct',
				'meta-llama/llama-4-scout-17b-16e-instruct',
			];

			/**
			 * Filter the valid model IDs for validation.
			 * Pro can add additional models here.
			 *
			 * @param array $valid_models Array of valid model IDs.
			 *
			 * @since 1.0.0
			 */
			$valid_models = \apply_filters( 'viscribe_valid_models', $valid_models );

			if ( \in_array( $input['model'], $valid_models, true ) ) {
				$sanitized['model'] = $input['model'];
			} else {
				// Custom providers (Pro) use free-text model names which are validated later.
				$is_custom_provider = isset( $input['provider'] ) && 'custom' === $input['provider'];

				if ( ! $is_custom_provider ) {
					\add_settings_error( self::OPTION_NAME, 'invalid_model', \__( 'Invalid AI model selected. Using default model.', 'viscribe' ) );
					$sanitized['model'] = $this->get_defaults()['model'];
				}
			}
		}

		/**
		 * Filter the sanitized settings before saving.
		 * Pro can sanitize its own settings here.
		 *
		 * @param array $sanitized The sanitized settings.
		 * @param array $input     The raw input settings.
		 * @param array $old       The previous settings.
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'viscribe_sanitize_settings', $sanitized, $input, $old );
	}

	/**
	 * Get decrypted API key and derived display values.
	 *
	 * @return array{encrypted: string, decrypted: string, saved: bool, display_key: string, placeholder: string}
	 */
	private function get_api_key_info(): array {
		$options       = \get_option( self::OPTION_NAME, $this->get_defaults() );
		$encrypted_key = $options['api_key'] ?? '';
		$decrypted_key = '';

		if ( ! empty( $encrypted_key ) ) {
			$decrypted_key = $this->encryption_service->decrypt( $encrypted_key );
			if ( false === $decrypted_key ) {
				$decrypted_key = '';
			}
		}

		$saved = ! empty( $encrypted_key );

		return [
			'encrypted'   => $encrypted_key,
			'decrypted'   => $decrypted_key,
			'saved'       => $saved,
			'display_key' => $saved && ! empty( $decrypted_key ) ? API_Key_Validator::mask_for_display( $decrypted_key ) : '',
			'placeholder' => $saved ? __( 'Enter a new key to replace the saved one…', 'viscribe' ) : 'gsk_...',
		];
	}

	/**
	 * Render a simple checkbox field.
	 *
	 * @param string $template     Twig template relative to views/.
	 * @param string $field_key    Key in the options array.
	 * @param string $template_key Key passed to the template.
	 *
	 * @return void
	 */
	private function render_checkbox_field( string $template, string $field_key, string $template_key ): void {
		$options = \get_option( self::OPTION_NAME, $this->get_defaults() );
		$checked = $this->normalize_checkbox_value( $options[ $field_key ] ?? $this->get_defaults()[ $field_key ] );

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->template_engine->render( $template, [
			'option_name' => esc_attr( self::OPTION_NAME ),
			$template_key => (bool) $checked,
		] );
		// phpcs:enable
	}

	/**
	 * Render the API key field.
	 *
	 * @return void
	 */
	final public function render_api_key_field(): void {
		$info                   = $this->get_api_key_info();
		$using_api_key_constant = Groq_Service::has_api_key_constant();

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->template_engine->render(
			'admin/fields/api-key.twig', [
			'option_name'            => esc_attr( self::OPTION_NAME ),
			'display_key'            => esc_attr( $info['display_key'] ),
			'placeholder'            => esc_attr( $info['placeholder'] ),
			'saved'                  => $info['saved'],
			'using_api_key_constant' => $using_api_key_constant,
		] );
		// phpcs:enable
	}

	/**
	 * Get available file types.
	 *
	 * @return array
	 */
	private function get_available_file_types(): array {
		$available_types = [
			'image/jpeg' => 'JPEG',
			'image/png'  => 'PNG',
			'image/webp' => 'WebP',
			'image/gif'  => 'GIF',
		];

		/**
		 * Filter the available file types displayed in the UI.
		 * Pro can add additional file types (e.g., HEIC).
		 *
		 * @param array $available_types Array of MIME type => Label.
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'viscribe_available_file_types', $available_types );
	}

	/**
	 * Get available models.
	 *
	 * @param string $current Current model ID.
	 *
	 * @return array
	 */
	private function get_available_models( string $current ): array {
		$models = [
			'meta-llama/llama-4-scout-17b-16e-instruct'     => [
				'label'      => 'Llama 4 Scout',
				'provider'   => 'meta',
				'desc'       => 'Best for speed. Renames images faster while maintaining good accuracy.',
				'highlights' => [ 'speed' ],
				'specs'      => [
					'params'  => '17B (16 Experts)',
					'context' => '128k',
					'speed'   => '750 tokens/s',
					'size'    => '20 MB',
				],
				'deprecated' => false,
			],
			'meta-llama/llama-4-maverick-17b-128e-instruct' => [
				'label'      => 'Llama 4 Maverick',
				'provider'   => 'meta',
				'desc'       => 'Best for detailed image analysis. Generates more accurate filenames and alt text for complex images.',
				'highlights' => [ 'params' ],
				'specs'      => [
					'params'  => '17B (128 Experts)',
					'context' => '128k',
					'speed'   => '600 tokens/s',
					'size'    => '20 MB',
				],
				'deprecated' => true,
			],
		];

		/**
		 * Filter the available AI models displayed in the UI.
		 * Pro can add additional models here.
		 *
		 * @param array  $models  Array of model_id => ['label' => ..., 'desc' => ...].
		 * @param string $current The currently selected model ID.
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'viscribe_available_models', $models, $current );
	}

	/**
	 * Prepare models array for template output by escaping its elements.
	 *
	 * @param array $models Models array.
	 *
	 * @return array Escaped models array.
	 */
	private function prepare_models_for_template( array $models ): array {
		$escaped = [];
		foreach ( $models as $id => $data ) {
			$escaped[ esc_attr( (string) $id ) ] = [
				'label'      => esc_html( $data['label'] ?? '' ),
				'provider'   => esc_html( $data['provider'] ?? '' ),
				'desc'       => esc_html( $data['desc'] ?? '' ),
				'highlights' => array_map( 'esc_html', (array) ( $data['highlights'] ?? [] ) ),
				'specs'      => array_map( 'esc_html', (array) ( $data['specs'] ?? [] ) ),
				'deprecated' => (bool) ( $data['deprecated'] ?? false ),
			];
		}

		return $escaped;
	}

	/**
	 * Prepare model limit information for template output.
	 *
	 * @param array $model_limit_info Model limit information.
	 *
	 * @return array
	 */
	private function prepare_model_limit_info_for_template( array $model_limit_info ): array {
		$escaped = [
			'title'          => esc_html( $model_limit_info['title'] ?? '' ),
			'description'    => esc_html( $model_limit_info['description'] ?? '' ),
			'model_label'    => esc_html( $model_limit_info['model_label'] ?? '' ),
			'model_id'       => esc_html( $model_limit_info['model_id'] ?? '' ),
			'model_card_url' => esc_url( $model_limit_info['model_card_url'] ?? '' ),
			'items'          => [],
			'notes'          => [],
		];

		foreach ( (array) ( $model_limit_info['items'] ?? [] ) as $item ) {
			$escaped['items'][] = [
				'label'   => esc_html( $item['label'] ?? '' ),
				'value'   => esc_html( $item['value'] ?? '' ),
				'meaning' => esc_html( $item['meaning'] ?? '' ),
			];
		}

		foreach ( (array) ( $model_limit_info['notes'] ?? [] ) as $note ) {
			$escaped['notes'][] = [
				'variant' => esc_attr( $note['variant'] ?? 'tip' ),
				'title'   => esc_html( $note['title'] ?? '' ),
				'text'    => esc_html( $note['text'] ?? '' ),
			];
		}

		return $escaped;
	}

	/**
	 * Get human-readable limit information for the current Scout model.
	 *
	 * @return array
	 */
	private function get_current_model_limit_info( string $current_model ): array {
		$model_limits = [
			'meta-llama/llama-4-scout-17b-16e-instruct'     => [
				'model_label'    => 'Llama 4 Scout 17B 16E',
				'model_id'       => 'meta-llama/llama-4-scout-17b-16e-instruct',
				'model_card_url' => 'https://console.groq.com/docs/model/meta-llama/llama-4-scout-17b-16e-instruct',
				'speed'          => '~750 tokens/second',
				'speed_meaning'  => __( 'Usually responds quickly, so uploaded images should not wait long for a new filename.', 'viscribe' ),
			],
			'meta-llama/llama-4-maverick-17b-128e-instruct' => [
				'model_label'    => 'Llama 4 Maverick 17B 128E',
				'model_id'       => 'meta-llama/llama-4-maverick-17b-128e-instruct',
				'model_card_url' => 'https://console.groq.com/docs/model/meta-llama/llama-4-maverick-17b-128e-instruct',
				'speed'          => '~600 tokens/second',
				'speed_meaning'  => __( 'Usually responds a bit slower than Scout, but can provide more detailed image analysis.', 'viscribe' ),
			],
		];

		$current_limits = $model_limits[ $current_model ] ?? $model_limits['meta-llama/llama-4-scout-17b-16e-instruct'];

		return [
			'title'          => \__( 'Model Limits', 'viscribe' ),
			'description'    => \__( 'A quick overview of the limits that matter most when this model renames uploaded images.', 'viscribe' ),
			'model_label'    => $current_limits['model_label'],
			'model_id'       => $current_limits['model_id'],
			'model_card_url' => $current_limits['model_card_url'],
			'items'          => [
				[
					'label'   => __( 'Speed', 'viscribe' ),
					'value'   => $current_limits['speed'],
					'meaning' => $current_limits['speed_meaning'],
				],
				[
					'label'   => __( 'Context window', 'viscribe' ),
					'value'   => '131,072 tokens',
					'meaning' => __( 'Plenty of room for the prompt and image analysis. This plugin only uses a small part of it.', 'viscribe' ),
				],
				[
					'label'   => __( 'Max output', 'viscribe' ),
					'value'   => '8,192 tokens',
					'meaning' => __( 'The plugin only needs a short answer, so normal filename suggestions stay far below this limit.', 'viscribe' ),
				],
				[
					'label'   => __( 'Max file size', 'viscribe' ),
					'value'   => '20 MB per image',
					'meaning' => __( 'Images above this size can fail before renaming starts. Oversized originals may need resizing first.', 'viscribe' ),
				],
				[
					'label'   => __( 'Max input images', 'viscribe' ),
					'value'   => '5 images per request',
					'meaning' => __( 'This plugin sends one uploaded image at a time, so it stays comfortably within the limit.', 'viscribe' ),
				],
				[
					'label'   => __( 'Supported input', 'viscribe' ),
					'value'   => __( 'Text and images', 'viscribe' ),
					'meaning' => __( 'The plugin can send the uploaded image together with a short instruction for filename generation.', 'viscribe' ),
				],
				[
					'label'   => __( 'Supported output', 'viscribe' ),
					'value'   => __( 'Text only', 'viscribe' ),
					'meaning' => __( 'The model returns text, which this plugin turns into a filename and optional alt text.', 'viscribe' ),
				],
			],
			'notes'          => [
				[
					'variant' => 'tip',
					'title'   => \__( 'Rate limits still apply', 'viscribe' ),
					'text'    => \__( 'Your Groq account can still limit how many requests or tokens you may use over time, especially during larger upload bursts.', 'viscribe' ),
				],
				[
					'variant' => 'tip',
					'title'   => \__( 'EU licensing note', 'viscribe' ),
					'text'    => \__( 'Groq repeats Meta\'s note that certain multimodal rights may be limited in the EU. Check the linked model card if this could apply to you.', 'viscribe' ),
				],
				[
					'variant' => 'tip',
					'title'   => esc_html__( 'EU licensing note', 'viscribe' ),
					'text'    => esc_html__( 'Groq repeats Meta’s note that certain multimodal rights may be limited in the EU. Check the linked model card if this could apply to you.', 'viscribe' ),
				],
			],
		];
	}

	/**
	 * Render the enabled toggle field.
	 *
	 * @return void
	 */
	final public function render_enabled_field(): void {
		$this->render_checkbox_field( 'admin/fields/enabled.twig', 'enabled', 'enabled' );
	}

	/**
	 * Render the alt text toggle field.
	 *
	 * @return void
	 */
	final public function render_alt_text_field(): void {
		$this->render_checkbox_field( 'admin/fields/alt-text.twig', 'set_alt_text', 'set_alt' );
	}

	/**
	 * Render the file types field.
	 *
	 * @return void
	 */
	final public function render_file_types_field(): void {
		$options    = \get_option( self::OPTION_NAME, $this->get_defaults() );
		$file_types = $options['file_types'] ?? [];

		$available_types = $this->get_available_file_types();

		// All values escaped before passing to template.
		echo $this->template_engine->render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'admin/fields/file-types.twig', [
			'option_name'     => esc_attr( self::OPTION_NAME ),
			'file_types'      => array_map( 'esc_attr', $file_types ),
			'available_types' => array_map( 'esc_html', $available_types ),
		] );
	}

	/**
	 * Render the max keywords field.
	 *
	 * @return void
	 */
	final public function render_max_keywords_field(): void {
		$options      = \get_option( self::OPTION_NAME, $this->get_defaults() );
		$max_keywords = $options['max_keywords'] ?? 5;

		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->template_engine->render( 'admin/fields/max-keywords.twig', [
			'option_name'  => esc_attr( self::OPTION_NAME ),
			'max_keywords' => absint( $max_keywords ),
		] );
		// phpcs:enable
	}

	/**
	 * Render the model selection field.
	 *
	 * @return void
	 */
	final public function render_model_field(): void {
		$options         = \get_option( self::OPTION_NAME, $this->get_defaults() );
		$current         = esc_attr( $options['model'] ?? 'meta-llama/llama-4-scout-17b-16e-instruct' );
		$models          = $this->get_available_models( $current );
		$prepared_models = $this->prepare_models_for_template( $models );

		// All values escaped before passing to template.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Twig context contains escaped strings and the prepared model array only.
		echo $this->template_engine->render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'admin/fields/model.twig', [
			'option_name' => esc_attr( self::OPTION_NAME ),
			'current'     => esc_attr( $current ),
			'models'      => $prepared_models,
			'asset_url'   => esc_url( \plugins_url( 'assets', \dirname( __DIR__, 2 ) . '/viscribe.php' ) ),
		] );
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Get tab definitions for the settings page.
	 *
	 * @return array Associative array of tab_id => ['icon' => ..., 'label' => ..., 'active' => bool].
	 */
	private function get_tab_definitions(): array {
		$tabs = [
			'general'   => [
				'icon'   => 'dashicons-admin-settings',
				'label'  => \__( 'General', 'viscribe' ),
				'active' => true,
			],
			'api'       => [
				'icon'  => 'dashicons-admin-network',
				'label' => \__( 'API Key', 'viscribe' ),
			],
			'providers' => [
				'icon'  => 'dashicons-cloud',
				'label' => \__( 'Providers', 'viscribe' ),
			],
			'filetypes' => [
				'icon'  => 'dashicons-format-image',
				'label' => \__( 'File Types', 'viscribe' ),
			],
			'advanced'  => [
				'icon'  => 'dashicons-admin-tools',
				'label' => \__( 'Advanced', 'viscribe' ),
			],
		];

		/**
		 * Filter the settings page tab definitions.
		 * Pro can add or modify tabs here.
		 *
		 * @param array $tabs Associative array of tab_id => ['icon' => ..., 'label' => ..., 'active' => bool].
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'viscribe_settings_tabs', $tabs );
	}

	/**
	 * Get pre-rendered panel contents for each tab.
	 *
	 * @param array $args Computed variables for the templates.
	 *
	 * @return array Associative array of tab_id => pre-rendered HTML string.
	 */
	private function get_panel_contents( array $args ): array {
		$engine = $this->template_engine;

		$panels = [
			'general'   => $engine->render( 'admin/fields/general.twig', [
				'option_name'  => \esc_attr( self::OPTION_NAME ),
				'enabled'      => $args['enabled'],
				'set_alt_text' => $args['set_alt_text'],
			] ) . $engine->render( 'admin/fields/model-limits-info.twig', [
				'model_limit_info' => $args['model_limit_info'],
			] ),
			'api'       => $engine->render( 'admin/fields/api-key.twig', [
				'option_name'            => \esc_attr( self::OPTION_NAME ),
				'display_key'            => \esc_attr( $args['display_key'] ),
				'placeholder'            => \esc_attr( $args['placeholder'] ),
				'saved'                  => $args['saved'],
				'using_api_key_constant' => $args['using_api_key_constant'],
			] ),
			'providers' => $engine->render( 'admin/fields/providers.twig', [
				'option_name'  => \esc_attr( self::OPTION_NAME ),
				'models'       => $args['models'],
				'current'      => \esc_attr( $args['current'] ),
				'asset_url'    => \esc_url( $args['asset_url'] ),
			] ),
			'filetypes' => $engine->render( 'admin/fields/file-types.twig', [
				'option_name'     => \esc_attr( self::OPTION_NAME ),
				'file_types'      => \array_map( 'esc_attr', $args['file_types'] ),
				'available_types' => \array_map( 'esc_html', $args['available_types'] ),
			] ),
			'advanced'  => $engine->render( 'admin/fields/advanced.twig', [
				'option_name'  => \esc_attr( self::OPTION_NAME ),
				'max_keywords' => \absint( $args['max_keywords'] ),
				'diagnostics'  => $args['diagnostics'],
			] ),
		];

		/**
		 * Filter the pre-rendered panel HTML for each tab.
		 * Pro can add its own panels here.
		 *
		 * @param array $panels Associative array of tab_id => pre-rendered HTML string.
		 *
		 * @since 1.0.0
		 */
		return \apply_filters( 'viscribe_settings_panels', $panels );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	final public function render_settings_page(): void {
		if ( ! \current_user_can( 'manage_options' ) ) {
			return;
		}

		$options      = \get_option( self::OPTION_NAME, $this->get_defaults() );
		$api_key_info = $this->get_api_key_info();

		// File types.
		$file_types      = $options['file_types'] ?? [];
		$available_types = $this->get_available_file_types();

		// Models.
		$current          = $options['model'] ?? 'meta-llama/llama-4-scout-17b-16e-instruct';
		$models           = $this->get_available_models( $current );
		$prepared_models  = $this->prepare_models_for_template( $models );
		$model_limit_info = $this->prepare_model_limit_info_for_template( $this->get_current_model_limit_info( $current ) );

		// Check if cURL exists and is enabled
		$curl_enabled = \function_exists( 'curl_version' );

		$using_api_key_constant = Groq_Service::has_api_key_constant();

		$enabled      = $this->normalize_checkbox_value( $options['enabled'] ?? $this->get_defaults()['enabled'] );
		$set_alt_text = $this->normalize_checkbox_value( $options['set_alt_text'] ?? $this->get_defaults()['set_alt_text'] );
		$max_keywords = \absint( $options['max_keywords'] ?? 5 );
		$asset_url    = \esc_url( \plugins_url( 'assets', \dirname( __DIR__, 2 ) . '/viscribe.php' ) );

		$diagnostics = [
			'php'    => [
				'label' => \esc_html__( 'PHP Version', 'viscribe' ),
				'value' => \esc_html( PHP_VERSION ),
				'ok'    => (bool) \version_compare( PHP_VERSION, '8.2', '>=' ),
				'desc'  => \esc_html__( 'Required: 8.2 or higher', 'viscribe' ),
			],
			'wp'     => [
				'label' => \esc_html__( 'WordPress', 'viscribe' ),
				'value' => \esc_html( \get_bloginfo( 'version' ) ),
				'ok'    => (bool) \version_compare( \get_bloginfo( 'version' ), '6.0', '>=' ),
				'desc'  => \esc_html__( 'Required: 6.0 or higher', 'viscribe' ),
			],
			'memory' => [
				'label' => \esc_html__( 'Memory Limit', 'viscribe' ),
				'value' => \esc_html( \ini_get( 'memory_limit' ) ),
				'ok'    => true, // Informational.
				'desc'  => \esc_html__( 'Allocated memory for script execution', 'viscribe' ),
			],
			'upload' => [
				'label' => \esc_html__( 'Max Upload Size', 'viscribe' ),
				'value' => \esc_html( \ini_get( 'upload_max_filesize' ) ),
				'ok'    => true, // Informational.
				'desc'  => \esc_html__( 'Maximum file size set by server', 'viscribe' ),
			],
			'curl'   => [
				'label' => \esc_html__( 'cURL Enabled', 'viscribe' ),
				'value' => $curl_enabled ? \esc_html__( 'Yes', 'viscribe' ) : \esc_html__( 'No', 'viscribe' ),
				'ok'    => \function_exists( 'curl_version' ),
				'desc'  => \esc_html__( 'Required for API communication', 'viscribe' ),
			],
		];

		// Build tabs and panels dynamically (filterable by Pro).
		$tabs = $this->get_tab_definitions();

		$panels = $this->get_panel_contents( [
			'display_key'            => $api_key_info['display_key'],
			'placeholder'            => $api_key_info['placeholder'],
			'saved'                  => $api_key_info['saved'],
			'enabled'                => $enabled,
			'set_alt_text'           => $set_alt_text,
			'file_types'             => $file_types,
			'available_types'        => $available_types,
			'current'                => $current,
			'models'                 => $prepared_models,
			'model_limit_info'       => $model_limit_info,
			'max_keywords'           => $max_keywords,
			'asset_url'              => $asset_url,
			'using_api_key_constant' => $using_api_key_constant,
			'diagnostics'            => $diagnostics,
		] );

		// Display any validation/save errors above the form.
		\settings_errors( self::OPTION_GROUP );

		// All values escaped before passing to template.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Twig context contains only escaped strings and pre-rendered HTML.
		echo $this->template_engine->render( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			'admin/settings.twig', [
			'page_slug'    => \esc_attr( self::PAGE_SLUG ),
			'option_group' => \esc_attr( self::OPTION_GROUP ),
			'option_name'  => \esc_attr( self::OPTION_NAME ),
			'version'      => \esc_html( VISCRIBE_VERSION ),
			'page_title'   => \esc_html( \get_admin_page_title() ),
			'tabs'         => $tabs,
			'panels'       => $panels,
		] );
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Enqueues admin-specific assets such as styles and scripts on the settings page.
	 *
	 * @param string $hook The current admin page hook suffix.
	 *
	 * @return void
	 */
	final public function enqueue_assets( string $hook ): void {
		if ( 'media_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}

		$suffix = \defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';

		if ( '' === $suffix ) {
			$js_file = VISCRIBE_PLUGIN_DIR . 'assets/js/index.js';
			$version = \file_exists( $js_file ) ? \filemtime( $js_file ) : VISCRIBE_VERSION;
		} else {
			$asset_file   = VISCRIBE_PLUGIN_DIR . 'assets/js/index.asset.php';
			$version      = VISCRIBE_VERSION;
			if ( \file_exists( $asset_file ) ) {
				$asset_data = include $asset_file;
				$version    = $asset_data['version'] ?? VISCRIBE_VERSION;
			}
		}

		\wp_enqueue_style( 'viscribe-admin', VISCRIBE_PLUGIN_URL . 'assets/css/styles.min.css', [], $version );

		\wp_enqueue_script( 'viscribe-admin', VISCRIBE_PLUGIN_URL . 'assets/js/index.js', [ 'jquery' ], $version, true );

		\wp_localize_script( 'viscribe-admin', 'viscribeAdmin', [
			'ajaxUrl'             => \admin_url( 'admin-ajax.php' ),
			'usingApiKeyConstant' => Groq_Service::has_api_key_constant(),
			'nonces'              => [
				'test_connection' => \wp_create_nonce( 'viscribe_test_connection' ),
				'delete_api_key'  => \wp_create_nonce( 'viscribe_delete_api_key' ),
			],
			'strings'             => [
				'testing'                                                  => \__( 'Testing...', 'viscribe' ),
				'success'                                                  => \__( 'Connection successful!', 'viscribe' ),
				'error'                                                    => \__( 'Connection failed:', 'viscribe' ),
				'error_empty'                                              => \__( 'API key cannot be empty.', 'viscribe' ),
				/* translators: %s: Groq API key prefix. */
				'error_prefix'                                             => \sprintf( \__( 'The format of the API key is invalid. Groq API keys start with %s', 'viscribe' ), 'gsk_' ),
				/* translators: %d: Expected Groq API key length. */
				'error_length'                                             => \sprintf( \__( 'The API key has an invalid length. It must be exactly %d characters long.', 'viscribe' ), 56 ),
				'no_key'                                                   => \__( 'No API key configured.', 'viscribe' ),
				'delete_confirm'                                           => \__( 'Are you sure you want to delete the API Key? This action cannot be undone.', 'viscribe' ),
				'deleting'                                                 => \__( 'Deleting...', 'viscribe' ),
				'enter_key'                                                => \__( 'Enter your Groq API key.', 'viscribe' ),
				'request_failed'                                           => \__( 'Request failed:', 'viscribe' ),
				'delete_key_button'                                        => \__( 'Delete Key', 'viscribe' ),
			],
		] );

		\add_action( 'admin_footer', function () use ( $hook ) {
			if ( 'media_page_' . self::PAGE_SLUG !== $hook ) {
				return;
			}

			$sprite_path = VISCRIBE_PLUGIN_DIR . 'assets/icons/icons.svg';

			// Use SVG_Sanitizer to validate and sanitize the SVG content.
			$sanitized_svg = SVG_Sanitizer::load_and_sanitize_file( $sprite_path );

			if ( false !== $sanitized_svg ) {
				// Output is now safe after sanitization.
				echo $sanitized_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} );
	}

	/**
	 * Handle AJAX test connection request.
	 *
	 * @return void
	 */
	final public function ajax_test_connection(): void {
		// Apply rate limiting: 10 requests per minute.
		if ( ! Rate_Limiter::check_rate_limit( 'viscribe_test_connection', 10, 60 ) ) {
			Rate_Limiter::send_rate_limit_error( 'viscribe_test_connection' );
		}

		\check_ajax_referer( 'viscribe_test_connection', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( [ 'message' => \__( 'Permission denied.', 'viscribe' ) ] );
		}

		// If the API key constant is defined, always use it directly for testing.
		// This short-circuits all POST data handling regardless of what the JS sends.
		if ( Groq_Service::has_api_key_constant() ) {
			$result = $this->groq_service->test_connection( (string) \constant( 'VISCRIBE_API_KEY' ) );

			if ( true === $result ) {
				\wp_send_json_success( [ 'message' => \__( 'Connection successful!', 'viscribe' ) ] );
			} else {
				\wp_send_json_error( [ 'message' => $result ] );
			}

			return;
		}

		// No constant defined — use the key from POST data or the saved key.
		$api_key_raw = \filter_input( INPUT_POST, 'api_key', FILTER_UNSAFE_RAW, FILTER_NULL_ON_FAILURE );
		$is_new_key  = (bool) \filter_input( INPUT_POST, 'is_new_key', FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

		$api_key = null;

		if ( is_string( $api_key_raw ) ) {
			$api_key = \sanitize_text_field( \wp_unslash( $api_key_raw ) );

			// If the key is masked and it's not explicitly marked as a new key, use saved key.
			if ( API_Key_Validator::is_masked( $api_key ) && ! $is_new_key ) {
				$api_key = null;
			} else if ( empty( $api_key ) ) {
				// Explicitly empty key provided.
				\wp_send_json_error( [ 'message' => \__( 'No API key provided.', 'viscribe' ) ] );

				return;
			} else if ( ! API_Key_Validator::is_masked( $api_key ) ) {
				// Only validate if it's not masked (i.e., it's a new key).
				$validation = API_Key_Validator::validate_groq_key( $api_key );
				if ( ! $validation['valid'] ) {
					\wp_send_json_error( [ 'message' => $validation['message'] ] );

					return;
				}
			}
		}

		$result = $this->groq_service->test_connection( $api_key );

		if ( true === $result ) {
			\wp_send_json_success( [ 'message' => \__( 'Connection successful!', 'viscribe' ) ] );
		} else {
			\wp_send_json_error( [ 'message' => $result ] );
		}
	}

	/**
	 * Handle API key deletion.
	 *
	 * @return void
	 */
	final public function ajax_delete_api_key(): void {
		// Apply rate limiting: 5 requests per minute.
		if ( ! Rate_Limiter::check_rate_limit( 'viscribe_delete_api_key', 5, 60 ) ) {
			Rate_Limiter::send_rate_limit_error( 'viscribe_delete_api_key' );
		}

		\check_ajax_referer( 'viscribe_delete_api_key', 'nonce' );

		if ( ! \current_user_can( 'manage_options' ) ) {
			\wp_send_json_error( [ 'message' => \__( 'Permission denied.', 'viscribe' ) ] );
		}

		if ( Groq_Service::has_api_key_constant() ) {
			\wp_send_json_error( [
				                     'message' => \__( 'The API key is defined in wp-config.php and cannot be deleted here.', 'viscribe' ),
			                     ] );
		}

		$options            = \get_option( self::OPTION_NAME, $this->get_defaults() );
		$options['api_key'] = '';

		\update_option( self::OPTION_NAME, $options );

		\wp_send_json_success( [ 'message' => \__( 'API key deleted.', 'viscribe' ) ] );
	}
}
