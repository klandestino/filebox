<?php

/**
 * Filebox Notifier class
 */
class Filebox_Notifier {

	/**
	 */
	public static function __setup() {
		// Listen for folder browsing
		add_action( 'filebox_list_files_and_folders', array( Filebox_Notifier, 'notify_list' ) );

		// Listen for uploads
		add_action( 'filebox_file_upload', array( Filebox_Notifier, 'notify_file_upload' ), 10, 4 );
	}

	/**
	 * Adds a notification to all group members
	 * @param int $file_id
	 * @param int $folder_id
	 * @param int $group_id
	 * @param string $type
	 * @return void
	 */
	public static function add_notification( $file_id, $folder_id, $group_id, $type ) {
		$me = get_current_user_id();
		$sent = array();
		$members = array(
			groups_get_group_members( $group_id ),
			groups_get_group_mods( $group_id ),
			groups_get_group_admins( $group_id )
		);

		foreach( $members as $list ) {
			if( is_array( $list ) ) {
				if( array_key_exists( 'members', $list ) ) {
					$list = $list[ 'members' ];
				}

				foreach( $list as $member ) {
					if( $member->user_id != $me && ! in_array( $member_id, $sent ) ) {
						bp_core_add_notification( $file_id, $member->user_id, 'filebox_notifier', $type . '_' . $folder_id, $group_id );
						$sent[] = $member->user_id;
					}
				}
			}
		}
	}

	/**
	 * Deletes notifications for current user by folder id
	 * @param int $folder_id
	 * @return void
	 */
	public static function delete_notification( $folder_id ) {
		bp_core_delete_notifications_by_type( get_current_user_id(), 'filebox_notifier', 'file_upload_' . $folder_id );
		bp_core_delete_notifications_by_type( get_current_user_id(), 'filebox_notifier', 'file_update_' . $folder_id );
	}

	/**
	 * Notifies all group members with a new file upload except forum imports
	 * @param object $file
	 * @param object $folder
	 * @param object $group
	 * @param boolean $updated
	 * @return boolean
	 */
	public static function notify_file_upload( $file, $folder, $group, $updated ) {
		global $filebox;

		if( $filebox->get_topics_folder( $group->id ) != $folder->term_id ) {
			self::add_notification(
				$file->ID,
				$folder->term_id,
				$group->id,
				$updated ? 'file_update' : 'file_upload'
			);
		}
	}

	/**
	 * Handles folder listning and deletes notifications by folder
	 * @param object $folder
	 * @return void
	 */
	public static function notify_list( $folder ) {
		self::delete_notification( $folder->term_id );
	}

}
