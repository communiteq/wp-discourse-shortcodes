<?php
/**
 * Creates a Discourse link that opens a prefilled message.
 *
 * I'm not sure this needs to stay in the plugin. It would be better to create a message
 * through the Discourse API so that the user isn't required to use the Discourse composer.
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscoursePrefilledMessage {

	protected $options;

	protected $base_url;

	protected $discourse_link;

	/**
	 * DiscoursePrefilledMessage constructor.
	 *
	 * @param DiscourseLink $discourse_link
	 */
	public function __construct( $discourse_link ) {
		$this->discourse_link = $discourse_link;

		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
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
		$username  = ! empty( $attributes['username'] ) ? array_map( 'trim', explode( ',', $attributes['username'] ) )[0] : '';
		$groupname = ! empty( $attributes['groupname'] ) ? array_map( 'trim', explode( ',', $attributes['groupname'] ) )[0] : '';

		if ( empty( $this->options['enable-sso'] ) ) {

			return new \WP_Error(
				'wpds_configuration_error', 'The Discourse Prefilled Message shortcode
			requires WordPress to be enabled as the SSO Provider for your Discourse forum.'
			);
		}

		if ( empty( $username ) && empty( $groupname ) ) {

			return new \WP_Error( 'wpds_configuration_error', 'Either the username or the groupname must be supplied.' );
		}

		if ( $username && $groupname ) {
			// It can only be one or the other, for now, let's choose the username.
			$groupname = '';
		}

		$message_path = '/new-message';
		if ( $username ) {
			$message_path = urlencode(
				add_query_arg(
					array(
						'username' => $username,
						'title' => $title,
						'body' => $message,
					), $message_path
				)
			);
		} else {
			$message_path = urlencode(
				add_query_arg(
					array(
						'groupname' => $groupname,
						'title' => $title,
						'body' => $message,
					), $message_path
				)
			);
		}

		$message_attributes = array(
			'link_text' => $link_text,
			'classes' => $classes,
			'path' => $message_path,
			'sso' => 'true',
		);

		return $this->discourse_link->get_discourse_link( $message_attributes );
	}

}
