<?php global $settings;

$fields = array(
	'group-tab' => array(
		'textfield',
		__( 'Group tab title', 'filebox' )
	),
	'topics-folder-name' => array(
		'textfield',
		__( 'Folder name for imported forum attachments', 'filebox' )
	),
	'mail-delay' => array(
		'textfield',
		__( 'Delay in minutes before e-mails are sent', 'filebox' )
	),
	'multiple-mail-messages-subject' => array(
		'textfield',
		__( 'E-mail subject when message contains multiple uploads', 'filebox' ),
		__( '%1$s = blogname, %2$d = number of files', 'filebox' )
	),
	'file-update-mail-subject-single' => array(
		'textfield',
		__( 'E-mail subject for single file updated', 'filebox' ),
		__( '%1$s = blogname, %2$s = filename, %3$s = folder name, %4$s group name', 'filebox' )
	),
	'file-update-mail-message-line' => array(
		'textarea',
		__( 'E-mail message for single file updated', 'filebox' ),
		__( '%1$s = filename, %2$s = folder name, %3$s group name', 'filebox' )
	),
	'file-upload-mail-subject-single' => array(
		'textfield',
		__( 'E-mail subject for single file upload', 'filebox' ),
		__( '%1$s = blogname, %2$s = filename, %3$s = folder name, %4$s group name', 'filebox' )
	),
	'file-upload-mail-message-line' => array(
		'textarea',
		__( 'E-mail message for single file upload', 'filebox' ),
		__( '%1$s = filename, %2$s = folder name, %3$s group name', 'filebox' )
	),
	'file-update-notify-single' => array(
		'textfield',
		__( 'Notification for single file updated', 'filebox' ),
		__( '%1$s = filename, %2$s = folder name, %3$s group name', 'filebox' )
	),
	'file-update-notify-multi' => array(
		'textfield',
		__( 'Notification for multiple files updated in the same folder and group', 'filebox' ),
		__( '%1$s = filename, %2$s = folder name, %3$s group name', 'filebox' )
	),
	'file-upload-notify-single' => array(
		'textfield',
		__( 'Notification for single file uploaded', 'filebox' ),
		__( '%1$s = filename, %2$s = folder name, %3$s group name', 'filebox' )
	),
	'file-upload-notify-multi' => array(
		'textfield',
		__( 'Notification for multiple new files uploaded in the same folder and group', 'filebox' ),
		__( '%1$s = filename, %2$s = folder name, %3$s group name', 'filebox' )
	),
	'mail-message-wrap' => array(
		'textarea',
		__( 'E-mail message body wrapper', 'filebox' ),
		__( '%1$s = message bodies', 'filebox' )
	)
);

?>
<div class="wrap">
	<h2><?php _e( 'Filebox Settings', 'filebox' ); ?></h2>
	<form action="" method="post">
		<?php wp_nonce_field( 'filebox_admin' ); ?>

		<table class="form-table">
			<tbody>
				<?php foreach( $fields as $field_name => $field ) : ?>
					<tr>
						<th scope="row">
							<label for="<?php echo $field_name; ?>"><?php echo $field[ 1 ]; ?></label>
						</th>
						<td>
							<?php if( $field[ 0 ] == 'textfield' ) : ?>
								<input id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" type="textfield" class="large-text" value="<?php echo esc_attr( $settings[ $field_name ] ); ?>" />
							<?php elseif( $field[ 0 ] == 'textarea' ) : ?>
								<textarea id="<?php echo $field_name; ?>" name="<?php echo $field_name; ?>" class="large-text"><?php echo $settings[ $field_name ]; ?></textarea>
							<?php endif; ?>

							<?php if( array_key_exists( 2, $field ) ) : ?>
								<br />
								<?php echo $field[ 2 ]; ?>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>

		<p class="submit clear">
			<input class="button-filebox button-primary" name="filebox-save" type="submit" value="<?php echo esc_attr( __( 'Save' ) ); ?>" />
		</p>

	</form>
</div>
