<?php
/**
 * Plugin Name: WP-Discourse Shortcodes
 * Description: Hooks into the wp-discourse plugin to create shortcodes for login links to Discourse
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourseShortcodes;

use WPDiscourseShortcodes\PluginSetup\PluginSetup;
use WPDiscourseShortcodes\DiscourseGroups\DiscourseGroups;
use WPDiscourseShortcodes\DiscourseLatest\DiscourseLatest;
use WPDiscourseShortcodes\DiscourseLink\DiscourseLink;
use WPDiscourseShortcodes\DiscoursePrefilledMessage\DiscoursePrefilledMessage;

// Make sure the wp-discourse plugin is loaded.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		require_once( __DIR__ . '/lib/plugin-setup.php' );
		require_once( __DIR__ . '/lib/utilities.php' );
		require_once( __DIR__ . '/lib/discourse-link.php' );
		require_once( __DIR__ . '/lib/discourse-latest.php' );
		require_once( __DIR__ . '/lib/discourse-groups.php' );
		require_once( __DIR__ . '/lib/discourse-prefilled-message.php' );
		require_once( __DIR__ . '/lib/discourse-remote-message.php' );

		$wpdc_shortcodes_utilities      = new Utilities\Utilities();
		$wpdc_shortcodes_discourse_link = new DiscourseLink( $wpdc_shortcodes_utilities );
		$wpdc_shortcodes_prefilled_message = new DiscoursePrefilledMessage( $wpdc_shortcodes_utilities, $wpdc_shortcodes_discourse_link );
		$wpdc_shortcodes_remote_message = new DiscourseRemoteMessage\DiscourseRemoteMessage( $wpdc_shortcodes_utilities );
		new PluginSetup();
		new DiscourseGroups( $wpdc_shortcodes_utilities, $wpdc_shortcodes_remote_message );
		new DiscourseLatest( $wpdc_shortcodes_utilities );
	}
}

