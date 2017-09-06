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

		$topics            = $discourse_topics['topic_list']['topics'];
		$users             = $discourse_topics['users'];
		$poster_avatar_url = '';
		$poster_username   = '';
		$topic_count       = 0;

		$output = '<ul class="wpds-topiclist">';

		if ( ! empty( $this->options['wpds_ajax_refresh']) && ( 'latest' === $args['source'] || 'daily' === $args['period'])) {
			$output .= $this->render_topics_shortcode_options( $args );
		}

		foreach ( $topics as $topic ) {
			write_log('topic', $topic);
			if ( $topic_count < $args['max_topics'] && $this->display_topic( $topic ) ) {
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

				$output .= '<li class="wpds-topic"><div class="wpds-topic-poster-meta">';

				if ( 'true' === $args['display_avatars'] ) {
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

	protected function display_topic( $topic ) {

		return ! $topic['pinned_globally'] && 'regular' === $topic['archetype'] && - 1 !== $topic['posters'][0]['user_id'];
	}
}
