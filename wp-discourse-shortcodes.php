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
		require_once( __DIR__ . '/lib/discourse-link.php' );
		require_once( __DIR__ . '/lib/discourse-prefilled-message.php' );
		require_once( __DIR__ . '/lib/discourse-latest-topics.php' );
		require_once( __DIR__ . '/lib/discourse-groups.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-latest-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-groups-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-link-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-prefilled-message-shortcode.php' );

		new DiscourseShortcodes();
		$latest_topics = new LatestTopics();
		$discourse_link = new DiscourseLink();
		$prefilled_message = new DiscoursePrefilledMessage( $discourse_link );
		$discourse_groups = new DiscourseGroups( $discourse_link, $prefilled_message );
		new DiscourseLinkShortcode( $discourse_link );
		new DiscoursePrefilledMessageShortcode( $prefilled_message );
		new DiscourseLatestShortcode( $latest_topics );
		new DiscourseGroupsShortcode( $discourse_groups );

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
