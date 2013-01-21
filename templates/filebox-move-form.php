<?php global $folder, $file, $folder_list; ?>
<form id="move-form" action="" method="post" class="filebox-move-form filebox-iframe-form">
	<input type="hidden" name="action" value="<?php echo $file ? 'filebox_move_file' : 'filebox_move_folder'; ?>" />
	<?php wp_nonce_field( $file ? 'move_file' : 'move_folder', 'security' ); ?>

	<?php if( $file ): ?>
		<input type="hidden" name="file_id" value="<?php echo $file->ID; ?>" />
		<div class="image">
			<?php echo wp_get_attachment_image( reset( $file->attachments )->ID, 'thumbnail', ! wp_attachment_is_image( reset( $file->attachments )->ID ) ); ?>
		</div>
		<div class="details">
			<h2><?php echo $file->post_title; ?></h2>
			<p><?php echo $file->post_excerpt; ?></p>
		</div>
	<?php else: ?>
		<input type="hidden" name="folder_id" value="<?php echo $folder->term_id; ?>" />
		<div class="details">
			<h2><?php echo $folder->name; ?></h2>
			<p><?php echo $folder->description; ?></p>
		</div>
	<?php endif; ?>

	<ul class="folder-list">
		<?php $indent = 0; $last = null; ?>
		<?php foreach( $folder_list as $folder_item ): ?>
			<li class="folder folder-<?php echo $folder_item->term_id; ?>"><?php
				if( $last ) {
					if( ! $folder_item->parent ) {
						$indent = 0;
					} elseif( $last->term_id == $folder_item->parent ) {
						$indent++;
					} elseif( $last->parent != $folder_item->parent ) {
						$indent--;
					}
				}

				$item = $folder_item->name;
				$last = $folder_item;
				$ok = true;

				if( ! $file ) {
					if( $folder_item->term_id == $folder->term_id ) {
						$folder->indent = $indent;
						$ok = false;
						$item = sprintf( '<strong>%s</strong>', $item );
					} elseif( property_exists( $folder, 'indent' ) ) {
						if( $folder->indent >= $indent ) {
							$folder->indent = null;
						} elseif( $folder->indent ) {
							$ok = false;
						}
					}
				}

				if( $folder_item->count ) {
					$item .= sprintf( ' (%s)', $folder_item->count );
				}

				if( $ok ) {
					printf(
						'<input id="filebox-folder-%2$s" type="radio" name="%4$s" value="%2$s" /> %1$s <label for="filebox-folder-%2$s">%3$s</label>',
						str_repeat( '‒', $indent ),
						$folder_item->term_id,
						$item,
						$file ? 'folder_id' : 'folder_parent'
					);
				} else {
					printf( '%s <em>%s</em>', str_repeat( '‒', $indent ), $item );
				}
			?></li>
		<?php endforeach; ?>
	</ul>

	<input type="submit" name="submit" value="<?php esc_attr_e( 'Move', 'filebox' ); ?>" />
</div>
