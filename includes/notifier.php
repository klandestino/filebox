<?php

/**
 * Filebox Notifier class
 */
class Filebox_Notifier {

	/**
	 */
	public static function __setup() {
		// Listen for folder browsing
		add_action( 'filebox_list_files_and_folders', array( Filebox_Notifier, 'handle_list_open' ) );

		// Listen for uploads
		add_action( 'filebox_file_upload', array( Filebox_Notifier, 'notify_file_upload' ), 10, 4 );

		// Listen for file moving
		add_action( 'filebox_move_file', array( Filebox_Notifier, 'notify_file_move' ), 10, 4 );

		// Listen for file removal
		#add_action( 'trash_post', array( Filebox_Notifier, 'handle_file_removal' ) );
		#add_action( 'wp_trash_post', array( Filebox_Notifier, 'handle_file_removal' ) );
		#add_action( 'trashed_post', array( Filebox_Notifier, 'handle_file_removal' ) );
		add_action( 'delete_post', array( Filebox_Notifier, 'handle_file_removal' ) );

		// Listen for file saving, if it's trashed or not
		add_action( 'save_post', array( Filebox_Notifier, 'handle_file_update' ) );
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
						self::add_notification_email( $member->user_id, $file_id, $folder_id, $group_id, $type );
						$sent[] = $member->user_id;
					}
				}
			}
		}
	}

	/**
	 * Adds a notification email if user settings allows it
	 * E-mail params are stored in a user-meta array if the mail-delayed setting
	 * is set for later deliviery through wp_schedule_single_event.
	 * If mail-delay is not set, e-mail will be sent immediately.
	 * @param int $user_id
	 * @param int $file_id
	 * @param int $folder_id
	 * @param int $group_id
	 * @param string $action
	 * @return void
	 */
	public static function add_notification_email( $user_id, $file_id, $folder_id, $group_id, $action ) {
		if( bp_get_user_meta( $user_id, 'notification_filebox', true ) != 'no' ) {
			global $filebox;
			add_user_meta( $user_id, 'filebox_notifier_emails', compact( 'file_id', 'folder_id', 'group_id', 'action' ) );

			if( ! wp_next_scheduled( 'filebox_notifier_scheduled_email', $user_id ) ) {
				wp_schedule_single_event( microtime( true ) + ( ( ( int ) $filebox->options[ 'mail-delay' ] ) * 60 ), 'filebox_notifier_scheduled_email', array( $user_id ) );
			}
		}
	}

	/**
	 * Deletes notifications for current user by folder id
	 * @param int $folder_id
	 * @return void
	 */
	public static function delete_notification_folder( $folder_id ) {
		bp_core_delete_notifications_by_type( get_current_user_id(), 'filebox_notifier', 'file_upload_' . $folder_id );
		bp_core_delete_notifications_by_type( get_current_user_id(), 'filebox_notifier', 'file_update_' . $folder_id );
	}

	/**
	 * Deletes notifications for file
	 * @param int $file_id
	 * @return void
	 */
	public static function delete_notification_file( $file_id ) {
		global $wpdb, $bp;
		$wpdb->query( $wpdb->prepare(
			"DELETE FROM {$bp->core->table_name_notifications} WHERE item_id = %d AND component_name = 'filebox_notifier'",
			$file_id
		) );
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
	 * Corrects file-upload notifications folder-id when moving files.
	 * @param object $file
	 * @param object $folder
	 * @param int $old_folder_id
	 * @param object $group
	 * @return boolean
	 */
	public static function notify_file_move( $file, $folder, $old_folder_id, $group ) {
		global $wpdb, $bp;

		foreach( array(
			'file_upload_' . $old_folder_id => 'file_upload_' . $folder->term_id,
			'file_update_' . $old_folder_id => 'file_update_' . $folder->term_id
		) as $from => $to ) {
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$bp->core->table_name_notifications}
					SET component_action = %s
					WHERE item_id = %d
						AND component_name = 'filebox_notifier'
						AND component_action = %s",
				$to,
				$file->ID,
				$from
			) );
		}
	}

	/**
	 */

	/**
	 * Handles folder listning and deletes notifications by folder
	 * @param object $folder
	 * @return void
	 */
	public static function handle_list_open( $folder ) {
		self::delete_notification_folder( $folder->term_id );
	}

	/**
	 * Handles post/file removal and deletes notifications by item id
	 * @param int $post_id
	 * @return void
	 */
	public static function handle_file_removal( $post_id ) {
		if( get_post_type( $post_id ) == 'document' ) {
			self::delete_notification_file( $post_id );
		}
	}

	/**
	 * Handles post/file removal and deletes notifications by item id
	 * @param int $post_id
	 * @return void
	 */
	public static function handle_file_update( $post_id ) {
		if( get_post_type( $post_id ) == 'document' ) {
			if( $rev_id = wp_is_post_revision( $post_id ) ) {
				$post_id = $rev_id;
			}

			if( get_post_status( $post_id ) != 'publish' ) {
				self::delete_notification_file( $post_id );
			}
		}
	}

}
