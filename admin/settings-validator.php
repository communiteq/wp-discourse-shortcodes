<?php

namespace WPDiscourse\LatestTopics;

class SettingsValidator {
	public function __construct() {
		add_filter( 'wpdc_validate_dclt_clear_topics_cache', array( $this, 'validate_checkbox' ) );
	}

	public function validate_checkbox( $input ) {
		return 1 === intval( $input ) ? 1 : 0;
	}
}