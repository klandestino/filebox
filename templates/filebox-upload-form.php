		<form enctype="multipart/form-data" method="post" action="" class="media-upload-form type-form validate" id="file-form">
			<?php wp_nonce_field( 'filebox-upload' ); ?>

			<h3 class="media-title"><?php _e( 'Add files from your computer', 'filebox' ); ?></h3>

			<?php
			global $folder_id, $file_id, $is_IE, $is_opera;

			if( ! _device_can_upload() ) {
				echo '<p>';
				_e( 'The web browser on your device cannot be used to upload files. You may be able to use the <a href="http://wordpress.org/extend/mobile/">native app for your device</a> instead.', 'filebox' );
				echo '</p>';
			} else {
				$upload_size_unit = $max_upload_size = wp_max_upload_size();
				$sizes = array( 'KB', 'MB', 'GB' );

				for( $u = -1; $upload_size_unit > 1024 && $u < count( $sizes ) - 1; $u++ ) {
					$upload_size_unit /= 1024;
				}

				if( $u < 0 ) {
					$upload_size_unit = 0;
					$u = 0;
				} else {
					$upload_size_unit = ( int ) $upload_size_unit;
				}

				if( is_multisite() && ! is_upload_space_available() ) {
					do_action( 'upload_ui_over_quota' );
				} else {
					$post_params = array(
						'action' => 'filebox_upload_file',
						'security' => wp_create_nonce( 'upload_file' ),
						'folder_id' => $folder_id,
						'file_id' => $file_id
					);

					$plupload_init = array(
						'runtimes' => 'html5,silverlight,flash,html4',
						'browse_button' => 'plupload-browse-button',
						'container' => 'plupload-upload-ui',
						'drop_element' => 'drag-drop-area',
						'file_data_name' => 'file_upload',
						'multiple_queues' => true,
						'max_file_size' => $max_upload_size . 'b',
						'url' => 'ajaxurl',
						'flash_swf_url' => includes_url( 'js/plupload/plupload.flash.swf' ),
						'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
						'filters' => array( array( 'title' => __( 'Allowed Files', 'filebox' ), 'extensions' => '*' ) ),
						'multipart' => true,
						'urlstream_upload' => true,
						'multipart_params' => $post_params
					);

					// Multi-file uploading doesn't currently work in iOS Safari,
					// single-file allows the built-in camera to be used as source for images
					if( wp_is_mobile() || $file_id ) {
						$plupload_init[ 'multi_selection' ] = false;
					}

					$plupload_init = apply_filters( 'plupload_init', $plupload_init );
					?><script type="text/javascript" language="javascript">
						//<![CDATA[
						<?php
						$large_size_h = absint( get_option( 'large_size_h' ) );

						if( ! $large_size_h ) {
							$large_size_h = 1024;
						}

						$large_size_w = absint( get_option( 'large_size_w' ) );

						if( ! $large_size_w ) {
							$large_size_w = 1024;
						}
						?>
						var resize_height = <?php echo $large_size_h; ?>, resize_width = <?php echo $large_size_w; ?>;
						var _wpUploaderInit = <?php echo json_encode( $plupload_init ); ?>;
						_wpUploaderInit[ 'url' ] = ajaxurl;

						jQuery( function( $ ) {
							var uploader = new plupload.Uploader( _wpUploaderInit );

							function get_nice_size( size ) {
								if( size < 1024 ) {
									return size + ' B';
								} else if( size < 1048576 ) {
									return Math.round( size / 1024 ) + ' KB';
								} else if( size < 1073741824 ) {
									return Math.round( ( size / 1024 ) / 1024 ) + ' MB';
								} else {
									return Math.round( ( ( size / 1024 ) / 1024 ) / 1024 ) + ' GB';
								}
							}

							uploader.bind( 'Init', function( up ) {
								var uploaddiv = $( '#plupload-upload-ui'  );

								if( up.features.dragdrop ) {
									uploaddiv.addClass( 'drag-drop' );
									$( '#drag-drop-area' ).bind(
										'dragover.wp-uploader', function() {
											uploaddiv.addClass( 'drag-over' );
									} ).bind(
										'dragleave.wp-uploader, drop.wp-uploader', function() {
											uploaddiv.removeClass( 'drag-over' );
										}
									);
								} else {
									uploaddiv.removeClass( 'drag-drop' );
									$( '#drag-drop-area' ).unbind( '.wp-uploader' );
								}
							} );

							uploader.bind( 'FilesAdded', function( up, files ) {
								$.each( files, function( i, file ) {
									$( '#media-items' ).append(
										'<div class="media-item open" id="media-item-' + file.id + '">' +
											'<div class="progress">' +
												'<div class="percent"></div>' +
												'<div class="bar"></div>' +
											'</div>' +
											'<div class="filename original">' + file.name + ' (' + get_nice_size( file.size ) + ')</div>' +
										'</div>'
									);
								} );
								up.refresh();
								setTimeout( function() {
									$( '#plupload-upload-ui' ).fadeOut( 'fast' );
									uploader.start();
								}, 1000 );
							} );

							uploader.bind( 'Error', function( up, err ) {
								$( '#upload-error' ).append( '<p>' + err.message + ( err.file ? ': ' + err.file.name : '' ) + '</p>' );

								if( err.file ) {
									$( '#media-item-' + err.file.id ).remove();
								}

								up.refresh();
							});

							uploader.bind( 'UploadProgress', function( up, file ) {
								$( '#media-item-' + file.id + ' .percent' ).text( file.percent + '%' );
								$( '#media-item-' + file.id + ' .bar' ).width( Math.round( 200 * ( file.percent / 100 ) ) );
							} );

							uploader.bind( 'FileUploaded', function( up, file ) {
								$( '#media-item-' + file.id + ' .percent' ).text( '100%' );
								$( '#media-item-' + file.id + ' .bar' ).width( 200 );
							} );

							uploader.bind( 'UploadComplete', function() {
								window.location.reload();
							} );

							$( '#plupload-start-button' ).click( function() {
								uploader.start();
							} );

							uploader.init();
						} );
						//]]>
					</script>

					<div id="plupload-upload-ui">
						<div id="drag-drop-area">
							<div class="drag-drop-inside">
								<p class="drag-drop-info"><?php _e( 'Drop files here', 'filebox' ); ?></p>
								<p><?php _e( 'or', 'filebox' ); ?></p>
								<p class="drag-drop-buttons">
									<input id="plupload-browse-button" type="button" value="<?php esc_attr_e( 'Select Files', 'filebox' ); ?>" class="button" />
								</p>
							</div>
						</div>
					</div>

					<span class="max-upload-size"><?php printf(
						__( 'Maximum upload file size: %d%s.', 'filebox' ),
						esc_html( $upload_size_unit ),
						esc_html( $sizes[ $u ] )
					); ?></span>
					<?php if( ( $is_IE || $is_opera ) && $max_upload_size > 100 * 1024 * 1024 ): ?>
						<span class="big-file-warning"><?php _e('Your browser has some limitations uploading large files with the multi-file uploader. Please use the browser uploader for files over 100MB.'); ?></span>
					<?php endif; ?>

					<div id="upload-error"></div>
					<div id="media-items"></div>
				<?php }
			} ?>
		</form>
