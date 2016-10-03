<?php

namespace WPDiscourseShortcodes\DiscourseRemoteMessage;

class DiscourseRemoteMessage {
	protected $utilities;
	protected $options;
	protected $base_url;


	public function __construct( $utilities ) {
		$this->utilities = $utilities;

		add_action( 'init', array( $this, 'setup' ) );
		add_shortcode( 'discourse_remote_message', array( $this, 'discourse_remote_message' ) );
		add_action( 'wp_ajax_process_discourse_remote_message', array(
			$this,
			'ajax_process_remote_message'
		) );
		add_action( 'wp_ajax_nopriv_process_discourse_remote_message', array(
			$this,
			'ajax_process_remote_message'
		) );
	}

	public function setup() {
		$this->options  = $this->utilities->get_options();
		$this->base_url = $this->utilities->base_url( $this->options );
	}

	public function discourse_remote_message( $atts ) {
		$attributes = shortcode_atts( array(
			'title'      => '',
			'message'    => '',
			'recipients' => '',
		), $atts, 'discourse_remote_message' );

		return $this->remote_message_form( $attributes );
	}

	public function remote_message_form( $attributes ) {
		static $form_id = 0;
		$form_id    = $form_id + 1;
		$form_name  = 'discourse_remote_message_form_' . (string) $form_id;
		$title      = ! empty( $attributes['title'] ) ? $attributes['title'] : null;
		$message    = ! empty ( $attributes['message'] ) ? $attributes['message'] : null;
		$recipients = ! empty( $attributes['recipients'] ) ? $attributes['recipients'] : null;
		?>

		<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post"
		      class="discourse-remote-message">
			<?php wp_nonce_field( $form_name, $form_name ); ?>
			<input type="hidden" name="action" value="process_discourse_remote_message">
			<input type="hidden" name="form_name" value="<?php echo $form_name; ?>">
			<input type="hidden" name="recipients" value="<?php echo $recipients; ?>">

			<label for="user_email"><?php esc_html_e( 'Your email address:' ); ?></label><br>
			<input type="email" name="user_email"><br>
			<?php if ( $title ) {
				echo '<input type="hidden" name="title" value="' . sanitize_text_field( $title ) . '">';
			} else {
				echo '<label for="title">Subject:</label><br>';
				echo '<input type="text" name="title" value="' . sanitize_text_field( $title ) . '">';
			} ?>
			<?php
			if ( $message ) {
				echo '<input type="hidden" name="message" value="' . esc_textarea( $message ) . '">';
			} else {
				echo '<label for="message">Message:</label><br>';
				echo '<textarea name="message" cols="12" rows="5"></textarea>';
			}
			?>
			<input type="submit" value="Send us a message" id="<?php echo $form_name; ?>">
		</form>

		<?php
	}

	public function ajax_process_remote_message() {
		$form_name = ! empty( $_POST['form_name'] ) ? sanitize_key( wp_unslash( $_POST['form_name'] ) ) : '';
		if ( ! isset( $_POST[ $form_name ] ) ||
		     ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $form_name ] ) ), $form_name )
		) {
			exit();
		}

		// Get the form values.
		$email      = ! empty( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : null;
		$title      = ! empty( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$message    = ! empty( $_POST['message'] ) ? esc_textarea( wp_unslash( $_POST['message'] ) ) : '';
		$recipients = ! empty( $_POST['recipients'] ) ? sanitize_text_field( wp_unslash( $_POST['recipients'] ) ) : null;

		$api_key      = $this->options['api-key'];
		$api_username = $this->options['publish-username'];


		// Check to see if there is an existing User with that email address.
		$user_url = $this->base_url . '/admin/users/list/active.json';
		$user_url = add_query_arg( array(
			'filter'       => urlencode( $email ),
			'api_key'      => $api_key,
			'api_username' => $api_username,
		), $user_url );

		$user_url = esc_url_raw( $user_url );
		$response = wp_remote_get( $user_url );

		if ( $this->utilities->validate( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 1 === count( $response ) && ! empty( $response[0]['username'] ) ) {

				$username = $response[0]['username'];
//				$user_id  = $response[0]['id'];
			} else {
				// Create a staged User.
				$password = wp_generate_password( 15 );
				$username = explode( '@', $email )[0];
				$name     = $username;

				$create_user_url = $this->base_url . '/users';

				$data = array(
					'api_key'      => $api_key,
					'api_username' => $api_username,
					'password'     => $password,
					'email'        => $email,
					'username'     => $username,
					'name'         => $name,
					'active'       => 'false',
					'staged'       => 'true',
				);

				$post_options = array(
					'body' => $data,
				);

				$response = wp_remote_post( $create_user_url, $post_options );

//				if ( $this->utilities->validate( $response ) ) {
//					$response = json_decode( wp_remote_retrieve_body( $response ), true );
//					$user_id  = $response['user_id'];
//				} else {
//					write_log( $response );
//					exit;
//				}

				if ( ! $this->utilities->validate( $response ) ) {

					$this->redirect_to_referer();
					exit;
				}
			}

			// Create the message.
			$message_url = $this->base_url . '/posts';
			$data        = array(
				'title'            => $title,
				'raw'              => $message,
				'api_username'     => $username,
				'archetype'        => 'private_message',
				'target_usernames' => $recipients,
				'api_key'          => $api_key,
				'skip_validations' => 'true',
			);

			$response = wp_remote_post( $message_url, array(
				'body' => $data
			) );

			write_log( $response );

			if ( ! $this->utilities->validate( $response ) ) {
				// Change to redirect to error page.
//				$this->redirect_to_referer();
//				exit;
			}
		}

//		$referer_url = explode( '?', wp_get_referer() )[0];
//		$form_url    = home_url( $referer_url );
//		wp_safe_redirect( esc_url_raw( $form_url ) );

		$this->redirect_to_referer();
		exit;
	}

	protected function discourse_username_from_email( $email, $api_key, $api_username ) {
		$user_url = $this->base_url . '/admin/users/list/active.json';
		$user_url = add_query_arg( array(
			'filter'       => urlencode( $email ),
			'api_key'      => $api_key,
			'api_username' => $api_username,
		), $user_url );

		$user_url = esc_url_raw( $user_url );
		$response = wp_remote_get( $user_url );

		if ( $this->utilities->validate( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( 1 === count( $response ) && ! empty( $response[0]['username'] ) ) {

				return $response[0]['username'];
			}

			return null;
		}

		return null;
	}

	protected function redirect_to_referer() {
		$referer_url = explode( '?', wp_get_referer() )[0];
		$form_url    = home_url( $referer_url );

		wp_safe_redirect( esc_url_raw( $form_url ) );
	}
}