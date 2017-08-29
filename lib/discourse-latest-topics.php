<?php

namespace WPDiscourse\Shortcodes;

class LatestTopics {
	use Utilities;

	/**
	 * The key for the plugin's options array.
	 *
	 * @access protected
	 * @var string
	 */
	protected $option_key = 'wpds_options';

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
	protected $api_key;
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
		if ( ! empty( $this->options['wpds_topic_webhook_refresh'] ) ) {
			register_rest_route( 'wp-discourse/v1', 'latest-topics', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'update_latest_topics' ),
				),
//				array(
//					'methods'  => \WP_REST_Server::READABLE,
//					'callback' => array( $this, 'get_latest_topics' ),
//				)
			) );
		}
	}

	/**
	 * Create latest topics.
	 *
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

		$latest = $this->latest_topics();

		set_transient( 'wpds_latest_topics', $latest, DAY_IN_SECONDS );

		return null;
	}

	/**
	 * Get the latest topics from either from the stored transient, or from Discourse.
	 *
	 * @return array
	 */
	public function get_latest_topics() {
		$discourse_topics = get_transient( 'wpds_latest_topics' );
		$force            = ! empty( $this->options['wpds_clear_topics_cache'] );

		if ( $force ) {
			// Reset the force option.
			$plugin_options                            = get_option( $this->option_key );
			$plugin_options['wpds_clear_topics_cache'] = 0;

			update_option( $this->option_key, $plugin_options );
		}

		if ( empty( $discourse_topics ) || $force ) {

			$discourse_topics = $this->latest_topics();
			$cache_duration   = ! empty( $this->options['wpds_topic_cache_duration'] ) ? $this->options['wpds_topic_cache_duration'] : 10;

			if ( ! empty( $discourse_topics ) || ! is_wp_error( $discourse_topics ) ) {
				set_transient( 'wpds_latest_topics', $discourse_topics, $cache_duration * MINUTE_IN_SECONDS );
			}
		}

		return $discourse_topics;
	}

	/**
	 * Gets the latest topics from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	protected function latest_topics() {
		if ( empty( $this->discourse_url ) ) {

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

		if ( ! validate( $remote ) ) {

			return new \WP_Error( 'wp_discourse_response_error', 'An error was returned from Discourse when fetching the latest topics.' );
		}

		return json_decode( wp_remote_retrieve_body( $remote ), true );
	}
}
