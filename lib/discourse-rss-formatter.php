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
	 * Format the Discourse topics.
	 *
	 * @param array $discourse_topics The array of topics.
	 * @param array $args The shortcode attributes.
	 *
	 * @return string
	 */
	public function format_topics( $discourse_topics, $args ) {

		if ( empty( $this->discourse_url ) || empty( $discourse_topics['topic_list'] ) ) {

			return '';
		}

		$topics            = $discourse_topics['topic_list']['topics'];
		$users             = $discourse_topics['users'];
		$poster_avatar_url = '';
		$poster_username   = '';

		$output = '<ul class="wpds-topiclist">';

		$topic_count = 0;
		foreach ( $topics as $topic ) {
			if ( $topic_count < $args['max_topics'] && $this->display_topic( $topic ) ) {
				$topic_url            = $this->options['url'] . "/t/{$topic['slug']}/{$topic['id']}";
				$created_at           = date_create( get_date_from_gmt( $topic['created_at'] ) );
				$created_at_formatted = date_format( $created_at, 'F j, Y' );
				$last_activity        = $topic['last_posted_at'];
				$category             = $this->find_discourse_category( $topic );

				$output .= '<li class="wpds-topic"><div class="wpds-topic-poster-meta">';
				if ( 'true' === $args['display_avatars'] ) {
					$posters = $topic['posters'];
					foreach ( $posters as $poster ) {
						if ( preg_match( '/Original Poster/', $poster['description'] ) ) {
							$original_poster_id = $poster['user_id'];
							foreach ( $users as $user ) {
								if ( $original_poster_id === $user['id'] ) {
									$poster_username   = $user['username'];
									$avatar_template   = str_replace( '{size}', 22, $user['avatar_template'] );
									$poster_avatar_url = $this->options['url'] . $avatar_template;
								}
							}
						}
					}

					$avatar_image = '<img class="wpds-latest-avatar" src="' . esc_url( $poster_avatar_url ) . '">';

					$output .= apply_filters( 'wpds_shorcodes_avatar', $avatar_image, esc_url( $poster_avatar_url ) );
				}
				$output .= '<span class="wpds-username">' . esc_html( $poster_username ) . '</span>' . '<span class="wpds-term"> posted on </span><span class="wpds-created-at">' . $created_at_formatted . '</span><br>
						<span class="wpds-term">in </span><span class="wpds-shortcode-category" >' . $this->discourse_category_badge( $category ) . '</span></div>
						<p class="wpds-topic-title"><a href="' . esc_url( $topic_url ) . '">' . esc_html( $topic['title'] ) . '</a></p>
						<p class="wpds-topic-activity-meta"><span class="wpds-term">replies</span> <span class="wpds-num-replies">' .
				           esc_attr( ( $topic['posts_count'] ) - 1 ) .
				           '</span> <span class="wpds-term">last activity</span> <span class="wpds-last-activity">' .
				           // Unless webhooks are setup, the last activity will only be as acurate as the cache period.
				           $this->calculate_last_activity( $last_activity ) . '</span></p></li>';

				$topic_count += 1;
			}// End if().
		}// End foreach().
		$output .= '</ul>';

		return $output;
	}

	/**
	 * Format the Discourse RSS feed.
	 *
	 * @param array $discourse_topics The array of topics.
	 * @param array $args The shortcode attributes.
	 *
	 * @return string
	 */
	public function format_rss_topics( $topics ) {
		$output = '<ul class="wpds-topiclist">';
		foreach ( $topics as $topic ) {
			$author       = $topic['author'];
			$cleaned_name = trim( $author, '\@' );
			$author_url   = $this->discourse_url . '/u/' . $cleaned_name;
			$category     = $this->find_discourse_category_by_name( $topic['category'] );
			$wp_permalink = ! empty( $topic['wp_permalink'] ) ? $topic['wp_permalink'] : null;

			$output .= '<li class="wpds-rss-topic">';
			$output .= '<h3 class="wpds-topic-title"><a href="' . esc_url( $topic['permalink'] ) . '">' . esc_html( $topic['title'] ) . '</a></h3>';
			$output .= '<div class="wpds-topic-poster-meta"><span class="wpds-term">posted by </span><a href="' . esc_url( $author_url ) . '">' . esc_html( $cleaned_name ) . '</a>'
			           . '<span class="wpds-term"> on </span><span class="wpds-created-at">' . $topic['date']
			           . '</span><br><span class="wpds-term">in </span><span class="wpds-shortcode-category" >' . $this->discourse_category_badge( $category ) . '</span>';
			if ( $wp_permalink ) {
				$output .= '<br><span class="wpds-term"> orginally posted at </span><a href="' . esc_url( $wp_permalink ) . '">' . esc_url( $wp_permalink ) . '</a>';
			}

			$output .= '</div>';
			if ( count( $topic['images'] ) ) {
				$output .= '<p>' . $topic['images'][0] . '</p>';
			}
			$output .= join( '', $topic['description'] );
			if ( $topic['reply_count'] ) {
				$output .= '<p class="wpds-topic-activity-meta"><span class="wpds-term">Replies </span>' . $topic['reply_count'] . '</p>';
			}
			$output .= '<p><a href="' . esc_url( $topic['permalink'] ) . '">join the discussion</a></p>';
		}

		$output .= '</ul>';

		return $output;
	}

	protected function display_topic( $topic ) {

		return ! $topic['pinned_globally'] && 'regular' === $topic['archetype'] && - 1 !== $topic['posters'][0]['user_id'];
	}

	protected
	function find_discourse_category_by_name( $name ) {
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