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
		add_action( 'init', array( $this, 'clear_groups_data' ) );
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
	 * Deletes the Discourse groups option and transients for a single request.
	 *
	 * @return null
	 */
	public function clear_groups_data() {
		if ( ! empty( $this->options['wpds_fetch_discourse_groups'] ) ) {
			delete_option( 'wpds_discourse_groups' );
			delete_transient( 'wpds_selected_groups_data' );
			delete_transient( 'wpds_formatted_groups' );

			$wpds_options                                = get_option( 'wpds_options' );
			$wpds_options['wpds_fetch_discourse_groups'] = 0;
			update_option( 'wpds_options', $wpds_options );
		}

		return null;
	}

	public function get_formatted_groups( $args ) {
		$args   = shortcode_atts( array(
			'group_list'       => '',
			'link_open_text'   => 'Join the',
			'link_close_text'  => '',
			'sso'              => 'false',
			'tile'             => 'false',
			'show_description' => 'true',
		), $args );
		$groups = $this->get_discourse_groups( $args['group_list'] );

		if ( empty( $groups ) || is_wp_error( $groups ) ) {

			return '';
		}
		$formatted_groups = $this->format_groups( $groups, $args );

		return $formatted_groups;
	}

	/**
	 * @param string $group_list Groupnames to retrieve.
	 *
	 * @return array|\WP_Error
	 */
	public function get_discourse_groups( $group_list ) {
		$discourse_groups = get_transient( 'wpds_selected_groups_data' );

		if ( empty( $discourse_groups || is_wp_error( $discourse_groups ) ) ) {
			$all_groups = $this->get_non_automatic_groups();

			if ( empty( $all_groups || is_wp_error( $all_groups ) ) ) {

				return new \WP_Error( 'wpds_response_error', 'The Discourse groups could not be retrieved.' );
			}

			if ( ! empty( $group_list ) ) {
				$chosen_groups = [];
				$selected      = array_map( 'trim', explode( ',', $group_list ) );

				foreach ( $all_groups as $group ) {
					if ( ! empty( $group['name'] ) && in_array( $group['name'], $selected, true ) ) {
						$chosen_groups[] = $group;
					}
				}
				$discourse_groups = $chosen_groups;
			} else {
				$discourse_groups = $all_groups;
			}

			set_transient( 'wpds_selected_groups_data', DAY_IN_SECONDS, $discourse_groups );
		}

		return $discourse_groups;
	}

	/**
	 * @param  array $groups An array of Discourse group data.
	 * @param array $args The shortcode args.
	 *
	 * @return mixed
	 */
	public function format_groups( $groups, $args ) {
		$output = get_transient( 'wpds_formatted_groups' );

		if ( empty( $output ) ) {
			$link_open_text  = ! empty( $args['link_open_text'] ) ? $args['link_open_text'] . ' ' : '';
			$link_close_text = ! empty( $args['link_close_text'] ) ? ' ' . $args['link_close_text'] : '';
			$tile_class      = 'true' === $args['tile'] ? ' wpds-tile' : 'wpds-no-tile';

			$output = '<div class="wpds-groups wpds-tile-wrapper"><div class="' . esc_attr( $tile_class ) . '">';
			foreach ( $groups as $group ) {
				$group_name      = ! empty( $group['name'] ) ? $group['name'] : '';
				$group_path      = "/groups/{$group_name}";
				$full_group_name = ! empty( $group['full_name'] ) ? $group['full_name'] : str_replace( '_', ' ', $group_name );
				$link_text       = $link_open_text . ' ' . $full_group_name . $link_close_text;
				$link_args       = array(
					'link_text' => $link_text,
					'path'      => $group_path,
					'classes'   => 'wpds-group-link',
					'sso'       => $args['sso'],
				);

				$output .= '<div class="wpds-group">';
				$output .= '<header>';
				$output .= '<h4 class="wpds-groupname">' . esc_html( $full_group_name ) . '</h4>';
				$output .= '</header>';

				if ( 'true' === $args['show_description'] ) {
					$output .= '<div class="wpds-group-description">' . wp_kses_post( $group['bio_raw'] ) . '</div>';
				}

				$output .= '<footer>';
				$output .= wp_kses_post( $this->discourse_link->get_discourse_link( $link_args ) ) . '</div>';
				$output .= '</footer>';
			}// End foreach().

			$output .= '</div></div>';

			set_transient( 'wpds_formatted_groups', $output, DAY_IN_SECONDS );
		}

		return apply_filters( 'wpds_formatted_groups', $output, $groups, $args );
	}

	/**
	 * Retrieves the groups from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	protected function get_non_automatic_groups() {
		$groups = get_option( 'wpds_discourse_groups' );

		if ( empty( $groups ) ) {

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
				if ( ! empty( $group ) && empty( $group['automatic'] ) ) {
					$non_automatic_groups[] = $group;
				}
			}

			$groups = $non_automatic_groups;

			update_option( 'wpds_discourse_groups', $groups );
		}

		return $groups;
	}
}
