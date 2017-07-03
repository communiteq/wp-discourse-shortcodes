<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class DiscourseGroupsShortcode {
	protected $groups;

	public function __construct( $groups ) {
		$this->groups = $groups;

		add_shortcode( 'discourse_groups', array( $this, 'discourse_groups' ) );
	}

	public function discourse_groups() {
		$discourse_groups = $this->groups->get_discourse_groups();
	}



}