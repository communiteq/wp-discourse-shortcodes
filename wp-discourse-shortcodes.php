<?php
/**
 * Plugin Name: WP Discourse Shortcodes
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourse\Shortcodes;

use \WPDiscourse\Admin\OptionsPage as OptionsPage;
use \WPDiscourse\Admin\FormHelper as FormHelper;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
/**
 * Initializes the plugin, checks that WPDiscourse exists before requiring plugin files.
 */
function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		require_once( __DIR__ . '/lib/utilities.php' );
		require_once( __DIR__ . '/lib/discourse-shortcodes.php' );
		require_once( __DIR__ . '/lib/discourse-latest-topics.php' );
		require_once( __DIR__ . '/lib/discourse-groups.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-latest-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-groups-shortcode.php' );

		new DiscourseShortcodes();
		$latest_topics = new LatestTopics();
		$groups = new Groups();
		new DiscourseLatestShortcode( $latest_topics );
		new DiscourseGroupsShortcode( $groups );

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
