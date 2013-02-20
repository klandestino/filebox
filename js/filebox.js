jQuery( function( $ ) {
	function clickcontrol( elm ) {
		if( elm.data( 'clicked' ) ) {
			return true;
		}

		elm.data( 'clicked', true );
		return false;
	}

	function get_id_and_type( element ) {
		return {
			id: /filebox-([0-9]+)/.exec( $( element ).closest( '.filebox-actions' ).attr( 'class' ) ),
			type: /filebox-(folders|files)/.exec( $( element ).closest( '.filebox-actions' ).attr( 'class' ) )
		};
	}

	function trash_file() {
		if( clickcontrol( $( this ) ) ) return;

		var id_and_type = get_id_and_type( this );
		var action = '', conf = '', data = {}, id = id_and_type.id, type = id_and_type.type;

		if( id && type && typeof( _filebox_nonces ) != 'undefined' ) {
			id = id[ 1 ];
			type = type[ 1 ];

			if( type == 'folders' ) {
				data.action = 'filebox_delete_folder';
				data.security = _filebox_nonces.delete_folder;
				data.folder_id = id;
				conf = 'confirm_folder_delete';
			} else {
				data.action = 'filebox_trash_file';
				data.security = _filebox_nonces.trash_file;
				data.file_id = id;
				conf = 'confirm_file_trash';
			}

			if( confirm( filebox[ conf ] ) ) {
				var link = $( this ).addClass( 'working' );

				$.ajax( ajaxurl, {
					type: 'POST',
					dataType: 'json',
					data: data,
					success: function( response ) {
						if( response ) {
							if( typeof( response.error ) != 'undefined' ) {
								link.removeClass( 'working' ).data( 'clicked', null );
								alert( response.error );
								return;
							}
						}

						$( '.filebox-' + type + '.filebox-' + id ).fadeOut( 'fast' );
					},
					error: function( response ) {
						console.log( response );
					}
				} );
			} else {
				$( this ).data( 'clicked', false  );
			}
		}
	}

	function delete_file() {
		if( clickcontrol( $( this ) ) ) return;

		var id_and_type = get_id_and_type( this );

		if( id_and_type.id && typeof( _filebox_nonces ) != 'undefined' ) {
			id = id_and_type.id[ 1 ];

			if( confirm( filebox.confirm_file_delete ) ) {
				var link = $( this ).addClass( 'working' );

				$.ajax( ajaxurl, {
					type: 'POST',
					dataType: 'json',
					data: { action: 'filebox_delete_file', security: _filebox_nonces.delete_file, 'file_id': id },
					success: function( response ) {
						if( response ) {
							if( typeof( response.error ) != 'undefined' ) {
								link.removeClass( 'working' ).data( 'clicked', null );
								alert( response.error );
								return;
							}
						}

						$( '.filebox-' + id ).fadeOut( 'fast' );
					},
					error: function( response ) {
						console.log( response );
					}
				} );
			} else {
				$( this ).data( 'clicked', false );
			}
		}
	}

	function reset_file() {
		if( clickcontrol( $( this ) ) ) return;

		var id_and_type = get_id_and_type( this );

		if( id_and_type.id && typeof( _filebox_nonces ) != 'undefined' ) {
			id = id_and_type.id[ 1 ];
			var link = $( this ).addClass( 'working' );

			$.ajax( ajaxurl, {
				type: 'POST',
				dataType: 'json',
				data: { action: 'filebox_reset_file', security: _filebox_nonces.reset_file, 'file_id': id },
				success: function( response ) {
					if( response ) {
						if( typeof( response.error ) != 'undefined' ) {
							link.removeClass( 'working' ).data( 'clicked', null );
							alert( response.error );
							return;
						}
					}

					$( '.filebox-' + id ).fadeOut( 'fast' );
				},
				error: function( response ) {
					console.log( response );
				}
			} );
		}
	}

	function iframe_form() {
		if( clickcontrol( $( this ) ) ) return false;

		var button = $( this ).find( 'input:submit' ).addClass( 'working' );
		var form = $( this );
		form.data( 'error', [] );
		var elements = form.find( 'input.required' );
		var length = elements.length;

		function submit() {
			var error = form.data( 'error' );
			
			if( error.length > 0 ) {
				button.removeClass( 'working' ).data( 'clicked', null );
				form.data( 'clicked', null );
				alert( error.join( "\n" ) );
			} else {
				$.ajax( ajaxurl, {
					type: 'POST',
					dataType: 'json',
					data: form.serialize(),
					success: function( response ) {
						if( response ) {
							if( typeof( response.error ) != 'undefined' ) {
								button.removeClass( 'working' ).data( 'clicked', null );
								form.data( 'clicked', null );
								alert( response.error );
								return;
							}
						}

						window.location.reload();
					},
					error: function( response ) {
						console.log( response );
					}
				} );
			}
		}

		if( length == 0 ) {
			submit();
		} else {
			elements.each( function( i, elm ) {
				elm = $( elm );

				if( elm.val().replace( /\s+/, ' ' ).replace( /^\s+|\s+$/, '' ) == '' ) {
					elm.addClass( 'error' );
					var name = elm.siblings( 'label[for="' + elm.attr( 'id' ) + '"]' ).text();
					if( ! name ) name = elm.attr( 'name' );
					var error = form.data( 'error' );
					error.push( filebox.required_error_message.replace( '%s', name ) );
					form.data( 'error', error );
				} else {
					elm.removeClass( 'error' );
				}

				length--;

				if( length == 0 ) {
					submit();
				}
			} );
		}

		return false;
	}

	$( '.filebox-action-trash' ).click( trash_file );
	$( '.filebox-action-delete' ).click( delete_file );
	$( '.filebox-action-reset' ).click( reset_file );
	$( '.filebox-iframe-form' ).submit( iframe_form );

	if( typeof( tb_position ) != 'undefined' ) {
		tb_position();
	}

} );
