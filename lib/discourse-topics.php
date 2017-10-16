<?php
/**
 * Gets and returns formatted Discourse topics.
 *
 * Sets up the REST API route for updating topics through an optional webhook.
 * Topics are formatted based on args passed to the `get_topics` function.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseTopics
 *
 * @package WPDiscourse\Shortcodes
 */
class DiscourseTopics {

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
	 * The Discourse forum URL.
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
	 * @var DiscourseTopicFormatter
	 */
	protected $topic_formatter;

	/**
	 * LatestTopics constructor.
	 *
	 * @param DiscourseTopicFormatter $topic_formatter An instance of DiscourseTopicFormatter.
	 */
	public function __construct( $topic_formatter ) {
		$this->topic_formatter = $topic_formatter;

		// A flag set for webhook requests.
		add_option( 'wpds_update_latest', 1 );
		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_topic_route' ) );
	}

	/**
	 * Sets up the plugin options.
	 */
	public function setup_options() {
		$this->options       = DiscourseUtilities::get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
		$this->api_key       = ! empty( $this->options['api-key'] ) ? $this->options['api-key'] : null;
		$this->api_username  = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : null;
	}

	/**
	 * Initializes a WordPress Rest API route and endpoint.
	 */
	public function initialize_topic_route() {
		if ( ! empty( $this->options['wpds_topic_webhook_refresh'] ) || ! empty( $this->options['wpds_ajax_refresh'] ) ) {
			register_rest_route(
				'wp-discourse/v1', 'latest-topics', array(
					array(
						'methods'  => \WP_REST_Server::CREATABLE,
						'callback' => array( $this, 'set_update_flag' ),
					),
					array(
						'methods'  => \WP_REST_Server::READABLE,
						'callback' => array( $this, 'get_ajax_topics' ),
					),
				)
			);
		}
	}

	/**
	 * Sets the wpds_update_latest flag on receiving a Topic webhook request from Discourse.
	 *
	 * @param \WP_REST_Request $data The webhook data returned from Discourse.
	 *
	 * @return null|\WP_Error
	 */
	public function set_update_flag( $data ) {
		$data = DiscourseUtilities::verify_discourse_webhook_request( $data );

		if ( is_wp_error( $data ) ) {

			return new \WP_Error(
				'wpds_response_error', 'There was an error returned from Discourse when processing the
			latest_topics webhook.'
			);
		}

		update_option( 'wpds_update_latest', 1 );

		return null;
	}

	/**
	 * Returns the formatted topics - triggered by call from the client.
	 *
	 * @param \WP_REST_Request $request The request sent through the WordPress API.
	 *
	 * @return int|mixed|string|\WP_Error
	 */
	public function get_ajax_topics( $request ) {
		$use_webhook    = ! empty( $this->options['wpds_topic_webhook_refresh'] );
		$cache_duration = isset( $request['cache_duration'] ) ? esc_attr( wp_unslash( $request['cache_duration'] ) ) : 10;
		$keep_cache  = false;
		$id             = esc_attr( wp_unslash( $request['id'] ) );
		// For now, ajax requests are only being called when $source is 'latest', it might make sense to add 'top/daily'.
		$source         = ! empty( $request['source'] ) ? esc_attr( wp_unslash( $request['source'] ) ) : 'latest';
		$period         = ! empty( $request['period'] ) ? esc_attr( wp_unslash( $request['period'] ) ) : 'daily';
		$sync_key       = 'latest' === $source ? 'wpds_latest_topics_last_sync' : 'wpds_' . $period . 'topics_last_sync';

		if ( ! $use_webhook ) {
			$last_sync     = get_option( $sync_key );
			// The cache_duration arg is in minutes. Convert to seconds.
			$keep_cache = ( $cache_duration * 60 ) + $last_sync > time();
			// If a webhook is being used, keep the cached topics if wpds_update_latest hasn't been set to 1.
		} elseif ( 'latest' === $source && empty( get_option( 'wpds_update_latest' ) ) ) {
			$keep_cache = true;
		}

		if ( $keep_cache ) {

			// Returning 0 to the AJAX request will prevent it from reloading the HTML.
			return 0;
		}

		$args                   = [];
		$args['cache_duration'] = $cache_duration;
		$args['source']         = $source;
		$args['period']         = $period;
		$args['id']             = $id;

		if ( ! empty( $request['max_topics'] ) ) {
			$args['max_topics'] = esc_attr( wp_unslash( $request['max_topics'] ) );
		}

		if ( ! empty( $request['display_avatars'] ) ) {
			$args['display_avatars'] = esc_attr( wp_unslash( $request['display_avatars'] ) );
		}

		if ( ! empty( $request['tile'] ) ) {
			$args['tile'] = esc_attr( wp_unslash( $request['tile'] ) );
		}

		if ( ! empty( $request['excerpt_length'] ) ) {
			$args['excerpt_length'] = esc_attr( wp_unslash( $request['excerpt_length'] ) );
		}

		if ( ! empty( $request['username_position'] ) ) {
			$args['username_position'] = esc_attr( wp_unslash( $request['username_position'] ) );
		}

		if ( ! empty( $request['category_position'] ) ) {
			$args['category_position'] = esc_attr( wp_unslash( $request['category_position'] ) );
		}

		if ( ! empty( $request['date_position'] ) ) {
			$args['date_position'] = esc_attr( wp_unslash( $request['date_position'] ) );
		}

		if ( ! empty( $request['ajax_timeout'] ) ) {
			$args['ajax_timeout'] = esc_attr( wp_unslash( $request['ajax_timeout'] ) );
		}

		// Returns the HTML.
		$topics = $this->get_topics( $args );

		if ( is_wp_error( $topics ) || empty( $topics ) ) {

			return 0;
		}

		return $topics;
	}

	/**
	 * Returns the formatted Discourse topics.
	 *
	 * @param array $args The shortcode args.
	 *
	 * @return mixed|string|\WP_Error
	 */
	public function get_topics( $args ) {
		$args   = shortcode_atts(
			array(
				'max_topics'        => 5,
				'cache_duration'    => 10,
				'display_avatars'   => 'true',
				'source'            => 'latest',
				'period'            => 'daily',
				'tile'              => 'false',
				'excerpt_length'    => null,
				'username_position' => 'top',
				'category_position' => 'top',
				'date_position'     => 'top',
				'ajax_timeout'      => 2,
				'id'                => null,
			), $args
		);
		$time   = time();
		// The source can be set to either 'latest' or 'top'. If set to anything else it will return topics based on the period.
		$source = $args['source'];
		// If top is the source, the period is used to select the top correct top page.
		$period = $args['period'];
		// Just in case it's been set incorrectly.
		if ( ! preg_match( '/^(all|yearly|quarterly|monthly|weekly|daily)$/', $period ) ) {
			$period = 'yearly';
		}

		$source_key = 'latest' === $source ? 'latest' : $period;
		$path       = 'latest' === $source ? '/latest.json' : "/top/{$period}.json";
		// $id is used so that more than one shortcode for a given source can be stored as a transient.
		$id         = $args['id'] ? $args['id'] : $source_key;
		// The key under which the topic data transient is saved.
		$topics_data_key = 'latest' === $source ? 'wpds_latest_topics' : 'wpds_' . $period . '_topics';
		// The key under which the formatted topics transient is saved.
		$formatted_html_key = $topics_data_key . '_html';
		$sync_key           = $topics_data_key . '_last_sync';
		$last_sync      = get_option( $sync_key );
		$cache_duration = $args['cache_duration'] * 60;

		// If this is being called from the AJAX function, it will be calculated twice. Should be refactored.
		if ( ! empty( $this->options['wpds_topic_webhook_refresh'] ) && 'latest' === $source_key ) {
				$update = get_option( 'wpds_update_latest' );
		} else {
			$update = $cache_duration + $last_sync < $time ? 1 : 0;
		}

		// Get the topics data.
		$topics_data = get_transient( $topics_data_key );

		// Maybe update the topics data. If updated, delete the formatted topics.
		if ( empty( $topics_data ) || is_wp_error( $topics_data ) || $update ) {
			$topics_data = $this->fetch_topics_data( $path );

			if ( is_wp_error( $topics_data ) ) {

				return new \WP_Error( 'wpds_request_error', 'The topic list could not be returned from Discourse.' );
			}

			set_transient( $topics_data_key, $topics_data, DAY_IN_SECONDS );
			// It's safe to delete this here, the topics_data has been successfully returned.
			// Deletes the entire formated_html transient array, so subsequent shortcodes of same type will also be refreshed.
			delete_transient( $formatted_html_key );
			update_option( $sync_key, $time );
			update_option( 'wpds_update_latest', 0 );
		}

		// The formatted topics are stored in an array. Allows for caching topics for more than one shortcode.
		$formatted_topics_array = get_transient( $formatted_html_key );

		// Will be empty after either saving a post that contains a discourse_topics shortcode, or updating topics_data.
		if ( empty( $formatted_topics_array[ $id ] ) ) {

			$formatted_topics = $this->topic_formatter->format_topics( $topics_data, $args );

			if ( empty( $formatted_topics ) ) {

				return new \WP_Error( 'wpds_topics_formatting_error', 'An error was returned from the topics_formatter' );
			}

			$formatted_topics_array[ $id ] = $formatted_topics;

			set_transient( $formatted_html_key, $formatted_topics_array, DAY_IN_SECONDS );
		} else {
			$formatted_topics = $formatted_topics_array[ $id ];
		}

		return $formatted_topics;
	}

	/**
	 * Fetches the topic list data from Discourse.
	 *
	 * @param string $path The Discourse path to pull from.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	protected function fetch_topics_data( $path ) {
		if ( empty( $this->discourse_url ) || empty( $this->api_key ) || empty( $this->api_username ) ) {

			return new \WP_Error( 'wpds_configuration_error', 'The WP Discourse plugin is not properly configured.' );
		}

		$topics_url = esc_url_raw( $this->discourse_url . $path );

		if ( ! empty( $this->options['wpds_display_private_topics'] ) ) {
			$topics_url = esc_url_raw(
				add_query_arg(
					array(
						'api_key'      => $this->api_key,
						'api_username' => $this->api_username,
					), $topics_url
				)
			);
		}

		$response = wp_remote_get(
			$topics_url, array(
				'timeout' => 60,
			)
		);

		if ( ! DiscourseUtilities::validate( $response ) ) {

			return new \WP_Error( 'wpds_response_error', 'An error was returned from Discourse when fetching the latest topics.' );
		}

		$topics_data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Add the cooked content to each topic.
		$topics = $topics_data['topic_list']['topics'];

		// Only get topic content if wpds_topic_content is enabled.
		if ( ! empty( $this->options['wpds_topic_content'] ) ) {

			$count      = 0;
			$max_topics = ! empty( $this->options['wpds_max_topics'] ) ? $this->options['wpds_max_topics'] : 6;
			foreach ( $topics as $index => $topic ) {
				if ( $count < $max_topics && $this->display_topic( $topic ) ) {
					$cooked = $this->get_discourse_post( $topic['id'] );

					if ( is_wp_error( $cooked ) ) {
						$cooked = '';
					} else {
						$cooked = wp_kses_post( $cooked );
					}

					$topics_data['topic_list']['topics'][ $index ]['cooked'] = $cooked;

					$count++;
				}
			}
		}

		return $topics_data;
	}

	/**
	 * Gets the cooked content of the first post in a topic.
	 *
	 * @param int $topic_id The topic to get the post for.
	 *
	 * @return array|\WP_Error
	 */
	protected function get_discourse_post( $topic_id ) {
		if ( empty( $this->discourse_url ) || empty( $this->api_key ) || empty( $this->api_username ) ) {

			return new \WP_Error( 'wpds_configuration_error', 'The WP Discourse plugin is not properly configured.' );
		}

		$topic_url = "{$this->discourse_url}/t/{$topic_id}.json";
		$topic_url = esc_url_raw(
			add_query_arg(
				array(
					'api_key'      => $this->api_key,
					'api_username' => $this->api_username,
				), $topic_url
			)
		);

		$response = wp_remote_get(
			$topic_url, array(
				'timeout' => 60,
			)
		);

		if ( ! DiscourseUtilities::validate( $response ) ) {

			return new \WP_Error( 'wpds_response_error', 'There was an error retrieving the post for topic_id: ' . esc_attr( $topic_id ) . '.' );
		}

		$topic = json_decode( wp_remote_retrieve_body( $response ), true );
		$post  = $topic['post_stream']['posts'][0]['cooked'];

		return $post;
	}

	/**
	 * Selects whether or not to display a topic.
	 *
	 * @param array $topic The topic to test.
	 *
	 * @return bool
	 */
	protected function display_topic( $topic ) {

		return ! $topic['pinned_globally'] && 'regular' === $topic['archetype'] && - 1 !== $topic['posters'][0]['user_id'];
	}
}
