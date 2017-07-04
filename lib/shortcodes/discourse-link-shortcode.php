<?php

namespace WPDiscourse\Shortcodes;

class DiscourseLinkShortcode {
	protected $discourse_link;

	public function __construct( $discourse_link ) {
		$this->discourse_link = $discourse_link;

		add_shortcode( 'discourse_link', array( $this, 'discourse_link' ) );
	}

	public function discourse_link( $attributes ) {
		echo $this->discourse_link->get_discourse_link( $attributes );
	}
}