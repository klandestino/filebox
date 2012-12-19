<?php $options = Filebox::__options(); ?>

<h4><?php _e( 'Who can upload files and make changes in the document archive?', 'filebox' ); ?></h4>

<p>
	<input type="radio" id="filebox-perm-members" name="permissions" value="members" <?php if( $options[ 'permissions' ] == 'members') echo 'checked="checked" '; ?>/>
	<label for="filebox-perm-members"><?php _e( 'All group members have full rights in the document archive.', 'filebox' ); ?></label>

	<input type="radio" id="filebox-perm-admin" name="permissions" value="admin" <?php if( $options[ 'permissions' ] == 'admin' ) echo 'checked="checked" '; ?>/>
	<label for="filebox-perm-admin"><?php _e( 'Only group administrators may make changes.', 'filebox' ); ?></label>

	<input type="radio" id="filebox-perm-person" name="permissions" value="person" <?php if( $options[ 'permissions' ] == 'person' ) echo 'checked="checked" '; ?>/>
	<label for="filebox-perm-person"><?php _e( 'This person only may make changes:', 'filebox' ); ?></label>
	<select name="filebox-perm-person-person">
		<?php
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
		?>
	</select>
</p>

<p>
<?php _e( 'Note: All group members will be able to view and download everything in the document archive.', 'filebox' ); ?>
</p>
