<?php
/**
 * Plugin Name: WP-Discourse Shortcodes
 * Description: Hooks into the wp-discourse plugin to create shortcodes for login links to Discourse
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourseShortcodes;

// Make sure the wp-discourse plugin is loaded.
use WPDiscourseShortcodes\PluginSetup\PluginSetup;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		require_once( __DIR__ . '/lib/plugin-setup.php' );
		require_once( __DIR__ . '/lib/utilities.php' );
		require_once( __DIR__ . '/lib/discourse-link.php' );
		require_once( __DIR__ . '/lib/discourse-latest.php' );
		require_once( __DIR__ . '/lib/discourse-groups.php' );
		require_once( __DIR__ . '/lib/discourse-message.php' );
		require_once( __DIR__ . '/lib/discourse-topic.php' );
		require_once( __DIR__ . '/lib/discourse-remote-message.php' );

		$wpdc_shortcodes_utilities = new Utilities\Utilities();
//		$wpdc_shortcodes_message   = new DiscourseMessage\DiscourseMessage( $wpdc_shortcodes_utilities );
		$wpdc_shortcodes_remote_message =  new DiscourseRemoteMessage\DiscourseRemoteMessage( $wpdc_shortcodes_utilities );
		new PluginSetup();
		new DiscourseGroups\DiscourseGroups( $wpdc_shortcodes_utilities, $wpdc_shortcodes_remote_message );
		new DiscourseLatest\DiscourseLatest( $wpdc_shortcodes_utilities );
		new DiscourseLink\DiscoureLink( $wpdc_shortcodes_utilities );
		new DiscourseTopic\DiscourseTopic( $wpdc_shortcodes_utilities );
	}
}

