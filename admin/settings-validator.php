<?php

namespace WPDiscourse\Shortcodes;

class SettingsValidator {
	protected $webhook_refresh = false;
	protected $ajax_refresh = false;

	public function __construct() {
		add_filter( 'wpdc_validate_wpds_clear_topics_cache', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_display_private_topics', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_use_default_styles', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_topic_webhook_refresh', array( $this, 'validate_webhook_request' ) );
		add_filter( 'wpdc_validate_wpds_webhook_secret', array( $this, 'validate_webhook_secret' ) );
		add_filter( 'wpdc_validate_wpds_ajax_refresh', array( $this, 'validate_ajax_refresh' ) );
		// Todo: add a validation function so this can't be set below a sane value.
		add_filter( 'wpdc_validate_wpds_ajax_timeout', array( $this, 'validate_int' ) );
		add_filter( 'wpdc_validate_wpds_fetch_discourse_groups', array( $this, 'validate_checkbox' ) );
	}

	public function validate_checkbox( $input ) {
		return 1 === intval( $input ) ? 1 : 0;
	}

	public function validate_int( $input ) {
		return intval( $input );
	}

	public function validate_webhook_request( $input ) {
		$this->webhook_refresh = $this->validate_checkbox( $input );

		return $this->webhook_refresh;
	}

	public function validate_ajax_refresh( $input ) {
		// Todo: this might not be needed.
		$this->ajax_refresh = $this->validate_checkbox( $input );

		return $this->ajax_refresh;
	}

	public function validate_webhook_secret( $input ) {
		// The input sanitization removes tags and converts angle brackets to html entities.
		if ( strpos( $input, '<' ) > - 1 || strpos( $input, '>' ) > - 1 ) {
			add_settings_error( 'wpds', 'webhook_secret', __( 'Angle brackets (<, >) cannot be used in the webhook secret key.', 'wpds' ) );

			return '';
		}

		$secret = sanitize_text_field( $input );
		if ( ( empty( $secret ) || iconv_strlen( $secret ) < 12 ) && $this->webhook_refresh ) {

			add_settings_error( 'wpds', 'webhook_secret', __( 'To use the discourse_latest shortcode, you must provide a webhook secret key at least 12 characters long.', 'wpds' ) );
		}

		return $secret;
	}
}
