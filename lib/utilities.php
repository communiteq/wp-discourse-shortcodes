<?php

namespace WPDiscourseShortcodes\Utilities;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class Utilities {

	public function get_options() {

		return DiscourseUtilities::get_options();
	}

	public function base_url( array $options ) {

		return ! empty( $options['url'] ) ? $options['url'] : '';
	}

	public function get_url( $base_url, $login = false, $return_path = '' ) {
		if ( ! $login || 'false' === $login ) {
			$url = $base_url . $return_path;
		} else {
			$url = $base_url . '/session/sso?return_path=' . $return_path;
		}

		return $url;
	}

}