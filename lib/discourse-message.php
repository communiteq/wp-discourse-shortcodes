<?php

namespace WPDiscourseShortcodes\DiscourseMessage;

class DiscourseMessage {
	protected $utilities;
	protected $options;
	protected $base_url;

	public function __construct( $utilities ) {
		$this->utilities = $utilities;

		add_action( 'init', array( $this, 'setup' ) );
	}

	public function setup() {
		$this->options = $this->utilities->get_options();
		$this->base_url = $this->utilities->base_url( $this->options );

		add_shortcode( 'discourse_message', array( $this, 'discourse_message' ) );
	}

	public function discourse_message( $atts ) {
		$attributes = shortcode_atts( array(
			'link_text' => 'Contact Us',
			'classes'   => '',
			'title' => '',
			'username' => '',
			'message' => '',
		), $atts, 'discourse_message' );

		$title = ! empty( $attributes['title'] ) ? $attributes['title'] : null;
		$username = ! empty( $attributes['username'] ) ? $attributes['username'] : '';
		$message = ! empty( $attributes['message'] ) ? $attributes['message'] : null;

		$sso_url     = $this->base_url . '/session/sso?return_path=';
		$return_path = urlencode( add_query_arg( array(
			'username' => $username,
			'title'    => $title,
			'body'     => $message,
		), $this->base_url . '/new-message' ) );

		$topic_url  = $sso_url . $return_path;
		$classes    = $attributes['classes'] ? 'class="' . $attributes['classes'] . '"' : '';
		$topic_link = '<a ' . $classes . ' href="' . $topic_url . '">' . $attributes['link_text'] . '</a>';

		return $topic_link;
	}
}