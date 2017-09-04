<?php

namespace WPDiscourse\Shortcodes;

class DiscourseRSSFormatter {
	use Utilities;

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
		$this->options       = $this->get_options();
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
		$excerpt_length = $args['excerpt_length'];
		$topic_list_id  = 'wpds_rss_list_' . time();
		$output         = '<ul class="wpds-rss-list" id="' . esc_attr( $topic_list_id ) . '">';
		foreach ( $topics as $topic ) {
			$description = ! empty( $topic['description'] ) ? $topic['description'] : '';
			if ( 'full' !== $excerpt_length ) {
				$description = wp_trim_words( wp_strip_all_tags( $description ), $args['excerpt_length'] );
			}

			$author = ! empty( $topic['username'] ) ? $topic['username'] : '';
			if ( ! empty( $topic['username'] ) && ! empty( $topic['name'] ) ) {
				$author = $topic['username'] . ' ' . $topic['name'];
			} elseif ( ! empty( $topic['username'] ) ) {
				$author = $topic['username'];
			} else {
				$author = '';
			}
			// Todo: set this in discourse-rss.php?
			$cleaned_name  = trim( $author, '\@' );
			$author_url    = "{$this->discourse_url}/u/{$cleaned_name}";
			$category_name = ! empty( $topic['category'] ) ? $topic['category'] : '';
			$category      = $this->find_discourse_category_by_name( $category_name );
			$wp_permalink  = ! empty( $topic['wp_permalink'] ) ? $topic['wp_permalink'] : null;

			$output .= '<li class="wpds-rss-topic ' . esc_attr( $category['slug'] ) . '">';

			$output .= '<header>';
			$output .= '<h3 class="wpds-rss-title"><a href="' . esc_url( $topic['permalink'] ) . '">' . esc_html( $topic['title'] ) . '</a></h3>';
			$output .= '<span class="wpds-shortcode-category" >' . $this->discourse_category_badge( $category ) . '</span>';
			$output .= '<div class="wpds-rss-poster-meta">';

			$output .= '<a href="' . esc_url( $author_url ) . '">';
			if ( 'true' === $args['display_avatars'] && ! empty( $topic['avatar_url'] ) ) {
				$avatar_image = '<img class="wpds-rss-avatar" src="' . esc_url( $topic['avatar_url'] ) . '">';

				$output .= apply_filters( 'wpds_shorcodes_avatar', $avatar_image, esc_url( $topic['avatar_url'] ) );
			}

			$output .= '<span class="wpds-rss-username">' . esc_html( $cleaned_name ) . '</span></a><span class="wpds-created-at">' . esc_html( $topic['date'] ) . '</span>';
			if ( $wp_permalink && ! empty( $args['display_permalink'] ) ) {
				// Todo: don't use <br><br>
				$output .= '<br><br><span class="wpds-term wpds-wp-link"> orginally published at </span><a href="' . esc_url( $wp_permalink ) . '">' . esc_url( $wp_permalink ) . '</a>';
			}
			$output .= '</div>';
			$output .= '</header>';

			if ( 'full' !== $excerpt_length ) {
				$output .= '<p class="wpds-discussion-excerpt">' . wp_kses_post( $description ) . '</p>';
			} else {
				// Todo: sub this back in.
//				$output .= wp_kses_post( $description );

				$output .= '<div class="wpds-full-discussion">' . $description . '</div>';
			}
			if ( ! empty( $topic['reply_count'] ) ) {
				$reply_text = 1 === $topic['reply_count'] ? __( 'reply', 'wpds' ) : __( 'replies', 'wpds' );
				$output     .= '<p class="wpds-topic-activity-meta">' . esc_html( $topic['reply_count'] ) . ' <span class="wpds-term">' . esc_html( $reply_text ) . '</span></p>';
				$output     .= '<div class="wpds-discussion-link-wrapper"><p class="wpds-discussion-link"><a href="' . esc_url( $topic['permalink'] ) . '" class="has-replies">' . __( 'join the discussion', 'wpds' ) . '</a></p></div></li>';
			} else {
				$reply_text = __( 'no replies', 'wpds' );
				$output     .= '<p class="wpds-topic-activity-meta"><span class="wpds-term">' . esc_html( $reply_text ) . '</span></p>';
				$output     .= '<div class="wpds-discussion-link-wrapper"><p class="wpds-discussion-link"><a href="' . esc_url( $topic['permalink'] ) . '" class="no-replies">' . __( 'start the discussion', 'wpds' ) . '</a></p></div></li>';
			}
		}

		$output .= '</ul>';

		apply_filters( 'wpds_after_formatting_rss', $output, $topics, $args );

		return $output;
	}

	protected function display_topic( $topic ) {

		return ! $topic['pinned_globally'] && 'regular' === $topic['archetype'] && - 1 !== $topic['posters'][0]['user_id'];
	}

	protected function find_discourse_category_by_name( $name ) {
		$categories = $this->get_discourse_categories();
		foreach ( $categories as $category ) {
			if ( $name === $category['name'] ) {

				return $category;
			}
		}

		return null;
	}

	/**
	 * Finds the category of a topic.
	 *
	 * @param array $topic A Discourse topic.
	 *
	 * @return null
	 */
	protected function find_discourse_category(
		$topic
	) {
		$categories  = $this->get_discourse_categories();
		$category_id = $topic['category_id'];

		foreach ( $categories as $category ) {
			if ( $category_id === $category['id'] ) {
				return $category;
			}
		}

		return null;
	}

	/**
	 * Creates the markup for a category badge.
	 *
	 * @param array $category A Discourse category.
	 *
	 * @return string
	 */
	protected function discourse_category_badge(
		$category
	) {
		$category_name  = $category['name'];
		$category_color = '#' . $category['color'];
		$category_badge = '<span class="discourse-shortcode-category-badge" style="width: 8px; height: 8px; background-color: ' .
		                  esc_attr( $category_color ) . '; display: inline-block;"></span><span class="discourse-category-name"> ' . esc_html( $category_name ) . '</span>';

		return $category_badge;
	}

	/**
	 * Formats the last_activity string.
	 *
	 * @param string $last_activity The time of the last activity on the topic.
	 *
	 * @return string
	 */
	protected function calculate_last_activity(
		$last_activity
	) {
		$now           = time();
		$last_activity = strtotime( $last_activity );
		$seconds       = $now - $last_activity;

		$minutes = intval( $seconds / 60 );
		if ( $minutes === 0 ) {
			return 'A few seconds ago';
		}
		if ( $minutes < 60 ) {
			return 1 === $minutes ? '1 minute ago' : $minutes . ' minutes ago';
		}

		$hours = intval( $minutes / 60 );
		if ( $hours < 24 ) {
			return 1 === $hours ? '1 hour ago' : $hours . ' hours ago';
		}

		$days = intval( $hours / 24 );
		if ( $days < 30 ) {
			return 1 === $days ? '1 day ago' : $days . ' days ago';
		}

		$months = intval( $days / 30 );
		if ( $months < 12 ) {
			return 1 === $months ? '1 month ago' : $months . ' months ago';
		}

		$years = intval( $months / 12 );

		return 1 === $years ? '1 year ago' : $years . ' years ago';
	}

}