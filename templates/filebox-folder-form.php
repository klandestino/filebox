<form id="folder-form" action="" method="post" class="filebox-folder-form filebox-iframe-form">
	<?php global $folder_id, $folder_parent, $folder; ?>
	<?php wp_nonce_field( is_object( $folder ) ? 'rename_folder' : 'add_folder', 'security' ); ?>
	<input type="hidden" name="action" value="<?php echo is_object( $folder ) ? 'filebox_rename_folder' : 'filebox_add_folder'; ?>" />
	<input type="hidden" name="folder_parent" value="<?php echo $folder_parent; ?>" />
	<input type="hidden" name="folder_id" value="<?php echo $folder_id; ?>" />

	<p>
		<label for="folder-name" class="folder-name"><?php _e( 'Folder name', 'filebox' ); ?></label><br/>
		<input id="folder-name" type="text" class="folder-name text" name="folder_name" value="<?php echo is_object( $folder ) ? esc_attr( $folder->name ) : ''; ?>" />
	</p>

	<p>
		<label for="folder-desc" class="folder-desc"><?php _e( 'Folder description', 'filebox' ); ?></label><br/>
		<input id="folder-desc" type="text" class="folder-desc text" name="folder_description" value="<?php echo is_object( $folder ) ? esc_attr( $folder->description ) : ''; ?>" />
	</p>

	<input type="submit" name="submit" value="<?php esc_attr_e( 'Save folder', 'filebox' ); ?>" />
</form>
