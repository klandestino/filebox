<?php global $filebox, $subscribe; ?>
<table class="notification-settings" id="filebox-notification-settings">
	<thead>
		<tr>
			<th class="icon"></th>
			<th class="title"><?php echo $filebox->options[ 'group-tab' ]; ?></th>
			<th class="yes"><?php _e( 'Yes', 'buddypress' ) ?></th>
			<th class="no"><?php _e( 'No', 'buddypress' )?></th>
		</tr>
	</thead>

	<tbody>
		<tr id="filebox-notification-settings">
			<td></td>
			<td><?php _e( 'Files has been uploaded or updated', 'filebox' ); ?></td>
			<td class="yes"><input type="radio" name="notifications[notification_filebox]" value="yes" <?php checked( $subscribe, 'yes', true ) ?>/></td>
			<td class="no"><input type="radio" name="notifications[notification_filebox]" value="no" <?php checked( $subscribe, 'no', true ) ?>/></td>
		</tr>
	</tbody>
</table>


