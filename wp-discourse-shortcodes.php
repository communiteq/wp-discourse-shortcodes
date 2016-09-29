<?php
/**
 * Plugin Name: WP-Discourse Shortcodes
 * Description: Hooks into the wp-discourse plugin to create shortcodes for login links to Discourse
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourseShortcodes;

// Make sure the wp-discourse plugin is loaded.
add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		require_once( __DIR__ . '/lib/utilities.php' );
		require_once( __DIR__ . '/lib/discourse-link.php' );
		require_once( __DIR__ . '/lib/discourse-latest.php' );
		require_once( __DIR__ . '/lib/discourse-groups.php' );
		require_once( __DIR__ . '/lib/discourse-message.php' );
		require_once( __DIR__ . '/lib/discourse-topic.php' );

		$wpdc_shortcodes_utilities = new \WPDiscourseShortcodes\Utilities\Utilities();
		$wpdc_shortcodes_groups    = new \WPDiscourseShortcodes\DiscourseGroups\DiscourseGroups( $wpdc_shortcodes_utilities );
		$wpdc_shortcodes_latest    = new \WPDiscourseShortcodes\DiscourseLatest\DiscourseLatest( $wpdc_shortcodes_utilities );
		$wpdc_shortcodes_link      = new \WPDiscourseShortcodes\DiscourseLink\DiscoureLink( $wpdc_shortcodes_utilities );
		$wpdc_shortcodes_message   = new \WPDiscourseShortcodes\DiscourseMessage\DiscourseMessage( $wpdc_shortcodes_utilities );
		$wpdc_shortcodes_topic     = new \WPDiscourseShortcodes\DiscourseTopic\DiscourseTopic( $wpdc_shortcodes_utilities );
	}
}

