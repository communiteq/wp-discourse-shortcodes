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
		require_once( __DIR__ . '/lib/discourse-topics.php' );
		require_once( __DIR__ . '/lib/discourse-groups.php' );
		require_once( __DIR__ . '/lib/discourse-topic-formatter.php' );
		require_once( __DIR__ . '/lib/discourse-rss.php' );
		require_once( __DIR__ . '/lib/discourse-rss-formatter.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-topics-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-rss-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-groups-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-link-shortcode.php' );
		require_once( __DIR__ . '/lib/shortcodes/discourse-prefilled-message-shortcode.php' );

		new DiscourseShortcodes();
		$topic_formatter = new DiscourseTopicFormatter();
		$discourse_topics = new DiscourseTopics( $topic_formatter );
		$rss_formatter = new DiscourseRSSFormatter();
		$discourse_rss = new DiscourseRSS( $rss_formatter );
		$discourse_link = new DiscourseLink();
		$prefilled_message = new DiscoursePrefilledMessage( $discourse_link );
		$discourse_groups = new DiscourseGroups( $discourse_link, $prefilled_message );
		new DiscourseLinkShortcode( $discourse_link );
		new DiscoursePrefilledMessageShortcode( $prefilled_message );
		new DiscourseTopicsShortcode( $discourse_topics );
		new DiscourseRSSShortcode( $discourse_rss );
		new DiscourseGroupsShortcode( $discourse_groups );

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
