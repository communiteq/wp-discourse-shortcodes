<?php
/**
 * Plugin Name: WP-Discourse Shortcodes
 * Description: Hooks into the wp-discourse plugin to create shortcodes for login links to Discourse
 * Version: 0.1
 * Author: scossar
 */

namespace WPDiscourseShortcodes\Shortcodes;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

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
		add_shortcode( 'discourse_groups', array( $this, 'discourse_groups' ) );
	}

	public function discourse_groups() {
		$groups           = $this->get_discourse_groups();
		$formatted_groups = $this->format_groups( $groups );

		return $formatted_groups;

	}

	protected function get_topic_by_slug( $slug ) {
		$base_url = $this->base_url . "/t/{$slug}.json";
		$response = wp_remote_get( $base_url );

		if ( ! DiscourseUtilities::validate( $response ) ) {

			return null;
		}
		$topic = json_decode( wp_remote_retrieve_body( $response ), true );

		return $topic;
	}

	protected function get_group_description( $group_name ) {
		$group_name        = str_replace( '_', '-', $group_name );
		$topic_slug        = 'about-the-' . $group_name . '-group';
		$group_description = $this->get_topic_by_slug( $topic_slug )['post_stream']['posts'][0]['cooked'];

		return $group_description;
	}

	protected function get_group_owners( $group_name ) {
		$base_url = $this->base_url . "/groups/{$group_name}/members.json";
		$response = wp_remote_get( $base_url );

		if ( ! DiscourseUtilities::validate( $response ) ) {
			return null;
		}

		$group_members = json_decode( wp_remote_retrieve_body( $response ), true );
		$owners        = $group_members['owners'];

		return $owners;
	}

	protected function format_groups( $groups ) {
		$output = '<div class="discourse-shortcode-groups">';
		foreach ( $groups as $group ) {
			if ( ! $group['automatic'] && $group['visible'] ) {
				$pretty_group_name = str_replace( '_', ' ', $group['name'] );
				$user_count        = $group['user_count'];
				// For now only the first owner is being selected. Eventually it should be possible to send the
				// message to all of the group's owners.
				$owner_names = isset( $group['owners'] ) ? $group['owners'] : null;

				$output .= '<div class="discourse-shortcode-group clearfix">';
				$output .= '<h3 class="discourse-shortcode-groupname">' . $pretty_group_name . '</h3>';
				$output .= '<span class="discourse-shortcode-groupcount">';
				$output .= 1 === intval( $user_count ) ? '1 member' : intval( $user_count ) . ' members';
				$output .= '</span>';
				$output .= '<div class="discourse-shortcode-group-description">';
				$output .= $group['description'];
				$output .= '</div>';
				$request_args = array(
					'link_text' => 'Request to join the ' . $pretty_group_name . ' group',
					'title'     => 'A request to join the ' . $pretty_group_name . ' group',
					'username'  => $owner_names,
					'classes'   => 'discourse-button',
				);
				$output .= $this->discourse_message( $request_args );
				$output .= '</div>';
			}
		}
		$output .= '</div>';

		return $output;
	}


	protected function get_discourse_groups() {
		$options = $this->options;

		$groups = get_transient( 'discourse_groups' );

		if ( empty( $groups ) ) {
			$url = array_key_exists( 'url', $options ) ? $options['url'] : '';
			$url = add_query_arg( array(
				'api_key'      => array_key_exists( 'api-key', $options ) ? $options['api-key'] : '',
				'api_username' => array_key_exists( 'publish-username', $options ) ? $options['publish-username'] : '',
			), $url . '/admin/groups.json' );

			$url      = esc_url_raw( $url );
			$response = wp_remote_get( $url );

			if ( ! DiscourseUtilities::validate( $response ) ) {
				return null;
			}

			$groups = json_decode( wp_remote_retrieve_body( $response ), true );

			foreach ( $groups as $key => $group ) {
				$groups[$key]['description'] = $this->get_group_description( $group['name'] );
				$owners                        = $this->get_group_owners( $group['name'] );
				if ( $owners ) {
					foreach ( $owners as $owner ) {
						$owner_names[] = $owner['username'];
					}
					$groups[ $key ]['owners'] = $owner_names[0];
				} else {
					$groups[$key]['owners'] = isset( $this->options['publish-username'] ) ? $this->options['publish-username'] : null;
				}
			}

			set_transient( 'discourse_groups', $groups, HOUR_IN_SECONDS );
		}

		return $groups;
	}

	public function discourse_latest( $atts ) {
		$parsed_attributes = shortcode_atts( array(
			'max_topics' => 5,
		), $atts );
		$latest_topics     = $this->latest_topics();
		$formatted_topics  = $this->format_topics( $parsed_attributes, $latest_topics );

		return $formatted_topics;
	}

	protected function latest_topics() {
		$latest_url = esc_url_raw( $this->base_url . '/latest.json' );

		$latest_topics = get_transient( 'wp_discourse_latest_topics' );
		if ( empty( $latest_topics ) ) {
			$remote = wp_remote_get( $latest_url );
			if ( ! DiscourseUtilities::validate( $remote ) ) {
				return 'We are currently unable to retrieve the latest Discourse topics.';
			}

			$latest_topics = json_decode( wp_remote_retrieve_body( $remote ), true );
			set_transient( 'wp_discourse_latest_topics', $latest_topics, 10 * MINUTE_IN_SECONDS );
		}

		return $latest_topics;
	}

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
				$output .= '<img class="discourse-latest-avatar" src="' . $poster_avatar_url . '">';
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

		$classes        = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';
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

		$topic_url  = $sso_url . $return_path;
		$classes    = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';
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

		$topic_url  = $sso_url . $return_path;
		$classes    = $parsed_attributes['classes'] ? 'class="' . $parsed_attributes['classes'] . '"' : '';
		$topic_link = '<a ' . $classes . ' href="' . $topic_url . '">' . $parsed_attributes['link_text'] . '</a>';

		return $topic_link;
	}
}
