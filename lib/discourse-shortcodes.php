<?php

namespace WPDiscourse\Shortcodes;

class DiscourseShortcodes {
	use Utilities;

	/**
	 * The key for the plugin's options array.
	 *
	 * @access protected
	 * @var string
	 */
	protected $option_key = 'wpds_options';

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
	 * The options array added by this plugin.
	 *
	 * @access protected
	 * @var array
	 */
	protected $wpds_options = array(
//		'wpds_topic_cache_duration'     => 10,
//		'wpds_clear_topics_cache' => 0,
		'wpds_display_private_topics' => 0,
		'wpds_use_default_styles' => 1,
//		'wpds_topic_webhook_refresh'    => 0,
		'wpds_webhook_secret'     => '',
		'wpds_fetch_discourse_groups' => 0,
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
		add_option( 'wpds_options', $this->wpds_options );
		$this->options       = $this->get_options();
	}

	/**
	 * Enqueue styles.
	 */
	public function plugin_scripts() {
		if ( ! empty( $this->options['wpds_use_default_styles'] ) ) {
			wp_register_style( 'wpds_styles', plugins_url( '/css/styles.css', __FILE__ ) );
			wp_enqueue_style( 'wpds_styles' );
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