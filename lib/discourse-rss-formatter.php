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
		$output = '';

		do_action( 'wpds_before_rss_list', $topics, $args );

		// Return false from this hook to bypass formatting.
		$use_plugin_formatting = apply_filters( 'wpds_use_plugin_rss_formatting', true );

		if ( $use_plugin_formatting ) {

			$output = '<ul class="wpds-rss-list ' . esc_attr( $image_class ) . '" id="' . esc_attr( $topic_list_id ) . '">';

			if ( ! empty( $this->options['wpds_ajax_refresh'] ) ) {
				$output .= $this->render_rss_shortcode_options( $args );
			}

			foreach ( $topics as $topic ) {
				$description = ! empty( $topic['description'] ) ? $topic['description'] : '';

				$author        = ! empty( $topic['author'] ) ? $topic['author'] : '';
				$cleaned_name  = trim( $author, '\@' );
				$author_url    = "{$this->discourse_url}/u/{$cleaned_name}";
				$category_name = ! empty( $topic['category'] ) ? $topic['category'] : '';
				$category      = $this->find_discourse_category_by_name( $category_name );
				$wp_permalink  = ! empty( $topic['wp_permalink'] ) ? $topic['wp_permalink'] : null;
				$title         = ! empty( $topic['title'] ) ? $topic['title'] : '';
				$date          = ! empty( $topic['date'] ) ? $topic['date'] : '';
				$reply_count   = ! empty( $topic['reply_count'] ) ? $topic['reply_count'] : '';
				$permalink     = ! empty( $topic['permalink'] ) ? $topic['permalink'] : null;


				$output .= '<li class="wpds-rss-topic ' . esc_attr( $category['slug'] ) . '">';
				$output .= '<h3 class="wpds-rss-title"><a href="' . esc_url( $permalink ) . '">' . esc_html( $title ) . '</a></h3>';
				$output .= '<div class="wpds-rss-poster-meta"><span class="wpds-term"> ' . __( 'posted by', 'wpds' ) . '</span> <a href="' .
				           esc_url( $author_url ) . '">' . esc_html( $cleaned_name ) . '</a> <span class="wpds-term">' . __( 'on', 'wpds' ) .
				           '</span> <span class="wpds-created-at">' . esc_html( $date ) . '</span><br><span class="wpds-term">' .
				           __( 'in', 'wpds' ) . '</span> <span class="wpds-shortcode-category" >' . $this->discourse_category_badge( $category ) . '</span>';

				if ( $wp_permalink && 'true' === $args['wp_link'] ) {
					$output .= '<br><span class="wpds-term"> orginally published at </span><a href="' . esc_url( $wp_permalink ) . '">' . esc_url( $wp_permalink ) . '</a>';
				}
				$output .= '</div>';

				if ( 'full' !== $args['excerpt_length'] ) {
					$output .= '<p>' . wp_kses_post( $description ) . '</p>';
				} else {
					// Todo: sub this back in.
//				$output .= wp_kses_post( $description );
					$output .= $description;
				}

				if ( $reply_count ) {
					$output .= '<p class="wpds-topic-activity-meta"><span class="wpds-term">' . __( 'replies', 'wpds' ) . '</span> ' . esc_html( $reply_count ) . '</p>';
				}

				$output .= '<p class="wpds-rss-discussion"><a href="' . esc_url( $permalink ) . '">' . __( 'join the discussion', 'wpds' ) . '</a></p></li>';
			}

			$output .= '</ul>';
		}

		$output = apply_filters( 'wpds_after_formatting_rss', $output, $topics, $args );

		return $output;
	}
}
