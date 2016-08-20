<?php
/**
 * Plugin Name: WP-Discourse Shortcodes
 * Description: Hooks into the wp-discourse plugin to create shortcodes for login links to Discourse
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourseShortcodes\Shortcodes;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		$discourse_shortcodes = new \WPDiscourseShortcodes\Shortcodes\WPDiscourseShortcodes();
	}
}

class WPDiscourseShortcodes {
	protected $options;
	protected $base_url;

	public function __construct() {
		$this->options  = get_option( 'discourse' );
		$this->base_url = array_key_exists( 'url', $this->options ) ? $this->options['url'] : '';

		add_shortcode( 'discourse_link', array( $this, 'discourse_link' ) );
		add_shortcode( 'discourse_topic', array( $this, 'discourse_topic' ) );
		add_shortcode( 'discourse_message', array( $this, 'discourse_message' ) );
	}

	public function discourse_link( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'link_text'   => 'Visit Our Forum',
			'return_path' => '/',
			'classes'     => '',
			'login'       => true,
		), $atts );

		if ( 'false' === $parsed_attributes['login'] ) {
			$url = $this->base_url . $parsed_attributes['return_path'];
		} else {
			$url = esc_url_raw( $this->base_url . '/session/sso?return_path=' . $parsed_attributes['return_path'] );
		}

		$classes = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';

		$discourse_link = '<a ' . $classes . ' href="' . $url . '">' . $parsed_attributes['link_text'] . '</a>';

		return $discourse_link;
	}

	public function discourse_topic( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'link_text' => 'Start a topic on our forum',
			'classes'   => '',
		), $atts );

		$title    = isset( $atts['title'] ) ? $atts['title'] : null;
		$body     = isset( $atts['body'] ) ? $atts['body'] : null;
		$category = isset( $atts['category'] ) ? $atts['category'] : null;

		$sso_url     = $this->base_url . '/session/sso?return_path=';
		$return_path = urlencode( add_query_arg( array(
			'title'    => $title,
			'body'     => $body,
			'category' => $category,
		), $this->base_url . '/new-topic' ) );

		$topic_url   = $sso_url . $return_path;

		$classes = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';

		$topic_link = '<a ' . $classes . ' href="' . $topic_url . '">' . $parsed_attributes['link_text'] . '</a>';

		return $topic_link;
	}

	public function discourse_message( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'link_text' => 'Contact Us',
			'classes'   => '',
		), $atts );

		$title    = isset( $atts['title'] ) ? $atts['title'] : null;
		$username     = isset( $atts['username'] ) ? $atts['username'] : null;
		$message = isset( $atts['message'] ) ? $atts['message'] : null;

		$sso_url     = $this->base_url . '/session/sso?return_path=';
		$return_path = urlencode( add_query_arg( array(
			'username' => $username,
			'title'    => $title,
			'body'     => $message,
		), $this->base_url . '/new-message' ) );

		$topic_url   = $sso_url . $return_path;

		$classes = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';

		$topic_link = '<a ' . $classes . ' href="' . $topic_url . '">' . $parsed_attributes['link_text'] . '</a>';

		return $topic_link;
	}
}
