<?php

namespace WPDiscourseShortcodes\PluginSetup;

class PluginSetup {

	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'plugin_scripts' ) );
	}

	public function plugin_scripts() {
		wp_register_style( 'wpdc-shortcode-styles', plugins_url( '/../css/styles.css', __FILE__) );
		wp_enqueue_style( 'wpdc-shortcode-styles' );
	}
}
