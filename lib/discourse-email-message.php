<?php

namespace WPDiscourseShortcodes\DiscourseEmailMessage;

class DiscourseEmailMessage {
//	protected $utilities;
//	protected $options;


	public function __construct() {
//		$this->utilities = $utilities;

		add_action( 'init', array( $this, 'setup' ) );
		add_action( 'wp_ajax_process_discourse_email', array( $this, 'ajax_process_email' ) );
		add_action( 'wp_ajax_no_priv_process_discourse_email', array(
			$this,
			'ajax_process_email'
		) );
	}

	public function setup() {
		add_shortcode( 'discourse_email_message', array( $this, 'discourse_email_message' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	public function enqueue_scripts() {
		wp_register_script( 'handle_email_submission_js', plugins_url( '../js/handle-email-submission.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'handle_email_submission_js', 'handle_email_submission_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		wp_enqueue_script( 'handle_email_submission_js' );
	}

	public function discourse_email_message( $atts ) {
		$attributes = shortcode_atts( array(
			'to'      => '',
			'from'    => '',
			'subject' => '',
			'message'    => '',
		), $atts, 'discourse_email_message' );

		return $this->email_form( $attributes );
	}

	protected function email_form( $attributes ) {
		static $form_id = 0;
		$form_id   = $form_id + 1;
		$form_name = 'discourse_email_form_' . (string) $form_id;
		$to        = ! empty( $attributes['to'] ) && is_email( $attributes['to'] ) ? $attributes['to'] : null;
		if ( ! $to ) {
			new \WP_Error( 'The \'to\' email has not been set.' );
			return '';
		}

		$subject = ! empty( $attributes['subject'] ) ? $attributes['subject'] : null;

		$message = ! empty ( $attributes['message'] ) ? $attributes['message'] : null;
		?>

		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" class="discourse-email-message">
			<?php wp_nonce_field( $form_name, $form_name ); ?>

			<input type="hidden" name="discourse_email_form_name" value="<?php echo $form_name; ?>">
			<input type="hidden" name="discourse_to_address"
			       value="<?php echo sanitize_email( $to ); ?>">
			<?php
			if ( $subject ) {
				echo '<input type="hidden" name="discourse_email_subject" value="' . sanitize_text_field( $subject ) . '">';
			}

			?>
			<label for="user_email"><?php esc_html_e( 'Your email address:' ) ?></label><br>
			<input type="email" name="user_email"><br>
			<?php
			if ( $message ) {
				echo '<input type="hidden" name="discourse_email_message" value="' . esc_textarea( $message ) . '">';
			} else {
				echo '<textarea name="discourse_email_message" cols="12" rows="5"></textarea>';
			}
			?>
			<input type="submit" value="Send us a message" id="<?php echo $form_name; ?>">
		</form>

		<?php
	}

	public function ajax_process_email() {
		if ( ! empty( $_POST['formName'] ) && $form_name =  sanitize_key( wp_unslash( $_POST['formName'])))
		if ( ! isset( $_POST['nonce'] ) ||
		     ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nonce'] ) ), $form_name ) ) {
			echo 'there has been an error';
			exit();
		}

		$to = ! empty( $_POST['to'] ) ? sanitize_email( wp_unslash( $_POST['to'] ) ) : null;
		$from = ! empty( $_POST['from'] ) ? sanitize_email( wp_unslash( $_POST['from'] ) ) : null;
		$subject = !empty( $_POST['subject'] ) ? sanitize_text_field( wp_unslash( $_POST['subject'] ) ) : '';
		if ( ! empty( $_POST['prefilledMessage'] ) ) {
			$message = esc_textarea( wp_unslash( $_POST['prefilledMessage'] ) );
		} elseif ( ! empty( $_POST['composedMessage'] ) ) {
			$message = esc_textarea( wp_unslash( $_POST['composedMessage'] ) );
		} else {
			$message = '';
		}

		wp_mail( $to, $subject, $message);




		exit();

	}


}