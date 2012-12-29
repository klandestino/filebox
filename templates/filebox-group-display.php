<?php
require_once( ABSPATH . '/wp-admin/includes/media.php' );

global $bp, $filebox;

$args = array( 'group_id' => $bp->groups->current_group->id );

if( array_key_exists( 'folder', $_GET ) ) {
	$args[ 'folder_slug' ] = $_GET[ 'folder' ];
} elseif( preg_match( '/\/filebox\/(?:([^\/]+)\/?)+$/', $_SERVER[ 'REQUEST_URI' ], $match ) ) {
	$args[ 'folder_slug' ] = end( $match );
}

$documents = $filebox->list_files_and_folders( $args, ARRAY_A );

?>
<pre>
	<?php print_r( $documents ); ?>
</pre>

<?php #media_upload_form(); ?>
