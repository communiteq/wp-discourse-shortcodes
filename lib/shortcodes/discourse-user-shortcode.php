<?php
/**
 * Outputs the discourse_user shortcode.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

/**
 * Class DiscourseUserShortcode
 *
 * @package WPDiscourse\Shortcodes
 */
class DiscourseUserShortcode {

	/**
	 * An instance of the DiscourseUser class.
	 *
	 * @access protected
	 * @var DiscourseUser
	 */
	protected $discourse_user;

	/**
	 * DiscourseUserShortcode constructor.
	 *
	 * @param DiscourseUser $discourse_user An instance of the DiscourseUser class.
	 */
	public function __construct( $discourse_user ) {
		$this->discourse_user = $discourse_user;

		add_shortcode( 'discourse_user', array( $this, 'discourse_user' ) );
		add_shortcode( 'discourse_users', array( $this, 'discourse_users' ) );
	}

	/**
	 * Create the user shortcode.
	 *
	 * @param array $args The shortcode arguments.
	 * @return string
	 */
	public function discourse_user( $args ) {

		$discourse_user = $this->discourse_user->get_user( $args );

		if ( is_wp_error( $discourse_user ) ) {

			return '';
		}

		return $discourse_user;
	}

	/**
	 * Create the users shortcode.
	 *
	 * @param array $args The shortcode arguments.
	 * @return string
	 */
	public function discourse_users( $args ) {

		$discourse_users = $this->discourse_user->get_users( $args );

		if ( is_wp_error( $discourse_users ) ) {

			return '';
		}

		return $discourse_users;
	}
}
