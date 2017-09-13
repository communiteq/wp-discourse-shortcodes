<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseLink {

	/**
	 * The merged options from WP Discourse and WP Discourse Shortcodes.
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
	 * DiscourseLink constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	/**
	 * Sets up the plugin options.
	 */
	public function setup_options() {
		$this->options       = DiscourseUtilities::get_options();
		$this->discourse_url = ! empty( $this->options['url'] ) ? $this->options['url'] : '';
	}

	/**
	 * @param array $args The shortcode attributes.
	 *
	 * @return string
	 */
	public function get_discourse_link( $args ) {
		$args = shortcode_atts( array(
			'link_text' => 'Visit our Forum',
			'path'      => '/',
			'classes'   => '',
			'sso'       => 'false',
		), $args );

		$url = $this->get_url( $args['path'], $args['sso'] );

		if ( ! empty( $args['classes'] ) ) {
			$discourse_link = '<a class="wpds-link ' . esc_attr( $args['classes'] ) . '" href="' . esc_url_raw( $url ) . '">' . esc_html( $args['link_text'] ) . '</a>';
		} else {
			$discourse_link = '<a class="wpds-link" href="' . esc_url_raw( $url ) . '">' . esc_html( $args['link_text'] ) . '</a>';
		}

		return $discourse_link;
	}

	/**
	 * Gets a  Discourse URL for a specific path.
	 *
	 * @param string $sso Whether or not to create an SSO link.
	 * @param string $path The Discourse relative URL.
	 *
	 * @return string
	 */
	protected function get_url( $path, $sso ) {
		if ( 'true' === $sso && ! empty( $this->options['enable-sso'] ) ) {
			$url = $this->discourse_url . '/session/sso?return_path=' . $path;
		} else {
			$url = $this->discourse_url . $path;
		}

		return $url;
	}
}
