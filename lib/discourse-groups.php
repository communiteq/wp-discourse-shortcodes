<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class Groups {
	use Utilities;

	protected $options;
	protected $base_url;
	protected $api_key;
	protected $api_username;


	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options      = $this->get_options();
		$this->base_url     = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
		$this->api_key      = ! empty( $this->options['api-key'] ) ? $this->options['api-key'] : null;
		$this->api_username = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : null;
	}

	/**
	 * Retrieves the groups from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	public function get_discourse_groups() {
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

			$groups        = json_decode( wp_remote_retrieve_body( $response ), true );
			$non_automatic_groups = [];

			foreach ( $groups as $group ) {
				if ( empty( $group['automatic'] ) ) {
					$non_automatic_groups[] = $group;
				}
			}

			$groups = $non_automatic_groups;

			update_option( 'wpds_discourse_groups', $groups );
		}

		write_log( 'non automatic groups', $groups );
		return $groups;
	}
}