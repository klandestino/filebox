<?php

/**
 * Filebox Buddypress component extension
 */
class Filebox_Notifier_Component extends BP_Component {

	/**
	 * Forum notifier component setup. Creates component object
	 * and inserts it in buddpress.
	 */
	public static function __setup() {
		global $bp;
		$bp->filebox_notifier = new Filebox_Notifier_Component();
	}

	/**
	 * Start the buddypress extension
	 */
	public function __construct() {
		parent::start(
			'filebox_notifier',
			__( 'Filebox Notifier', 'filebox' ),
			BP_PLUGIN_DIR
		);

		// Action run when displaying notification settings (enable or disable emails)
		add_action( 'bp_notification_settings', array( &$this, 'settings_screen' ) );
	}

	/**
	 * Setting up buddypress component properties
	 * This is an override
	 * @return void
	 */
	public function setup_globals() {
		if ( ! defined( 'FILEBOX_NOTIFIER_SLUG' ) ) {
			define( 'FILEBOX_NOTIFIER_SLUG', $this->id );
		}

		$globals = array(
			'slug' => FILEBOX_NOTIFIER_SLUG,
			'has_directory' => false,
			'notification_callback' => 'filebox_notifier_messages_format'
		);

		parent::setup_globals( $globals );
	}

	/**
	 * Displays a edit screen for notifications inside the buddypress notification settings form
	 * @return void
	 */
	public function settings_screen() {
		global $subscribe;

		if( ! $subscribe = bp_get_user_meta( bp_displayed_user_id(), 'notification_filebox', true ) ) {
			$subscribe = 'yes';
		}

		Filebox::get_template( 'filebox-notification-settings' );
	}

}

/**
 * Formats notification messages. Used as a callback by buddypress
 * @param string $action usually new_[topic|reply|quote]_[ID]
 * @param int $item_id the post id usually
 * @param int $secondary_item_id the parent post id usually
 * @param int $total_items total item count of how many notifications there are with the same $action
 * @param string $format string, array or object
 * @return array formatted messages
 */
function filebox_notifier_messages_format( $action, $item_id, $secondary_item_id, $total_items, $format = 'string' ) {
	global $filebox;

	$text = '';
	$link = '';

	$file = $filebox->get_file( $item_id );
	$folder = $filebox->get_folder( $filebox->get_folder_by_file( $item_id ) );
	$group = groups_get_group( array( 'group_id' => $filebox->get_group_by_folder( $folder->term_id ) ) );
	$link = $filebox->get_folder_url( $folder->term_id );

	if( ! $file ) {
		Filebox_Notifier::delete_notification_file( $item_id );
		return 'Error';
	}

	switch( substr( $action, 0, 11 ) ) {
		case 'file_update':
			if( $total_items > 1 ) {
				$text = sprintf( $filebox->options[ 'file-update-notify-multi' ], $total_items, $folder->name, $group->name );
			} else {
				$text = sprintf( $filebox->options[ 'file-update-notify-single' ], $file->post_title, $folder->name, $group->name );
			}
			break;

		case 'file_upload':
			if( $total_items > 1 ) {
				$text = sprintf( $filebox->options[ 'file-upload-notify-multi' ], $total_items, $folder->name, $group->name );
			} else {
				$text = sprintf( $filebox->options[ 'file-upload-notify-single' ], $file->post_title, $folder->name, $group->name );
			}
			break;
	}

	switch( $format ) {
		case 'string':
			$return = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$link,
				esc_attr( $text ),
				$text
			);
			break;

		case 'email':
			$return = sprintf(
				"%s\n%s",
				$text,
				$link
			);
			break;

		default:
			$return = array(
				'text' => $text,
				'link' => $link
			);
	}

	return $return;
}
