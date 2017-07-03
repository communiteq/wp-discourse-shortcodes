<?php

namespace WPDiscourse\Shortcodes;

class SettingsValidator {
	protected $webhook_refresh = false;

	public function __construct() {
		add_filter( 'wpdc_validate_dclt_clear_topics_cache', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_dclt_use_default_styles', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_dclt_cache_duration', array( $this, 'validate_int' ) );
		add_filter( 'wpdc_validate_dclt_webhook_refresh', array( $this, 'validate_webhook_request' ) );
		add_filter( 'wpdc_validate_dclt_webhook_secret', array( $this, 'validate_webhook_secret' ) );
	}

	public function validate_checkbox( $input ) {
		return 1 === intval( $input ) ? 1 : 0;
	}

	public function validate_int( $input ) {
		return intval( $input );
	}

	public function validate_webhook_request( $input ) {
		$this->webhook_refresh = true;

		return $this->validate_checkbox( $input );
	}

	public function validate_webhook_secret( $input ) {
		if ( empty( $input) && true === $this->webhook_refresh ) {
			add_settings_error( 'dclt', 'webhook_secret', __( 'To use Discourse webhooks you must provide a webhook secret key.', 'dclt') );

			return '';
		}

		return sanitize_text_field( $input );
	}
}