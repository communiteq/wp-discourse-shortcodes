<?php
/**
 * Formats Discourse topics.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseTopicFormatter
 *
 * @package WPDiscourse\Shortcodes
 */
class DiscourseTopicFormatter {
	use Formatter;

	/**
	 * The merged options from WP Discourse and WP Discourse Shortcodes.
	 *
	 * All options are held in a single array, use a custom plugin prefix to avoid naming collisions with wp-discourse.
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
	 * DiscourseTopicFormatter constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	/**
	 * Sets up the plugin options.
	 */
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

		if ( empty( $this->discourse_url ) || empty( $discourse_topics['topic_list'] || empty( $discourse_topics['topic_list']['topics'] ) ) ) {

			return '';
		}

		do_action( 'wpds_before_topiclist', $discourse_topics, $args );
		// To bypass the plugin's formatting, return false from this hook, then hook into 'wpds_after_topiclist_formatting' to add your own formatting.
		$use_plugin_formatting = apply_filters( 'wpds_use_plugin_topiclist_formatting', true );
		$output                = '';

		if ( $use_plugin_formatting ) {
			$topics            = $discourse_topics['topic_list']['topics'];
			$users             = $discourse_topics['users'];
			$source            = $args['source'];
			$poster_avatar_url = '';
			$poster_username   = '';
			$topic_count       = 0;
			$use_ajax          = ! empty( $this->options['wpds_ajax_refresh'] ) && 'latest' === $source;
			$ajax_class        = $use_ajax ? ' wpds-topiclist-refresh' : '';
			$tile_class        = 'true' === $args['tile'] ? ' wpds-tile' : '';
			$source_class      = 'latest' === $source ? ' wpds-latest-topics' : ' wpds-' . $source . '-' . $args['period'] . '-topics';
			$date_format       = ! empty( $this->options['custom-datetime-format'] ) ? $this->options['custom-datetime-format'] : 'Y/m/d';

			$output = '<div class="wpds-tile-wrapper' . esc_attr( $ajax_class ) . '" data-wpds-shortcode-id="' . esc_attr( $args['id'] ) .
					  '"><ul class="wpds-topiclist' . esc_attr( $tile_class ) . esc_attr( $source_class ) . '">';

			// Renders a div with data attributes that are retrieved by the client.
			if ( $use_ajax ) {
				$output .= $this->render_topics_shortcode_options( $args );
			}

			foreach ( $topics as $topic ) {
				if ( $topic_count < $args['max_topics'] && $this->display_topic( $topic ) ) {
					$topic_url            = $this->options['url'] . "/t/{$topic['slug']}/{$topic['id']}";
					$created_at           = date_create( get_date_from_gmt( $topic['created_at'] ) );
					$created_at_formatted = date_format( $created_at, $date_format );
					$category             = $this->find_discourse_category( $topic );
					$category_class       = ! empty( $category ) ? ' ' . $category['slug'] : '';
					$like_count           = $topic['like_count'];
					$likes_class          = $like_count ? ' wpds-has-likes' : '';
					$reply_count          = $topic['posts_count'] - 1;
					$posters              = $topic['posters'];
					$cooked               = ! empty( $topic['cooked'] ) ? $this->get_topic_content( $topic['cooked'], $args['excerpt_length'] ) : null;

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

					$output .= '<li class="wpds-topic' . esc_attr( $category_class ) . '">';
					$output .= '<div class="wpds-topiclist-clamp">';

					// Add content above the header.
					$output = apply_filters( 'wpds_topiclist_above_header', $output, $topic, $category, $poster_avatar_url, $args );

					$output .= '<header>';

					if ( 'top' === $args['username_position'] ) {
						$output .= '<span class="wpds-topiclist-username">' . esc_html( $poster_username ) . '</span> <span class="wpds-topiclist-username"><span class="wpds-term">' . __( 'posted on ', 'wpds' ) . '</span>';
					}

					if ( 'top' === $args['date_position'] ) {
						$output .= '<span class="wpds-created-at">' . esc_html( $created_at_formatted ) . '</span>';
					}

					$output .= '<h4 class="wpds-topic-title"><a href="' . esc_url( $topic_url ) . '">' . esc_html( $topic['title'] ) . '</a></h4>';

					if ( 'top' === $args['category_position'] ) {
						$output .= '<span class="wpds-shortcode-category">' . $this->discourse_category_badge( $category ) . '</span>';
					}

					$output .= '</header>';

					if ( $cooked ) {
						$output .= '<div class="wpds-topiclist-content">' . wp_kses_post( $cooked ) . '</div>';
					}

					$output = apply_filters( 'wpds_topiclist_above_footer', $output, $topic, $category, $poster_avatar_url, $args );
					$output .= '</div>'; // End of .wpds-topiclist-clamp.

					$output .= '<footer>';
					$output .= '<div class="wpds-topiclist-footer-meta">';
					if ( 'true' === $args['display_avatars'] ) {
						$avatar_image = '<img class="wpds-latest-avatar" src="' . esc_url( $poster_avatar_url ) . '">';

						$output .= apply_filters( 'wpds_topiclist_avatar', $avatar_image, esc_url( $poster_avatar_url ) );
					}
					if ( 'bottom' === $args['username_position'] ) {
						$output .= '<span class="wpds-topiclist-username">' . esc_html( $poster_username ) . '</span>';
					}
					if ( 'bottom' === $args['category_position'] ) {
						$output .= '<span class="wpds-shortcode-category">' . $this->discourse_category_badge( $category ) . '</span>';
					}
					$output .= '<span class="wpds-likes-and-replies">';
					$output .= '<span class="wpds-topiclist-likes' . esc_attr( $likes_class ) . '"><i class="icon-heart" aria-hidden="true"></i><span class="wpds-topiclist-like-count">' . esc_attr( $like_count ) . '</span></span>';
					$output .= '<a class="wpds-topiclist-reply-link" href="' . esc_url( $topic_url ) . '"><i class="icon-reply" aria-hidden="true"></i><span class="wpds-topiclist-replies">' . esc_attr( $reply_count ) . '</span></a>';
					$output .= '</div>';
					$output .= '</footer>';
					$output = apply_filters( 'wpds_topiclist_below_footer', $output, $topic, $category, $args );
					$output .= '</li>';

					$topic_count++;
				}// End if().
			}// End foreach().
			$output .= '</ul></div>';
		}

		$output = apply_filters( 'wpds_after_topiclist_formatting', $output, $discourse_topics, $args );

		return $output;
	}

	/**
	 * Whether or not to display a topic.
	 *
	 * Don't display topics that are pinned_globally, or created by the system user.
	 *
	 * @param array $topic The topic to check.
	 *
	 * @return bool
	 */
	protected function display_topic( $topic ) {

		return ! $topic['pinned_globally'] && 'regular' === $topic['archetype'] && - 1 !== $topic['posters'][0]['user_id'];
	}
}
