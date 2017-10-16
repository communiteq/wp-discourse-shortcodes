<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseGroupsShortcode {

	/**
	 * @var DiscourseGroups An instance of DiscourseGroups.
	 */
	protected $discourse_groups;

	/**
	 * DiscourseGroupsShortcode constructor.
	 *
	 * @param DiscourseGroups $discourse_groups An instance of DiscourseGroups.
	 */
	public function __construct( $discourse_groups ) {
		$this->discourse_groups = $discourse_groups;

		add_shortcode( 'discourse_groups', array( $this, 'discourse_groups' ) );
	}

	/**
	 * Returns the output for the 'discourse_groups' shortcode.
	 *
	 * @return string
	 */
	public function discourse_groups( $args ) {
//		$attributes = shortcode_atts( array(
//			'link_type'         => 'visit',
//			'group_list'     => '',
//			'link_open_text'    => 'Join',
//			'link_close_text' => '',
//		), $attributes, 'discourse_groups' );
//
//		$groups = $this->discourse_groups->get_discourse_groups( $attributes['group_list'] );
//
//		echo $groups ? $this->discourse_groups->format_groups( $groups, $attributes ) : '';

		$groups = $this->discourse_groups->get_formatted_groups( $args );

		if ( is_wp_error( $groups )) {

			return '';
		}

		return $groups;
	}
}
