<?php

namespace WPDiscourse\Shortcodes;

class DiscourseRSSShortcode {
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
	 * An instance of the DiscourseRSS class.
	 *
	 * @access protected
	 * @var DiscourseRSS
	 */
	protected $discourse_rss;

	/**
	 * DiscourseLatestShortcode constructor.
	 *
	 * @param DiscourseRSS $discourse_rss An instance of the DiscourseRSS class.
	 */
	public function __construct( $discourse_rss ) {
		$this->discourse_rss = $discourse_rss;

//		add_action( 'init', array( $this, 'setup_options' ) );
//		add_shortcode( 'discourse_topics', array( $this, 'discourse_topics' ) );
		add_shortcode( 'discourse_rss', array( $this, 'discourse_rss' ) );
	}

	/**
	 * Set the plugin options.
	 */
//	public function setup_options() {
//		$this->options       = $this->get_options();
//		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : null;
//	}

	public function discourse_rss() {

		$discourse_rss = $this->discourse_rss->get_latest_rss();

		if ( is_wp_error( $discourse_rss ) ) {

			return '';
		}

		return $discourse_rss;
	}
}