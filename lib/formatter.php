<?php
/**
 * A collection of helper function.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Trait Formatter
 *
 * @package WPDiscourse\Shortcodes
 */
trait Formatter {
	// because wp-discourse (or WP) sometimes does not cache this correctly, the get_discourse_categories() call
	// could request site.json up to 20 times per page
	// https://meta.discourse.org/t/wp-discourse-performing-a-lot-of-requests-to-site-json/332287
	protected $categories = null;

	protected function get_discourse_categories() {
		if ($this->categories == null) {
			$this->categories = DiscourseUtilities::get_discourse_categories();
		}
		return $this->categories;
	}

	/**
	 * Gets a Discourse category from its name.
	 *
	 * @param string $name The name of the category to find.
	 *
	 * @return null
	 */
	public function find_discourse_category_by_name( $name ) {
		$categories = $this->get_discourse_categories();
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
		$categories = $this->get_discourse_categories();
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
	 * This isn't being used anywhere.
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

	/**
	 * Returns a string of HTML that's used to add the shortcode parameters as data attrubutes for ajax requests.
	 *
	 * @param array $args The shortcode args.
	 *
	 * @return string
	 */
	public function render_topics_shortcode_options( $args ) {
		$output = '<div class="wpds-topic-shortcode-options" ';
		foreach ($args as $argname => $argval) {
			$output .= sprintf('data-wpds-%s="%s" ', str_replace('_', '-', $argname), esc_attr($argval));
		}
		$output .= "></div>";
		return $output;
	}

	/**
	 * Add 'display' to the safe styles list.
	 *
	 * Hook into the 'safe_style_css' filter.
	 *
	 * @param array $styles The array of safe styles.
	 *
	 * @return array
	 */
	public function add_display_to_safe_styles( $styles ) {
		$styles[] = 'display';

		return $styles;
	}

	/**
	 * Returns either the full topic content, or an excerpt of a given length.
	 *
	 * @param string     $html The topic html.
	 * @param int|string $excerpt_length The excerpt length to return.
	 *
	 * @return null|string
	 */
	public function get_topic_content( $html, $excerpt_length ) {
		if ( ! $excerpt_length ) {

			return null;
		} elseif ( 'full' === $excerpt_length ) {

			return $html;
		} else {
			$excerpt_length = intval( $excerpt_length );
			// Setting use_internal_errors makes it possible to pass badly fomatted HTML.
			libxml_use_internal_errors( true );
			$doc = new \DOMDocument( '1.0', 'utf-8' );
			// Clear errors to free memory.
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
	 * Extracts the images and an excerpt from a string of HTML.
	 *
	 * @param string $html The HTML to parse.
	 * @param string $excerpt_length The excerpt length.
	 *
	 * @return array
	 */
	public function parse_text_and_images( $html, $excerpt_length ) {
		if ( 'full' !== $excerpt_length ) {
			$excerpt_length = intval( $excerpt_length );
		}

		libxml_use_internal_errors( true );
		$doc = new \DOMDocument( '1.0', 'utf-8' );
		libxml_clear_errors();
		$html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' . $html . '</body></html>';
		$doc->loadHTML( $html );

		$images = $this->extract_and_remove_images( $doc );
		$html = $doc->saveHTML();

		if ( 'full' === $excerpt_length ) {
			$excerpt = wp_strip_all_tags( $html );
		} else {
			$excerpt = wp_trim_words( wp_strip_all_tags( $html ), $excerpt_length );
		}

		unset( $doc );

		return array(
			'images' => $images,
			'description' => $excerpt,
		);
	}

	/**
	 * Extracts the image tags from a DOMDocument.
	 *
	 * @param \DOMDocument $doc The DOMDocument to parse.
	 *
	 * @return array
	 */
	protected function extract_and_remove_images( \DOMDocument $doc ) {
		$images = [];
		$image_tags = $doc->getElementsByTagName( 'img' );

		if ( $image_tags->length ) {
			foreach ( $image_tags as $image_tag ) {
				$images[] = $doc->saveHTML( $image_tag );
				$image_tag->parentNode->removeChild( $image_tag );
			}
		}

		return $images;
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
		$elements = $xpath->query( '//span[@class]' );

		if ( $elements && $elements->length ) {
			foreach ( $elements as $element ) {
				$element->parentNode->removeChild( $element );
			}
		}

		$elements = $xpath->query( '//small' );

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
