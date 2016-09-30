<?php

namespace WPDiscourseShortcodes\DiscourseRemoteMessage;

class DiscourseRemoteMessage {
	protected $utilities;
	protected $options;
	protected $base_url;


	public function __construct( $utilities ) {
		$this->utilities = $utilities;

		add_action( 'init', array( $this, 'setup' ) );
		add_action( 'wp_ajax_process_discourse_remote_message', array( $this, 'ajax_process_remote_message' ) );
		add_action( 'wp_ajax_no_priv_process_discourse_remote_message', array(
			$this,
			'ajax_process_remote_message'
		) );
	}

	public function setup() {
		$this->options = $this->utilities->get_options();
		$this->base_url = $this->utilities->base_url( $this->options );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_shortcode( 'discourse_remote_message', array( $this, 'discourse_remote_message' ) );
	}

	public function enqueue_scripts() {
		wp_register_script( 'handle_remote_message_submission_js', plugins_url( '../js/handle-remote-message-submission.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'handle_remote_message_submission_js', 'handle_remote_message_submission_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'handle_remote_message_submission_js' );
	}

	public function discourse_remote_message_message( $atts ) {
		$attributes = shortcode_atts( array(
			'to'      => '',
			'from'    => '',
			'subject' => '',
			'message'    => '',
		), $atts, 'discourse_remote_message' );

		return $this->remote_message_form( $attributes );
	}

	protected function remote_message_form( $attributes ) {
		static $form_id = 0;
		$form_id   = $form_id + 1;
		$form_name = 'discourse_remote_message_form_' . (string) $form_id;
		$title = ! empty( $attributes['title'] ) ? $attributes['title'] : null;

		$message = ! empty ( $attributes['message'] ) ? $attributes['message'] : null;
		?>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="discourse-remote-message">
			<?php wp_nonce_field( $form_name, $form_name ); ?>

			<input type="hidden" name="discourse_remote_message_form_name" value="<?php echo $form_name; ?>">
			if ( $title ) {
				echo '<input type="hidden" name="discourse_remote_message_subject" value="' . sanitize_text_field( $title ) . '">';
			}

			?>
			<label for="user_email"><?php esc_html_e( 'Your email address:' ) ?></label><br>
			<input type="email" name="user_email"><br>
			<?php
			if ( $message ) {
				echo '<input type="hidden" name="discourse_remote_message" value="' . esc_textarea( $message ) . '">';
			} else {
				echo '<textarea name="discourse_remote_message" cols="12" rows="5"></textarea>';
			}
			?>
			<input type="submit" value="Send us a message" id="<?php echo $form_name; ?>">
		</form>

		<?php
	}

	public function ajax_process_remote_message() {
		if ( ! empty( $_POST['formName'] ) && $form_name =  sanitize_key( wp_unslash( $_POST['formName'])))
		if ( ! isset( $_POST['nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), $form_name ) ) {
			echo 'there has been an error';
			exit();
		}

		$userEmail = ! empty( $_POST['from'] ) ? sanitize_email( wp_unslash( $_POST['userEmail'] ) ) : null;
		$title = !empty( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		if ( ! empty( $_POST['prefilledMessage'] ) ) {
			$message = esc_textarea( wp_unslash( $_POST['prefilledMessage'] ) );
		} elseif ( ! empty( $_POST['composedMessage'] ) ) {
			$message = esc_textarea( wp_unslash( $_POST['composedMessage'] ) );
		} else {
			$message = '';
		}

		// now find user or create staged user.


		exit();

	}


}