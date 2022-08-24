<?php
/**
 * Gets and returns a formatted Discourse user.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class DiscourseUser
 *
 * @package WPDiscourse\Shortcodes
 */
class DiscourseUser {

	/**
	 * An instance of the UserFormatter class.
	 *
	 * @access protected
	 * @var DiscourseUserFormatter
	 */
	protected $user_formatter;

	/**
	 * The WP Discourse options.
	 *
	 * @access protected
	 * @var array
	 */
	protected $options;

	/**
	 * DiscourseUser constructor.
	 *
	 * @param DiscourseUserFormatter $user_formatter An instance of DiscourseUserFormatter.
	 */
	public function __construct( $user_formatter ) {
		$this->user_formatter = $user_formatter;
		$this->options = DiscourseUtilities::get_options();
	}

	/**
	 * Returns the formatted Discourse user.
	 *
	 * @param array $args The shortcode args.
	 *
	 * @return mixed|string|\WP_Error
	 */
	public function get_user( $args ) {
		$user_atts = $this->get_user_atts( $args );
		$user_data = $this->get_user_data( $user_atts );

		if ( ! $user_data ) {
			return new \WP_Error( 'discourse_shortcodes_discourse_user', "User '{$user_atts['user']}' not found." );
		}

		return $this->user_formatter->format_user( $user_data, $user_atts );
	}

	/**
	 * Returns a list of formatted Discourse users.
	 *
	 * @param array $args The shortcode args.
	 *
	 * @return mixed|string|\WP_Error
	 */
	public function get_users( $args ) {
		$user_atts  = $this->get_user_atts( $args );
		$users_atts = $this->get_users_atts( $args );
		$users_data = $this->get_users_data( $user_atts, $users_atts );

		if ( empty( $users_data ) ) {
			return new \WP_Error( 'discourse_shortcodes_discourse_users', "No users found." );
		}

		return $this->user_formatter->format_users( $users_data, $user_atts );
	}

	protected function get_user_atts( $args ) {
		return shortcode_atts(
			array(
				'user'        	=> 'system',
				'avatar_size' 	=> 120,
				'show_name'   	=> 'true',
				'show_username'	=> 'true'
			), $args
		);
	}

	protected function get_users_atts( $args ) {
		return shortcode_atts(
			array(
				'period'              => 'weekly',
				'group'               => null,
				'exclude_usernames'   => null,
				'order'               => null,
				'asc'                 => null,
				'name'                => null,
				'username'            => null,
				'user_field_ids'      => null,
				'plugin_column_ids'   => null,
				'page'                => null,
				'limit'               => null
			), $args
		);
	}

	protected function get_users_data( $user_atts, $users_atts ) {
		$cached_usernames = $this->get_cached_directory_usernames( $users_atts );

		if ( $cached_usernames ) {
			$cached_usernames = $this->apply_users_limit( $cached_usernames, $users_atts );
			$cached_users_data = $this->get_cached_users_data( $cached_usernames );
			$cached_users_data_usernames = array_map( function( $user ) { return $user['username']; }, $cached_users_data );
			$missing_usernames = array_diff( $cached_usernames, $cached_users_data_usernames );

			if ( empty( $missing_usernames ) ) {
				return array_filter( $cached_users_data, function( $user_data ) use ( $cached_usernames ) {
					return in_array( $user_data['username'], $cached_usernames );
				});
			}
		}

		$raw_users = $this->request_users_data( $users_atts );
		$raw_users = $this->apply_users_limit( $raw_users, $users_atts );

		$users_data = array_map( function( $raw_user ) use ( $user_atts ) {
			return $this->map_user_data( $raw_user, $user_atts );
		}, $raw_users );

		$this->set_cached_users_data( $users_data );
		$this->set_cached_directory_usernames( $users_atts, $users_data );

		return $users_data;
	}

	protected function get_user_data( $user_atts ) {
		$cached_users_data = $this->get_cached_users_data( array( $user_atts['user'] ) );

		if ( $cached_users_data ) {
			return reset( $cached_users_data );
		}

		$raw_user = $this->request_user_data( $user_atts['user'] );
		$user_data = $this->map_user_data( $raw_user, $user_atts );

		$this->set_cached_users_data( array( $user_data ) );

		return $user_data;
	}

	protected function map_user_data( $raw_user, $user_atts ) {
		$user_data = array(
			'id' => $raw_user->id
		);

		if ( $user_atts['show_username'] === "true" ) {
			$user_data['username'] = $raw_user->username;
		}

		if ( $user_atts['avatar_size'] ) {
			$avatar_path = str_replace( '{size}', $user_atts['avatar_size'], $raw_user->avatar_template );
			$user_data['avatar_url'] = $this->options['url'] . $avatar_path;
		}

		if ( $user_atts['show_name'] === "true" ) {
			$user_data['name'] = $raw_user->name;
		}

		return $user_data;
	}

	protected function request_user_data( $username ) {
		$response  = DiscourseUtilities::discourse_request( "/u/{$username}.json" );
		return $response ? $response->user : null;
	}

	protected function request_users_data( $users_atts ) {
		$query    = http_build_query( $users_atts );
		$response = DiscourseUtilities::discourse_request( "/directory_items.json?$query" );
		return $response ? array_map( function( $item ) {
			return $item->user;
		}, $response->directory_items ) : null;
	}

	protected function get_cached_users_data( $usernames ) {
		$users_data_cache = get_transient( 'wpds_users_data' ) ?: array();

		return array_values(
			array_filter( $users_data_cache,
				function( $username ) use ( $usernames ) {
					return in_array( $username, $usernames );
				}, ARRAY_FILTER_USE_KEY
			)
		);
	}

	protected function set_cached_users_data( $users_data ) {
		$users_data_cache = get_transient( 'wpds_users_data' ) ?: array();
		foreach( $users_data as $user ) {
			$users_data_cache[ $user['username'] ] = $user;
		}
		set_transient( 'wpds_users_data', $users_data_cache, DAY_IN_SECONDS );
	}

	protected function get_cached_directory_usernames( $users_atts ) {
		$key_suffix = $this->build_user_directory_key_suffix( $users_atts );
		return get_transient( "wpds_users_$key_suffix" ) ?: array();
	}

	protected function set_cached_directory_usernames( $users_atts, $users_data ) {
		$key_suffix = $this->build_user_directory_key_suffix( $users_atts );
		$usernames = array_map( function( $user ) { return $user['username']; }, $users_data );
		set_transient( "wpds_users_$key_suffix", $usernames, DAY_IN_SECONDS );
	}

	protected function build_user_directory_key_suffix( $users_atts ) {
		$users_atts_keys = array_filter( array_keys( $users_atts ), function( $att_key ) use ( $users_atts ) {
			return !empty( $users_atts[ $att_key ] );
		});
		$suffix = "";
		foreach( $users_atts_keys as $i => $att_key ) {
			$att_value = $users_atts[$att_key];
			$suffix .= "{$att_key}_{$att_value}";
			if ( $i < ( count( $users_atts_keys ) - 1 ) ) {
				$suffix .= "_";
			}
		}
		return $suffix;
	}

	protected function apply_users_limit( $users_data, $users_atts ) {
		if ( ! empty( $users_atts['limit'] ) ) {
			$limit = (int) $users_atts['limit'];
			return array_slice( $users_data, 0, $limit );
		} else {
			return $users_data;
		}
	}
}
