jQuery( function( $ ) {

	function get_id_and_type( element ) {
		return {
			id: /filebox-([0-9]+)/.exec( $( element ).closest( '.filebox-actions' ).attr( 'class' ) ),
			type: /filebox-(folders|files)/.exec( $( element ).closest( '.filebox-actions' ).attr( 'class' ) )
		};
	}

	function trash_file() {
		var id_and_type = get_id_and_type( this );
		var action = '', conf = '', data = {}, id = id_and_type.id, type = id_and_type.type;

		if( id && type ) {
			id = id[ 1 ];
			type = type[ 1 ];

			if( type == 'folders' ) {
				data.action = 'folebox_delete_folder';
				data.folder_id = id;
				conf = 'confirm_folder_delete';
			} else {
				data.action = 'filebox_trash_file';
				data.file_id = id;
				conf = 'confirm_file_trash';
			}

			if( confirm( filebox[ conf ] ) ) {
				$.ajax( ajaxurl, {
					type: 'POST',
					dataType: 'json',
					data: data,
					success: function( response ) {
						$( '.filebox-' + type + '.filebox-' + id ).fadeOut( 'fast' );
					},
					error: function( response ) {
						console.log( response );
					}
				} );
			}
		}
	}

	function delete_file() {
		var id_and_type = get_id_and_type( this );

		if( id_and_type.id ) {
			id = id_and_type.id[ 1 ];

			if( confirm( filebox.confirm_file_delete ) ) {
				$.ajax( ajaxurl, {
					type: 'POST',
					dataType: 'json',
					data: { action: 'filebox_delete_file', 'file_id': id },
					success: function( response ) {
						$( '.filebox-' + id ).fadeOut( 'fast' );
					},
					error: function( response ) {
						console.log( response );
					}
				} );
			}
		}
	}

	function reset_file() {
		var id_and_type = get_id_and_type( this );

		if( id_and_type.id ) {
			id = id_and_type.id[ 1 ];

			$.ajax( ajaxurl, {
				type: 'POST',
				dataType: 'json',
				data: { action: 'filebox_reset_file', 'file_id': id },
				success: function( response ) {
					$( '.filebox-' + id ).fadeOut( 'fast' );
				},
				error: function( response ) {
					console.log( response );
				}
			} );
		}
	}

	function iframe_form() {
		$.ajax( ajaxurl, {
			type: 'POST',
			dataType: 'json',
			data: $( this ).serialize(),
			success: function( response ) {
				window.location.reload();
			},
			error: function( response ) {
				console.log( response );
			}
		} );

		return false;
	}

	$( '.filebox-action-trash' ).click( trash_file );
	$( '.filebox-action-delete' ).click( delete_file );
	$( '.filebox-action-reset' ).click( reset_file );
	$( '.filebox-iframe-form' ).submit( iframe_form );

} );
