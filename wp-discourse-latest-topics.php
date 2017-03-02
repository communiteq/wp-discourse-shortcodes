<?php
/**
 * Plugin Name: WP Discourse Latest Topics
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourse\LatestTopics;

use \WPDiscourse\Admin\OptionsPage as OptionsPage;
use \WPDiscourse\Admin\FormHelper as FormHelper;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
/**
 * Initializes the plugin, checks that WPDiscourse exists before requiring plugin files.
 */
function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {

		require_once( __DIR__ . '/lib/discourse-latest-topics.php' );
		require_once( __DIR__ . '/lib/discourse-latest-shortcode.php' );

		$latest_topics = new LatestTopics();
		new DiscourseLatestShortcode( $latest_topics );

		// Only load admin files in admin.
		if ( is_admin() ) {
			require_once( __DIR__ . '/admin/admin.php' );
			require_once( __DIR__ . '/admin/settings-validator.php' );

			$options_page = OptionsPage::get_instance();
			$form_helper  = FormHelper::get_instance();

			new SettingsValidator();
			new Admin( $options_page, $form_helper );
		}
	}
}