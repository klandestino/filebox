<?php

/**
 * Extends buddypress groups with filebox functionality
 */
class Filebox_Buddypress_Group extends BP_Group_Extension {

	public $name = 'Filebox';
	public $slug = 'filebox';
	public $visibility = 'public';
	public $enable_create_step = true;
	public $enable_nav_item = true;
	public $enable_edit_item = true;

	public $create_step_position = 18;
	public $nav_item_position = 31;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Filebox', 'filebox' );
	}

	/**
	 * Prints a create screen for group extension.
	 * Returns false if user is not at the creation step
	 * @return false|void
	 */
	public function create_screen() {
		if ( ! bp_is_group_creation_step( $this->slug ) ) {
			return false;
		}

		$this->settings_screen( true );
	}

	/**
	 * Prints a settings screen.
	 * @param boolean $create True if settings will be set during a creation step.
	 * @return void
	 */
	public function settings_screen( $create = false ) {
		global $bp;

		Filebox::__template( 'filebox-group-settings' );

		if ($create) {
			wp_nonce_field( 'groups_create_save_' . $this->slug );	  
		} else {
			?>
				<input type="submit" name="save" value="Save" />
			<?php
			wp_nonce_field( 'groups_edit_save_' . $this->slug );	
		}
	}

	public function create_screen_save() {
		global $bp;
		check_admin_referer( 'groups_create_save_' . $this->slug );
		/* Save any details submitted here */
		groups_update_groupmeta($bp->groups->new_group_id, 'wpfilebox-perm', $_POST['wpfileboxDocArchivePerm']);	   
		groups_update_groupmeta($bp->groups->new_group_id, 'wpfilebox-perm-person', $_POST['wpfileboxDocArchivePermPerson']);
	}

	public function edit_screen() {
		if ( !bp_is_group_admin_screen( $this->slug ) )
			return false;

		$this->settingsScreen(false);
	}

	public function edit_screen_save() {
		global $bp;

		if ( !isset( $_POST['save'] ) )
			return false;

		check_admin_referer( 'groups_edit_save_' . $this->slug );

		/* Insert your edit screen save code here */
		groups_update_groupmeta($bp->groups->current_group->id, 'wpfilebox-perm', $_POST['wpfileboxDocArchivePerm']);	   
		groups_update_groupmeta($bp->groups->current_group->id, 'wpfilebox-perm-person', $_POST['wpfileboxDocArchivePermPerson']);

		/* To post an error/success message to the screen, use the following */
		/*
		if ( !$success )
			bp_core_add_message( __( 'There was an error saving, please try again', 'buddypress' ), 'error' );
		else
			bp_core_add_message( __( 'Settings saved successfully', 'buddypress' ) );
		*/

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
	}

	public function display() {
		global $bp;
		?>
			<div class="wp-filebox" data-path="buddypress<?php echo $bp->groups->current_group->id; ?>:/"></div>
		<?php
	}

	/*
	public function widget_display() { ?>
		<div class="info-group">
			<h4><?php echo esc_attr( $this->name ) ?></h4>
			<p>
				You could display a small snippet of information from your group extension here. It will show on the group
				home screen.
			</p>
		</div>
		<?php
	}
	*/

}
