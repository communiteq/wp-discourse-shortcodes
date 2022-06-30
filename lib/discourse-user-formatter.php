<?php
/**
 * Formats a Discourse user.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseUserFormatter
 *
 * @package WPDiscourse\Shortcodes
 */
class DiscourseUserFormatter {
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
	 * Sets up the plugin options.
	 */
	public function setup_options() {
		$this->options       = DiscourseUtilities::get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
	}

	/**
	 * Format the Discourse user.
	 *
	 * @param array $user_data The user data.
	 * @param array $atts The shortcode attributes.
	 *
	 * @return string
	 */
	public function format_user( $user_data, $atts ) {
		$use_plugin_formatting = apply_filters( 'wpds_use_plugin_user_formatting', true );
		$output                = '';

		if ( $use_plugin_formatting ) {
			$output = "<div class='wpds-user-wrapper' data-discourse-user-id='{$user_data['id']}'>";

			if ( isset( $user_data['avatar_url'] ) ) {
				$output .= "<img src={$user_data['avatar_url']} class='wpds-avatar'>";
			}

			$output .= "<div class='wpds-username'>{$user_data['username']}</div>";

			if ( isset( $user_data['name'] ) ) {
				$output .= "<span class='wpds-name'>{$user_data['name']}</span>";
			}

			$output .= '</div>';
		}

		return apply_filters( 'wpds_after_user_formatting', $output, $user_data, $atts );
	}

	/**
	 * Format Discourse users.
	 *
	 * @param array $user_data The users data.
	 * @param array $atts The shortcode attributes.
	 *
	 * @return string
	 */
	public function format_users( $users_data, $atts ) {
		$use_plugin_formatting = apply_filters( 'wpds_use_plugin_users_formatting', true );
		$output                = '';

		if ( $use_plugin_formatting ) {
			$output = "<ul class='wpds-users-wrapper'>";

			foreach( $users_data as $user_data ) {
				$output .= $this->format_user( $user_data, $atts );
			}

			$output .= '</ul>';
		}

		return apply_filters( 'wpds_after_users_formatting', $output, $user_data, $atts );
	}
}
