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

	public function get_topic_by_slug( $slug, $base_url ) {
		$topic_url = $base_url . "/t/{$slug}.json";
		$response = wp_remote_get( $topic_url );

		if ( ! $this->validate( $response ) ) {

			return null;
		}
		$topic = json_decode( wp_remote_retrieve_body( $response ), true );

		return $topic;
	}


	// Return WPDiscourse\Utilities functions.

	public function validate( $response ) {

		return DiscourseUtilities::validate( $response );
	}

	public function get_discourse_categories() {

		return DiscourseUtilities::get_discourse_categories();
	}

}