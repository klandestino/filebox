<?php global $file, $history; ?>
<div id="filebox-file-history" class="filebox-file-history">
	<div class="image">
		<?php echo wp_get_attachment_image( reset( $file->attachments )->ID, 'thumbnail', ! wp_attachment_is_image( reset( $file->attachments )->ID ) ); ?>
	</div>
	<div class="details">
		<h2><?php echo $file->post_title; ?></h2>
		<p><?php echo $file->post_excerpt; ?></p>
	</div>
	<table cellspacing="0" cellpadding="0" class="history">
		<thead>
			<tr>
				<th><?php _e( 'Changed', 'filebox' ); ?></th>
				<th><?php _e( 'Comment', 'filebox' ); ?></th>
				<th><?php _e( 'Author', 'filebox' ); ?></th>
				<th><?php _e( 'Title', 'filebox' ); ?></th>
				<th><?php _e( 'Description', 'filebox' ); ?></th>
				<th><?php _e( 'Folder', 'filebox' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php $even = true;
			foreach( $history[ 'file_history' ] as $commit ): $even = ! $even; ?>
				<tr class="<?php echo $even ? 'even' : 'odd'; ?>">
					<td><?php echo $commit[ 'date' ]; ?></td>
					<td><?php echo $commit[ 'comment' ]; ?></td>
					<td><?php echo get_the_author( $commit[ 'id' ] ); ?></td>
					<td><?php echo $commit[ 'title' ]; ?></td>
					<td><?php echo $commit[ 'description' ]; ?></td>
					<td><?php echo $commit[ 'folder' ]; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
