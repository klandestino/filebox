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
				<th><?php _e( 'Changed by', 'filebox' ); ?></th>
				<th><?php _e( 'Change', 'filebox' ); ?></th>
				<th><?php _e( 'Description', 'filebox' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php $even = true;
			foreach( $history[ 'file_history' ] as $commit ): $even = ! $even; ?>
				<tr class="<?php echo $even ? 'even' : 'odd'; ?>">
					<td><?php echo
						date_i18n( get_option( 'date_format' ), strtotime( $commit[ 'date' ] ) ) .
						' - ' .
						date_i18n( get_option( 'time_format' ), strtotime( $commit[ 'date' ] ) );
					?></td>
					<td><?php echo $commit[ 'comment' ]; ?></td>
					<th><a href="<?php echo $commit[ 'link' ] ?>"><?php echo $commit[ 'title' ]; ?></a></th>
				</tr>
				<tr class="<?php echo $even ? 'even' : 'odd'; ?>">
					<td><?php echo $commit[ 'author' ]->display_name; ?></td>
					<td><?php echo $commit[ 'folder' ]; ?></td>
					<td><?php echo $commit[ 'description' ]; ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>
