<?php

namespace WPDiscourseShortcodes\DiscourseLink;

use \WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseLink {
	protected $utilities;
	protected $options;
	protected $base_url;

	public function __construct( $utilities ) {
		$this->utilities = $utilities;
		add_action( 'init', array( $this, 'setup' ) );
	}

	public function setup() {
		$this->options = $this->utilities->get_options();
		$this->base_url = $this->utilities->base_url( $this->options );

		add_shortcode( 'discourse_link', array( $this, 'discourse_link' ) );
	}

	public function discourse_link( $atts ) {
		$attributes = shortcode_atts( array(
			'link_text'   => 'Visit Our Forum',
			'return_path' => '/',
			'classes'     => '',
			'login'       => true,
		), $atts );

		$url = $this->utilities->get_url( $this->base_url, $attributes['login'], $attributes['return_path'] );

		$classes        = $attributes['classes'] ? 'class="' . $attributes['classes'] . '"' : '';
		$discourse_link = '<a ' . $classes . ' href="' . $url . '">' . $attributes['link_text'] . '</a>';

		return $discourse_link;
	}

}