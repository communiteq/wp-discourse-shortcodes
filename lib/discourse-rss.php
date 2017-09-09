<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseRSS {

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
		// Todo: workaround for accessing rss URLs with a port number. Remove this code!
		if ( defined( 'DEV_MODE' ) && 'DEV_MODE' ) {
			write_log( 'in dev mode, remove this code discourse-rss.php' );
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
		add_option( 'wpds_update_latest_rss', 1 );
		$this->options       = DiscourseUtilities::get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
	}

	public function get_rss( $args ) {
		$args = shortcode_atts( array(
			'max_topics'        => 5,
			'source'            => 'latest',
			'period'            => 'yearly',
			'cache_duration'    => 10,
			'excerpt_length'    => 27,
			'display_images'    => 'true',
			'wp_link'           => 'false',
			'tile'              => 'false',
			'username_position' => 'top',
			'date_position'     => 'top',
			'category_position' => 'top',
			'show_replies'      => 'true',
		), $args );
		$time = time();

		if ( 'latest' === $args['source'] ) {
			$formatted_rss = get_transient( 'wpds_latest_rss' );

			$last_sync      = get_option( 'wpds_latest_rss_last_sync' );
			$cache_duration = $args['cache_duration'] * 60;
			$update         = $cache_duration + $last_sync < $time;

			if ( empty( $formatted_rss ) || $update ) {

				$latest_rss = $this->fetch_rss( 'latest', $args );

				if ( empty( $latest_rss ) || is_wp_error( $latest_rss ) ) {

					return new \WP_Error( 'wpds_get_rss_error', 'There was an error retrieving the formatted RSS.' );
				} else {

					$formatted_rss = $this->rss_formatter->format_rss_topics( $latest_rss, $args );
					set_transient( 'wpds_latest_rss', $formatted_rss, DAY_IN_SECONDS );
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
				if ( empty( $top_rss ) || is_wp_error( $top_rss ) ) {

					return new \WP_Error( 'wpds_get_rss_error', 'There was an error retrieving the formatted RSS.' );
				} else {

					$formatted_rss = $this->rss_formatter->format_rss_topics( $top_rss, $args );
					set_transient( $rss_key, $formatted_rss, DAY_IN_SECONDS );
					update_option( $rss_sync_key, $time );
				}
			}

			return $formatted_rss;
		}

		return new \WP_Error( 'wpds_get_rss_error', 'A valid RSS source was not set.' );
	}

	public
	function feed_cache_duration() {
		return 30;
	}

	/**
	 * Fetch and parse the latest RSS feed from Discourse.
	 *
	 * This function should only be run when content has been updated on Discourse.
	 *
	 * @return array|\WP_Error
	 */
	protected
	function fetch_rss(
		$source, $args
	) {
		if ( empty( $this->discourse_url ) ) {

			return new \WP_Error( 'wp_discourse_configuration_error', 'The WP Discourse plugin is not properly configured.' );
		}

		$rss_url = esc_url_raw( "{$this->discourse_url}/{$source}.rss" );
		include_once( ABSPATH . WPINC . '/feed.php' );

		// Break and then restore the cache.
		add_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'feed_cache_duration' ) );
		$feed = fetch_feed( $rss_url );
		remove_filter( 'wp_feed_cache_transient_lifetime', array( $this, 'feed_cache_duration' ) );

		if ( ! empty ( $feed->errors ) || is_wp_error( $feed ) ) {

			return new \WP_Error( 'wp_discourse_rss_error', 'An RSS feed was not returned by Discourse.' );
		}

		$max_items   = $feed->get_item_quantity( $args['max_topics'] );
		$date_format = ! empty( $this->options['custom-datetime-format'] ) ? $this->options['custom-datetime-format'] : 'Y/m/d';
		$feed_items  = $feed->get_items( 0, $max_items );
		$rss_data    = [];
		// Don't create warnings for misformed HTML.
		libxml_use_internal_errors( true );
		$dom = new \domDocument( '1.0', 'utf-8' );
		// Clear the internal error cache.
		libxml_clear_errors();

		foreach ( $feed_items as $item_index => $item ) {
			$title            = $item->get_title();
			$permalink        = $item->get_permalink();
			$category         = $item->get_category()->get_term();
			$date             = $item->get_date( $date_format );
			$description_html = $item->get_description();
			$description_html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $description_html . '</body></html>';
			$wp_permalink     = '';
			$author_data      = $item->get_author()->get_name();
			if ( strpos( trim( $author_data ), ' ' ) ) {
				$author_data = explode( ' ', $author_data );
				$username    = trim( $author_data[0], '\@' );
				$name        = $author_data[1];
			} else {
				$username = trim( $author_data, '\@' );
				$name     = '';
			}

			$dom->loadHTML( $description_html );
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

			if ( 'false' === $args['display_images'] ) {
				$image_tags = $dom->getElementsByTagName( 'img' );
				if ( $image_tags->length ) {
					foreach ( $image_tags as $image_tag ) {
						$image_tag->parentNode->removeChild( $image_tag );
					}
				}
			}

			$blockquote  = $dom->getElementsByTagName( 'blockquote' )->item( 0 );
			$description = $dom->saveHTML( $blockquote );
			// Remove the outer blockquote tags.
			$description = substr( $description, 12, - 13 );

			if ( 'full' !== $args['excerpt_length'] ) {
				if ( ! is_wp_error( $description ) ) {
					// Stripping tags from an error throws an error!
					$description = wp_trim_words( wp_strip_all_tags( $description ), $args['excerpt_length'] );
				} else {
					$description = '';
				}
			}

			$rss_data[ $item_index ]['title']        = $title;
			$rss_data[ $item_index ]['permalink']    = $permalink;
			$rss_data[ $item_index ]['wp_permalink'] = $wp_permalink;
			$rss_data[ $item_index ]['category']     = $category;
			$rss_data[ $item_index ]['username']     = $username;
			$rss_data[ $item_index ]['name']         = $name;
			$rss_data[ $item_index ]['date']         = $date;
			$rss_data[ $item_index ]['description']  = $description;
			$rss_data[ $item_index ]['reply_count']  = $reply_count;
		}// End foreach().

		unset( $dom );

		return $rss_data;
	}
}