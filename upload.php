<?php

define( 'WP_USE_THEMES', true );

$dir = dirname( $_SERVER[ 'SCRIPT_FILENAME' ] );
require_once( substr( $dir, 0, strpos( $dir, '/wp-content' ) ) . '/wp-load.php' );
require_once( ABSPATH . '/wp-admin/includes/template.php' );
require_once( ABSPATH . '/wp-admin/includes/screen.php' );
require_once( ABSPATH . '/wp-admin/includes/media.php' );
require_once( ABSPATH . '/wp-includes/media-template.php' );

wp_enqueue_script( 'plupload-all' );
wp_enqueue_style( 'media' );

function filebox_upload_form() {
	global $filebox, $folder_id;

	$folder_id = array_key_exists( 'folder_id', $_GET ) ? $_GET[ 'folder_id' ] : 0;

	if( $filebox->is_allowed( $folder_id ) ) {
		Filebox::get_template( 'filebox-upload-form' );
	} else {
		echo '<p>Not allowed</p>';
	}
}

wp_iframe( 'filebox_upload_form' );
