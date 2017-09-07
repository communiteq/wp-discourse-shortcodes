<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseTopicFormatter {
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


		do_action( 'wpds_before_topiclist', $discourse_topics, $args );
		$use_plugin_formatting = apply_filters( 'wpds_use_plugin_topiclist_formatting', true );
		$output                = '';

		if ( $use_plugin_formatting ) {

			$topics            = $discourse_topics['topic_list']['topics'];
			$users             = $discourse_topics['users'];
			$poster_avatar_url = '';
			$poster_username   = '';
			$topic_count       = 0;
			$use_ajax          = ! empty( $this->options['wpds_ajax_refresh'] ) &&
			                     ! empty( $this->options['wpds_topic_webhook_refresh'] ) &&
			                     ( 'latest' === $args['source'] || 'daily' === $args['period'] );
			$ajax_class        = $use_ajax ? ' wpds-topiclist-refresh' : '';
			$tile_class       = 'true' === $args['tile'] ? ' wpds-tile' : '';

			$output = '<div class="wpds-tile-wrapper' . esc_attr( $ajax_class ) . '"><ul class="wpds-topiclist' . esc_attr( $tile_class ) . '">';

			if ( $use_ajax ) {
				$output .= $this->render_topics_shortcode_options( $args );
			}

			foreach ( $topics as $topic ) {
				if ( $topic_count < $args['max_topics'] && $this->display_topic( $topic ) ) {
					$topic_url            = $this->options['url'] . "/t/{$topic['slug']}/{$topic['id']}";
					$created_at           = date_create( get_date_from_gmt( $topic['created_at'] ) );
					$created_at_formatted = date_format( $created_at, 'F j, Y' );
					$category             = $this->find_discourse_category( $topic );
					$like_count           = apply_filters( 'wpds_topiclist_like_count', $topic['like_count'] );
					$likes_class          = $like_count ? ' wpds-has-likes' : '';
					$reply_count          = $topic['posts_count'] - 1;
					$posters              = $topic['posters'];
					$cooked = ! empty( $topic['cooked']) ? $topic['cooked'] : null;

					foreach ( $posters as $poster ) {
						if ( preg_match( '/Original Poster/', $poster['description'] ) ) {
							$original_poster_id = $poster['user_id'];
							foreach ( $users as $user ) {
								if ( $original_poster_id === $user['id'] ) {
									$poster_username   = $user['username'];
									$avatar_template   = str_replace( '{size}', 44, $user['avatar_template'] );
									$poster_avatar_url = $this->options['url'] . $avatar_template;
								}
							}
						}
					}

					// Todo: rename the wpds-topic-poster-meta class.
					$output .= '<li class="wpds-topic"><div class="wpds-topic-poster-meta">';


					$output .= '<header>';
					$output .= '<span class="wpds-created-at">' . esc_html( $created_at_formatted ) . '</span><br>';
					$output .= '<h4 class="wpds-topic-title"><a href="' . esc_url( $topic_url ) . '">' . esc_html( $topic['title'] ) . '</a></h4>';
					$output .= '<span class="wpds-term">' . __( '', 'wpds' ) . '</span> <span class="wpds-shortcode-category">' . $this->discourse_category_badge( $category ) . '</span>';
					$output .= '</header>';
					$output .= '<div class="wpds-topiclist-content">' . $cooked . '</div>';
					$output .= '<footer>';
					$output .= '<div class="wpds-topiclist-meta">';
					$output .= '<span class="wpds-topiclist-topic-meta">';
					if ( 'true' === $args['display_avatars'] ) {
						$avatar_image = '<img class="wpds-latest-avatar" src="' . esc_url( $poster_avatar_url ) . '">';

						$output .= apply_filters( 'wpds_topiclist_avatar', $avatar_image, esc_url( $poster_avatar_url ) );
					}
					$output .= '<span class="wpds-topiclist-username"><span class="wpds-term">' . __( 'posted by ', 'wpds' ) . '</span>' . esc_html( $poster_username ) . '</span>';
					$output .= '</span>';
					$output .= '<span class="wpds-likes-and-replies">';
					$output .= '<span class="wpds-topiclist-likes' . esc_attr( $likes_class ) . '"><i class="icon-heart" aria-hidden="true"></i><span class="wpds-topiclist-like-count">' . esc_attr( $like_count ) . '</span></span>';
					$output .= '<a class="wpds-topiclist-reply-link" href="' . esc_url( $topic_url ) . '"><i class="icon-reply" aria-hidden="true"></i><span class="wpds-topiclist-replies">' . esc_attr( $reply_count ) . '</span></a>';
					$output .= '</div>';
					$output .= '</footer></div></li>';

					$topic_count += 1;
				}// End if().
			}// End foreach().
			$output .= '</ul></div>';
		}

		$output = apply_filters( 'wpds_after_topiclist_formatting', $output, $discourse_topics, $args );

		return $output;
	}

	protected function display_topic( $topic ) {

		return ! $topic['pinned_globally'] && 'regular' === $topic['archetype'] && - 1 !== $topic['posters'][0]['user_id'];
	}
}
