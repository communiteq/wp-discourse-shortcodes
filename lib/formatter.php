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

	public function render_topics_shortcode_options( $args ) {
		$max_topics        = ' data-wpds-maxtopics="' . esc_attr( $args['max_topics'] ) . '"';
		$cache_duration    = ' data-wpds-cache-duration="' . esc_attr( $args['cache_duration'] ) . '"';
		$display_avatars   = ' data-wpds-display-avatars="' . esc_attr( $args['display_avatars'] ) . '"';
		$source            = ' data-wpds-source="' . esc_attr( $args['source'] ) . '"';
		$period            = ' data-wpds-period="' . esc_attr( $args['period'] ) . '"';
		$tile              = ' data-wpds-tile="' . esc_attr( $args['tile'] ) . '"';
		$excerpt_length    = ' data-wpds-excerpt-length="' . esc_attr( $args['excerpt_length'] ) . '"';
		$username_position = ' data-wpds-username-position="' . esc_attr( $args['username_position'] ) . '"';
		$category_position = ' data-wpds-category-position="' . esc_attr( $args['category_position'] ) . '"';
		$date_position     = ' data-wpds-date-position="' . esc_attr( $args['date_position'] ) . '"';
		$ajax_timeout      = ' data-wpds-ajax-timeout="' . esc_attr( $args['ajax_timeout'] ) . '"';
		$id                = ' data-wpds-id="' . esc_attr( $args['id'] ) . '"';

		$output = '<div class="wpds-topic-shortcode-options" ' . $max_topics . $cache_duration . $display_avatars . $source .
		          $period . $tile . $excerpt_length . $username_position . $category_position . $date_position . $ajax_timeout .
		          $id . '></div>';

		return $output;
	}

	/**
	 * Add 'display' to the safe styles list.
	 *
	 * Hook into the 'safe_style_css' filter.
	 *
	 * @param $styles
	 *
	 * @return array
	 */
	public function add_display_to_safe_styles( $styles ) {
		$styles[] = 'display';

		return $styles;
	}

	public function get_topic_content( $html, $excerpt_length ) {
		if ( ! $excerpt_length ) {

			return null;
		} elseif ( 'full' === $excerpt_length ) {

			return $html;
		} else {
			$excerpt_length = intval( $excerpt_length );
			libxml_use_internal_errors( true );
			$doc = new \DOMDocument( '1.0', 'utf-8' );
			libxml_clear_errors();
			// Create a valid document with charset.
			$html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
			$doc->loadHTML( $html );

			$html    = $this->clean_discourse_content( $doc );
			$excerpt = wp_trim_words( wp_strip_all_tags( $html ), $excerpt_length );

			unset( $doc );

			return $excerpt;
		}
	}

	/**
	 * Clean the HTML returned from Discourse.
	 *
	 * @param \DOMDocument $doc The DOMDocument to parse.
	 *
	 * @return string
	 */
	protected function clean_discourse_content( \DOMDocument $doc ) {
		$xpath    = new \DOMXPath( $doc );
		$elements = $xpath->query( "//span[@class]" );

		if ( $elements && $elements->length ) {
			foreach ( $elements as $element ) {
				$element->parentNode->removeChild( $element );
			}
		}

		$elements = $xpath->query( "//small" );

		if ( $elements && $elements->length ) {
			foreach ( $elements as $element ) {
				$element->parentNode->removeChild( $element );
			}
		}

		$html = $doc->saveHTML();

		unset( $xpath );

		return $html;
	}
}