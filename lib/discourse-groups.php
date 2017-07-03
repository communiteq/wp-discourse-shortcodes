<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class Groups {
	protected $options;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Retrieves the groups from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	public function get_discourse_groups( $group_list, $clear_cache ) {

		$groups = get_transient( 'discourse_groups' );

		if ( empty( $groups ) || 'true' === $clear_cache ) {

			$api_key      = ! empty( $this->options['api-key'] ) ? $this->options['api-key'] : '';
			$api_username = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : '';
			$base_url     = ! empty( $this->options['url'] ) ? $this->options['url'] : '';


			$groups_url = $base_url . '/admin/groups.json';
			$groups_url = add_query_arg( array(
				'api_key'      => $api_key,
				'api_username' => $api_username,
			), $groups_url );

			$groups_url = esc_url_raw( $groups_url );
			$response   = wp_remote_get( $groups_url );

			if ( ! DiscourseUtilities::validate( $response ) ) {

				// Todo: return error.
				return null;
			}

			$groups = json_decode( wp_remote_retrieve_body( $response ), true );
			write_log( 'groups', $groups );
			$chosen_groups = [];

			if ( $group_list ) {
				$group_array = explode( ',', $group_list );
				foreach ( $groups as $group ) {
					if ( in_array( $group['name'], $group_array, true ) ) {
						$chosen_groups[] = $group;
					}
				}
			} else {
				// Select the 'mentionable' groups.
				foreach ( $groups as $group ) {
//					if ( $group['mentionable'] ) {
					if ( true ) {
						$chosen_groups[] = $group;
					}
				}
			}

//			foreach ( $chosen_groups as $key => $group ) {
//				$chosen_groups[ $key ]['description'] = $this->get_group_description( $group['name'] );
//			}

			$groups = $chosen_groups;
			set_transient( 'discourse_groups', $groups, DAY_IN_SECONDS );
		}

		return $groups;
	}
}