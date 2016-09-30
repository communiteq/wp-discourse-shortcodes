<?php

namespace WPDiscourseShortcodes\DiscourseGroups;

class DiscourseGroups {

	/**
	 * A Utilities object.
	 *
	 * @access protected
	 * @var  \WPDiscourseShortcodes\Utilities
	 */
	protected $utilities;

	/**
	 * The wp-discourse plugin options.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;

	/**
	 * The base url of the Discourse forum.
	 *
	 * @access protected
	 * @var string
	 */
	protected $base_url;

	protected $discourse_message;

	/**
	 * DiscourseGroups constructor.
	 *
	 * @param \WPDiscourseShortcodes\Utilities\Utilities $utilities A Utilities object.
	 */
	public function __construct( $utilities, $discourse_message ) {
		$this->utilities         = $utilities;
		$this->discourse_message = $discourse_message;

		add_action( 'init', array( $this, 'setup' ) );
	}

	/**
	 * Setup the options property for the class and add the shortcode.
	 */
	public function setup() {
		$this->options  = $this->utilities->get_options();
		$this->base_url = $this->utilities->base_url( $this->options );

		add_shortcode( 'discourse_groups', array( $this, 'discourse_groups' ) );
	}

	/**
	 * Returns the output for the 'discourse_groups' shortcode.
	 *
	 * @return string
	 */
	public function discourse_groups( $atts ) {
		$attributes = shortcode_atts( array(
			'invite_link' => false,
			'clear_cache' => false,
		), $atts, 'discourse_groups' );

		$groups = $this->get_discourse_groups( $attributes['clear_cache']);

		return $groups ? $this->format_groups( $groups, $attributes ) : '';
	}

	/**
	 * Retrieves the groups from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	protected function get_discourse_groups( $clear_cache ) {
		$options = $this->options;

		$groups = get_transient( 'discourse_groups' );

		if ( empty( $groups ) || 'true' === $clear_cache ) {
			$url = array_key_exists( 'url', $options ) ? $options['url'] : '';
			$url = add_query_arg( array(
				'api_key'      => array_key_exists( 'api-key', $options ) ? $options['api-key'] : '',
				'api_username' => array_key_exists( 'publish-username', $options ) ? $options['publish-username'] : '',
			), $url . '/admin/groups.json' );

			$url      = esc_url_raw( $url );
			$response = wp_remote_get( $url );

			if ( ! $this->utilities->validate( $response ) ) {
				return null;
			}

			$groups = json_decode( wp_remote_retrieve_body( $response ), true );

			foreach ( $groups as $key => $group ) {
				$groups[ $key ]['description'] = $this->get_group_description( $group['name'] );
				$owners                        = $this->get_group_owners( $group['name'] );
				if ( $owners ) {
					foreach ( $owners as $owner ) {
						$owner_names[] = $owner['username'];
					}
					$groups[ $key ]['owners'] = $owner_names[0];
				} else {
					$groups[ $key ]['owners'] = isset( $this->options['publish-username'] ) ? $this->options['publish-username'] : null;
				}
			}

			set_transient( 'discourse_groups', $groups, HOUR_IN_SECONDS );
		}

		return $groups;
	}

	/**
	 * @param array $groups The Discourse groups.
	 *
	 * @return string
	 */
	protected function format_groups( $groups, $attributes ) {
		$output = '<div class="discourse-shortcode-groups">';
		foreach ( $groups as $group ) {
			if ( ! $group['automatic'] && $group['visible'] ) {
				$pretty_group_name = str_replace( '_', ' ', $group['name'] );
				$user_count        = $group['user_count'];
				// For now only the first owner is being selected. Eventually it should be possible to send the
				// message to all of the group's owners.
				$owner_names = isset( $group['owners'] ) ? $group['owners'] : null;

				$output .= '<div class="discourse-shortcode-group clearfix">';
				$output .= '<h3 class="discourse-shortcode-groupname">' . $pretty_group_name . '</h3>';
				$output .= '<span class="discourse-shortcode-groupcount">';
				$output .= 1 === intval( $user_count ) ? '1 member' : intval( $user_count ) . ' members';
				$output .= '</span>';
				$output .= '<div class="discourse-shortcode-group-description">';
				$output .= $group['description'];
				$output .= '</div>';
				$request_args = array(
					'link_text' => 'Request to join the ' . $pretty_group_name . ' group',
					'title'     => 'A request to join the ' . $pretty_group_name . ' group',
					'username'  => $owner_names,
					'classes'   => 'discourse-button',
				);
				if ( ! empty( $attributes['invite_link'] ) && 'true' === $attributes['invite_link'] ) {
					$output .= $this->discourse_message->discourse_message( $request_args );

				}
				$output .= '</div>';
			}
		}
		$output .= '</div>';

		return $output;
	}

	protected function get_group_description( $group_name ) {
		$group_name        = str_replace( '_', '-', $group_name );
		$topic_slug        = 'about-the-' . $group_name . '-group';
		$group_description = $this->utilities->get_topic_by_slug( $topic_slug, $this->base_url )['post_stream']['posts'][0]['cooked'];

		return $group_description;
	}

	protected function get_group_owners( $group_name ) {
		$base_url = $this->base_url . "/groups/{$group_name}/members.json";
		$response = wp_remote_get( $base_url );

		if ( ! $this->utilities->validate( $response ) ) {
			return null;
		}

		$group_members = json_decode( wp_remote_retrieve_body( $response ), true );
		$owners        = $group_members['owners'];

		return $owners;
	}
}
