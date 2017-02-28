<?php

namespace WPDiscourse\LatestTopics;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class LatestTopics {

	protected $option_key = 'dclt_options';

	protected $options;

	protected $dclt_options = array(
		'dclt_clear_topics_cache' => 1,
		'dclt_use_default_styles' => 1,
	);

	public function __construct() {
		add_action( 'init', array( $this, 'initialize_plugin' ) );
		add_filter( 'wpdc_utilities_options_array', array( $this, 'add_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'plugin_scripts' ) );
	}

	public function initialize_plugin() {
		add_option( 'dclt_options', $this->dclt_options );
		$this->options = DiscourseUtilities::get_options();
	}

	public function plugin_scripts() {
		if ( ! empty( $this->options['dclt_use_default_styles'] ) && 1 === intval( $this->options['dclt_use_default_styles'] ) ) {
			wp_register_style( 'dclt_styles', plugins_url( '/css/styles.css', __FILE__ ) );
			wp_enqueue_style( 'dclt_styles' );
		}
	}

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