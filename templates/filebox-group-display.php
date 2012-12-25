<?php #require_once( ABSPATH . '/wp-admin/includes/media.php' ); ?>
<?php global $bp, $filebox; ?>
<?php $documents = $filebox->list_files_and_folders( array( 'group_id' => $bp->groups->current_group->id ), ARRAY_A ); ?>
<pre>
	<?php print_r( $documents ); ?>
</pre>

<?php #media_upload_form(); ?>
