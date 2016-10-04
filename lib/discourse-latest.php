<?php

namespace WPDiscourseShortcodes\DiscourseLatest;

class DiscourseLatest {
	protected $utilities;
	protected $options;
	protected $base_url;

	public function __construct( $utilities ) {
		$this->utilities = $utilities;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_shortcode( 'discourse_latest', array( $this, 'discourse_latest' ) );
	}

	public function setup_options() {
		$this->options = $this->utilities->get_options();
		$this->base_url = $this->utilities->base_url( $this->options );
	}

	public function discourse_latest( $atts ) {
		$attributes = shortcode_atts( array(
			'max_topics' => 5,
			'cache_duration' => 10,
		), $atts );

		$latest_topics     = $this->latest_topics( $attributes['cache_duration'] );
		return $latest_topics ? $this->format_topics( $attributes, $latest_topics ) : '';
	}

	protected function latest_topics( $cache_duration ) {
		$latest_url = esc_url_raw( $this->base_url . '/latest.json' );

		$latest_topics = get_transient( 'wp_discourse_latest_topics' );
		if ( empty( $latest_topics ) ) {
			$remote = wp_remote_get( $latest_url );
			if ( ! $this->utilities->validate( $remote ) ) {
				return null;
			}

			$latest_topics = json_decode( wp_remote_retrieve_body( $remote ), true );
			set_transient( 'wp_discourse_latest_topics', $latest_topics, $cache_duration * MINUTE_IN_SECONDS );
		}

		return $latest_topics;
	}

	protected function find_discourse_category( $topic ) {
		$categories  = $this->utilities->get_discourse_categories();
		$category_id = $topic['category_id'];

		foreach ( $categories as $category ) {
			if ( $category_id === $category['id'] ) {
				return $category;
			}
		}

		return null;
	}

	protected function discourse_category_badge( $category ) {
		$category_name  = $category['name'];
		$category_color = '#' . $category['color'];
		$category_badge = '<span class="discourse-shortcode-category-badge" style="width: 8px; height: 8px; background-color: ' . $category_color . '; display: inline-block;"></span><span class="discourse-category-name"> ' . $category_name . '</span>';

		return $category_badge;
	}

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

	protected function format_topics( $args, $topics_array ) {
		$output = '<ul class="discourse-topiclist">';
		$topics = array_slice( $topics_array['topic_list']['topics'], 0, $args['max_topics'] );
		$users  = $topics_array['users'];
		foreach ( $topics as $topic ) {
			if ( ! $topic['pinned'] ) {
				$topic_url            = esc_url_raw( $this->base_url . "/t/{$topic['slug']}/{$topic['id']}" );
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
								$poster_avatar_url = esc_url_raw( $this->base_url . $avatar_template );
							}
						}
					}
				}

				$output .= '<li class="discourse-topic">';
				$output .= '<div class="discourse-topic-poster-meta">';
				$avatar_image = '<img class="discourse-latest-avatar" src="' . $poster_avatar_url . '">';
				$output .= apply_filters( 'wp_discourse_shorcodes_avatar', $avatar_image, $poster_avatar_url );
				$output .= '<span class="discourse-username">' . $poster_username . '</span>' . ' posted on ' . '<span class="discourse-created-at">' . $created_at_formatted . '</span><br>';
				$output .= 'in <span class="discourse-shortcode-category" >' . $this->discourse_category_badge( $category ) . '</span>';
				$output .= '</div>';
				$output .= '<a href="' . $topic_url . '">';
				$output .= '<h3 class="discourse-topic-title">' . $topic['title'] . '</h3>';
				$output .= '</a>';
				$output .= '<div class="discourse-topic-activity-meta">';
				$output .= 'replies <span class="discourse-num-replies">' . ( $topic['posts_count'] - 1 ) . '</span> last activity <span class="discourse-last-activity">' . $this->calculate_last_activity( $last_activity ) . '</span>';
				$output .= '</div>';
				$output .= '</li>';
			}
		}

		$output .= '</ul>';

		return $output;
	}
}