<?php

namespace WPDiscourseShortcodes\DiscoursePrefilledMessage;

class DiscoursePrefilledMessage {
	protected $utilities;
	protected $discourse_link;
	protected $options;
	protected $base_url;

	public function __construct( $utilities, $discourse_link ) {
		$this->utilities = $utilities;
		$this->discourse_link = $discourse_link;

		add_action( 'init', array( $this, 'setup' ) );
		add_shortcode( 'discourse_prefilled_message', array( $this, 'discourse_prefilled_message' ) );
	}

	public function setup() {
		$this->options  = $this->utilities->get_options();
		$this->base_url = $this->utilities->base_url( $this->options );
	}

	public function discourse_prefilled_message( $atts ) {
		$attributes = shortcode_atts( array(
			'link_text' => 'Contact Us',
			'classes'   => '',
			'title'     => '',
			'message'   => '',
			'username'  => '',
			'groupname' => '',
		), $atts );

		$link_text = ! empty( $attributes['link_text'] ) ? $attributes['link_text'] : '';
		$classes   = ! empty( $attributes['classes'] ) ? 'class="' . $attributes['classes'] . '"' : '';
		$title     = ! empty( $attributes['title'] ) ? $attributes['title'] : null;
		$message   = ! empty( $attributes['message'] ) ? $attributes['message'] : null;
		// If a comma separated list is being passed, just take the first item.
		$username  = ! empty( $attributes['username'] ) ? explode( ',', $attributes['username'] )[0] : '';
		$groupname = ! empty( $attributes['groupname'] ) ? explode( ',', $attributes['groupname'] )[0] : '';

		if ( $username && $groupname ) {
			// It can only be one or the other, for now, let's choose the username.
			$groupname = '';
		}

		if ( ! $username && ! $groupname ) {
			return '';
		}

		$message_url = $this->base_url . '/new-message';
		if ( $username ) {
			$message_url = urlencode( add_query_arg( array(
				'username' => $username,
				'title' => $title,
				'body' => $message,
			), $message_url ) );
		} else {
			$message_url = urlencode( add_query_arg( array(
				'groupname' => $groupname,
				'title' => $title,
				'body' => $message,
			), $message_url ) );
		}

		write_log( $message_url);

		$message_attributes = array(
			'link_text' => $link_text,
			'classes' => $classes,
			'login' => 'true',
			'return_path' => $message_url,
		);

		return $this->discourse_link->discourse_link( $message_attributes );
	}

}