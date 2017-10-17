<?php
/**
 * Sets up the plugins options page.
 *
 * @package WPDiscourse\Shortcodes
 */

namespace WPDiscourse\Shortcodes;

use WPDiscourse\Utilities\Utilities as DiscourseUtilities;

/**
 * Class Admin
 *
 * @package WPDiscourse\Shortcodes
 */
class Admin {

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
	 * @param \WPDiscourse\Admin\FormHelper  $form_helper An instance of the FormHelper class.
	 */
	public function __construct( $options_page, $form_helper ) {
		$this->options_page = $options_page;
		$this->form_helper  = $form_helper;

		add_action( 'init', array( $this, 'setup_options' ) );
		add_action( 'admin_init', array( $this, 'register_latest_topics_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_latest_topics_page' ) );
		add_action( 'wpdc_options_page_append_settings_tabs', array( $this, 'settings_tab' ), 5, 1 );
		add_action( 'wpdc_options_page_after_tab_switch', array( $this, 'wpds_settings_fields' ) );
		add_action( 'save_post', array( $this, 'clear_post_topics_cache' ) );
		add_action( 'save_post', array( $this, 'clear_post_groups_cache' ) );
		add_action( 'update_option_wpds_options', array( $this, 'clear_topics_cache' ) );
		add_action( 'update_option_wpds_options', array( $this, 'clear_groups_cache' ) );
	}

	/**
	 * Checks if a discourse_topics shortcode exists in a post that is saved, if so, it calls clear_topics_cache.
	 *
	 * @param int $post_id The post_id to check.
	 *
	 * @return null
	 */
	public function clear_post_topics_cache( $post_id ) {
		$current_post = get_post( $post_id );
		if ( ! empty( $current_post-> post_content ) && has_shortcode( $current_post->post_content, 'discourse_topics' ) ) {
			$this->clear_topics_cache();
		}

		return null;
	}

	/**
	 * Chacks if a discourse_groups shortcode exists in a post that is saved, if so, it deletes the groups transients.
	 *
	 * @param int $post_id The post_id to check.
	 *
	 * @return null
	 */
	public function clear_post_groups_cache( $post_id ) {
		$current_post = get_post( $post_id );
		if ( ! empty( $current_post-> post_content ) && has_shortcode( $current_post->post_content, 'discourse_groups' ) ) {
			delete_transient( 'wpds_groups' );
			delete_transient( 'wpds_formatted_groups' );
		}

		return null;
	}

	/**
	 * Clear topics caches.
	 *
	 * Called directly when the wpds options page is saved.
	 */
	public function clear_topics_cache() {
			delete_transient( 'wpds_latest_topics' );
			delete_transient( 'wpds_latest_topics_html' );
			delete_transient( 'wpds_all_topics' );
			delete_transient( 'wpds_all_topics_html' );
			delete_transient( 'wpds_daily_topics' );
			delete_transient( 'wpds_daily_topics_html' );
			delete_transient( 'wpds_monthly_topics' );
			delete_transient( 'wpds_monthly_topics_html' );
			delete_transient( 'wpds_quarterly_topics' );
			delete_transient( 'wpds_quarterly_topics_html' );
			delete_transient( 'wpds_yearly_topics' );
			delete_transient( 'wpds_yearly_topics_html' );
	}

	/**
	 * Clears the groups transients and the wpds_discourse_groups option.
	 *
	 * Called when the wpds options page is saved.
	 */
	public function clear_groups_cache() {
		delete_transient( 'wpds_groups' );
		delete_transient( 'wpds_formatted_groups' );
		delete_option( 'wpds_discourse_groups' );
	}

	/**
	 * Setup the plugin options.
	 *
	 * The options array will contain the WPDiscourse options merged with the options that have been added through this
	 * plugin in `discourse-latest-topics.php`.
	 */
	public function setup_options() {
		$this->options     = DiscourseUtilities::get_options();
		$this->webhook_url = ! empty( $this->options['url'] ) ? $this->options['url'] . '/admin/api/web_hooks' : null;
	}

	/**
	 * Add settings section, settings fields, and register the setting.
	 */
	public function register_latest_topics_settings() {
		add_settings_section(
			'wpds_settings_section', __( 'WP Discourse Shortcodes Settings', 'wpds' ), array(
				$this,
				'shortcode_settings_details',
			), 'wpds_options'
		);

		add_settings_field(
			'wpds_topic_content', __( 'Retrieve Post Content', 'wpds' ), array(
			$this,
			'topic_content_checkbox',
		), 'wpds_options', 'wpds_settings_section'
		);

		add_settings_field(
			'wpds_max_topics', __( 'Maximum Number of Discourse Topics', 'wpds' ), array(
				$this,
				'max_topics_input',
			), 'wpds_options', 'wpds_settings_section'
		);

		add_settings_field(
			'wpds_display_private_topics', __( 'Display Private Topics', 'wpds' ), array(
				$this,
				'display_private_topics_checkbox',
			), 'wpds_options', 'wpds_settings_section'
		);

		add_settings_field(
			'wpds_use_default_styles', __( 'Use Default Styles', 'wpds' ), array(
				$this,
				'use_default_styles_checkbox',
			), 'wpds_options', 'wpds_settings_section'
		);

		add_settings_field(
			'wpds_vertical_ellipsis', __( 'Truncate Topic Content', 'wpds' ), array(
				$this,
				'vertical_ellipsis_checkbox',
			), 'wpds_options', 'wpds_settings_section'
		);

		add_settings_field(
			'wpds_topic_webhook_refresh', __( 'Enable Discourse Webhook', 'wpds' ), array(
				$this,
				'webhook_refresh_checkbox',
			), 'wpds_options', 'wpds_settings_section'
		);

		add_settings_field(
			'wpds_ajax_refresh', __( 'Ajax Load', 'wpds' ), array(
				$this,
				'ajax_load_checkbox',
			), 'wpds_options', 'wpds_settings_section'
		);

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
             <li><code>[discourse_topics]</code> <em> - displays a Discourse topic list</em></li>
			<br>
            <li><code>[discourse_groups]</code> <em> - displays a Discourse groups</em></li>
            <br>
            <li><code>[discourse_link]</code> <em>- links to a Discourse route</em></li>
            <br>
		</ul>

        <p><em>
                As long as you have configured the WP Discourse plugin's Connection settings, all of the shortcodes
                will give some output without your having to supply any attributes. See the <a href="https://github.com/scossar/wp-discourse-shortcodes">docs</a>
                for details about the attributes available for each shortcode.
            </em>
        </p>
        <p>
            <em>
                Feel free to raise any issues you have with the plugin on <a href="https://github.com/scossar/wp-discourse-shortcodes/issues">GitHub</a>.
            </em>
        </p>
        <p>
            <em>
                The settings below are all for the discourse_topics shortcode
            </em>
        </p>
        <h3>Discourse Topics Shortcode options</h3>
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
	 */
	public function webhook_refresh_checkbox() {
		$wordpress_url = home_url( '/wp-json/wp-discourse/v1/latest-topics' );

		// This is a little awkward.
		if ( ! empty( $this->webhook_url ) ) {
			$description = 'To enable the webhook you need to first enter and save a webhook secret key on the WP Discourse Webhooks tab.
                           You then need to setup the webhook on your Discourse forum at <a target="_blank" href="' .
						   esc_url( $this->webhook_url ) . '">' . esc_url( $this->webhook_url ) . '</a>. ' .
						   'On that page, set the \'Payload URL\' to <strong>' . esc_url( $wordpress_url ) . '</strong> .
                           Set the \'Secret\' field to the same value as you have set for the webhook secret key on WordPress.
                           On the events section of that page, select the \'Topic Event\' checkbox to receive
                           updates when there is a new topic. To receive updates when there are new replies, also select the \'Post Event\' checkbox.';

		} else {
			$description = 'To enable the webhook you need to first enter and save a webhook secret key on the WP Discourse Webhooks tab.
                           You then need to setup the webhook on your Discourse forum at <strong>http://discourse.example.com/admin/api/web_hooks</strong>
                           Set the \'Secret\' field to the same value as you have set for the webhook secret key on WordPress.
		                   On that page, set the "Payload URL" to <strong>' . esc_url( $wordpress_url ) . '</strong> . On the events section of that page, select the "Topic Event" checkbox to receive
                           updates when there is a new topic. To receive updates when there are new replies, also select the "Post Event" checkbox.';
		}

		$this->form_helper->checkbox_input( 'wpds_topic_webhook_refresh', 'wpds_options', __( "Use a Discourse Webhook to refresh the 'latest' topic list.", 'wpds' ), $description );
	}

	/**
	 * Displays the ajax_load_checkbox field.
	 */
	public function ajax_load_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_ajax_refresh', 'wpds_options', __( 'Use an ajax request to load topics on the front end.', 'wpds' ),
            __( "Enable this if caching on your WordPress site is preventing the 'latest' topic list from being updated.", 'wpds' ) );
	}

	/**
	 * Displays the display_private_topics checkbox field.
	 */
	public function display_private_topics_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_display_private_topics', 'wpds_options', __( 'Display private topics in topic list.', 'wpds' ),
            __( 'By default, private topics are ignored.', 'wpds' ) );
	}

	/**
	 * Displays the use_default_styles_checkbox field.
	 */
	public function use_default_styles_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_use_default_styles', 'wpds_options', __( 'Use the default plugin styles.', 'wpds' ),
            __( "The plugin comes with some basic styles that can be used as a starting point for your site. If enabled, adding the attribute
            'tile=true' to either the discourse_topics or discourse_groups shortcode will cause their output to be displayed as a tiled grid.", 'wpds') );
	}

	/**
	 * Displays the vertical_ellipsis checkbox field.
	 */
	public function vertical_ellipsis_checkbox() {
		$this->form_helper->checkbox_input( 'wpds_vertical_ellipsis', 'wpds_options', __( "Use the plugin's vertical_ellipsis javascript to truncate overflowing text content", 'wpds' ),
            "The height of the output from the Discourse topic list is unpredictable. If you give the 'li.wpds-topic' a fixed height, or use the default
             css with 'tile=true' as an attribute, this javascript will truncate the text so that it fits in the container. For efficiency reasons,
             it's best to try and get the excerpt length to match it's containers height.");
	}

	/**
	 * Displays the max_topics input field.
	 */
	public function max_topics_input() {
		$this->form_helper->input( 'wpds_max_topics', 'wpds_options', __( 'Maximum number of topics to retrieve post content for. Set it to the
		largest max_topics value that you are using in any discourse_topics shortcodes on your site. (This setting can be ignored
		if Retrieve Post Content is not enabled.)', 'wpds' ), 'number', 0 );
	}

	/**
	 * Displays the topic_content checkbox field.
	 */
	public function topic_content_checkbox() {
		$this->form_helper->checkbox_input(
			'wpds_topic_content', 'wpds_options', __( "Retrieve the topic's post content from Discourse.", 'wpds' ),
			__( "The Discourse topic list doesn't return any post content for a topic. To get the content requires making extra HTTP requests to Discourse.
			Enabling this setting will allow you to display an excerpt along with each topic in the topic list.", 'wpds' )
		);
	}
}
