<?php
/**
 Plugin Name: WP Discourse Latest Topics
 Version: 0.1
 Author: scossar
 */

namespace WPDiscourse\LatestTopics;

use \WPDiscourse\Admin\OptionsPage as OptionsPage;
use \WPDiscourse\Admin\FormHelper as FormHelper;
use WPDiscourse\Admin\SettingsValidator;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );
function init() {
	require_once( __DIR__ . '/lib/discourse-latest-topics.php' );
	require_once( __DIR__ . '/lib/discourse-latest-shortcode.php' );
	new LatestTopics();
	new DiscourseLatestShortcode();

	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		if ( is_admin() ) {
			require_once( __DIR__ . '/admin/admin.php' );
			require_once( __DIR__ . '/admin/settings-validator.php' );

			$options_page = OptionsPage::get_instance();
			$form_helper = FormHelper::get_instance();

			new SettingsValidator();
			new Admin( $options_page, $form_helper );
		}
	}
}