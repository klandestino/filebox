<?php
global $bp, $filebox;

function get_file_size( $id ) {
	$bytes = filesize( get_attached_file( $id ) );

	if( $bytes < 1024 ) {
		return $bytes . ' B';
	} elseif( $bytes < 1048576 ) {
		return round( $bytes / 1024, 1 ) . ' KB';
	} elseif( $bytes < 1073741824 ) {
		return round( ( $bytes / 1024 ) / 1024, 1 ) . ' MB';
	} else {
		return round( ( ( $bytes / 1024 ) / 1024 ) / 1024, 1 ) . ' GB';
	}
}

$args = array( 'group_id' => $bp->groups->current_group->id );

if( preg_match_all( '/(?:\/([^\/]+))/', $_SERVER[ 'REQUEST_URI' ], $match ) ) {
	$args[ 'folder_slug' ] = array_slice( $match[ 1 ], array_search( 'filebox', $match[ 1 ], true ) + 1 );
}

$documents = $filebox->list_files_and_folders( $args, ARRAY_A );
$trash_count = $filebox->trash_count( $bp->groups->current_group->id );
$folder_base_url = bp_get_group_permalink( $bp->groups->current_group ) . 'filebox';
?>

<ul class="filebox-breadcrumbs">
	<li class="title"><?php _e( 'Filebox', 'filebox' ); ?></li>
	<?php if( array_key_exists( 'breadcrumbs', $documents[ 'meta' ] ) ): ?>
		<?php foreach( $documents[ 'meta' ][ 'breadcrumbs' ] as $folder ): ?>
			<?php $folder_base_url .= $folder->parent ? '/' . $folder->slug : ''; ?>
			<li class="folder">» <a href="<?php echo esc_url( $folder_base_url ); ?>"><?php echo esc_attr( $folder->name ); ?></a></li>
		<?php endforeach; ?>
	<?php endif; ?>
	<?php $folder_base_url .= $documents[ 'meta' ][ 'current' ]->parent ? '/' . $documents[ 'meta' ][ 'current' ]->slug : ''; ?>
	<li class="folder<?php echo $documents[ 'meta' ][ 'id' ] ? ' current' : ''; ?>">» <a href="<?php echo esc_url( $folder_base_url ); ?>"><?php echo esc_attr( $documents[ 'meta' ][ 'current' ]->name ); ?></a></li>
	<?php if( $trash_count || array_key_exists( 'trash', $documents[ 'meta' ] ) ): ?>
		<li class="trash<?php echo array_key_exists( 'trash', $documents[ 'meta' ] ) ? ' current' : ''; ?>"> | <a href="<?php echo esc_url( bp_get_group_permalink( $bp->groups->current_group ) . 'filebox/trash' ); ?>"><?php echo sprintf( __( 'Trash (%d)', 'filebox' ), $trash_count ); ?></a></li>
	<?php endif; ?>
</ul>

<?php if( ! array_key_exists( 'trash', $documents[ 'meta' ] ) ): ?>
	<ul class="filebox-buttons">
		<li class="upload">
			<a href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=upload&folder_id=<?php echo $documents[ 'meta' ][ 'id' ]; ?>" class="thickbox add_media button" title="<?php esc_attr_e( 'Add files', 'filebox' ) ?>" onclick="return false;" >
				<?php _e( 'Add files', 'filebox' ); ?>
			</a>
		</li>
		<li class="folder">
			<a href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=folder&folder_parent=<?php echo $documents[ 'meta' ][ 'id' ]; ?>" class="thickbox add_media button" title="<?php esc_attr_e( 'Add folder', 'filebox' ) ?>" onclick="return false;" >
				<?php _e( 'Add folder', 'filebox' ); ?>
			</a>
		</li>
	</ul>
<?php endif; ?>

<table class="filebox-folder-file-list" cellspacing="0">
	<thead>
		<tr>
			<th class="filebox-checkall"></th>
			<th class="filebox-title"><?php _e( 'Title', 'filebox' ); ?></th>
			<th class="filebox-changed"><?php _e( 'Changed', 'filebox' ); ?></th>
			<th class="filebox-owner"><?php _e( 'Owner', 'filebox' ); ?></th>
			<th class="filebox-size"><?php _e( 'Size', 'filebox' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php $even = true;
			foreach( array( 'folders', 'files' ) as $type ): ?>
			<?php foreach( $documents[ $type ] as $doc ): $even = ! $even; ?>
				<tr class="filebox-<?php echo $type; ?> <?php echo $even ? 'even' : 'odd'; ?> filebox-title filebox-<?php echo $type == 'folders' ? $doc->term_id : $doc->ID; ?>">
					<td rowspan="3" class="filebox-icon">
						<?php if( $type == 'folders' ): ?>
							<img src="<?php echo FILEBOX_PLUGIN_URL . 'images/folder-' . ( $doc->count ? 'files' : 'empty' ); ?>.png" width="46" height="60" />
						<?php else: $attachment = reset( $doc->attachments ); ?>
							<?php echo wp_get_attachment_image( $attachment->ID, 'filebox-thumbnail', ! wp_attachment_is_image( $attachment->ID ) ); ?>
						<?php endif; ?>
					</td>
					<th class="filebox-title">
						<?php if( $type == 'folders' ): ?>
							<a href="<?php echo esc_url( $folder_base_url . '/'. $doc->slug ); ?>"><?php echo esc_attr( $doc->name ); ?></a>
						<?php else: ?>
							<a href="<?php echo esc_url( get_permalink( $doc->ID ) ); ?>"><?php echo esc_attr( $doc->post_title ); ?></a>
						<?php endif; ?>
					</th>
					<td class="filebox-changed"><?php echo $type == 'folders' ? '' : date_i18n( get_option( 'date_format' ), strtotime( $doc->post_modified ) ); ?></td>
					<td class="filebox-owner"><?php echo $type == 'folders' ? '<em>' . $bp->groups->current_group->name . '</em>' : $doc->user->display_name; ?></td>
					<td class="filebox-size"><?php echo $type == 'folders' ? sprintf( __( '%d files', 'filebox' ), $doc->count ) : get_file_size( reset( $doc->attachments )->ID ) ; ?></td>
				</tr>
				<tr class="filebox-<?php echo $type; ?> <?php echo $even ? 'even' : 'odd'; ?> filebox-desc filebox-<?php echo $type == 'folders' ? $doc->term_id : $doc->ID; ?>">
					<td colspan="4"><?php echo $type == 'folders' ? $doc->description : $doc->post_excerpt; ?></td>
				</tr>
				<tr class="filebox-<?php echo $type; ?> <?php echo $even ? 'even' : 'odd'; ?> filebox-actions filebox-<?php echo $type == 'folders' ? $doc->term_id : $doc->ID; ?>">
					<td colspan="4">
						<ul class="actions">
							<?php if( $type == 'files' ): ?>
								<?php if( $doc->post_status == 'trash' ): ?>
									<li><a class="filebox-action-reset" href="javascript://"><?php _e( 'Reset', 'filebox' ); ?></a></li>
									<li><a class="filebox-action-delete" href="javascript://"><?php _e( 'Delete permanently', 'filebox' ); ?></a></li>
								<?php else: ?>
									<li><a class="filebox-action-edit thickbox" href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=file&folder_id=<?php echo $documents[ 'meta' ][ 'id' ]; ?>&file_id=<?php echo $doc->ID; ?>" title="<?php _e( 'Edit', 'filebox' ); ?>" onclick="return false;"><?php _e( 'Edit', 'filebox' ); ?></a></li>
									<li><a class="filebox-action-upload thickbox" href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=upload&folder_id=<?php echo $documents[ 'meta' ][ 'id' ]; ?>&file_id=<?php echo $doc->ID; ?>" title="<?php esc_attr_e( 'Upload new version', 'filebox' ); ?>" onclick="return false;"><?php _e( 'Upload new version', 'filebox' ); ?></a></li>
									<li><a class="filebox-action-history thickbox" href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=history&file_id=<?php echo $doc->ID; ?>" title="<?php esc_attr_e( 'File history', 'filebox' ); ?>" onclick="return false;"><?php _e( 'Show history', 'filebox' ); ?></a></li>
									<li><a class="filebox-action-move thickbox" href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=move&folder_id=<?php echo $documents[ 'meta' ][ 'id' ]; ?>&file_id=<?php echo $doc->ID; ?>" title="<?php esc_attr_e( 'Move file', 'filebox' ); ?>" onclick="return false;"><?php _e( 'Move', 'filebox' ); ?></a></li>
									<li><a class="filebox-action-trash" href="javascript://"><?php _e( 'Trash', 'filebox' ); ?></a></li>
								<?php endif; ?>
							<?php else: ?>
								<li><a class="filebox-action-edit thickbox" href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=folder&folder_id=<?php echo $doc->term_id; ?>" title="<?php esc_attr_e( 'Edit folder', 'filebox' ); ?>" onclick="return false;"><?php _e( 'Edit', 'filebox' ); ?></a></li>
								<li><a class="filebox-action-move thickbox" href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=move&folder_id=<?php echo $doc->term_id; ?>" title="<?php esc_attr_e( 'Move folder', 'filebox' ); ?>" onclick="return false;"><?php _e( 'Move', 'filebox' ); ?></a></li>
								<li><a class="filebox-action-trash" href="javascript://"><?php _e( 'Delete', 'filebox' ); ?></a></li>
							<?php endif; ?>
						</ul>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>

	</tbody>
</table>
