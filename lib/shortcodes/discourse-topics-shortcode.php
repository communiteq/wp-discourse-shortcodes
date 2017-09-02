<?php

namespace WPDiscourse\Shortcodes;

class DiscourseTopicsShortcode {
	use Utilities;

	/**
	 * The plugin options.
	 *
	 * @access protected
	 * @var array
	 */
//	protected $options;

	/**
	 * The Discourse forum URL.
	 *
	 * @access protected
	 * @var string
	 */
//	protected $discourse_url;

	/**
	 * An instance of the LatestTopics class.
	 *
	 * @access protected
	 * @var DiscourseTopics
	 */
	protected $discourse_topics;

	/**
	 * DiscourseLatestShortcode constructor.
	 *
	 * @param DiscourseTopics $latest_topics An instance of the LatestTopics class.
	 */
	public function __construct( $discourse_topics ) {
		$this->discourse_topics = $discourse_topics;

//		add_action( 'init', array( $this, 'setup_options' ) );
		add_shortcode( 'discourse_topics', array( $this, 'discourse_topics' ) );
		add_shortcode( 'discourse_latest_rss', array( $this, 'discourse_latest_rss' ) );
	}

	/**
	 * Set the plugin options.
	 */
//	public function setup_options() {
//		$this->options       = $this->get_options();
//		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
//	}

	/**
	 * Create the shortcode.
	 *
	 * @return string
	 */
	public function discourse_topics() {

		$discourse_latest = $this->discourse_topics->get_latest_topics();

		if ( is_wp_error( $discourse_latest ) ) {

			return '';
		}

		return $discourse_latest;
	}

	public function discourse_latest_rss() {

		$discourse_topics = $this->discourse_topics->get_latest_rss();

		if ( is_wp_error( $discourse_topics ) ) {

			return '';
		}

		return $discourse_topics;
	}

}
