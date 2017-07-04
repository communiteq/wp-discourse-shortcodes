<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseGroups {
	use Utilities;

	protected $options;
	protected $discourse_link;
	protected $prefilled_message;
	protected $base_url;
	protected $api_key;
	protected $api_username;


	public function __construct( $discourse_link, $prefilled_message ) {
		$this->discourse_link    = $discourse_link;
		$this->prefilled_message = $prefilled_message;
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options( $prefilled_message ) {
		$this->options      = $this->get_options();
		$this->base_url     = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
		$this->api_key      = ! empty( $this->options['api-key'] ) ? $this->options['api-key'] : null;
		$this->api_username = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : null;
	}

	public function get_discourse_groups( $group_names ) {
		$discourse_groups = $this->get_all_groups();

		if ( ! empty( $group_names ) ) {
			$chosen_groups = [];
			$selected      = array_map( 'trim', explode( ',', $group_names ) );

			foreach ( $discourse_groups as $group ) {
				if ( in_array( $group['name'], $selected, true ) ) {
					$chosen_groups[] = $group;
				}
			}

			return $chosen_groups;
		} else {

			return $discourse_groups;
		}
	}

	/**
	 * @param array $groups The Discourse groups.
	 *
	 * @return string
	 */
	public function format_groups( $groups, $attributes ) {

		$output = '<div class="wpdc-shortcodes-groups">';
		foreach ( $groups as $group ) {
			$group_path      = '/groups/' . esc_attr( $group['name'] );
			$full_group_name = ! empty( $group['full_name'] ) ? $group['full_name'] : str_replace( '_', ' ', $group['name'] );
			$link_open_text  = ! empty( $attributes['link_open_text'] ) ? $attributes['link_open_text'] . ' ' : '';
			$link_close_text = ! empty( $attributes['link_close_text'] ) ? ' ' . $attributes['link_close_text'] : '';
			$link_text       = esc_html( $link_open_text ) . ' ' . esc_html( $full_group_name ) . esc_html( $link_close_text );

			$output .= '<div class="wpdc-shortcodes-group clearfix">';
			$output .= '<h3 class="wpdc-shortcodes-groupname">' . $full_group_name . '</h3>';

			$output .= '<div class="wpdc-shortcodes-group-description">';
			$output .= wp_kses_post( $group['bio_raw'] );
			$output .= '</div>';

			if ( ! empty( $this->options['enable-sso'] ) &&
			     ! empty( $attributes['link_type'] ) && 'message' === $attributes['link_type'] &&
			     ! empty( $attributes['allow_membership_requests'] )
			) {
				$message_args = array(
					'title'     => 'Request to join the ' . $full_group_name . ' group',
					'classes'   => 'wpdc-shortcodes-message-link',
					'groupname' => $group['name'],
					'link_text' => $link_text,
				);

				$output .= $this->prefilled_message->discourse_prefilled_message( $message_args );
			} elseif ( ! empty( $attributes['link_type'] ) && 'link' === $attributes['link_type'] ) {
				$message_args = array(
					'link_text' => $link_text,
					'path'      => $group_path,
					'classes'   => 'wpdc-shortcodes-visit-link',
				);

				$output .= $this->discourse_link->get_discourse_link( $message_args );
			}
			$output .= '</div>';

		}

		$output .= '</div>';

		return apply_filters( 'wpdc_shortcodes_groups', $output );
	}

	/**
	 * Retrieves the groups from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	public
	function get_all_groups() {
		$groups       = get_option( 'wpds_discourse_groups' );
		$fetch_groups = false;

		if ( ! empty( $this->options['wpds_fetch_discourse_groups'] ) ) {
			// Set the wpds_fetch_discourse_groups option to 0 after a single request.
			$wpds_options                                = get_option( 'wpds_options' );
			$wpds_options['wpds_fetch_discourse_groups'] = 0;
			update_option( 'wpds_options', $wpds_options );

			$fetch_groups = true;
		}

		if ( empty( $groups ) || $fetch_groups ) {

			if ( empty( $this->base_url ) || empty( $this->api_key ) || empty( $this->api_username ) ) {

				return new \WP_Error( 'discourse_configuration_error', 'Unable to retrieve groups from Discourse. The WP Discourse plugin is
				not properly configured.' );
			}

			$groups_url = $this->base_url . '/admin/groups.json';
			$groups_url = add_query_arg( array(
				'api_key'      => $this->api_key,
				'api_username' => $this->api_username,
			), $groups_url );

			$groups_url = esc_url_raw( $groups_url );
			$response   = wp_remote_get( $groups_url );

			if ( ! $this->validate( $response ) ) {

				return new \WP_Error( 'discourse_invalid_response', 'An invalid response was returned when retrieving the Discourse groups.' );
			}

			$groups               = json_decode( wp_remote_retrieve_body( $response ), true );
			$non_automatic_groups = [];

			foreach ( $groups as $group ) {
				if ( empty( $group['automatic'] ) ) {
					$non_automatic_groups[] = $group;
				}
			}

			$groups = $non_automatic_groups;

			update_option( 'wpds_discourse_groups', $groups );
		}

		return $groups;
	}

}