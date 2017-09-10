<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class SettingsValidator {
	protected $options;
	protected $webhook_refresh = false;
	protected $ajax_refresh = false;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_filter( 'wpdc_validate_wpds_max_topics', array( $this, 'validate_int' ) );
		add_filter( 'wpdc_validate_wpds_display_private_topics', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_use_default_styles', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_topic_webhook_refresh', array( $this, 'validate_webhook_refresh' ) );
		add_filter( 'wpdc_validate_wpds_ajax_refresh', array( $this, 'validate_ajax_refresh' ) );
		add_filter( 'wpdc_validate_wpds_fetch_discourse_groups', array( $this, 'validate_checkbox' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	public function validate_checkbox( $input ) {
		return 1 === intval( $input ) ? 1 : 0;
	}

	public function validate_int( $input ) {
		return intval( $input );
	}

	public function validate_webhook_refresh( $input ) {
		$this->webhook_refresh = $this->validate_checkbox( $input );
		if ( 1 === $this->webhook_refresh && empty( $this->options['webhook-secret'])) {
			add_settings_error( 'wpds', 'webhook_refresh', __( 'To use a the latest_topics webhook, you must set a webhook secret key on the Webhook options tab.', 'wpds' ) );

			return 0;
		}

		return $this->webhook_refresh;
	}

	public function validate_ajax_refresh( $input ) {
		$this->ajax_refresh = $this->validate_checkbox( $input );

		return $this->ajax_refresh;
	}
}
