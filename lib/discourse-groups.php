<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseGroups {

	/**
	 * The merged options from WP Discourse and WP Discourse Shortcodes.
	 *
	 * All options are held in a single array, use a custom plugin prefix to avoid naming collisions with wp-discourse.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;

	/**
	 * The Discourse forum url.
	 *
	 * @access protected
	 * @var string
	 */
	protected $base_url;

	/**
	 * The Discourse API key.
	 *
	 * @access protected
	 * @var string
	 */
	protected $api_key;

	/**
	 * The Discourse api_username.
	 *
	 * @access protected
	 * @var string
	 */
	protected $api_username;

	/**
	 * In instance of the DiscourseLink class.
	 *
	 * @access protected
	 * @var DiscourseLink
	 */
	protected $discourse_link;

	/**
	 * DiscourseGroups constructor.
	 *
	 * @param DiscourseLink $discourse_link An instance of DiscourseLink.
	 */
	public function __construct( $discourse_link ) {
		$this->discourse_link = $discourse_link;
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	/**
	 * Setup the plugin options.
	 */
	public function setup_options() {
		$this->options      = DiscourseUtilities::get_options();
		$this->base_url     = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
		$this->api_key      = ! empty( $this->options['api-key'] ) ? $this->options['api-key'] : null;
		$this->api_username = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : null;
	}

	/**
	 * @param string $group_names An optional string of groupnames to retrieve.
	 *
	 * @return array|mixed|null|object
	 */
	public function get_discourse_groups( $group_names = '' ) {
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
	 * @param  array $groups An array of Discourse group data.
	 * @param array $args The shortcode args.
	 *
	 * @return mixed
	 */
	public function format_groups( $groups, $args ) {

		$output = '<div class="wpds-groups-list">';
		foreach ( $groups as $group ) {
			$group_path      = '/groups/' . esc_attr( $group['name'] );
			$full_group_name = ! empty( $group['full_name'] ) ? $group['full_name'] : str_replace( '_', ' ', $group['name'] );
			$link_open_text  = ! empty( $args['link_open_text'] ) ? $args['link_open_text'] . ' ' : '';
			$link_close_text = ! empty( $args['link_close_text'] ) ? ' ' . $args['link_close_text'] : '';
			$link_text       = esc_html( $link_open_text ) . ' ' . esc_html( $full_group_name ) . esc_html( $link_close_text );

			$output .= '<div class="wpds-group clearfix">';
			$output .= '<h3 class="wpds-groupname">' . $full_group_name . '</h3>';

			$output .= '<div class="wpds-group-description">';
			$output .= wp_kses_post( $group['bio_raw'] );
			$output .= '</div>';

			$link_args = array(
				'link_text' => $link_text,
				'path'      => $group_path,
				'classes'   => 'wpds-group-link',
			);
			$output    .= $this->discourse_link->get_discourse_link( $link_args );
			$output    .= '</div>';

		}// End foreach().

		$output .= '</div>';

		return apply_filters( 'wpds_formatted_groups', $output, $groups, $args );
	}

	/**
	 * Retrieves the groups from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	public
	function get_all_groups() {
		$groups = get_option( 'wpds_discourse_groups' );
		$force  = false;

		if ( ! empty( $this->options['wpds_fetch_discourse_groups'] ) ) {
			// Set the wpds_fetch_discourse_groups option to 0 after a single request.
			$wpds_options                                = get_option( 'wpds_options' );
			$wpds_options['wpds_fetch_discourse_groups'] = 0;
			update_option( 'wpds_options', $wpds_options );

			$force = true;
		}

		if ( empty( $groups ) || $force ) {

			if ( empty( $this->base_url ) || empty( $this->api_key ) || empty( $this->api_username ) ) {

				return new \WP_Error( 'wpds_configuration_error', 'Unable to retrieve groups from Discourse. The WP Discourse plugin is
				not properly configured.' );
			}

			$groups_url = $this->base_url . '/admin/groups.json';
			$groups_url = esc_url_raw( add_query_arg( array(
				'api_key'      => $this->api_key,
				'api_username' => $this->api_username,
			), $groups_url ) );

			$response = wp_remote_get( $groups_url );

			if ( ! DiscourseUtilities::validate( $response ) ) {

				return new \WP_Error( 'wpds_invalid_response', 'An invalid response was returned when retrieving the Discourse groups.' );
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
