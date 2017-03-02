<?php

namespace WPDiscourse\LatestTopics;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseLatestShortcode {
	/**
	 * The plugin options.
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
	 * An instance of the LatestTopics class.
	 *
	 * @access protected
	 * @var LatestTopics
	 */
	protected $latest_topics;

	/**
	 * DiscourseLatestShortcode constructor.
	 *
	 * @param LatestTopics $latest_topics An instance of the LatestTopics class.
	 */
	public function __construct( $latest_topics ) {
		$this->latest_topics = $latest_topics;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_shortcode( 'discourse_latest', array( $this, 'discourse_latest' ) );
	}

	/**
	 * Set the plugin options.
	 */
	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Create the shortcode.
	 *
	 * @param array $atts The shortcode attributes.
	 *
	 * @return string
	 */
	public function discourse_latest( $atts ) {

		$attributes = shortcode_atts( array(
			'max_topics' => 5,
			'display_avatars' => 'true',
		), $atts );

		$discourse_topics = $this->latest_topics->get_latest_topics();

		return $this->format_topics( $discourse_topics, $attributes );
	}

	/**
	 * Format the Discourse topics.
	 *
	 * @param array $discourse_topics The array of topics.
	 * @param array $args The shortcode attributes.
	 *
	 * @return string
	 */
	protected function format_topics( $discourse_topics, $args ) {

		if ( empty( $discourse_topics['topic_list'] ) ) {
			return '';
		}

		$topics = $discourse_topics['topic_list']['topics'];

		// If the first topic is pinned, don't display it.
		if ( ! empty( $topics[0]['pinned'] ) && 1 === intval( $topics[0]['pinned'] ) ) {
			$topics = array_slice( $topics, 1, $args['max_topics'] );
		} else {
			$topics = array_slice( $topics, 0, $args['max_topics'] );
		}

		$users             = $discourse_topics['users'];
		$poster_avatar_url = '';
		$poster_username   = '';

		$output = '<ul class="dclt-topiclist">';

		foreach ( $topics as $topic ) {
			$topic_url            = $this->options['url'] . "/t/{$topic['slug']}/{$topic['id']}";
			$created_at           = date_create( get_date_from_gmt( $topic['created_at'] ) );
			$created_at_formatted = date_format( $created_at, 'F j, Y' );
			$last_activity        = $topic['last_posted_at'];
			$category             = $this->find_discourse_category( $topic );
			$posters              = $topic['posters'];
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

			$avatar_image = '<img class="dclt-latest-avatar" src="' . esc_url( $poster_avatar_url ) . '">';
			$output .= '<li class="dclt-topic"><div class="dclt-topic-poster-meta">';
			if ( 'true' === $args['display_avatars'] ) {
				$output .= apply_filters( 'dclt_shorcodes_avatar', $avatar_image, esc_url( $poster_avatar_url ) );
			}
			$output .= '<span class="dclt-username">' . esc_html( $poster_username ) . '</span>' . '<span class="dclt-term"> posted on </span>
						<span class="dclt-created-at">' . $created_at_formatted . '</span><br>
						<span class="dclt-term">in </span><span class="dclt-shortcode-category" >' . $this->discourse_category_badge( $category ) . '</span></div>
						<p class="dclt-topic-title"><a href="' . esc_url( $topic_url ) . '">' . esc_html( $topic['title'] ) . '</a></p>
						<p class="dclt-topic-activity-meta"><span class="dclt-term">replies</span> <span class="dclt-num-replies">' .
			           esc_attr( ( $topic['posts_count'] ) - 1 ) .
			           '</span> <span class="dclt-term">last activity</span> <span class="dclt-last-activity">' .
			           // Unless webhooks are setup, the last activity will only be as acurate as the cache period.
			           $this->calculate_last_activity( $last_activity ) . '</span></p></li>';
		}
		$output .= '</ul>';

		return $output;
	}

	/**
	 * Finds the category of a topic.
	 *
	 * @param array $topic A Discourse topic.
	 *
	 * @return null
	 */
	protected function find_discourse_category( $topic ) {
		$categories  = DiscourseUtilities::get_discourse_categories();
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
	protected function discourse_category_badge( $category ) {
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
	protected function calculate_last_activity( $last_activity ) {
		$now           = time();
		$last_activity = strtotime( $last_activity );
		$seconds       = $now - $last_activity;

		$minutes = intval( $seconds / 60 );
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