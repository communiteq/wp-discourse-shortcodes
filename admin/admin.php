<?php

namespace WPDiscourse\Shortcodes;

class Admin {
	use Utilities;

	/**
	 * The WPDiscourse options page.
	 *
	 * Use this class to hook into the WPDiscourse options page.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\OptionsPage
	 */
	protected $options_page;

	/**
	 * The WPDiscourse FormHelper.
	 *
	 * Use of this class is optional, it makes it simple to build common form elements and save their values
	 * to option arrays.
	 *
	 * @access protected
	 * @var \WPDiscourse\Admin\FormHelper
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
	 * @param \WPDiscourse\Admin\OptionsPage $options_page An instance of the OptionsPage class.
	 * @param \WPDiscourse\Admin\FormHelper $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'admin_init', array( $this, 'register_latest_topics_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_latest_topics_page' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'settings_tab' ), 5, 1 );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'wpds_settings_fields' ) );
		add_action( 'save_post', array( $this, 'clear_topics_cache' ) );
	}

	/**
	 * Clear topics cache if a post contains the 'discourse_topics' shortcode.
     *
     * This allows updates to shortcode attributes to take effect immediately.
	 */
	public function clear_topics_cache( $post_id ) {
	    $current_post = get_post( $post_id );

		if ( ! empty( $current_post->post_content ) && has_shortcode( $current_post->post_content, 'discourse_topics' ) ) {
			delete_transient( 'wpds_latest_topics' );
			delete_transient( 'wpds_top_yearly' );
			delete_transient( 'wpds_top_quarterly' );
			delete_transient( 'wpds_top_monthly' );
			delete_transient( 'wpds_top_daily' );
		}
	}

	/**
	 * Setup the plugin options.
	 *
	 * The options array will contain the WPDiscourse options merged with the options that have been added through this
	 * plugin in `discourse-latest-topics.php`.
	 */
	public function setup_options() {
		$this->options     = $this->get_options();
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

		add_settings_field( 'wpds_fetch_discourse_groups', __( 'Refresh Discourse Groups', 'wpds' ), array(
			$this,
			'fetch_discourse_groups_checkbox',
		), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_display_private_topics', __( 'Display Private Topics', 'wpds' ), array(
			$this,
			'display_private_topics_checkbox',
		), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_use_default_styles', __( 'Use Default Styles', 'wpds' ), array(
			$this,
			'use_default_styles_checkbox',
		), 'wpds_options', 'wpds_settings_section' );


		add_settings_field( 'wpds_topic_webhook_refresh', __( 'Enable Discourse Webhook', 'wpds' ), array(
			$this,
			'webhook_refresh_checkbox',
		), 'wpds_options', 'wpds_settings_section' );

		add_settings_field( 'wpds_ajax_refresh', __( 'Ajax Load', 'wpds' ), array(
			$this,
			'ajax_load_checkbox',
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
            <em>The following shortcodes are available:</em>
        </p>
        <ul>
            <li><code>[discourse_latest]</code> <em>paramaters:</em> <code>max_topics</code> <em>(default '5')</em>
                <code>display_avatars</code> <em>(default 'true')</em></li>
            <br>
            <li><code>[discourse_link]</code> <em>paramaters:</em> <code>link_text</code> <em>(default 'Visit Our
                    Forum')</em> <code>path</code> <em>(default '/')</em><br>
                <code>classes</code> <em>(default '')</em> <code>login</code> <em>(default 'true', requires SSO Provider
                    to be enabled)</em></li>
            <br>
            <li><code>[discourse_prefilled_message]</code> <em>paramaters:</em> <code>link_text</code> <em>(default
                    'Contact Us')</em> <code>classes</code> <em>(default '')</em><br>
                <code>title</code> <em>(default '')</em> <code>message</code> <em>(default '')</em>
                <code>username</code> <em>(default '')</em> <code>groupname</code> <em>(default ''.) If
                    both a username and a groupname are supplied, will default to groupname. The
                    discourse_prefilled_message shortcode requires
                    WordPress to be enabled as the SSO Provider for Discourse.</em></li>
            <br>
            <li><code>[discourse_groups]</code> <em>parameters:</em> <code>link_type</code> <em>(default 'visit',
                    available 'visit', 'message')</em> <code>group_list</code>
                <em>(If left empty, will default to all non-automatic groups, otherwise, supply a comma separated list
                    of group_names.)</em>
                <code>link_open_text</code> <em>(default 'Join')</em> <code>link_close_text</code> <em>(default '')</em>
            </li>
        </ul>
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
			$description = 'To use the latest_topics shortcode, you need to setup a <strong>webhook</strong> on your Discourse forum at <a href="' .
			               esc_url( $this->webhook_url ) . '">' . esc_url( $this->webhook_url ) . '</a>. ' .
			               'On that page, set the "Payload URL" to <strong>' . esc_url( $wordpress_url ) . '</strong>.
                           On the events section of that page, select the "Topic Event" checkbox to receive
                           updates when there is a new topic. To receive updates when there are new replies, also select the "Post Event" checkbox.';

		} else {
			$description = 'To use the latest_topics shortcode you need to setup a <strong>webhook</strong> on your Discourse forum at <strong>http://discourse.example.com/admin/api/web_hooks</strong>
		                   On that page, set the "Payload URL" to <strong>' . esc_url( $wordpress_url ) . '</strong>. On the events section of that page, select the "Topic Event" checkbox to receive
                           updates when there is a new topic. To receive updates when there are new replies, also select the "Post Event" checkbox.';
		}

		$this->form_helper->checkbox_input( 'wpds_topic_webhook_refresh', 'wpds_options', __( 'Use a Discourse Webhook to refresh comments.', 'wpds' ), $description );
	}

	/**
	 * Displays the ajax_load_checkbox field.
	 */
	public function ajax_load_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_ajax_refresh', 'wpds_options', __( 'Use an ajax request to load topics on the front end.', 'wpds' ) );
	}

	public function display_private_topics_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_display_private_topics', 'wpds_options', __( 'Display private topics in
	    topic list.', 'wpds' ) );
	}

	/**
	 * Displays the use_default_styles_checkbox field.
	 */
	public function use_default_styles_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_use_default_styles', 'wpds_options', __( 'Use the default plugin styles.', 'wpds' ) );
	}

	public function fetch_discourse_groups_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_fetch_discourse_groups', 'wpds_options', __( 'Refresh Discourse groups.', 'wpds' ) );
	}
}
