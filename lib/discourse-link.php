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
		$attributes = shortcode_atts(
			array(
				'link_text'   => 'Visit Our Forum',
				'path' => '/',
				'classes'     => '',
				'login'       => true,
			), $attributes
		);

		$url = esc_url( $this->get_url( $this->base_url, $attributes['login'], $attributes['path'] ) );
		$classes        = $attributes['classes'] ? 'class="' . $attributes['classes'] . '"' : '';
		$discourse_link = '<a ' . $classes . ' href="' . $url . '">' . esc_html( $attributes['link_text'] ) . '</a>';

		return $discourse_link;
	}

	protected function get_url( $base_url, $login = false, $path = '' ) {
		if ( ! $login || 'false' === $login ) {
			$url = $base_url . $path;
		} else {
			$url = $base_url . '/session/sso?return_path=' . $path;
		}

		return $url;
	}
}
