<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseRSSFormatter {
	use Formatter;

	protected $options;

	/**
	 * The Discourse forum URL.
	 *
	 * @access protected
	 * @var string
	 */
	protected $discourse_url;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );

	}

	public function setup_options() {
		$this->options       = DiscourseUtilities::get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
	}

	/**
	 * Format the Discourse RSS feed.
	 *
	 * @param array $discourse_topics The array of topics.
	 * @param array $args The shortcode attributes.
	 *
	 * @return string
	 */
	public function format_rss_topics( $topics, $args ) {
		$image_class   = 'true' === $args['display_images'] ? 'wpds-rss-display-images' : '';
		$topic_list_id = 'wpds_rss_list_' . time();
		$use_ajax      = ! empty( $this->options['wpds_ajax_refresh'] ) &&
		                 ! empty( $this->options['wpds_topic_webhook_refresh'] ) &&
		                 ( 'latest' === $args['source'] || 'daily' === $args['period'] );
		$ajax_class    = $use_ajax ? ' wpds-rss-list-refresh' : '';
		$tile_class    = 'true' === $args['tile'] ? ' wpds-tile' : '';

		$output = '';

		do_action( 'wpds_before_rss_list', $topics, $args );

		// Return false from this hook to bypass formatting.
		$use_plugin_formatting = apply_filters( 'wpds_use_plugin_rss_formatting', true );

		if ( $use_plugin_formatting ) {

			$output = '<div class="wpds-tile-wrapper' . esc_attr( $ajax_class ) .
			          '" id="' . esc_attr( $topic_list_id ) . '"><ul class="wpds-rss-list ' . esc_attr( $image_class ) . esc_attr( $tile_class ) . '">';

			if ( ! empty( $this->options['wpds_ajax_refresh'] ) ) {
				$output .= $this->render_rss_shortcode_options( $args );
			}

			foreach ( $topics as $topic ) {
				$description   = ! empty( $topic['description'] ) ? $topic['description'] : '';
				$username      = ( ! empty( $topic['username'] ) ) ? $topic['username'] : '';
				$category_name = ! empty( $topic['category'] ) ? $topic['category'] : '';
				$category      = $this->find_discourse_category_by_name( $category_name );
				$wp_permalink  = ! empty( $topic['wp_permalink'] ) ? $topic['wp_permalink'] : null;
				$title         = ! empty( $topic['title'] ) ? $topic['title'] : '';
				$date          = ! empty( $topic['date'] ) ? $topic['date'] : '';
				$permalink     = ! empty( $topic['permalink'] ) ? $topic['permalink'] : null;
				$reply_count   = ! empty( $topic['reply_count'] ) ? $topic['reply_count'] : null;
				if ( ! $reply_count ) {
					$replies_text = __( 'no replies', 'wpds' );
				} elseif ( 1 === $reply_count ) {
					$replies_text = __( 'reply', 'wpds' );
				} else {
					$replies_text = __( 'replies', 'wpds' );
				}

				$output .= '<li class="wpds-rss-topic ' . esc_attr( $category['slug'] ) . '">';
				// Add content above the header.
				$output = apply_filters( 'wpds_rsslist_above_header', $output, $topic, $category, $args );

				$output .= '<header>';

				if ( 'top' === $args['username_position'] ) {
					$output .= '<span class="wpds-rss-list-username">' . esc_html( $username ) . '</span> <span class="wpds-term">' . __( 'posted on ', 'wpds' ) . '</span>';
				}

				if ( 'top' === $args['date_position'] ) {
					$output .= '<span class="wpds-created-at">' . esc_html( $date ) . '</span>';
				}

				$output .= '<h4 class="wpds-rss-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h4>';

				if ( 'top' === $args['category_position'] ) {
					$output .= '<span class="wpds-term">' . __( '', 'wpds' ) . '</span> <span class="wpds-shortcode-category">' . $this->discourse_category_badge( $category ) . '</span>';
				}

				if ( $wp_permalink && 'true' === $args['wp_link'] ) {
					$output .= '<br><span class="wpds-term"> orginally published at </span><a href="' . esc_url( $wp_permalink ) . '">' . esc_url( $wp_permalink ) . '</a>';
				}

				$output .= '</header>';


				if ( 'full' !== $args['excerpt_length'] ) {
					$output .= '<p class="wpds-rss-list-description">' . wp_kses_post( $description ) . '</p>';
				} else {
					// Todo: sub this back in.
//				$output .= wp_kses_post( $description );
					$output .= '<div class="wpds-rss-list-content">' . $description . '</div>';
				}

				$output = apply_filters( 'wpds_topiclist_above_footer', $output, $topic, $category, $args );

				$output .= '<footer><div class="wpds-rss-list-footer-meta">';

				if ( 'bottom' === $args['username_position'] ) {
					$output .= '<span class="wpds-rss-list-username">' . esc_html( $username ) . '</span><br>';
				}

				if ( 'bottom' === $args['category_position'] ) {
					$output .= '<span class="wpds-term">' . __( '', 'wpds' ) . '</span> <span class="wpds-shortcode-category">' . $this->discourse_category_badge( $category ) . '</span>';
				}

				$output .= '<span class="wpds-likes-and-replies">';
				if ( $args['show_replies'] ) {
					$output .= '<a class="wpds-rss-list-reply-link" href="' . esc_url( $permalink ) . '"><span class="wpds-topiclist-replies">' .
					           esc_attr( $reply_count ) . ' </span>' . esc_html( $replies_text ) . '</a>';
				}

				$output .= '</span></div></footer>';
				$output = apply_filters( 'wpds_after_formatting_rss', $output, $topics, $args );
				$output .= '</li>';
			}

			$output .= '</ul></div>';
		}

		$output = apply_filters( 'wpds_after_topiclist_formatting', $output, $topics, $args );

		return $output;
	}
}
