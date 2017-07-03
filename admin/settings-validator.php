<?php

namespace WPDiscourse\Shortcodes;

class SettingsValidator {
	protected $webhook_refresh = false;

	public function __construct() {
		add_filter( 'wpdc_validate_wpds_clear_topics_cache', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_use_default_styles', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_topic_cache_duration', array( $this, 'validate_int' ) );
		add_filter( 'wpdc_validate_wpds_topic_webhook_refresh', array( $this, 'validate_webhook_request' ) );
		add_filter( 'wpdc_validate_wpds_webhook_secret', array( $this, 'validate_webhook_secret' ) );
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

	public function validate_webhook_secret( $input ) {
		if ( empty( $input) && true === $this->webhook_refresh ) {
			add_settings_error( 'wpds', 'webhook_secret', __( 'To use Discourse webhooks you must provide a webhook secret key.', 'wpds') );

			return '';
		}

		return sanitize_text_field( $input );
	}
}