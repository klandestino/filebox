<?php

define( 'WP_USE_THEMES', true );

$dir = dirname( $_SERVER[ 'SCRIPT_FILENAME' ] );
require_once( substr( $dir, 0, strpos( $dir, '/wp-content' ) ) . '/wp-load.php' );
require_once( ABSPATH . '/wp-admin/includes/template.php' );
require_once( ABSPATH . '/wp-admin/includes/screen.php' );
require_once( ABSPATH . '/wp-admin/includes/media.php' );
require_once( ABSPATH . '/wp-includes/media-template.php' );

wp_enqueue_script( 'plupload-all' );
wp_enqueue_script( 'filebox' );
wp_enqueue_style( 'media' );

function filebox_upload_form() {
	global $filebox, $folder_id, $file_id;

	$folder_id = array_key_exists( 'folder_id', $_GET ) ? $_GET[ 'folder_id' ] : 0;
	$file_id = array_key_exists( 'file_id', $_GET ) ? $_GET[ 'file_id' ] : 0;

	if( $filebox->is_allowed( $folder_id ) ) {
		Filebox::get_template( 'filebox-upload-form' );
	} else {
		echo '<p>Not allowed</p>';
	}
}

function filebox_file_form() {
	global $filebox, $file, $file_id;

	$folder_id = array_key_exists( 'folder_id', $_GET ) ? $_GET[ 'folder_id' ] : 0;
	$file_id = array_key_exists( 'file_id', $_GET ) ? $_GET[ 'file_id' ] : 0;
	$file = $filebox->get_file( $file_id );

	if( $file && $filebox->is_allowed( $folder_id ) ) {
		Filebox::get_template( 'filebox-file-form' );
	} else {
		echo '<p>Not allowed</p>';
	}
}

function filebox_file_history() {
	global $filebox, $file, $file_id, $history;

	$folder_id = array_key_exists( 'folder_id', $_GET ) ? $_GET[ 'folder_id' ] : 0;
	$file_id = array_key_exists( 'file_id', $_GET ) ? $_GET[ 'file_id' ] : 0;
	$file = $filebox->get_file( $file_id );
	$history = $filebox->history_file( array( 'file_id' => $file_id ), ARRAY_A );

	if( $file && $filebox->is_allowed( $folder_id ) ) {
		Filebox::get_template( 'filebox-file-history' );
	} else {
		echo '<p>Not allowed</p>';
	}
}

function filebox_folder_form() {
	global $filebox, $folder, $folder_id, $folder_parent;

	$folder_id = array_key_exists( 'folder_id', $_GET ) ? $_GET[ 'folder_id' ] : 0;
	$folder_parent = array_key_exists( 'folder_parent', $_GET ) ? $_GET[ 'folder_parent' ] : 0;
	$folder = $filebox->get_folder( $folder_id );

	if(
		( $folder_id && $filebox->is_allowed( $folder_id ) )
		|| ( $folder_parent && $filebox->is_allowed( $folder_parent ) )
	) {
		Filebox::get_template( 'filebox-folder-form' );
	} else {
		echo '<p>Not allowed</p>';
	}
}

if( array_key_exists( 'form', $_GET ) ) {
	switch( $_GET[ 'form' ] ) {
		case 'upload':
			wp_iframe( 'filebox_upload_form' );
			break;
		case 'file':
			wp_iframe( 'filebox_file_form' );
			break;
		case 'history':
			wp_iframe( 'filebox_file_history' );
			break;
		case 'folder':
			wp_iframe( 'filebox_folder_form' );
			break;
	}
}
