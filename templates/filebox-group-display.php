<?php
global $bp, $filebox;

$args = array( 'group_id' => $bp->groups->current_group->id );

if( preg_match( '/\/filebox\/(?:([^\/]+)\/?)+$/', $_SERVER[ 'REQUEST_URI' ], $match ) ) {
	$args[ 'folder_slug' ] = end( $match );
}

$documents = $filebox->list_files_and_folders( $args, ARRAY_A );
$folder_base_url = bp_get_group_permalink( $bp->groups->current_group ) . 'filebox';
?>

<?php if( count( $documents[ 'meta' ][ 'breadcrumbs' ] ) ): ?>
	<ul class="filebox-breadcrumbs">
	<li><?php _e( 'Filebox', 'filebox' ); ?></li>
		<?php foreach( $documents[ 'meta' ][ 'breadcrumbs' ] as $folder ): ?>
			<li>» <a href="<?php echo esc_url( $folder_base_url ); ?>"><?php echo esc_attr( $folder->name ); ?></a></li>
			<?php $folder_base_url .= '/' . $folder->slug; ?>
		<?php endforeach; ?>
		<li>» <a href="<?php echo esc_url( $folder_base_url ); ?>"><?php echo esc_attr( $documents[ 'meta' ][ 'current' ]->name ); ?></a></li>
		<?php $folder_base_url .= '/' . $documents[ 'meta' ][ 'current' ]->slug; ?>
	</ul>
<?php endif; ?>

<ul class="filebox-buttons">
	<li>
		<a href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=upload&folder_id=<?php echo $documents[ 'meta' ][ 'id' ]; ?>" id="content-add_media" class="thickbox add_media button" title="<?php esc_attr_e( 'Upload', 'filebox' ) ?>" onclick="return false;" >
			<?php _e( 'Add files', 'filebox' ); ?>
		</a>
	</li>
	<li>
		<a href="<?php echo FILEBOX_PLUGIN_URL; ?>form.php?form=folder&folder_parent=<?php echo $documents[ 'meta' ][ 'id' ]; ?>" id="content-add_folder" class="thickbox add_media button" title="<?php esc_attr_e( 'Upload', 'filebox' ) ?>" onclick="return false;" >
			<?php _e( 'Add folder', 'filebox' ); ?>
		</a>
	</li>
</ul>

<table class="filebox-table">
	<thead>
		<tr>
			<th class="filebox-checkall">
				<label for="filebox-checkall" class="filebox-checkall"><?php _e( 'Check all', 'filebox' ); ?></label>
				<input type="checkbox" id="filebox-checkall" />
			</th>
			<th class="filebox-title"><?php _e( 'Title', 'filebox' ); ?></th>
			<th class="filebox-changed"><?php _e( 'Changed', 'filebox' ); ?></th>
			<th class="filebox-owner"><?php _e( 'Owner', 'filebox' ); ?></th>
			<th class="filebox-size"><?php _e( 'Size', 'filebox' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<!--
		<tr class="template filebox-%filebox-type filebox-description">
			<td class="filebox-icon">%filebox-icon</td>
			<th class="filebox-title">%filebox-title</th>
			<td class="filebox-changed">%filebox-changed</td>
			<td class="filebox-owner">%filebox-owner</td>
			<td class="filebox-size">%filebox-size</td>
		</tr>
		<tr class="template filebox-%filebox-type filebox-actions">
			<td colspan="4" class="filebox-actions">
				<ul class="filebox-actions">
					<li>Show</li>
					<li>Edit</li>
				</ul>
			</td>
		</tr>
		-->

		<?php foreach( array( 'folders', 'files' ) as $type ): ?>
			<?php foreach( $documents[ $type ] as $doc ): ?>
				<tr class="filebox-<?php echo $type; ?> filebox-title filebox-<?php echo $type == 'folders' ? $doc->term_id : $doc->ID; ?>">
					<td rowspan="3" class="filebox-icon">
						<?php if( $type == 'folders' ): ?>
							<img src="<?php echo wp_mime_type_icon( 'archive' ); ?>" width="46" height="60" />
						<?php else: $attachment = reset( $doc->attachments ); ?>
							<?php echo wp_get_attachment_image( $attachment->ID, 'filebox-thumbnail', ! wp_attachment_is_image( $attachment->ID ) ); ?>
						<?php endif; ?>
					</td>
					<th>
						<?php if( $type == 'folders' ): ?>
							<a href="<?php echo esc_url( $folder_base_url . '/'. $doc->slug ); ?>"><?php echo esc_attr( $doc->name ); ?></a>
						<?php else: ?>
							<a href="<?php echo esc_url( get_permalink( $doc->ID ) ); ?>"><?php echo esc_attr( $doc->post_title ); ?></a>
						<?php endif; ?>
					</th>
					<td class="filebox-changed"><?php echo $type == 'folders' ? '' : $doc->post_date; ?></td>
					<td class="filebox-owner"><?php echo $type == 'folders' ? '<em>' . $bp->groups->current_group->name . '</em>' : get_the_author( $doc->ID ); ?></td>
					<td class="filebox-size"><?php echo $type == 'folders' ? sprintf( __( '%d items', 'filebox' ), $doc->count ) : round( filesize( get_attached_file( reset( $doc->attachments )->ID ) ) / 1024, 1 ) . ' kB' ; ?></td>
				</tr>
				<tr class="filebox-<?php echo $type; ?> filebox-desc filebox-<?php echo $type == 'folders' ? $doc->term_id : $doc->ID; ?>">
					<td colspan="3"><?php echo $type == 'folders' ? $doc->description : $doc->post_excerpt; ?></td>
				</tr>
				<tr class="filebox-<?php echo $type; ?> filebox-actions filebox-<?php echo $type == 'folders' ? $doc->term_id : $doc->ID; ?>">
					<td colspan="3">
						<ul>
							<li><a class="filebox-action-edit" href="javascript://"><?php _e( 'Edit', 'filebox' ); ?></a></li>
							<?php if( $type == 'files' ): ?>
								<li><a class="filebox-action-upload" href="javascript://"><?php _e( 'Upload new version', 'filebox' ); ?></a></li>
								<li><a class="filebox-action-history" href="javascript://"><?php _e( 'Show history', 'filebox' ); ?></a></li>
							<?php endif; ?>
							<li><a class="filebox-action-move" href="javascript://"><?php _e( 'Move', 'filebox' ); ?></a></li>
							<li><a class="filebox-action-trash" href="javascript://"><?php _e( 'Trash', 'filebox' ); ?></a></li>
						</ul>
					</td>
				</tr>
			<?php endforeach; ?>
		<?php endforeach; ?>

	</tbody>
</table>
