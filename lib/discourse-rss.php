<?php

namespace WPDiscourse\Shortcodes;

class DiscourseRSS {
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
	 * An instance of the DiscourseRSSFormatter class.
	 *
	 * @access protected
	 * @var DiscourseRSSFormatter
	 */
	protected $rss_formatter;

	/**
	 * LatestTopics constructor.
	 *
	 * @param DiscourseRSSFormatter $rss_formatter An instance of DiscourseRSSFormatter.
	 */
	public function __construct( $rss_formatter ) {
		$this->rss_formatter = $rss_formatter;

		add_action( 'init', array( $this, 'setup_options' ) );
//		add_action( 'rest_api_init', array( $this, 'initialize_topic_route' ) );
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

	public function get_rss( $args ) {
		$args = shortcode_atts( array(
			'max_topics'     => 5,
			'source'         => 'latest',
			'period'         => 'yearly',
			'cache_duration' => 10,
			'excerpt_length' => 55,
			'display_images' => 'true',
		), $args );
		$time = time();

		if ( 'latest' === $args['source'] ) {
			$formatted_rss = get_transient( 'wpds_latest_rss' );

			if ( empty( $this->options['wpds_topic_webhook_refresh'] ) ) {
				// Webhooks aren't enabled, use the cache_duration arg.
				$last_sync      = get_option( 'wpds_latest_rss_last_sync' );
				$cache_duration = $args['cache_duration'] * 60;
				$update         = $cache_duration + $last_sync < $time;
			} else {
				// Todo: value is being set in discourse-topic.php, move to webhook class.
				$update = ! empty( get_option( 'wpds_update_latest_rss_content' ) );
			}

			if ( empty( $formatted_rss ) || $update ) {

				$latest_rss = $this->fetch_rss( 'latest', $args );

				if ( empty( $latest_rss ) && ! is_wp_error( $latest_rss ) ) {

					return new \WP_Error( 'wpds_get_rss_error', 'There was an error retrieving the formatted RSS.' );
				} else {

					$formatted_rss = $this->rss_formatter->format_rss_topics( $latest_rss, $args );
					set_transient( 'wpds_latest_rss', $formatted_rss, DAY_IN_SECONDS );
					update_option( 'wpds_update_latest_rss_content', 0 );
					update_option( 'wpds_latest_rss_last_sync', $time );
				}
			}

			return $formatted_rss;
		}

		if ( 'top' === $args['source'] ) {
			$period = $args['period'];
			if ( ! preg_match( '/^(all|yearly|quarterly|monthly|weekly|daily)$/', $period ) ) {
				$period = 'yearly';
			}
			$rss_key        = 'wpds_top_' . $period . '_rss';
			$rss_sync_key   = $rss_key . '_last_sync';
			$last_sync      = get_option( $rss_sync_key );
			$cache_duration = $args['cache_duration'] * 60;
			$update         = $cache_duration + $last_sync < $time;
			$formatted_rss  = get_transient( $rss_key );

			if ( empty( $formatted_rss ) || $update ) {
				$source  = 'top/' . $period;
				$top_rss = $this->fetch_rss( $source, $args );
				if ( empty( $top_rss ) && ! is_wp_error( $top_rss ) ) {

					return new \WP_Error( 'wpds_get_rss_error', 'There was an error retrieving the formatted RSS.' );
				} else {

					$formatted_rss = $this->rss_formatter->format_rss_topics( $top_rss, $args );
					set_transient( $rss_key, $formatted_rss, DAY_IN_SECONDS );
					update_option( $rss_sync_key, $time );
				}
			}

			return $formatted_rss;
		}

		// Todo: add error message.
		return new \WP_Error();
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
	protected function fetch_rss( $source, $args ) {
		if ( empty( $this->discourse_url ) ) {

			return new \WP_Error( 'wp_discourse_configuration_error', 'The WP Discourse plugin is not properly configured.' );
		}

		// Todo: esc_url_raw!
//		$rss_url = $this->discourse_url . '/' . $source . '.rss';
		$rss_url = esc_url_raw( "{$this->discourse_url}/{$source}.rss" );

		include_once( ABSPATH . WPINC . '/feed.php' );
		// Break and then restore the cache.
		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'feed_cache_duration' ) );
		// Todo: look at this error: Non-static method WP_Feed_Cache::create() should not be called statically.
		$feed = fetch_feed( $rss_url );
		remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'feed_cache_duration' ) );

		// Todo: add some more validation to this.
		if ( is_wp_error( $feed ) ) {

			return new \WP_Error( 'wp_discourse_rss_error', 'An RSS feed was not returned by Discourse.' );
		}

		$max_items  = $feed->get_item_quantity( $args['max_items'] );
		$feed_items = $feed->get_items( 0, $max_items );
		$rss_data   = [];
		// Don't create warnings for misformed HTML.
		libxml_use_internal_errors( true );
		$dom = new \domDocument( '1.0', 'utf-8' );
		// Clear the internal error cache.
		libxml_clear_errors();

		foreach ( $feed_items as $item_index => $item ) {
			$title            = $item->get_title();
			$permalink        = $item->get_permalink();
			$category         = $item->get_category()->get_term();
			$author           = $item->get_author()->get_name();
			$date             = $item->get_date( 'F j, Y' );
			$description_html = $item->get_description();
			$description_html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $description_html . '</body></html>';
			$dom->loadHTML( $description_html );
			$wp_permalink = '';

			$paragraphs = $dom->getElementsByTagName( 'p' );

			// If the post begins with 'Originally published at...' text, save the link and remove its enclosing small tags.
			$possible_link_p     = $paragraphs->item( 1 );
			$possible_link_nodes = $possible_link_p->getElementsByTagName( 'small' );
			if ( $possible_link_nodes->length ) {
				$link_nodes = $possible_link_nodes->item( 0 )->getElementsByTagName( 'a' );
				if ( $link_nodes->length ) {
					$wp_link_node = $link_nodes->item( 0 );
					$wp_permalink = $wp_link_node->getAttribute( 'href' );
					$possible_link_nodes->item( 0 )->parentNode->removeChild( $possible_link_nodes->item( 0 ) );
					$br_nodes = $possible_link_p->getElementsByTagName( 'br' );
					foreach ( $br_nodes as $br_node ) {
						$possible_link_p->removeChild( $br_node );
					}
				}
			}

			// The third to last paragraph contains the post count.
			$replies_p   = $paragraphs->item( $paragraphs->length - 3 );
			$reply_count = filter_var( $replies_p->textContent, FILTER_SANITIZE_NUMBER_INT ) - 1;

			$image_tags = $dom->getElementsByTagName( 'img' );
			$images     = [];
			if ( $image_tags->length ) {
				foreach ( $image_tags as $image_tag ) {
					$images[] = $dom->saveHTML( $image_tag );
					if ( 'false' === $args['display_images'] ) {
						$image_tag->parentNode->removeChild( $image_tag );
					}
				}
			}

			$blockquote  = $dom->getElementsByTagName( 'blockquote' )->item( 0 );
			$description = $dom->saveHTML( $blockquote );
			$description = substr( $description, 12, - 13 );

			$rss_data[ $item_index ]['title']        = $title;
			$rss_data[ $item_index ]['permalink']    = $permalink;
			$rss_data[ $item_index ]['wp_permalink'] = $wp_permalink;
			$rss_data[ $item_index ]['category']     = $category;
			$rss_data[ $item_index ]['author']       = $author;
			$rss_data[ $item_index ]['date']         = $date;
			$rss_data[ $item_index ]['description']  = $description;
			$rss_data[ $item_index ]['images']       = $images;
			$rss_data[ $item_index ]['reply_count']  = $reply_count;
		}// End foreach().

		unset( $dom );

		return $rss_data;
	}
}