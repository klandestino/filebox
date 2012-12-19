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
	$text = '';
	$link = '';

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
