<?php

namespace WPDiscourse\LatestTopics;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class Admin {
	protected $options_page;
	protected $form_helper;
	protected $options;
	protected $webhook_url;

	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'admin_init', array( $this, 'register_latest_topics_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_latest_topics_page' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'settings_tab' ) );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'dclt_settings_fields' ) );
	}

	public function setup_options() {
		$this->options = DiscourseUtilities::get_options();
		$this->webhook_url = ! empty( $this->options['url'] ) ? $this->options['url'] . '/admin/api/web_hooks' : null;
    }

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_latest_topics_settings() {
		add_settings_section( 'dclt_settings_section', __( 'Discourse Latest Topics Settings', 'dclt' ), array(
			$this,
			'latest_topics_settings_details',
		), 'dclt_options' );


		add_settings_field( 'dclt_cache_duration', __( 'Topics Cache Duration', 'dclt' ), array(
			$this,
			'cache_duration_input',
		), 'dclt_options', 'dclt_settings_section' );

		add_settings_field( 'dclt_webhook_refresh', __( 'Refresh Comments With Discourse Webhook', 'dclt' ), array(
			$this,
			'webhook_refresh_checkbox',
		), 'dclt_options', 'dclt_settings_section' );

		add_settings_field( 'dclt_webhook_secret', __( 'Discourse Webhook Secret Key', 'dclt' ), array(
		        $this,
            'webhook_secret_input',
        ), 'dclt_options', 'dclt_settings_section' );

		add_settings_field( 'dclt_use_default_styles', __( 'Use Default Styles', 'dclt' ), array(
			$this,
			'use_default_styles_checkbox',
		), 'dclt_options', 'dclt_settings_section' );

		add_settings_field( 'dclt_clear_topics_cache', __( 'Clear Topics Cache', 'dclt' ), array(
			$this,
			'clear_topics_cache_checkbox',
		), 'dclt_options', 'dclt_settings_section' );

		// The settings fields will be saved in the 'dclt_options' array as `dclt_options[ $key ].`
		register_setting( 'dclt_options', 'dclt_options', array( $this->form_helper, 'validate_options' ) );
	}

	public function add_latest_topics_page() {
		$latest_topics_settings = add_submenu_page(
		// The parent page from the wp-discourse plugin.
			'wp_discourse_options',
			__( 'Latest Topics', 'dclt' ),
			__( 'Latest Topics', 'dclt' ),
			'manage_options',
			'dclt_options',
			array( $this, 'dclt_options_page' )
		);
		add_action( 'load-' . $latest_topics_settings, array( $this->form_helper, 'connection_status_notice' ) );
	}

	public function dclt_options_page() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'dclt_options' );
		}
	}

	public function settings_tab( $tab ) {
		$active = 'dclt_options' === $tab;
		?>
        <a href="?page=wp_discourse_options&tab=dclt_options"
           class="nav-tab <?php echo $active ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Latest Topics', 'dclt' ); ?>
        </a>
		<?php
	}

	public function latest_topics_settings_details() {
		?>
        <p>Discourse latest topics details...</p>
		<?php
	}

	public function dclt_settings_fields( $tab ) {
		if ( 'dclt_options' === $tab ) {
			settings_fields( 'dclt_options' );
			do_settings_sections( 'dclt_options' );
		}
	}

	public function webhook_refresh_checkbox() {
		$wordpress_url = home_url( '/wp-json/wp-discourse/v1/latest-topics' );
		if ( ! empty( $this->webhook_url ) ) {
			$description = 'To use this setting you need to setup a <strong>webhook</strong> on your Discourse forum at <a href="' .
                           esc_url( $this->webhook_url) . '">' . esc_url( $this->webhook_url ) . '</a>. ' .
                           'On that page, set the "Payload URL" to <strong>' . esc_url( $wordpress_url ) . '</strong>.
                           On the events section of that page, select the "Topic Event" checkbox to receive
                           updates when there is a new topic. To receive updates when there are new replies, also select the "Post Event" checkbox.';

        } else {
		    $description = 'To use this setting you need to setup a <strong>webhook</strong> on your Discourse forum at <strong>http://discourse.example.com/admin/api/web_hooks</strong>
		                   On that page, set the "Payload URL" to <strong>' . esc_url( $wordpress_url ) . '</strong>. On the events section of that page, select the "Topic Event" checkbox to receive
                           updates when there is a new topic. To receive updates when there are new replies, also select the "Post Event" checkbox.';
        }

		$this->form_helper->checkbox_input( 'dclt_webhook_refresh', 'dclt_options', __( 'Use a Discourse Webhook to refresh comments.', 'dclt' ), $description );
	}

	public function webhook_secret_input() {
        if ( ! empty( $this->webhook_url ) ) {
            $description = 'The secret key used to verify Discourse webhook requests. It needs to match the key set at <a href="' .
                           esc_url( $this->webhook_url) . '">' . esc_url( $this->webhook_url ) . '</a>.';
        } else {
            $description = 'The secret key used to verify Discourse webhook requests. It needs to match the key set at <strong>http://discourse.example.com/admin/api/web_hooks</strong>.';
        }

        $this->form_helper->input( 'dclt_webhook_secret', 'dclt_options', $description );
    }

	public function clear_topics_cache_checkbox() {
		$this->form_helper->checkbox_input( 'dclt_clear_topics_cache', 'dclt_options', __( 'Clear the cache to fetch fresh topics from Discourse.', 'dclt' ) );
	}

	public function use_default_styles_checkbox() {
		$this->form_helper->checkbox_input( 'dclt_use_default_styles', 'dclt_options', __( 'Use the default plugin styles.', 'dclt' ) );
	}

	public function cache_duration_input() {
		$this->form_helper->input( 'dclt_cache_duration', 'dclt_options', __( 'Time in minutes to cache Discourse Topics.', 'dclt' ), 'number', 0 );
	}
}