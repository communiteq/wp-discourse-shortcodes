<?php

namespace WPDiscourse\Shortcodes;

class DiscourseRSSShortcode {

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

		add_shortcode( 'discourse_rss', array( $this, 'discourse_rss' ) );
	}

	public function discourse_rss() {

		$discourse_rss = $this->discourse_rss->get_latest_rss();

		if ( is_wp_error( $discourse_rss ) ) {

			return '';
		}

		return $discourse_rss;
	}
}