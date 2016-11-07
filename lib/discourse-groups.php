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

	protected $discourse_prefilled_message;

	/**
	 * DiscourseGroups constructor.
	 *
	 * @param \WPDiscourseShortcodes\Utilities\Utilities $utilities A Utilities object.
	 */
	public function __construct( $utilities, $discourse_remote_message, $discourse_prefilled_message ) {
		$this->utilities                = $utilities;
		$this->discourse_remote_message = $discourse_remote_message;
		$this->discourse_prefilled_message = $discourse_prefilled_message;

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
			'invite'         => '',
			'group_list'     => '',
			'require_name'   => 'true',
			'clear_cache'    => '',
			'button_text'    => 'Join',
			'user_details'   => 'true',
			'remote_message' => 'true',

		), $atts, 'discourse_groups' );

		$groups = $this->get_discourse_groups( $attributes['group_list'], $attributes['clear_cache'] );

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

			$groups = json_decode( wp_remote_retrieve_body( $response ), true );
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

			if ( 'true' === $attributes['invite'] && $group['mentionable'] ) {
				if ( 'true' === $attributes['remote_message'] ) {

					$remote_message_args = array(
						'title'        => 'Request to join the ' . $pretty_group_name . ' group',
						'message'      => 'A request to join the ' . $pretty_group_name . ' group',
						'recipients'   => $group['name'],
						'require_name' => $attributes['require_name'],
						'user_details' => 'true',
						'button_text'  => $attributes['button_text'],
					);

					$output .= '<h4 class="wpdc-shortcodes-join">Join the ' . $pretty_group_name . ' Group</h4>';
					$output .= $this->discourse_remote_message->discourse_remote_message( $remote_message_args );
				} elseif ( 1 === $this->options['enable-sso'] ) {
					// Add a link to create a prefilled message.
					$prefilled_message_args = array(
						'title' => 'Request to join the ' . $pretty_group_name . ' group',
						'classes' => 'wpdc-shortcodes-message-link',
						'username' => $this->group_owner_names( $group['name'] ),
						'link_text' => $attributes['button_text'],
					);

					$output .= '<h4 class="wpdc-shortcodes-join">Join the ' . $pretty_group_name . ' Group</h4>';
					$output .= $this->discourse_prefilled_message->discourse_prefilled_message( $prefilled_message_args );
				}
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

	protected function group_owner_names( $group_name ) {
		$owners_info = $this->get_group_owners( $group_name );
		$owner_names_array = [];
		foreach ( $owners_info as $info ) {
			$owner_names_array[] = $info['username'];
		}

		return join( ',', $owner_names_array );
	}
}
