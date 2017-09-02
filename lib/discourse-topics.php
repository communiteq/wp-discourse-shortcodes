<?php

namespace WPDiscourse\Shortcodes;

class DiscourseTopics {
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
		// Todo: workaround for accessing rss URLs with a port number. Remove this code!
		if ( defined( 'DEV_MODE' ) && 'DEV_MODE' ) {
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

		// $latest = $this->fetch_latest_topics();
		// set_transient( 'wpds_latest_topics', $latest, DAY_IN_SECONDS );
		update_option( 'wpds_update_content', 1 );

		return null;
	}

	/**
	 * Get the latest topics from either from the stored transient, or from Discourse.
	 *
	 * @return string|null
	 */
	public function get_latest_topics() {
		$latest_topics = get_transient( 'wpds_latest_topics' );
		$force         = ! empty( get_option( 'wpds_update_content' ) ) || ! empty( $this->options['wpds_clear_topics_cache'] );

		if ( $force ) {
			update_option( 'wpds_update_content', 0 );
			// Reset the force option.
			$plugin_options                            = get_option( $this->option_key );
			$plugin_options['wpds_clear_topics_cache'] = 0;

			// Todo: uncomment this!
			// update_option( $this->option_key, $plugin_options );
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
			'max_topics' => 5,
			'display_avatars' => true,
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

	/**
	 * Get the latest topics from either from the stored transient, or from Discourse.
	 *
	 * @return string|null
	 */
	public function get_latest_rss() {
		$latest_rss = get_transient( 'wpds_latest_rss' );
		$force         = ! empty( get_option( 'wpds_update_content' ) ) || ! empty( $this->options['wpds_clear_topics_cache'] );

		if ( $force ) {
			update_option( 'wpds_update_content', 0 );
			// Reset the force option.
			$plugin_options                            = get_option( $this->option_key );
			$plugin_options['wpds_clear_topics_cache'] = 0;

			// Todo: uncomment this!
			// update_option( $this->option_key, $plugin_options );
		}

		if ( empty( $latest_rss ) || $force ) {

			$latest_rss = $this->fetch_latest_rss();

			if ( ! empty( $latest_rss ) && ! is_wp_error( $latest_rss ) ) {

				set_transient( 'wpds_latest_rss', $latest_rss, DAY_IN_SECONDS );
			} else {

				return null;
			}
		}

		$formatted_rss = $this->topic_formatter->format_rss_topics( $latest_rss );

		return $formatted_rss;
	}

	public function feed_cache_duration() {
		// Todo: set this to a sane value.
		return 30;
	}

	/**
	 * Fetch and parse the latest RSS feed from Discourse.
	 *
	 * This function should only be run when content has been updated on Discourse.
	 *
	 * @return array|\WP_Error
	 */
	protected function fetch_latest_rss() {
		if ( empty( $this->discourse_url ) || empty( $this->api_key ) || empty( $this->api_username ) ) {

			return new \WP_Error( 'wp_discourse_configuration_error', 'The WP Discourse plugin is not properly configured.' );
		}

		$latest_url = $this->discourse_url . '/latest.rss';

		include_once( ABSPATH . WPINC . '/feed.php' );
		// Break and then restore the cache.
		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'feed_cache_duration' ) );
		$feed = fetch_feed( $latest_url );
		remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'feed_cache_duration' ) );
		if ( is_wp_error( $feed ) ) {

			return new \WP_Error( 'wp_discourse_rss_error', 'An RSS feed was not returned by Discourse.' );
		}

		$maxitems   = $feed->get_item_quantity( 45 );
		$feed_items = $feed->get_items( 0, $maxitems );
		$latest     = [];
		// Don't create warnings for misformed HTML.
		libxml_use_internal_errors( true );
		$dom = new \domDocument( '1.0', 'utf-8' );
		// Clear the internal error cache.
		libxml_clear_errors();

		foreach ( $feed_items as $key => $item ) {
			$title            = $item->get_title();
			$permalink        = $item->get_permalink();
			$category         = $item->get_category()->get_term();
			$author           = $item->get_author()->get_name();
			$date             = $item->get_date( 'F j, Y' );
			$description_html = $item->get_description();
			$description_html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $description_html . '</body></html>';
			$dom->loadHTML( $description_html );
			$description  = [];
			$wp_permalink = '';
			$reply_count  = 0;
			// Getting content from <p> elements avoids having to deal with Discourse lightboxes.
			$paragraphs = $dom->getElementsByTagName( 'p' );

			// This is relying on the structure of the topic description that's returned by Discourse - will probably need tweaking.
			foreach ( $paragraphs as $index => $paragraph ) {
				if ( $paragraph->textContent && $index > 0 && $index < $paragraphs->length - 3 ) {
					if ( 1 === $index ) {
						$small_tags = $paragraph->getElementsByTagName( 'small' );
						if ( $small_tags->length ) {
							$link_nodes = $small_tags->item( 0 )->getElementsByTagName( 'a' );
							if ( $link_nodes->length ) {
								$wp_link_node = $small_tags->item( 0 );
								// Save and then remove the WordPress link that's added when posts are published from WP to Discourse.
								$wp_permalink = $wp_link_node->getElementsByTagName( 'a' )->item( 0 )->getAttribute( 'href' );
								$paragraph->removeChild( $wp_link_node );
							}
						}
					}

					// Save the description as an array of paragraphs.
					$description[] = $dom->saveHTML( $paragraph );
				}

				// The third to last paragraph contains the reply count.
				if ( $index === $paragraphs->length - 3 ) {
					$reply_count = filter_var( $paragraph->textContent, FILTER_SANITIZE_NUMBER_INT ) - 1;
				}
			}

			$image_tags = $dom->getElementsByTagName( 'img' );
			$images     = [];
			if ( $image_tags->length ) {
				foreach ( $image_tags as $image_tag ) {
					$images[] = $dom->saveHTML( $image_tag );
				}
			}

			$latest[ $key ]['title']        = $title;
			$latest[ $key ]['permalink']    = $permalink;
			$latest[ $key ]['wp_permalink'] = $wp_permalink;
			$latest[ $key ]['category']     = $category;
			$latest[ $key ]['author']       = $author;
			$latest[ $key ]['date']         = $date;
			$latest[ $key ]['description']  = $description;
			$latest[ $key ]['images']       = $images;
			$latest[ $key ]['reply_count']  = $reply_count;
		}// End foreach().

		unset( $dom );

		return $latest;
	}
}
