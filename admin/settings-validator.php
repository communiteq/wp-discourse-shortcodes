<?php
/**
 * Sanitizes and validates the plugin's options.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class SettingsValidator
 *
 * @package WPDiscourse\Shortcodes
 */
class SettingsValidator {

	/**
	 * The merged options from WP Discourse and WP Discourse Shortcodes.
	 *
	 * All options are held in a single array, use a custom plugin prefix to avoid naming collisions with wp-discourse.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;

	/**
	 * Whether or not a webhook is being used.
	 *
	 * This isn't doing anything useful now.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $webhook_refresh = false;

	/**
	 * Whether or not ajax is being used.
	 *
	 * This isn't doing anything useful now.
	 *
	 * @access protected
	 * @var bool
	 */
	protected $ajax_refresh = false;

	/**
	 * SettingsValidator constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_filter( 'wpdc_validate_wpds_max_topics', array( $this, 'validate_int' ) );
		add_filter( 'wpdc_validate_wpds_topic_content', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_display_private_topics', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_use_default_styles', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_vertical_ellipsis', array( $this, 'validate_checkbox' ) );
		add_filter( 'wpdc_validate_wpds_topic_webhook_refresh', array( $this, 'validate_webhook_refresh' ) );
		add_filter( 'wpdc_validate_wpds_ajax_refresh', array( $this, 'validate_ajax_refresh' ) );
		add_filter( 'wpdc_validate_wpds_clear_cache', array( $this, 'validate_clear_cache' ) );
	}

	/**
	 * Setup the plugin options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Validates a checkbox.
	 *
	 * @param string|int $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_checkbox( $input ) {
		return 1 === intval( $input ) ? 1 : 0;
	}

	/**
	 * Validates a number.
	 *
	 * @param string|int $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_int( $input ) {
		return intval( $input );
	}

	/**
	 * Validates the webhook_refresh checkbox.
	 *
	 * @param string|int $input The input to be validated.
	 *
	 * @return bool|int
	 */
	public function validate_webhook_refresh( $input ) {
		$this->webhook_refresh = $this->validate_checkbox( $input );
		if ( 1 === $this->webhook_refresh && empty( $this->options['webhook-secret'] ) ) {
			add_settings_error( 'wpds', 'webhook_refresh', __( 'To use a the latest_topics webhook, you must set a webhook secret key on the Webhook options tab.', 'wpds' ) );

			return 0;
		}

		return $this->webhook_refresh;
	}

	/**
	 * Validates the ajax_refresh checkbox.
	 *
	 * This isn't doing anything useful - could just use validate_checkbox.
	 *
	 * @param string|int $input The input to be validated.
	 *
	 * @return bool|int
	 */
	public function validate_ajax_refresh( $input ) {
		$this->ajax_refresh = $this->validate_checkbox( $input );

		return $this->ajax_refresh;
	}

	/**
	 * Validates the clear_cache checkbox.
	 *
	 * The point of this option is to clear the cache for a single request, this function calls an action to
	 * clear the cache and then returns 0.
	 *
	 * @param string|int $input The input to be validated.
	 *
	 * @return int
	 */
	public function validate_clear_cache( $input ) {
		do_action( 'wpds_clear_topics_cache' );

		return 0;
	}
}
