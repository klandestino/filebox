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

	public $default_options = array(
		'permissions' => 'members',
		'permissions_person' => ''
	);

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name = __( 'Filebox', 'filebox' );
	}

	/**
	 * Get group options
	 * @uses groups_get_groupmeta
	 * @param int $group_id
	 * @return array
	 */
	public function get_options( $group_id ) {
		$options = groups_get_groupmeta( $group_id, 'filebox' );

		if( ! is_array( $options ) ) {
			$options = $this->default_options;
		} else {
			foreach( $this->default_options as $i => $opt ) {
				if( ! array_key_exists( $i, $options ) ) {
					$options[ $i ] = $opt;
				}
			}
		}

		return $options;
	}

	/**
	 * Set group options
	 * @uses groups_update_groupmeta
	 * @param int $group_id
	 * @param array $options
	 * @return boolean Whatever groups_update_groupmeta returns
	 */
	public function set_options( $group_id, $options ) {
		$accepted = array();

		foreach( $this->default_options as $i => $opt ) {
			if( array_key_exists( $i, $options ) ) {
				$accepted[ $i ] = $options[ $i ];
			}
		}

		return groups_update_groupmeta( $group_id, 'filebox', $accepted );
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
		global $bp, $options;

		if( $create ) {
			$options = $this->get_options( $bp->groups->new_group_id );
		} else {
			$options = $this->get_options( $bp->groups->current_group->id );
		}

		Filebox::get_template( 'filebox-group-settings' );

		if( $create ) {
			wp_nonce_field( 'groups_create_save_' . $this->slug );
		} else {
			wp_nonce_field( 'groups_edit_save_' . $this->slug );
			echo '<input type="submit" name="save" value="Save" />';
		}
	}

	/**
	 * Saves settings from the create step
	 * @return void
	 */
	public function create_screen_save() {
		global $bp;
		check_admin_referer( 'groups_create_save_' . $this->slug );
		$this->set_options( $bp->groups->new_group_id, $_POST );
	}

	/**
	 * Prints a settings edit screen
	 * @return void
	 */
	public function edit_screen() {
		if ( ! bp_is_group_admin_screen( $this->slug ) ) {
			return false;
		}

		$this->settings_screen( false );
	}

	/**
	 * Saves settings from an edit screen and prints a redirect to the edit screen
	 * @return void
	 */
	public function edit_screen_save() {
		global $bp;

		if( ! array_key_exists( 'save', $_POST ) ) {
			return false;
		}

		check_admin_referer( 'groups_edit_save_' . $this->slug );

		if ( $this->set_options( $bp->groups->current_group->id, $_POST ) ) {
			bp_core_add_message( __( 'There was an error saving, please try again', 'filebox' ), 'error' );
		} else {
			bp_core_add_message( __( 'Settings saved successfully', 'filebox' ) );
		}

		bp_core_redirect( bp_get_group_permalink( $bp->groups->current_group ) . '/admin/' . $this->slug );
	}

	public function display() {
		Filebox::get_template( 'filebox-group-display' );
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
