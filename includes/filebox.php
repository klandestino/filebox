<?php

/**
 * Plugin main class
 */
class Filebox {

	public $options = array();

	/**
	 * Sets up this plugin main class
	 * @return void
	 */
	public static function __setup() {
		global $filebox;
		$filebox = new Filebox();
	}

	/**
	 * Get options from wordpress
	 * @uses get_option
	 * return array
	 */
	public static function __options() {
		$default = array(
		);

		$options = get_option( 'filebox' );

		foreach( $default as $i => $opt ) {
			if( ! array_key_exists( $i, $options ) ) {
				$options[ $i ] = $opt;
			}
		}

		return $options;
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->options = self::__options();
		// Maybe create post type (if documents is non-existent)
		add_action( 'init', array( $this, 'maybe_add_post_type' ) );
		// Maybe create taxonomy (if directories is non-existent)
		add_action( 'init', array( $this, 'maybe_add_taxonomy' ) );
		// Add scripts and css
		add_action( 'wp_enqueue_script', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Adds post type if it doesn't already exist.
	 * Uses document as post-type so it will work with
	 * wp-document-revisions
	 * @uses register_post_type
	 * @return void
	 */
	public function maybe_add_post_type() {
		if( ! post_type_exists( 'document' ) ) {
			register_post_type( 'document', array(
				'labels' => array(
					'name' => _x( 'Documents', 'post type general name', 'filebox' ),
					'singular_name' => _x( 'Document', 'post type singular name', 'filebox' )
				),
				'public' => true,
				'has_archive' => true,
				'hierarchical' => false,
				'supports' => array(
					'title',
					'author',
					'revisions',
					'excerpt',
					'custom-fields'
				)
			) );
		}
	}

	/**
	 * Adds taxonomy if it doesn't exist.
	 * Taxonomy is used to simulate directories
	 * @uses register_taxonomy
	 * @return void
	 */
	public function maybe_add_taxonomy() {
		if( ! taxonomy_exists( 'fileboxfolders' ) ) {
			register_taxonomy( 'fileboxfolders', array( 'document' ), array(
				'labels' => array(
					'name' => _x( 'Folders', 'taxonomy general name', 'filebox' ),
					'singular_name' => _x( 'Folder', 'taxonomy singular name', 'filebox' )
				),
				'hierarchical' => true
			) );
		}
	}

	/**
	 * Enqueue scripts and css
	 * @return void
	 */
	public function enqueue_scripts() {
		// Filebox general javascript methods
		wp_enqueue_script(
			'filebox',
			FILEBOX_PLUGIN_URL . 'js/filebox.js',
			array( 'jquery' )
		);
		// jQuery-plugin for file uploads
		wp_enqueue_script(
			'jquery-upload',
			FILEBOX_PLUGIN_URL . 'js/jquery.upload-1.0.2.js',
			array( 'jquery' )
		);
		// General css
		wp_enqueue_style(
			'filebox',
			FILEBOX_PLUGIN_URL . 'css/filebox.css'
		);
	}

}
