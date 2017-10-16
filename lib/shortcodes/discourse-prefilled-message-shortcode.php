<?php

namespace WPDiscourse\Shortcodes;

class DiscoursePrefilledMessageShortcode {
	protected $prefilled_message;

	/**
	 * DiscoursePrefilledMessageShortcode constructor.
	 *
	 * @param DiscoursePrefilledMessage $prefilled_message
	 */
	public function __construct( $prefilled_message ) {
		$this->prefilled_message = $prefilled_message;

		add_shortcode( 'discourse_prefilled_message', array( $this, 'discourse_prefilled_message' ) );
	}

	public function discourse_prefilled_message( $attributes ) {

		$message = $this->prefilled_message->discourse_prefilled_message( $attributes );

		if ( is_wp_error( $message ) ) {

			return '';
		}

		return $message;
	}
}
