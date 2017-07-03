<?php

namespace WPDiscourse\Shortcodes;

//use WPDiscourse\Admin\FormHelper;
//use WPDiscourse\Admin\OptionsPage;
//use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

class Admin {
    use Utilities;


	/**
     * The WPDiscourse options page.
     *
     * Use this class to hook into the WPDiscourse options page.
     *
     * @access protected
	 * @var OptionsPage
	 */
	protected $options_page;

	/**
     * The WPDiscourse FormHelper.
     *
     * Use of this class is optional, it makes it simple to build common form elements and save their values
     * to option arrays.
     *
     * @access protected
	 * @var FormHelper
	 */
	protected $form_helper;

	/**
     * Points to an array of all of the WPDiscourse options.
     *
     * @access protected
	 * @var array
	 */
	protected $options;

	/**
     * The WordPress webhook URL that is given to Discourse.
     *
     * @access protected
	 * @var string
	 */
	protected $webhook_url;

	/**
	 * Admin constructor.
	 *
	 * @param OptionsPage $options_page An instance of the OptionsPage class.
	 * @param FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'admin_init', array( $this, 'register_latest_topics_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_latest_topics_page' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'settings_tab' ), 5, 1 );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'wpds_settings_fields' ) );
	}

	/**
	 * Setup the plugin options.
     *
     * The options array will contain the WPDiscourse options merged with the options that have been added through this
     * plugin in `discourse-latest-topics.php`.
	 */
	public function setup_options() {
		$this->options = $this->get_options();
		$this->webhook_url = ! empty( $this->options['url'] ) ? $this->options['url'] . '/admin/api/web_hooks' : null;
    }

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_latest_topics_settings() {
		add_settings_section( 'wpds_settings_section', __( 'WP Discourse Shortcodes Settings', 'wpds' ), array(
			$this,
			'shortcode_settings_details',
		), 'wpds_options' );


		add_settings_field( 'wpds_topic_cache_duration', __( 'Topics Cache Duration', 'wpds' ), array(
			$this,
			'cache_duration_input',
		), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_topic_webhook_refresh', __( 'Refresh Comments With Discourse Webhook', 'wpds' ), array(
			$this,
			'webhook_refresh_checkbox',
		), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_webhook_secret', __( 'Discourse Webhook Secret Key', 'wpds' ), array(
		        $this,
            'webhook_secret_input',
        ), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_use_default_styles', __( 'Use Default Styles', 'wpds' ), array(
			$this,
			'use_default_styles_checkbox',
		), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_clear_topics_cache', __( 'Clear Topics Cache', 'wpds' ), array(
			$this,
			'clear_topics_cache_checkbox',
		), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_fetch_discourse_groups', __( 'Refresh Discourse Groups', 'wpds' ), array(
            $this,
            'fetch_discourse_groups_checkbox',
        ), 'wpds_options', 'wpds_settings_section' );

		// The settings fields will be saved in the 'wpds_options' array as `wpds_options[ $key ].`
		register_setting( 'wpds_options', 'wpds_options', array( $this->form_helper, 'validate_options' ) );
	}

	/**
	 * Adds the latest_topics sub-menu page to the 'wp_discourse_options' menu page.
	 */
	public function add_latest_topics_page() {
		$latest_topics_settings = add_submenu_page(
		// The parent page from the wp-discourse plugin.
			'wp_discourse_options',
			__( 'Shortcodes', 'wpds' ),
			__( 'Shortcodes', 'wpds' ),
			'manage_options',
			'wpds_options',
			array( $this, 'wpds_options_page' )
		);
		// This is optional, it checks the connection status with Discourse after saving the settings page.
		add_action( 'load-' . $latest_topics_settings, array( $this->form_helper, 'connection_status_notice' ) );
	}

	/**
	 * Creates the discourse latest topics options page by calling OptionsPage::display with 'dctl_options' (the name of
     * the latest-topics options tab.)
	 */
	public function wpds_options_page() {
		if ( current_user_can( 'manage_options' ) ) {
			$this->options_page->display( 'wpds_options' );
		}
	}

	/**
     * Adds the latest-topics options tab to the WP Discourse options menu.
     *
     * Hooked into 'wpdc_options_page_append_settings_tabs'. The value of the href element is used to set the
     * value of $tab in the OptionsPage::display function.
     *
	 * @param string $tab The active tab.
	 */
	public function settings_tab( $tab ) {
		$active = 'wpds_options' === $tab;
		?>
        <a href="?page=wp_discourse_options&tab=wpds_options"
           class="nav-tab <?php echo $active ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Shortcodes', 'wpds' ); ?>
        </a>
		<?php
	}

	/**
	 * Details about the setting.
	 */
	public function shortcode_settings_details() {
		?>
        <p>
            The WP Discourse Shortcodes plugin lets you add a <code>[discourse_latest]</code> shortcode to your WordPress pages.
            The shortcode will display the latest topics from your Discourse Forum. It takes the optional arguments of
            <code>max_topics</code> (defaults to <code>5</code>) and <code>display_avatars</code> (defaults to <code>true</code>.)
            To create a shortcode to display the latest 10 topics, and not display avatars, you would do this: <code>[discourse_latest max_topics=10 display_avatars=false]</code>
        </p>
		<?php
	}

	/**
     * Adds settings fields if 'wpds_options' is the current tab.
     *
     * Hooked into 'wpdc_options_page_after_tab_switch'.
     *
	 * @param string $tab The current active tab.
	 */
	public function wpds_settings_fields( $tab ) {
		if ( 'wpds_options' === $tab ) {
			settings_fields( 'wpds_options' );
			do_settings_sections( 'wpds_options' );
		}
	}

	/**
	 * Displays the webhook_refresh_checkbox field.
     *
     * This, and all the other settings fields functions, use the FormHelper methods to create the form elements.
     * Using the FormHelper methods is optional. If they are used, you need to be certain that your plugin options are
     * being added to the array returned by DiscourseUtilities::get_options. See discourse-latest-topics.php for details.
	 */
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

		$this->form_helper->checkbox_input( 'wpds_topic_webhook_refresh', 'wpds_options', __( 'Use a Discourse Webhook to refresh comments.', 'wpds' ), $description );
	}

	/**
	 * Displays the webhook_secret_input field.
	 */
	public function webhook_secret_input() {
        if ( ! empty( $this->webhook_url ) ) {
            $description = 'The secret key used to verify Discourse webhook requests. It needs to match the key set at <a href="' .
                           esc_url( $this->webhook_url) . '">' . esc_url( $this->webhook_url ) . '</a>.';
        } else {
            $description = 'The secret key used to verify Discourse webhook requests. It needs to match the key set at <strong>http://discourse.example.com/admin/api/web_hooks</strong>.';
        }

        $this->form_helper->input( 'wpds_webhook_secret', 'wpds_options', $description );
    }

	/**
	 * Displays the clear_topics_cache_checkbox field.
	 */
	public function clear_topics_cache_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_clear_topics_cache', 'wpds_options', __( 'Clear the cache to fetch fresh topics from Discourse (This
		will be reset after a single request.)', 'wpds' ) );
	}

	/**
	 * Displays the use_default_styles_checkbox field.
	 */
	public function use_default_styles_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_use_default_styles', 'wpds_options', __( 'Use the default plugin styles.', 'wpds' ) );
	}

	/**
	 * Displays the cache_duration_input field.
	 */
	public function cache_duration_input() {
		$this->form_helper->input( 'wpds_topic_cache_duration', 'wpds_options', __( 'Time in minutes to cache Discourse Topics.
		This value will be ignored if you enable a webhook from Discourse.', 'wpds' ), 'number', 0 );
	}

	public function fetch_discourse_groups_checkbox() {
	    $this->form_helper->checkbox_input( 'wpds_fetch_discourse_groups', 'wpds_options', __( 'Refresh Discourse groups.', 'wpds' ) );
    }
}