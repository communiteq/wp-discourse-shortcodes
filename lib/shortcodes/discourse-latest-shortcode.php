<?php

namespace WPDiscourse\Shortcodes;

class DiscourseLatestShortcode {
	use Utilities;

	/**
	 * The plugin options.
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
	 * An instance of the LatestTopics class.
	 *
	 * @access protected
	 * @var LatestTopics
	 */
	protected $latest_topics;

	/**
	 * DiscourseLatestShortcode constructor.
	 *
	 * @param LatestTopics $latest_topics An instance of the LatestTopics class.
	 */
	public function __construct( $latest_topics ) {
		$this->latest_topics = $latest_topics;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_shortcode( 'discourse_latest', array( $this, 'discourse_latest' ) );
	}

	/**
	 * Set the plugin options.
	 */
	public function setup_options() {
		$this->options       = $this->get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
	}

	/**
	 * Create the shortcode.
	 *
	 * @return string
	 */
	public function discourse_latest() {

		$discourse_topics = $this->latest_topics->get_latest_topics();

		if ( is_wp_error( $discourse_topics ) ) {

			return '';
		}

		return $discourse_topics;
	}

}