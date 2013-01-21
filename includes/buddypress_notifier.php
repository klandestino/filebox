<?php

/**
 * Filebox Buddypress component extension
 */
class Filebox_Buddypress_Notifier extends BP_Component {

	/**
	 * Forum notifier component setup. Creates component object
	 * and inserts it in buddpress.
	 */
	public static function __setup() {
		global $bp;
		$bp->filebox_notifier = new Filebox_Buddypress_Notifier();

		// Listen for folder browsing
		//add_action( 'filebox_list_files_and_folders', array( &$this, '

		// Listen for uploads
		add_action( 'filebox_file_upload', array( &$this, 'notify_file_upload' ), 10, 4 );
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
	 * Adds a notification to all group members
	 * @param int $file_id
	 * @param int $folder_id
	 * @param int $group_id
	 * @param string $type
	 * @return void
	 */
	public function add_notification( $file_id, $folder_id, $group_id, $type ) {
		$me = get_current_user_id();
		$members = array(
			groups_get_group_members( $group_id ),
			groups_get_group_mods( $group_id ),
			groups_get_group_admins( $group_id )
		);

		foreach( $members as $list ) {
			foreach( $list as $member ) {
				//if( $member->user_id != $me ) {
					bp_core_add_notification( $file_id, $member->user_id, 'filebox_notifier', $type . '_' . $folder_id, $group_id );
				//}
			}
		}
	}

	/**
	 * Notifies all group members with a new file upload
	 * @param object $file
	 * @param object $folder
	 * @param object $group
	 * @param boolean $updated
	 * @return boolean
	 */
	public function notify_file_upload( $file, $folder, $group, $updated ) {
		$this->add_notification(
			$file->ID,
			$folder->term_id,
			$group->group_id,
			$updated ? 'file_update' : 'file_upload'
		);
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

	switch( substr( $action, 0, 11 ) ) {
		case 'file_update':
			if( $total_items > 1 ) {
				$text = sprintf( __( '%1$d files has been updated in %2$s at %3$s' ), $total_items, $folder->name, $group->name );
			} else {
				$text = sprintf( __( '%1$s has been updated in %2$s at %3$s' ), $file->post_title, $folder->name, $group->name );
			}
			break;

		case 'file_upload':
			if( $total_items > 1 ) {
				$text = sprintf( __( '%1$d new files has been added in %2$s at %3$s' ), $total_items, $folder->name, $group->name );
			} else {
				$text = sprintf( __( 'File %1$s has been added in %2$s at %3$s' ), $file->post_title, $folder->name, $group->name );
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
