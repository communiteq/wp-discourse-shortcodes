<?php
/**
 * Outputs the discourse_topics shortcode.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

/**
 * Class DiscourseTopicsShortcode
 *
 * @package WPDiscourse\Shortcodes
 */
class DiscourseTopicsShortcode {

	/**
	 * An instance of the DiscourseTopics class.
	 *
	 * @access protected
	 * @var DiscourseTopics
	 */
	protected $discourse_topics;

	/**
	 * DiscourseLatestShortcode constructor.
	 *
	 * @param DiscourseTopics $discourse_topics An instance of the DiscourseTopics class.
	 */
	public function __construct( $discourse_topics ) {
		$this->discourse_topics = $discourse_topics;

		add_shortcode( 'discourse_topics', array( $this, 'discourse_topics' ) );
	}

	/**
	 * Create the shortcode.
	 *
	 * @param array $args The shortcode arguments.
	 * @return string
	 */
	public function discourse_topics( $args ) {

		$discourse_topics = $this->discourse_topics->get_topics( $args );

		if ( is_wp_error( $discourse_topics ) ) {

			return '';
		}

		return $discourse_topics;
	}
}
