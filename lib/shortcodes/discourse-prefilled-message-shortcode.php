<?php

namespace WPDiscourse\Shortcodes;

class DiscoursePrefilledMessageShortcode {
	protected $prefilled_message;

	public function __construct( $prefilled_message ) {
		$this->prefilled_message = $prefilled_message;

		add_shortcode( 'discourse_prefilled_message', array( $this, 'discourse_prefilled_message' ) );
	}

	public function discourse_prefilled_message( $attributes ) {
		echo $this->prefilled_message->discourse_prefilled_message( $attributes );
	}
}
