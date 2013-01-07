jQuery( function( $ ) {

	function trash_file() {
		var id = /filebox-([0-9]+)/.exec( $( this ).closest( '.filebox-actions' ).attr( 'class' ) );
		var type = /filebox-(folders|files)/.exec( $( this ).closest( '.filebox-actions' ).attr( 'class' ) );
		var action = '', conf = '', data = {};

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

	function add_folder() {
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
	$( '.filebox-folder-form' ).submit( add_folder );

} );
