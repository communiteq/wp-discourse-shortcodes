<?php

namespace WPDiscourse\Shortcodes;

class DiscourseLink {
	use Utilities;

	protected $options;
	protected $base_url;

	public function __construct() {
		add_action( 'init', array( $this, 'setup_options' ) );
	}

	public function setup_options() {
		$this->options = $this->get_options();
		$this->base_url = ! empty( $this->options['url'] ) ? $this->options['url'] : '';
	}

	public function get_discourse_link( $attributes ) {
		$attributes = shortcode_atts( array(
			'link_text'   => 'Visit Our Forum',
			'return_path' => '/',
			'classes'     => '',
			'login'       => true,
		), $attributes );

		$url = esc_url( $this->get_url( $this->base_url, $attributes['login'], $attributes['return_path'] ) );
		$classes        = $attributes['classes'] ? 'class="' . $attributes['classes'] . '"' : '';
		$discourse_link = '<a ' . $classes . ' href="' . $url . '">' . $attributes['link_text'] . '</a>';

		return $discourse_link;
	}

	protected function get_url( $base_url, $login = false, $return_path = '' ) {
		if ( ! $login || 'false' === $login ) {
			$url = $base_url . $return_path;
		} else {
			$url = $base_url . '/session/sso?return_path=' . $return_path;
		}

		return $url;
	}

}