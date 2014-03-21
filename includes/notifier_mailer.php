<?php

/**
 * Class for sending e-mail notifications and digestions
 */
class Filebox_Notifier_Mailer {

	/**
	 */
	static public function __setup() {
		// Log emails going out
		//add_filter( 'wp_mail', array( Filebox_Notifier_Mailer, 'log' ), 10, 5 );

		// Add scheduled action for delayed e-mails
		add_action( 'filebox_notifier_scheduled_email', array( Filebox_Notifier_Mailer, 'send_notification_email' ), 1, 1 );
	}

	/**
	 * Log whatever and return it
	 * @param mixed $whatever
	 * @return mixes $whatever
	 */
	static public function log( $whatever ) {
		$file = fopen( '/tmp/filebox-notifier.log', 'a' );
		fwrite( $file, var_export( $whatever, true ) );
		fclose( $file );
		return $whatever;
	}

	/**
	 * Sends notification email stored as a user-meta or attached as an argument
	 * @param int $user_id
	 * @param array $mail_properties optional, if used, user-meta will not be used
	 * @return void
	 */
	static public function send_notification_email( $user_id, $mail_properties = array() ) {
		if( empty( $mail_properties ) || ! is_array( $mail_properties ) ) {
			$mail_properties = get_user_meta( $user_id, 'filebox_notifier_emails' );
		}

		//self::log( $mail_properties );

		if( ! is_array( $mail_properties ) ) {
			// Fail, no e-mails found
			return;
		}

		if( array_key_exists( 'file_id', $mail_properties ) ) {
			$mail_properties = array( $mail_properties );
		}

		global $filebox;
		$messages = array();
		$user = get_userdata( $user_id );
		$blogname = get_option( 'blogname' );

		foreach( $mail_properties as $props ) {
			extract( $props );
			$file = $filebox->get_file( $file_id );
			$folder = $filebox->get_folder( $folder_id );
			$group = groups_get_group( array( 'group_id' => $group_id ) );

			switch( substr( $action, 0, 11 ) ) {
				case 'file_update' :
					$subject = sprintf(
						$filebox->options[ 'file-update-mail-subject-single' ],
						$blogname,
						$file->post_title,
						$folder->name,
						$group->name
					);
					$messages[] = sprintf(
						$filebox->options[ 'file-update-mail-message-line' ],
						$file->post_title,
						$folder->name,
						$group->name
					);
					break;

				case 'file_upload' :
					$subject = sprintf(
						$filebox->options[ 'file-upload-mail-subject-single' ],
						$blogname,
						$file->post_title,
						$folder->name,
						$group->name
					);
					$messages[] = sprintf(
						$filebox->options[ 'file-upload-mail-message-line' ],
						$file->post_title,
						$folder->name,
						$group->name
					);
					break;
			}

		}
		if( count( $messages ) > 1 ) {
			$subject = sprintf( $filebox->options[ 'multiple-mail-messages-subject' ], $blogname, count( $messages ) );
		}

		$message = sprintf( $filebox->options[ 'mail-message-wrap' ], implode( "\n\n--------------------\n\n", $messages ) );

		if( wp_mail( $user->user_email, $subject, $message ) ) {
			delete_user_meta( $user_id, 'filebox_notifier_emails' );
		}
	}

}
