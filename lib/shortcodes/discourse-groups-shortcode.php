<?php
/**
 * Outputs the discourse_groups shortcode.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

/**
 * Class DiscourseGroupsShortcode
 *
 * @package WPDiscourse\Shortcodes
 */
class DiscourseGroupsShortcode {

	/**
	 * The DiscourseGroups object.
	 *
	 * @access protected
	 * @var DiscourseGroups An instance of DiscourseGroups.
	 */
	protected $discourse_groups;

	/**
	 * DiscourseGroupsShortcode constructor.
	 *
	 * @param DiscourseGroups $discourse_groups An instance of DiscourseGroups.
	 */
	public function __construct( $discourse_groups ) {
		$this->discourse_groups = $discourse_groups;

		add_shortcode( 'discourse_groups', array( $this, 'discourse_groups' ) );
		add_action( 'save_post', array( $this, 'clear_post_groups_cache' ), 10, 3 );
		add_action( 'wpds_clear_groups_cache', array( $this, 'clear_groups_cache' ) ); // Called from settings-validator.php
	}

	/**
	 * Returns the output for the 'discourse_groups' shortcode.
	 *
	 * @param array $args The shortcode arguments.
	 * @return string
	 */
	public function discourse_groups( $args ) {

		$groups = $this->discourse_groups->get_formatted_groups( $args );

		if ( is_wp_error( $groups ) ) {

			return '';
		}

		return $groups;
	}

	/**
	 * Chacks if a discourse_groups shortcode exists in a post that is saved, if so, it deletes the groups transients.
	 *
	 * @param int $post_id The post_id to check.
	 *
	 * @return null
	 */
	public function clear_post_groups_cache( $post_id, $post, $update ) {
		if ( ! empty( $post-> post_content ) && has_shortcode( $post->post_content, 'discourse_groups' ) ) {
			$this->clear_groups_cache();
		}
		return null;
	}

	/**
	 * Clears the groups transients and the cached groups.
	 */
	public function clear_groups_cache() {
		delete_transient( 'wpds_groups' );
		delete_transient( 'wpds_formatted_groups' );
		delete_option( 'wpds_discourse_groups' );
	}
}
