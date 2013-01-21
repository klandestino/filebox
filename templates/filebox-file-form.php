<form id="file-form" action="" method="post" class="filebox-file-form filebox-iframe-form">
	<?php global $file_id, $file; ?>
	<?php wp_nonce_field( 'rename_file', 'security' ); ?>
	<input type="hidden" name="action" value="filebox_rename_file" />
	<input type="hidden" name="file_id" value="<?php echo $file_id; ?>" />

	<p>
		<label for="file-name" class="file-name"><?php _e( 'File title', 'filebox' ); ?></label><br/>
		<input id="file-name" type="text" class="file-name text" name="file_name" value="<?php echo esc_attr( $file->post_title ); ?>" />
	</p>

	<p>
		<label for="file-desc" class="file-desc"><?php _e( 'File description', 'filebox' ); ?></label><br/>
		<input id="file-desc" type="text" class="file-desc text" name="file_description" value="<?php echo esc_attr( $file->post_excerpt ); ?>" />
	</p>

	<input type="submit" name="submit" value="<?php esc_attr_e( 'Save file', 'filebox' ); ?>" />
</form>
