<?php

namespace WPDiscourse\Shortcodes;

Use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

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

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'rest_api_init', array( $this, 'initialize_topic_route' ) );
	}

	/**
	 * Sets up the plugin options.
	 */
	public function setup_options() {
		add_option( 'wpds_update_latest', 1 );
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
			register_rest_route( 'wp-discourse/v1', 'latest-topics', array(
				array(
					'methods'  => \WP_REST_Server::CREATABLE,
					'callback' => array( $this, 'set_update_flag' ),
				),
				array(
					'methods'  => \WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_ajax_topics' ),
				),
			) );
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

			return new \WP_Error( 'discourse_response_error', 'There was an error returned from Discourse when processing the
			latest_topics webhook.' );
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
		$expired_cache  = false;
		$source         = ! empty( $request['source'] ) ? esc_attr( wp_unslash( $request['source'] ) ) : 'latest';
		$cache_duration = isset( $request['cache_duration'] ) ? esc_attr( wp_unslash( $request['cache_duration'] ) ) : 10;
		$period         = ! empty( $request['period'] ) ? esc_attr( wp_unslash( $request['period'] ) ) : 'daily';
		$sync_key       = 'latest' === $source ? 'wpds_latest_last_sync' : 'wpds_top_' . $period . '_last_sync';

		if ( ! $use_webhook ) {
			$last_sync     = get_option( $sync_key );
			$expired_cache = $cache_duration + $last_sync > time();
		}

		if ( $expired_cache || ( 'latest' === $source && $use_webhook && empty( get_option( 'wpds_update_latest' ) ) ) ) {

			// The content is fresh.
			return 0;
		}

		$args = [];
		if ( ! empty( $request['max_topics'] ) ) {
			$args['max_topics'] = esc_attr( wp_unslash( $request['max_topics'] ) );
		}

		$args['cache_duration'] = $cache_duration;

		if ( ! empty( $request['display_avatars'] ) ) {
			$args['display_avatars'] = esc_attr( wp_unslash( $request['display_avatars'] ) );
		}

		$args['source'] = $source;
		$args['period'] = $period;

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

		if ( ! empty( $request['enable_ajax'] ) ) {
			$args['enable_ajax'] = esc_attr( wp_unslash( $request['enable_ajax'] ) );
		}

		$topics = $this->get_topics( $args, $expired_cache );

		if ( is_wp_error( $topics ) || empty( $topics ) ) {

			return 0;
		}

		return $topics;
	}

	/**
	 * Returns the formatted Discourse topics.
	 *
	 * @param array $args The shortcode args.
	 * @param bool $force Force update.
	 *
	 * @return mixed|string|\WP_Error
	 */
	public function get_topics( $args, $force = false ) {
		$args = shortcode_atts( array(
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
			'enable_ajax'       => 'false',
			'ajax_timeout'      => 120,
		), $args );
		$time = time();

		if ( 'latest' === $args['source'] ) {
			$formatted_topics = get_transient( 'wpds_latest_topics' );

			if ( empty( $this->options['wpds_topic_webhook_refresh'] && ! $force ) ) {
				// Webhooks aren't enabled, use the cache_duration arg.
				$last_sync      = get_option( 'wpds_latest_last_sync' );
				$cache_duration = $args['cache_duration'] * 60;
				$update         = $cache_duration + $last_sync < $time;
			} else {
				$update = $force || ! empty( get_option( 'wpds_update_latest' ) );
			}

			if ( empty( $formatted_topics ) || $update ) {

				$latest_topics = $this->fetch_topics( 'latest', $args );

				if ( empty( $latest_topics ) || is_wp_error( $latest_topics ) ) {

					return new \WP_Error( 'wpds_get_topics_error', 'There was an error retrieving the formatted latest topics.' );
				} else {
					$formatted_topics = $this->topic_formatter->format_topics( $latest_topics, $args );
					set_transient( 'wpds_latest_topics', $formatted_topics, DAY_IN_SECONDS );
					update_option( 'wpds_update_latest', 0 );
					update_option( 'wpds_latest_last_sync', $time );
				}
			}

			return $formatted_topics;
		}

		if ( 'top' === $args['source'] ) {
			$period = $args['period'];
			if ( ! preg_match( '/^(all|yearly|quarterly|monthly|weekly|daily)$/', $period ) ) {
				$period = 'yearly';
			}
			$top_key          = 'wpds_top_' . $period;
			$top_sync_key     = $top_key . '_last_sync';
			$last_sync        = get_option( $top_sync_key );
			$cache_duration   = $args['cache_duration'] * 60;
			$update           = $cache_duration + $last_sync < $time;
			$formatted_topics = get_transient( $top_key );

			if ( empty( $formatted_topics ) || $update ) {
				$source = 'top/' . $period;

				$top_topics = $this->fetch_topics( $source, $args );

				if ( empty( $top_topics ) || is_wp_error( $top_topics ) ) {

					return new \WP_Error( 'wpds_get_topics_error', 'There was an error retrieving the formatted top topics.' );
				} else {
					$formatted_topics = $this->topic_formatter->format_topics( $top_topics, $args );
					set_transient( $top_key, $formatted_topics, DAY_IN_SECONDS );
					update_option( $top_sync_key, $time );
				}
			}

			return $formatted_topics;
		}

		return new \WP_Error( 'wpds_get_topics_error', 'A valid topics source was not provided.' );
	}

	/**
	 * Fetches a topic list from Discourse.
	 *
	 * @param string $source The Discourse path to pull from.
	 * @param array $args The shortcode args.
	 *
	 * @return array|mixed|object|\WP_Error
	 */
	protected function fetch_topics( $source, $args ) {
		if ( empty( $this->discourse_url ) || empty( $this->api_key ) || empty( $this->api_username ) ) {

			return new \WP_Error( 'wpds_configuration_error', 'The WP Discourse plugin is not properly configured.' );
		}

		$topics_url = $this->discourse_url . "/{$source}.json";

		if ( ! empty( $this->options['wpds_display_private_topics'] ) ) {
			$topics_url = add_query_arg( array(
				'api_key'      => $this->api_key,
				'api_username' => $this->api_username,
			), $topics_url );
		}

		$response = wp_remote_get( $topics_url );

		if ( ! DiscourseUtilities::validate( $response ) ) {

			return new \WP_Error( 'wpds_response_error', 'An error was returned from Discourse when fetching the latest topics.' );
		}

		$topics_data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Add the cooked content to each topic.
		$excerpt_length = $args['excerpt_length'];
		if ( $excerpt_length && 'false' !== $excerpt_length ) {
			$max_topics = $args['max_topics'];
			$topics     = $topics_data['topic_list']['topics'];

			$count = 0;
			foreach ( $topics as $index => $topic ) {
				if ( $count < $max_topics && $this->display_topic( $topic ) ) {
					$cooked = $this->get_discourse_post( $topic['id'] );

					if ( is_wp_error( $cooked ) ) {
						$excerpt = '';
					} elseif ( 'full' === $excerpt_length ) {
						$excerpt = $cooked;
					} else {
						libxml_use_internal_errors( true );
						$doc = new \DOMDocument( '1.0', 'utf-8' );
						libxml_clear_errors();
						// Create a valid document with charset.
						$html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $cooked . '</body></html>';
						$doc->loadHTML( $html );

						$html    = $this->clean_discourse_content( $doc );
						$excerpt = wp_trim_words( wp_strip_all_tags( $html ), $excerpt_length );

						unset( $doc );
					}

					$topics_data['topic_list']['topics'][ $index ]['cooked'] = $excerpt;
					$count                                                   += 1;
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
		$topic_url = esc_url_raw( add_query_arg( array(
			'api_key'      => $this->api_key,
			'api_username' => $this->api_username,
		), $topic_url ) );

		$response = wp_remote_get( $topic_url );

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


	/**
	 * Clean the HTML returned from Discourse.
	 *
	 * @param \DOMDocument $doc The DOMDocument to parse.
	 *
	 * @return string
	 */
	protected function clean_discourse_content( \DOMDocument $doc ) {
		$xpath    = new \DOMXPath( $doc );
		$elements = $xpath->query( "//span[@class]" );

		if ( $elements && $elements->length ) {
			foreach ( $elements as $element ) {
				$element->parentNode->removeChild( $element );
			}
		}

		$html = $doc->saveHTML();

		unset( $xpath );

		return $html;
	}
}
