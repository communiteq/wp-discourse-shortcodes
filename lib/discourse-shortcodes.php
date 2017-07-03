<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseShortcodes {

	/**
	 * The key for the plugin's options array.
	 *
	 * @access protected
	 * @var string
	 */
	protected $option_key = 'dclt_options';

	/**
	 * The merged options from WP Discourse and WP Discourse Latest Topics.
	 *
	 * All options are held in a single array, use a custom plugin prefix to avoid naming collisions with wp-discourse.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;

	/**
	 * The Discourse forum url.
	 *
	 * @access protected
	 * @var string
	 */
	protected $discourse_url;

	/**
	 * The options array added by this plugin.
	 *
	 * @access protected
	 * @var array
	 */
	protected $dclt_options = array(
		'dclt_cache_duration'     => 10,
		'dclt_webhook_refresh'    => 0,
		'dclt_webhook_secret'     => '',
		'dclt_clear_topics_cache' => 0,
		'dclt_use_default_styles' => 1,
	);

	/**
	 * LatestTopics constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'wpdc_utilities_options_array', array( $this, 'add_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'plugin_scripts' ) );
	}

	/**
	 * Adds the plugin options, gets the merged wp-discourse/wp-discourse-latest-topics options, sets the discourse_url.
	 */
	public function initialize_plugin() {
		add_option( 'dclt_options', $this->dclt_options );
		$this->options       = DiscourseUtilities::get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
	}

	/**
	 * Enqueue styles.
	 */
	public function plugin_scripts() {
		if ( ! empty( $this->options['dclt_use_default_styles'] ) && 1 === intval( $this->options['dclt_use_default_styles'] ) ) {
			wp_register_style( 'dclt_styles', plugins_url( '/css/styles.css', __FILE__ ) );
			wp_enqueue_style( 'dclt_styles' );
		}
	}

	/**
	 * Hooks into 'wpdc_utilities_options_array'.
	 *
	 * This function merges the plugins options with the options array that is created in
	 * WPDiscourse\Utilities\Utilities::get_options. Doing this makes it possible to use the FormHelper function in the plugin.
	 * If you aren't using the FormHelper function, there is no need to do this.
	 *
	 * @param array $wpdc_options The unfiltered Discourse options.
	 *
	 * @return array
	 */
	public function add_options( $wpdc_options ) {
		static $merged_options = [];

		if ( empty( $merged_options ) ) {
			$added_options = get_option( $this->option_key );
			if ( is_array( $added_options ) ) {
				$merged_options = array_merge( $wpdc_options, $added_options );
			} else {
				$merged_options = $wpdc_options;
			}
		}

		return $merged_options;
	}
}