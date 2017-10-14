<?php

namespace WPDiscourse\Shortcodes;

class DiscoursePrefilledMessage {
	use Utilities;

	protected $options;

	protected $base_url;

	protected $discourse_link;

	public function __construct( $discourse_link ) {
		$this->discourse_link = $discourse_link;

		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = $this->get_options();
		$this->base_url = ! empty( $this->options['url'] ) ? $this->options['url'] : '';
	}

	public function discourse_prefilled_message( $attributes ) {
		$attributes = shortcode_atts(
			array(
				'link_text' => 'Contact Us',
				'classes'   => '',
				'title'     => '',
				'message'   => '',
				'username'  => '',
				'groupname' => '',
			), $attributes
		);

		$link_text = ! empty( $attributes['link_text'] ) ? $attributes['link_text'] : '';
		$classes   = ! empty( $attributes['classes'] ) ? $attributes['classes'] : '';
		$title     = ! empty( $attributes['title'] ) ? $attributes['title'] : null;
		$message   = ! empty( $attributes['message'] ) ? $attributes['message'] : null;
		// If a comma separated list is being passed, just take the first item.
		$username  = ! empty( $attributes['username'] ) ? explode( ',', $attributes['username'] )[0] : '';
		$groupname = ! empty( $attributes['groupname'] ) ? explode( ',', $attributes['groupname'] )[0] : '';

		if ( empty( $this->options['enable-sso'] ) ) {

			return new \WP_Error(
				'discourse_shortcode_configuration_error', 'The Discourse Prefilled Message shortcode
			requires WordPress to be enabled as the SSO Provider for your Discourse forum.'
			);
		}

		if ( empty( $username ) && empty( $groupname ) ) {

			return new \WP_Error( 'discourse_shortcode_configuration_error', 'Either the username or the groupname must be supplied.' );
		}

		if ( $username && $groupname ) {
			// It can only be one or the other, for now, let's choose the username.
			$groupname = '';
		}

		$message_url = $this->base_url . '/new-message';
		if ( $username ) {
			$message_url = urlencode(
				add_query_arg(
					array(
						'username' => $username,
						'title' => $title,
						'body' => $message,
					), $message_url
				)
			);
		} else {
			$message_url = urlencode(
				add_query_arg(
					array(
						'groupname' => $groupname,
						'title' => $title,
						'body' => $message,
					), $message_url
				)
			);
		}

		$message_attributes = array(
			'link_text' => $link_text,
			'classes' => $classes,
			'login' => 'true',
			'return_path' => $message_url,
		);

		return $this->discourse_link->get_discourse_link( $message_attributes );
	}

}
