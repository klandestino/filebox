<?php
/*
Plugin Name: Filebox
Plugin URI: https://github.com/klandestino/filebox.git
Description: Plugin for nice front-end handling of Wordpress attachments.
Version: 0.1
Author: Klandestino AB
Author URI: http://www.klandestino.se/
License: GPLv3 or later
*/

if( ! empty( $network_plugin ) ) {
	$plugin_file = $network_plugin;
} elseif( ! empty( $plugin ) ) {
	$plugin_file = $plugin;
} else {
	$plugin_file = __FILE__;
}

define( 'FILEBOX_VERSION', '0.1' );
// Set a symlink friendly plugin dir constant
define( 'FILEBOX_PLUGIN_DIR', dirname( $plugin_file ) );
// Set a symlink friendly plugin url constant
define( 'FILEBOX_PLUGIN_URL', plugin_dir_url( plugin_basename( $plugin_file ) ) );
// Where to find all includes
define( 'FILEBOX_INCLUDE_DIR', dirname( __FILE__ ) . '/includes' );
// Where to find all templates
define( 'FILEBOX_TEMPLATE_DIR', dirname( __FILE__ ) . '/templates' );

// Require the main class
require_once( FILEBOX_INCLUDE_DIR . '/filebox.php' );
// Require the admin class
require_once( FILEBOX_INCLUDE_DIR . '/admin.php' );

// Setup and run classes
Filebox::__setup();
Filebox_Admin::__setup();

// Setup and run buddypress extension if buddypress is installed
add_action( 'bp_setup_components', create_function( '', "
	require_once( FILEBOX_INCLUDE_DIR . '/buddypress.php' );
	Filebox_Buddypress_Notifier::__setup();
" ) );

// Set buddypress component to be active through a bp-filter
// This is a must do if we want the notifications to work.
add_filter( 'bp_active_components', create_function( '', "
	return array_merge( \$components, array( 'filebox_notifier' => true ) );
" ) );

// Hook languages-loading function to wordpress init action
add_action( 'init', create_function( '', "
	load_plugin_textdomain( 'filebox', false, plugin_basename( FILEBOX_PLUGIN_DIR ) . '/languages/' );
" ) );
