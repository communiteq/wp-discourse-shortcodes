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

	protected $discourse_remote_message;

	/**
	 * DiscourseGroups constructor.
	 *
	 * @param \WPDiscourseShortcodes\Utilities\Utilities $utilities A Utilities object.
	 */
	public function __construct( $utilities, $discourse_remote_message ) {
		$this->utilities                = $utilities;
		$this->discourse_remote_message = $discourse_remote_message;

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
			'invite'      => false,
			'group_list'  => false,
			'clear_cache' => false,
		), $atts, 'discourse_groups' );

		$groups = $this->get_discourse_groups( $attributes['group_list'], $attributes['clear_cache'] );

		write_log($attributes);
		return $groups ? $this->format_groups( $groups, $attributes ) : '';
	}

	/**
	 * Retrieves the groups from Discourse.
	 *
	 * @return array|mixed|null|object
	 */
	protected function get_discourse_groups( $group_list, $clear_cache ) {

		$groups = get_transient( 'discourse_groups' );

		if ( empty( $groups ) || 'true' === $clear_cache ) {

			$api_key      = ! empty( $this->options['api-key'] ) ? $this->options['api-key'] : '';
			$api_username = ! empty( $this->options['publish-username'] ) ? $this->options['publish-username'] : '';
			$groups_url   = $this->base_url . '/admin/groups.json';
			$groups_url   = add_query_arg( array(
				'api_key'      => $api_key,
				'api_username' => $api_username,
			), $groups_url );

			$groups_url = esc_url_raw( $groups_url );
			$response   = wp_remote_get( $groups_url );

			if ( ! $this->utilities->validate( $response ) ) {
				return null;
			}

			$groups        = json_decode( wp_remote_retrieve_body( $response ), true );
			$chosen_groups = [];

			if ( $group_list ) {
				$group_array = explode( ',', $group_list );
				foreach ( $groups as $group ) {
					if ( in_array( $group['name'], $group_array, true ) ) {
						$chosen_groups[] = $group;
					}
				}
			} else {
				// Select the 'mentionable' groups.
				foreach ( $groups as $group ) {
					if ( $group['mentionable'] ) {
						$chosen_groups[] = $group;
					}
				}
			}

			foreach ( $chosen_groups as $key => $group ) {
				$chosen_groups[ $key ]['description'] = $this->get_group_description( $group['name'] );
			}

			$groups = $chosen_groups;
			set_transient( 'discourse_groups', $groups, DAY_IN_SECONDS );
		}

		return $groups;
	}

	/**
	 * @param array $groups The Discourse groups.
	 *
	 * @return string
	 */
	protected function format_groups( $groups, $attributes ) {
		ob_start();

		$output = '<div class="wpdc-shortcodes-groups">';
		foreach ( $groups as $group ) {
			$pretty_group_name = str_replace( '_', ' ', $group['name'] );
			$user_count        = $group['user_count'];

			$output .= '<div class="wpdc-shortcodes-group clearfix">';
			$output .= '<h3 class="wpdc-shortcodes-groupname">' . $pretty_group_name . '</h3>';
			$output .= '<span class="wpdc-shortcodes-groupcount">';
			$output .= 1 === intval( $user_count ) ? '1 member' : intval( $user_count ) . ' members';
			$output .= '</span>';
			$output .= '<div class="wpdc-shortcodes-group-description">';
			$output .= $group['description'];
			$output .= '</div>';
//			$request_args = array(
//				'link_text' => 'Request to join the ' . $pretty_group_name . ' group',
//				'title'     => 'A request to join the ' . $pretty_group_name . ' group',
//				'username'  => $owner_names,
//				'classes'   => 'discourse-button',
//			);

			if ( 'true' === $attributes['invite'] && $group['mentionable'] ) {
				$message_args = array(
					'title'      => 'Request to join the ' . $pretty_group_name . ' group',
					'message'    => 'A request to join the ' . $pretty_group_name . ' group',
					'recipients' => $group['name'],
				);

				$output .= $this->discourse_remote_message->discourse_remote_message( $message_args );
			}
			$output .= '</div>';
		}
		$output .= '</div>';
		echo $output;

		$output = ob_get_clean();

		return apply_filters( 'wpdc_shortcodes_groups', $output );
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
