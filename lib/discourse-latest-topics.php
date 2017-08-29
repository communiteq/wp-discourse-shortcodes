<?php

namespace WPDiscourse\Shortcodes;

class LatestTopics {
	use Utilities;

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
	protected $discourse_url;

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
	 * LatestTopics constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_topic_route' ) );
	}

	/**
	 * Adds the plugin options, gets the merged wp-discourse/wp-discourse-latest-topics options, sets the discourse_url.
	 */
	public function setup_options() {
		$this->options       = $this->get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
		$this->api_key       = ! empty( $this->options['api-key'] ) ? $this->options['api-key'] : null;
		$this->api_username  = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : null;
	}

	/**
	 * Initializes a WordPress Rest API route and endpoint.
	 */
	public function initialize_topic_route() {
		if ( ! empty( $this->options['wpds_webhook_secret'] ) ) {
			register_rest_route( 'wp-discourse/v1', 'latest-topics', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_latest_topics' ),
				),
			) );
		}
	}

	/**
	 * Update latest topics transient.
	 *
	 * @param \WP_REST_Request $data
	 *
	 * @return null
	 */
	public function update_latest_topics( $data ) {
		$data = $this->verify_discourse_webhook_request( $data );

		if ( is_wp_error( $data ) ) {

			return new \WP_Error( 'discourse_response_error', 'There was an error returned from Discourse when processing the
			latest_topics webhook.' );
		}

		$latest = $this->fetch_latest_topics();

		set_transient( 'wpds_latest_topics', $latest, DAY_IN_SECONDS );

		return null;
	}

	/**
	 * Get the latest topics from either from the stored transient, or from Discourse.
	 *
	 * @return array|null
	 */
	public function get_latest_topics() {
		$latest_topics = get_transient( 'wpds_latest_topics' );

		if ( empty( $latest_topics ) ) {

			$latest_topics = $this->fetch_latest_topics();


			if ( ! empty( $latest_topics ) && ! is_wp_error( $latest_topics ) ) {

				set_transient( 'wpds_latest_topics', $latest_topics, DAY_IN_SECONDS );
			} else {

				return null;
			}
		}

		return $latest_topics;
	}

	/**
	 * Fetch the latest topics from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	protected function fetch_latest_topics() {
		if ( empty( $this->discourse_url ) || empty( $this->api_key ) || empty( $this->api_username ) ) {

			return new \WP_Error( 'wp_discourse_configuration_error', 'The WP Discourse plugin is not properly configured.' );
		}

		$latest_url = $this->discourse_url . '/latest.json';
		if ( ! empty( $this->options['wpds_display_private_topics'] ) ) {
			$latest_url = add_query_arg( array(
				'api_key'      => $this->api_key,
				'api_username' => $this->api_username,
			), $latest_url );
		}

		$latest_url = esc_url_raw( $latest_url );

		$remote = wp_remote_get( $latest_url );

		if ( ! $this->validate( $remote ) ) {

			return new \WP_Error( 'wp_discourse_response_error', 'An error was returned from Discourse when fetching the latest topics.' );
		}

		return json_decode( wp_remote_retrieve_body( $remote ), true );
	}
}
