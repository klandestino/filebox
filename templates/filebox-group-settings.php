<?php global $options; ?>

<h4><?php _e( 'Permissions', 'filebox' ); ?></h4>

<p><?php _e( 'Which members of this group are allowed to upload files and make changes?', 'filebox' ); ?></p>
<p><?php _e( 'Note: All group members will be able to view and download everything in the document archive.', 'filebox' ); ?></p>

<div class="radio">
	<label>
		<input type="radio" id="filebox-perm-members" name="permissions" value="members" <?php if( $options[ 'permissions' ] == 'members') echo 'checked="checked" '; ?>/>
		<strong><?php _e( 'All group members have full access to the document archive.', 'filebox' ); ?></strong>
	</label>

	<label>
		<input type="radio" id="filebox-perm-admin" name="permissions" value="admin" <?php if( $options[ 'permissions' ] == 'admin' ) echo 'checked="checked" '; ?>/>
		<strong><?php _e( 'Only group administrators can upload files and make changes.', 'filebox' ); ?></strong>
	</label>

	<label>
		<input type="radio" id="filebox-perm-admin" name="permissions" value="mods" <?php if( $options[ 'permissions' ] == 'mods' ) echo 'checked="checked" '; ?>/>
		<strong><?php _e( 'Group administrators and moderators can upload files and make changes.', 'filebox' ); ?></strong>
	</label>

	<!--<input type="radio" id="filebox-perm-person" name="permissions" value="person" <?php if( $options[ 'permissions' ] == 'person' ) echo 'checked="checked" '; ?>/>
	<label for="filebox-perm-person"><?php _e( 'This person only may make changes:', 'filebox' ); ?></label>
	<select name="filebox-perm-person-person">
		<?php
			/*
			$users = array();

			foreach( get_users( array(
				'fields' => 'all'
			) ) as $user ) {
				$users[ $user->user_login ] = $user->display_name;
			}

			asort( $users );

			foreach( $users as $user_login => $display_name ) {
				printf(
					'<option value="%s"%s>%s</option>',
					$user_login,
					(
						$options[ 'permissions_person' ] == $user_login
						|| ( empty( $options[ 'permissions_person' ] ) && $GLOBALS[ 'current_user' ]->user_login == $user_login )
					) ? ' selected="selected"' : '',
					$display_name
				);
			}
			*/
		?>
	</select>-->
</div>
