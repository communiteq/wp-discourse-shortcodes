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
		add_action( 'save_post', array( $this, 'clear_post_topics_cache' ), 1, 3 );
		add_action( 'wpds_clear_topics_cache', array( $this, 'clear_topics_cache' ) ); // Called from settings-validator.php
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

	/**
	 * Checks if a discourse_topics shortcode exists in a post that is saved, if so, it calls clear_topics_cache.
	 *
	 * @param int $post_id The post_id to check.
	 *
	 * @return null
	 */
	public function clear_post_topics_cache( $post_id, $post, $update ) {
		if ( ! empty( $post-> post_content ) && has_shortcode( $post->post_content, 'discourse_topics' ) ) {
			$this->clear_topics_cache();
		}

		return null;
	}

	/**
	 * Clear topics caches.
	 *
	 * Called directly when the wpds options page is saved.
	 */
	function list_all_transients() {
		global $wpdb;

		// Query for all transients in the wp_options table
		$transients = $wpdb->get_results(
			"SELECT option_name, option_value
			FROM {$wpdb->options}
			WHERE option_name LIKE '_transient_wpds_%'
			ORDER BY option_name"
		);

		return $transients;
	}

	public function clear_topics_cache() {
		foreach ($this->list_all_transients() as $transient) {
			delete_transient($transient->option_name);
		}
	}
}
