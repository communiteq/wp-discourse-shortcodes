<?php

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

trait Formatter {
	public function find_discourse_category_by_name( $name ) {
		$categories = DiscourseUtilities::get_discourse_categories();
		foreach ( $categories as $category ) {
			if ( $name === $category['name'] ) {

				return $category;
			}
		}

		return null;
	}

	/**
	 * Finds the category of a topic.
	 *
	 * @param array $topic A Discourse topic.
	 *
	 * @return null
	 */
	public function find_discourse_category( $topic ) {
		$categories = DiscourseUtilities::get_discourse_categories();
		if ( empty( $topic['category_id'] ) ) {

			return new \WP_Error( 'wpdc_topic_error', 'The Discourse topic did not have a category_id set.' );
		}

		$category_id = $topic['category_id'];

		foreach ( $categories as $category ) {
			if ( $category_id === $category['id'] ) {

				return $category;
			}
		}

		return null;
	}

	/**
	 * Creates the markup for a category badge.
	 *
	 * @param array $category A Discourse category.
	 *
	 * @return string
	 */
	public function discourse_category_badge( $category ) {
		$category_name  = $category['name'];
		$category_color = '#' . $category['color'];
		$category_badge = '<span class="discourse-shortcode-category-badge" style="width: 8px; height: 8px; background-color: ' .
		                  esc_attr( $category_color ) . '; display: inline-block;"></span><span class="discourse-category-name"> ' . esc_html( $category_name ) . '</span>';

		return $category_badge;
	}

	/**
	 * Formats the last_activity string.
	 *
	 * @param string $last_activity The time of the last activity on the topic.
	 *
	 * @return string
	 */
	public function calculate_last_activity( $last_activity ) {
		$now           = time();
		$last_activity = strtotime( $last_activity );
		$seconds       = $now - $last_activity;

		// Todo: internationalize strings.
		$minutes = intval( $seconds / 60 );
		if ( $minutes === 0 ) {
			return 'A few seconds ago';
		}
		if ( $minutes < 60 ) {
			return 1 === $minutes ? '1 minute ago' : $minutes . ' minutes ago';
		}

		$hours = intval( $minutes / 60 );
		if ( $hours < 24 ) {
			return 1 === $hours ? '1 hour ago' : $hours . ' hours ago';
		}

		$days = intval( $hours / 24 );
		if ( $days < 30 ) {
			return 1 === $days ? '1 day ago' : $days . ' days ago';
		}

		$months = intval( $days / 30 );
		if ( $months < 12 ) {
			return 1 === $months ? '1 month ago' : $months . ' months ago';
		}

		$years = intval( $months / 12 );

		return 1 === $years ? '1 year ago' : $years . ' years ago';
	}

	public function render_rss_shortcode_options($args) {
		$max_topics = 'data-wpds-maxtopics="' . esc_attr( $args['max_topics'] ) . '"';
		$display_images = 'data-wpds-display-images="' . esc_attr( $args['display_images'] ) . '"';
		$excerpt_length = 'data-wpds-excerpt-length="' . esc_attr( $args['excerpt_length'] ) . '"';
		$wp_link = 'data-wpds-wp-link="' . esc_attr( $args['wp_link'] ) . '"';
		$output = '<div class="wpds-rss-shortcode-options"' . $max_topics . ' ' . $display_images . ' ' . $excerpt_length . ' ' . $wp_link . '></div>';

		return $output;
	}

	public function render_topics_shortcode_options($args) {
		$max_topics = 'data-wpds-maxtopics="' . esc_attr( $args['max_topics'] ) . '"';
		$display_avatars = 'data-wpds-display-avatars="' . esc_attr( $args['display_avatars'] ) . '"';
		$source = 'data-wpds-source="' . esc_attr( $args['source'] ) . '"';
		$period = 'data-wpds-period="' . esc_attr( $args['period'] ) . '"';
		$output = '<div class="wpds-topic-shortcode-options"' . $max_topics . ' ' . $display_avatars . ' ' . $source . ' ' . $period . '></div>';

		return $output;
	}
}