<?php

/**
 * Filebox administration class
 */
class Filebox_Admin {

	static public function __setup() {
		add_action( 'init', array( Filebox_Admin, 'init' ) );
	}

	static public function init() {
		if( is_site_admin() ) {
			add_action( 'admin_init', array( Filebox_Admin, 'admin_page_save' ) );
			add_action( 'admin_menu', array( Filebox_Admin, 'admin_menu' ) );
		}
	}

	/**
	 * Adds menu item
	 * @return void
	 */
	public static function admin_menu() {
		add_submenu_page(
			'options-general.php',
			__( 'Filebox Settings', 'filebox' ),
			__( 'Filebox', 'filebox' ),
			'manage_options',
			'filebox',
			array( Filebox_Admin, 'admin_page' )
		);
	}

	/**
	 * Prints an admin page through template
	 * @return void
	 */
	public static function admin_page() {
		global $settings;
		$settings = Filebox::get_options();
		Filebox::get_template( 'filebox-admin-options' );
	}

		/**
	 * Receives the posted admin form and saved the settings
	 * @return void
	 */
	public static function admin_page_save() {
		if( array_key_exists( 'filebox-save', $_POST ) ) {
			check_admin_referer( 'filebox_admin' );
			$settings = Filebox::get_options();
			
			foreach( $settings as $key => $val ) {
				if( array_key_exists( $key, $_POST ) ) {
					$settings[ $key ] = $_POST[ $key ];
				}
			}

			if( ! array_key_exists( 'remove-docs-from-library', $_POST ) ) {
				$settings[ 'remove-docs-from-library' ] = false;
			}

			update_option( 'filebox', $settings );
			wp_redirect( add_query_arg( array( 'filebox-updated' => '1' ) ) );
		} elseif( array_key_exists( 'filebox-updated', $_GET ) ) {
			add_action( 'admin_notices', create_function( '', sprintf(
				'echo "<div class=\"updated\"><p>%s</p></div>";',
				__( 'Settings updated.', 'filebox' )
			) ) );
		}
	}

}
