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
	 * An instance of the TopicFormatter class.
	 *
	 * @access protected
	 * @var TopicFormatter
	 */
	protected $topic_formatter;

	/**
	 * LatestTopics constructor.
	 *
	 * @param TopicFormatter $topic_formatter An instance of TopicFormatter.
	 */
	public function __construct( $topic_formatter ) {
		$this->topic_formatter = $topic_formatter;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_topic_route' ) );
		// Todo: workaround for accessing rss URLs with a port number. Remove this code!
		if ( defined( 'DEV_MODE') && 'DEV_MODE' ) {
			add_filter( 'http_request_args', function ( $args ) {
				$args['reject_unsafe_urls'] = false;

				return $args;
			} );
		}
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
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_latest_topics' ),
				)
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
	 * @return string|null
	 */
	public function get_latest_topics() {
		$latest_topics = get_transient( 'wpds_latest_topics' );
		$force            = ! empty( $this->options['wpds_clear_topics_cache'] );

		if ( $force ) {
			// Reset the force option.
			$plugin_options                            = get_option( $this->option_key );
			$plugin_options['wpds_clear_topics_cache'] = 0;

			update_option( $this->option_key, $plugin_options );
		}

		if ( empty( $latest_topics ) || $force ) {

			$latest_topics = $this->fetch_latest_topics();


			if ( ! empty( $latest_topics ) && ! is_wp_error( $latest_topics ) ) {

				set_transient( 'wpds_latest_topics', $latest_topics, DAY_IN_SECONDS );
			} else {

				return null;
			}
		}

		$formatted_topics = $this->topic_formatter->format_topics( $latest_topics, array(
			'max_topics'      => 5,
			'display_avatars' => 'true',
		) );

		return $formatted_topics;
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

		$latest_url = $this->discourse_url . '/latest.rss';

		include_once(ABSPATH . WPINC . '/feed.php');
		$feed = fetch_feed( $latest_url );
		$maxitems = 0;
		if ( is_wp_error( $feed ) ) {

			return new \WP_Error( 'wp_discourse_rss_error', 'An RSS feed was not returned by Discourse.' );
		}

		$maxitems = $feed->get_item_quantity( 5 );
		write_log('max items', $maxitems );
		$feed_items = $feed->get_items( 0, $maxitems );
		foreach ( $feed_items as $item ) {
			$title = $item->get_title();
			$category = $item->get_category();
			$author = $item->get_author();
			$date = $item->get_date();
			$description = $item->get_description();
			write_log('title', $title);
			write_log('category', $category);
			write_log('author', $author);
			write_log('date', $date);
			write_log('description', $description);
		}

	}
}
