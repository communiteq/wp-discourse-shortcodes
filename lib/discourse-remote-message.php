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
			'title'        => '',
			'message'      => '',
			'recipients'   => '',
			'button_text'  => 'Contact',
			'email_heading' => 'Email: ',
			'subject_heading' => 'Subject: ',
			'message_heading' => 'Message: ',
			'require_name' => false,
			'user_details' => false,
		), $atts, 'discourse_remote_message' );

		return $this->remote_message_form( $attributes );
	}

	public function remote_message_form( $attributes ) {
		static $form_id = 0;
		$form_id                  = $form_id + 1;
		$form_name                = 'discourse_remote_message_form_' . (string) $form_id;
		$title                    = ! empty( $attributes['title'] ) ? $attributes['title'] : null;
		$message                  = ! empty ( $attributes['message'] ) ? $attributes['message'] : null;
		$recipients               = ! empty( $attributes['recipients'] ) ? $attributes['recipients'] : null;
		$name_required            = ! empty( $attributes['require_name'] ) ? 'true' === $attributes['require_name'] : false;
		$user_details             = ! empty( $attributes['user_details'] ) ? 'true' === $attributes['user_details'] : false;
		$user_supplied_title      = '';
		$user_supplied_message    = '';
		$user_supplied_realname   = '';
		$user_supplied_recipients = '';

		ob_start();
		?>

		<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post"
		      class="discourse-remote-message">
			<?php
			$current_form_name = ! empty( $_GET['form_name'] ) ? sanitize_key( wp_unslash( $_GET['form_name'] ) ) : '';

			if ( isset( $_GET['message_created'] ) && $current_form_name === $form_name ) {
				echo '<div class="success wpdc-shortcodes-success">Thanks! Your message has been received!</div>';
			}

			if ( isset( $_GET['form_errors'] ) && $current_form_name === $form_name ) {
				if ( empty( $_GET['title'] ) ) {
					$title_error_code = 'missing_title';
					$this->form_errors()->add( $title_error_code, 'You must supply a subject for your message.' );
				} else {
					$user_supplied_title = urldecode( $_GET['title'] );
				}

				if ( empty( $_GET['message'] ) ) {
					$message_error_code = 'missing_message';
					$this->form_errors()->add( $message_error_code, 'You must supply a message.' );
				} else {
					$user_supplied_message = urldecode( $_GET['message'] );
				}

				if ( empty( $_GET['real_name'] ) ) {
					$realname_error_code = 'missing_realname';
					$this->form_errors()->add( $realname_error_code, 'You must supply your name.' );
				} else {
					$user_supplied_realname = urldecode( $_GET['real_name'] );
				}

				if ( empty( $_GET['user_email'] ) ) {
					$email_error_code = 'missing_email';
					$this->form_errors()->add( $email_error_code, 'You must supply your email address.' );
				} else {
					$user_supplied_email = urldecode( $_GET['user_email'] );
				}

				if ( empty( $_GET['recipients'] ) ) {
					$recipients_error_code = 'missing_recipients';
					$this->form_errors()->add( $recipients_error_code, 'You must supply a recipient for your message' );
				} else {
					$user_supplied_recipients = urldecode( $_GET['recipients'] );
				}

			}

			if ( isset( $_GET['network_errors'] ) && $current_form_name === $form_name ) {
				if ( isset( $_GET['unable_to_create_staged_user'] ) || isset( $_GET['unable_to_create_message'] ) ) {
					echo '<div class="error configuration-error-div">We are sorry. It is not possible to process your request at this time.</div>';
				}
			}
			?>

			<?php wp_nonce_field( $form_name, $form_name ); ?>
			<input type="hidden" name="action" value="process_discourse_remote_message">
			<input type="hidden" name="form_name" value="<?php echo $form_name; ?>">
			<input type="hidden" name="recipients" value="<?php echo $recipients; ?>">

			<?php // Todo: make fields required ?>
			<?php
			// Real name field.
			if ( $name_required ) {
				$user_supplied_realname = ! empty( $user_supplied_realname ) ? $user_supplied_realname : '';
				echo '<label for="real_name">Your name: </label>';
				if ( isset( $realname_error_code ) ) {
					$error_message = $this->form_errors()->get_error_message( $realname_error_code );
					echo '<span class="error"><strong>Error</strong>: ' . $error_message . '</span>';
				}
				echo '<input type="hidden" name="name_required" value="true">';
				echo '<input type="text" name="real_name" value="' . $user_supplied_realname . '" >';
			}

			// User details.
			if ( $user_details ) {
				echo '<input type="hidden" name="user_details" value="true">';
			}
			?>

			<?php // Email field. ?>
			<label for="user_email"><?php echo $attributes['email_heading']; ?></label>
			<?php if ( isset( $email_error_code ) ) {
				$error_message = $this->form_errors()->get_error_message( $email_error_code );
				echo '<span class="error"><strong>Error</strong>: ' . $error_message . '</span>';
			} ?>
			<input type="email" name="user_email"
			       value="<?php echo ! empty( $user_supplied_email ) ? $user_supplied_email : ''; ?>">

			<?php // Subject field. ?>
			<?php if ( $title ) {
				echo '<input type="hidden" name="title" value="' . sanitize_text_field( $title ) . '">';
			} else {
				echo '<label for="title">' . $attributes['subject_heading'] . '</label>';
				if ( isset( $title_error_code ) ) {
					$error_message = $this->form_errors()->get_error_message( $title_error_code );
					echo '<span class="error"><strong>Error</strong>: ' . $error_message . '</span>';
				}
				$user_supplied_title = ! empty( $user_supplied_title ) ? $user_supplied_title : '';
				echo '<input type="text" name="title" value="' . $user_supplied_title . '">';
			}

			// Message field.
			if ( $message ) {
				echo '<input type="hidden" name="message" value="' . esc_textarea( $message ) . '">';
			} else {
				echo '<label for="message">' . $attributes['message_heading'] . '</label>';
				if ( isset( $message_error_code ) ) {
					$error_message = $this->form_errors()->get_error_message( $message_error_code );
					echo '<span class="error"><strong>Error</strong>: ' . $error_message . '</span>';
				}
				$user_supplied_message = ! empty( $user_supplied_message ) ? $user_supplied_message : '';
				echo '<textarea name="message" cols="12" rows="5">' . $user_supplied_message . '</textarea>';
			}
			?>

			<?php // Honeypot for bots. ?>
			<label for="more_info" class="wpdc-shortcodes-more-info">If you are a human, leave this field blank</label>
			<input type="text" name="more_info" class="wpdc-shortcodes-more-info">

			<input type="submit" value="<?php echo esc_textarea( $attributes['button_text'] ); ?>"
			       id="<?php echo $form_name; ?>">
		</form>

		<?php

		$output = ob_get_clean();

		return apply_filters( 'wpdc_shortcodes_message', $output );
	}

	public function ajax_process_remote_message() {
		$form_name = ! empty( $_POST['form_name'] ) ? sanitize_key( wp_unslash( $_POST['form_name'] ) ) : '';
		if ( ! isset( $_POST[ $form_name ] ) ||
		     ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ $form_name ] ) ), $form_name )
		) {
			exit();
		}

		// Redirection values.
		$referer_url = explode( '?', wp_get_referer() )[0];
		$form_url    = home_url( $referer_url );

		// The more_info field is a honeypot.
		if ( ! empty( $_POST['more_info'] ) ) {

			wp_safe_redirect( $form_url );
			exit;
		}

		// Form values.
		$name_required = ! empty( $_POST['name_required'] ) ? sanitize_text_field( wp_unslash( $_POST['name_required'] ) ) : '';
		$real_name     = ! empty( $_POST['real_name'] ) ? sanitize_text_field( wp_unslash( $_POST['real_name'] ) ) : '';
		$user_details  = ! empty( $_POST['user_details'] ) ? sanitize_key( wp_unslash( $_POST['user_details'] ) ) : false;
		$email         = ! empty( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
		$title         = ! empty( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$message       = ! empty( $_POST['message'] ) ? esc_textarea( wp_unslash( $_POST['message'] ) ) : '';
		$recipients    = ! empty( $_POST['recipients'] ) ? sanitize_text_field( wp_unslash( $_POST['recipients'] ) ) : '';

		if ( ! $email || ! $title || ! $message || ! $recipients || ( $name_required && ! $real_name ) ) {
			$form_url = add_query_arg( array(
				'form_errors' => true,
				'form_name'   => $form_name,
				'real_name'   => urlencode( $real_name ),
				'user_email'  => urlencode( $email ),
				'title'       => urlencode( $title ),
				'message'     => urlencode( $message ),
				'recipients'  => urlencode( $recipients ),
			), $form_url );

			wp_safe_redirect( $form_url );
			exit;
		}

		// Credentials.
		$api_key      = $this->options['api-key'];
		$api_username = $this->options['publish-username'];

		// Check to see if there is an existing User with that email address.
		$username = $this->discourse_username_from_email( $email, $api_key, $api_username );

		if ( ! $username ) {
			$username = explode( '@', $email )[0];
			$name     = ! empty( $real_name ) ? $real_name : $username;
			$response = $this->create_staged_user( $email, $username, $name, $api_key, $api_username );

			if ( ! $this->utilities->validate( $response ) ) {
				$form_url = add_query_arg( array(
					'network_errors'               => true,
					'form_name'                    => $form_name,
					'unable_to_create_staged_user' => true,
				), $form_url );

				wp_safe_redirect( $form_url );
				exit;
			}
		}

		// Create the message.
		$name     = ! empty( $real_name ) ? $real_name : $username;
		$response = $this->send_message( $title, $message, $username, $name, $user_details, $recipients, $api_key );

		if ( ! $this->utilities->validate( $response ) ) {
			$form_url = add_query_arg( array(
				'network_errors'           => true,
				'form_name'                => $form_name,
				'unable_to_create_message' => true,
			), $form_url );

			wp_safe_redirect( $form_url );
			exit;
		}

		$form_url = add_query_arg( array(
			'message_created' => true,
			'form_name'       => $form_name,
		), $form_url );

		wp_safe_redirect( $form_url );
		exit;
	}

	protected function form_errors() {
		static $wp_error;

		return isset( $wp_error ) ? $wp_error : ( $wp_error = new \WP_Error() );
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

	protected function create_staged_user( $email, $username, $name, $api_key, $api_username ) {
		$password = wp_generate_password( 15 );

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
			'timeout' => 45,
			'body'    => $data,
		);

		$response = wp_remote_post( $create_user_url, $post_options );

		return $response;
	}

	protected function send_message( $title, $message, $username, $name, $user_details, $recipients, $api_key ) {
		$message_url = $this->base_url . '/posts';
		if ( $user_details ) {
			$message = 'Sent from: ' . $name . '<br><br>' . $message;
		}

		$data = array(
			'title'            => $title,
			'raw'              => $message,
			'api_username'     => $username,
			'archetype'        => 'private_message',
			'target_usernames' => $recipients,
			'api_key'          => $api_key,
			'skip_validations' => 'true',
		);

		$response = wp_remote_post( $message_url, array(
			'timeout' => 45,
			'body'    => $data,
		) );

		return $response;
	}
}