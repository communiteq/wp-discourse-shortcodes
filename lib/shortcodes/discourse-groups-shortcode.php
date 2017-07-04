<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseGroupsShortcode {
	protected $discourse_groups;

	public function __construct( $discourse_groups ) {
		$this->discourse_groups = $discourse_groups;

		add_shortcode( 'discourse_groups', array( $this, 'discourse_groups' ) );
	}
	/**
	 * Returns the output for the 'discourse_groups' shortcode.
	 *
	 * @return string
	 */
	public function discourse_groups( $attributes ) {
		$attributes = shortcode_atts( array(
			'link_type'         => 'visit',
			'group_list'     => '',
			'link_open_text'    => 'Join',
			'link_close_text' => '',
		), $attributes, 'discourse_groups' );

		$groups = $this->discourse_groups->get_discourse_groups( $attributes['group_list'] );

		echo $groups ? $this->discourse_groups->format_groups( $groups, $attributes ) : '';
	}
}