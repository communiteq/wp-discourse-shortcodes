<?php
/**
 * Plugin Name: WP-Discourse Shortcodes
 * Description: Hooks into the wp-discourse plugin to create shortcodes for login links to Discourse
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourseShortcodes\Shortcodes;

add_action( 'plugins_loaded', __NAMESPACE__ . '\\init' );

function init() {
	if ( class_exists( '\WPDiscourse\Discourse\Discourse' ) ) {
		$discourse_shortcodes = new \WPDiscourseShortcodes\Shortcodes\WPDiscourseShortcodes();
	}
}

class WPDiscourseShortcodes {
	protected $options;
	protected $base_url;

	public function __construct() {
		$this->options  = get_option( 'discourse' );
		$this->base_url = array_key_exists( 'url', $this->options ) ? $this->options['url'] : '';

		add_shortcode( 'discourse_link', array( $this, 'discourse_link' ) );
		add_shortcode( 'discourse_topic', array( $this, 'discourse_topic' ) );
		add_shortcode( 'discourse_message', array( $this, 'discourse_message' ) );
		add_shortcode( 'discourse_latest', array( $this, 'discourse_latest' ) );
	}

	public function discourse_latest( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'max_topics' => 5,
		), $atts );
		$latest_topics     = $this->latest_topics( $parsed_attributes );
		$formatted_topics  = $this->format_topics( $latest_topics );

		return $formatted_topics;
	}

	protected function latest_topics( $args ) {
		$latest_url = esc_url_raw( $this->base_url . '/latest.json' );

		$latest_topics = get_transient( 'wp_discourse_latest_topics' );
		if ( empty( $latest_topics ) ) {
			$remote = wp_remote_get( $latest_url );
			if ( ! $this->validate( $remote ) ) {
				return 'We are currently unable to retrieve the latest Discourse topics.';
			}

			$latest_topics = json_decode( wp_remote_retrieve_body( $remote ), true );
//			if ( array_key_exists( 'topic_list', $remote ) ) {
//				$topic_list    = $remote['topic_list'];
//				$latest_topics = array_slice( $topic_list['topics'], 0, $args['max_topics'] );
//				set_transient( 'wp_discourse_latest_topics', $latest_topics, 1 * MINUTE_IN_SECONDS );
//			}
			set_transient( 'wp_discourse_latest_topics', $latest_topics );
		}

		return $latest_topics;
	}

	protected function format_topics( $topics_array ) {
		$output = '<ul class="discourse-topiclist">';
		$topics = $topics_array['topic_list']['topics'];
		$users  = $topics_array['users'];
		foreach ( $topics as $topic ) {
			if ( ! $topic['pinned'] ) {
				$topic_url = esc_url_raw( $this->base_url . "/t/{$topic['slug']}/{$topic['id']}" );
				$created_at = date_create( get_date_from_gmt( $topic['created_at'] ) );
				$created_at_formatted = date_format( $created_at, 'F j, Y' );
				$last_activity = date_create( get_date_from_gmt( $topic['last_posted_at'] ) );
				$last_activity_formatted = date_format( $last_activity, 'F j, Y' );
				$posters   = $topic['posters'];
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
				$output .= '<img class="discourse-latest-avatar" src="' . $poster_avatar_url . '">';
				$output .=  '<span class="discourse-username">' . $poster_username . '</span>' . ' posted on ' . '<span class="discourse-created-at">' . $created_at_formatted . ':</span>';
				$output .= '</div>';
				$output .= '<a href="' . $topic_url . '">';
				$output .= '<h3 class="discourse-topic-title">' . $topic['title'] . '</h3>';
				$output .= '</a>';
				$output .= '<div class="discourse-topic-activity-meta">';
				$output .= 'replies: <span class="discourse-num-replies">' . ( $topic['posts_count'] - 1 ) . '</span>, last activity: <span class="discourse-last-activity">' . $last_activity_formatted . '</span>';


				$output .= '</div>';


				$output .= '</li>';
			}
		}

		$output .= '</ul>';

		return $output;


	}

	public function discourse_link( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'link_text'   => 'Visit Our Forum',
			'return_path' => '/',
			'classes'     => '',
			'login'       => true,
		), $atts );

		if ( 'false' === $parsed_attributes['login'] ) {
			$url = $this->base_url . $parsed_attributes['return_path'];
		} else {
			$url = esc_url_raw( $this->base_url . '/session/sso?return_path=' . $parsed_attributes['return_path'] );
		}

		$classes = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';

		$discourse_link = '<a ' . $classes . ' href="' . $url . '">' . $parsed_attributes['link_text'] . '</a>';

		return $discourse_link;
	}

	public function discourse_topic( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'link_text' => 'Start a topic on our forum',
			'classes'   => '',
		), $atts );

		$title    = isset( $atts['title'] ) ? $atts['title'] : null;
		$body     = isset( $atts['body'] ) ? $atts['body'] : null;
		$category = isset( $atts['category'] ) ? $atts['category'] : null;

		$sso_url     = $this->base_url . '/session/sso?return_path=';
		$return_path = urlencode( add_query_arg( array(
			'title'    => $title,
			'body'     => $body,
			'category' => $category,
		), $this->base_url . '/new-topic' ) );

		$topic_url = $sso_url . $return_path;

		$classes = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';

		$topic_link = '<a ' . $classes . ' href="' . $topic_url . '">' . $parsed_attributes['link_text'] . '</a>';

		return $topic_link;
	}

	public function discourse_message( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'link_text' => 'Contact Us',
			'classes'   => '',
		), $atts );

		$title    = isset( $atts['title'] ) ? $atts['title'] : null;
		$username = isset( $atts['username'] ) ? $atts['username'] : null;
		$message  = isset( $atts['message'] ) ? $atts['message'] : null;

		$sso_url     = $this->base_url . '/session/sso?return_path=';
		$return_path = urlencode( add_query_arg( array(
			'username' => $username,
			'title'    => $title,
			'body'     => $message,
		), $this->base_url . '/new-message' ) );

		$topic_url = $sso_url . $return_path;

		$classes = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';

		$topic_link = '<a ' . $classes . ' href="' . $topic_url . '">' . $parsed_attributes['link_text'] . '</a>';

		return $topic_link;
	}

	/**
	 * Validates the response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @param array $response The response from `wp_remote_get` or `wp_remote_post`.
	 *
	 * @return int
	 */
	protected function validate( $response ) {
		// There will be a WP_Error if the server can't be accessed.
		if ( is_wp_error( $response ) ) {
			error_log( $response->get_error_message() );

			return 0;

			// There is a response from the server, but it's not what we're looking for.
		} elseif ( intval( wp_remote_retrieve_response_code( $response ) ) !== 200 ) {
			$error_message = wp_remote_retrieve_response_message( $response );
			error_log( 'There has been a problem accessing your Discourse forum. Error Message: ' . $error_message );

			return 0;
		} else {
			// Valid response.
			return 1;
		}
	}
}
