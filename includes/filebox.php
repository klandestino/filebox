<?php

/**
 * Plugin main class
 */
class Filebox {

	public $options = array();

	/**
	 * Sets up this plugin main class
	 * @return void
	 */
	public static function __setup() {
		global $filebox;
		$filebox = new Filebox();
	}

	/**
	 * Get options from wordpress
	 * @uses get_option
	 * return array
	 */
	public static function get_options() {
		$default = array(
			'group-tab' => __( 'File archive', 'filebox' ),
			'topics-folder-name' => __( 'Imported forum attachments', 'filebox' ),
			'mail-delay' => 15,
			// On-site notifications
			// 1 filename/files; 2 folder; 3 group
			'file-update-notify-single' => __( '%1$s updated in %2$s (%3$s)', 'filebox' ),
			'file-update-notify-multi' => __( '%1$d files updated in %2$s (%3$s)', 'filebox' ),
			'file-upload-notify-single' => __( '%1$s added in %2$s (%3$s)', 'filebox' ),
			'file-upload-notify-multi' => __( '%1$d new files in %2$s (%3$s)', 'filebox' ),
			// Mail notifications
			// 1 blogname; 2 filename; 3 folder, 4 group
			'file-update-mail-subject-single' => __( '[%1$s] One updated file in %4$s', 'filebox' ),
			'file-upload-mail-subject-single' => __( '[%1$s] One new file in %4$s', 'filebox' ),
			// 1 filename; 2 folder; 3 group
			'file-update-mail-message-line' => __( '%1$s updated in %2$s (%3$s)', 'filebox' ),
			'file-upload-mail-message-line' => __( '%1$s added in %2$s (%3$s)', 'filebox' ),
			// 1 blogname; 2 files
			'multiple-mail-messages-subject' => __( '[%1$s] %2$d uploaded files', 'filebox' ),
			// 1 messages
			'mail-message-wrap' => __( '%1$s

--------------------

You are receiving this email because you\'re a member of these groups.

Login and change you settings to unsubscribe from these emails.', 'filebox' )
		);

		$options = get_option( 'filebox', array() );

		foreach( $default as $i => $opt ) {
			if( ! array_key_exists( $i, $options ) ) {
				$options[ $i ] = $opt;
			}
		}

		return $options;
	}

	/**
	 * Locates and loads a template by using Wordpress locate_template.
	 * If no template is found, it loads a template from this plugins template
	 * directory.
	 * @uses locate_template
	 * @param string $slug
	 * @param string $name
	 * @return void
	 */
	public static function get_template( $slug, $name = '' ) {
		$template_names = array(
			$slug . '-' . $name . '.php',
			$slug . '.php'
		);

		$located = locate_template( $template_names );

		if ( empty( $located ) ) {
			foreach( $template_names as $name ) {
				if ( file_exists( FILEBOX_TEMPLATE_DIR . '/' . $name ) ) {
					load_template( FILEBOX_TEMPLATE_DIR . '/' . $name, false );
					return;
				}
			}
		} else {
			load_template( $located, false );
		}
	}

	/**
	 * Sanitizes a filename to a slug
	 * @param string $title
	 * @return string
	 */
	public static function sanitize_title( $title ) {
		while( preg_match( '/(\.[^\s]+)$/', $title, $suffix ) ) {
			$title = substr( $title, 0, strrpos( $title, $suffix[ 1 ] ) );
		}

		return sanitize_title( $title );
	}

	/**
	 * Class constructor
	 */
	public function __construct() {
		$this->options = self::get_options();
		// Maybe create post type (if documents is non-existent)
		add_action( 'init', array( &$this, 'maybe_add_post_type' ) );
		// Maybe create taxonomy (if directories is non-existent)
		add_action( 'init', array( &$this, 'maybe_add_taxonomy' ) );
		// Add image sizes
		add_action( 'init', array( &$this, 'add_image_sizes' ) );
		// Add scripts and styles
		add_action( 'init', array( &$this, 'register_scripts' ) );
		// enqueue scripts and styles
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_scripts' ) );

		// Action for fetching forum attachments
		add_action( 'bbp_new_topic', array( &$this, 'handle_new_forum_topic' ), 1000, 4 );
		add_action( 'bbp_new_reply', array( &$this, 'handle_new_forum_reply' ), 1000, 5 );

		// Security action for documents so non-members can't view group files
		add_filter( 'template_include', array( &$this, 'handle_file_loading' ) );

		/**
		 * WP Document Revisions filters and actions
		 */

		// Fix thumbnail issue if wp-document-revisions is installed
		add_filter( 'template_include', array( &$this, 'get_correct_thumbnail' ) );

		/**
		 * Add action for ajax-calls
		 */

		// List all files and folders
		add_action( 'wp_ajax_filebox_list', array( &$this, 'list_files_and_folders' ) );

		/**
		 * File actions
		 */

		// Upload file
		add_action( 'wp_ajax_filebox_upload_file', array( &$this, 'upload_file' ) );
		// Move file to folder
		add_action( 'wp_ajax_filebox_move_file', array( &$this, 'move_file' ) );
		// Rename file
		add_action( 'wp_ajax_filebox_rename_file', array( &$this, 'rename_file' ) );
		// File history
		add_action( 'wp_ajax_filebox_history_file', array( &$this, 'history_file' ) );
		// Trash file
		add_action( 'wp_ajax_filebox_trash_file', array( &$this, 'trash_file' ) );
		// Reset file
		add_action( 'wp_ajax_filebox_reset_file', array( &$this, 'reset_file' ) );
		// Reset file
		add_action( 'wp_ajax_filebox_delete_file', array( &$this, 'delete_file' ) );

		/**
		 * Folder actions
		 */

		// Add new folder
		add_action( 'wp_ajax_filebox_add_folder', array( &$this, 'add_folder' ) );
		// Move folder
		add_action( 'wp_ajax_filebox_move_folder', array( &$this, 'move_folder' ) );
		// Rename folder
		add_action( 'wp_ajax_filebox_rename_folder', array( &$this, 'rename_folder' ) );
		// Delete folder
		add_action( 'wp_ajax_filebox_delete_folder', array( &$this, 'delete_folder' ) );
	}

	/**
	 * Adds post type if it doesn't already exist.
	 * Uses document as post-type so it will work with
	 * wp-document-revisions
	 * @uses register_post_type
	 * @return void
	 */
	public function maybe_add_post_type() {
		if( ! post_type_exists( 'document' ) ) {
			register_post_type( 'document', array(
				'labels' => array(
					'name' => _x( 'Documents', 'post type general name', 'filebox' ),
					'singular_name' => _x( 'Document', 'post type singular name', 'filebox' )
				),
				'public' => true,
				'has_archive' => true,
				'hierarchical' => false,
				'supports' => array(
					'title',
					'author',
					'revisions',
					'excerpt',
					'custom-fields'
				)
			) );
		}
	}

	/**
	 * Adds taxonomy if it doesn't exist.
	 * Taxonomy is used to simulate directories and commit messages
	 * @uses register_taxonomy
	 * @return void
	 */
	public function maybe_add_taxonomy() {
		//if( ! taxonomy_exists( 'fileboxfolders' ) ) {
			register_taxonomy( 'fileboxfolders', array( 'document', 'revision' ), array(
				'labels' => array(
					'name' => __( 'Folders', 'filebox' ),
					'singular_name' => __( 'Folder', 'filebox' )
				),
				'hierarchical' => true,
				'public' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
				'rewrite' => false,
				'query_var' => true,
				'show_tagcloud' => false
			) );
		//}

		// Commit messages
		//if( ! taxonomy_exists( 'fileboxcommits' ) ) {
			register_taxonomy( 'fileboxcommits', array( 'document', 'revision' ), array(
				'labels' => array(
					'name' => __( 'Revision comments', 'filebox' ),
					'singular_name' => __( 'Revision comment', 'filebox' )
				),
				'hierarchical' => false,
				'public' => true,
				'show_ui' => true,
				'show_admin_column' => true,
				'show_in_nav_menus' => false,
				'rewrite' => false,
				'query_var' => true,
				'show_tagcloud' => false
			) );
		//}
	}

	public function add_image_sizes() {
		add_image_size( 'filebox-thumbnail', 46, 60, true );
	}

	/**
	 * Register scripts and styles
	 * @return void
	 */
	public function register_scripts() {
		// Filebox general javascript methods
		wp_register_script(
			'filebox',
			FILEBOX_PLUGIN_URL . 'js/filebox.js',
			array( 'jquery' )
		);
		wp_localize_script( 'filebox', 'filebox', array(
			'confirm_folder_delete' => __( "You're about to delete this folder and you can not undo this. Do you want to continue?", 'filebox' ),
			'confirm_file_trash' => __( "You're about to trash this file and you can undo this. Do you want to continue?", 'filebox' ),
			'confirm_file_delete' => __( "You're about to delete this file permanently and you can not undo this. Do you want to continue?", 'filebox' )
		) );

		// General style
		wp_register_style(
			'filebox',
			FILEBOX_PLUGIN_URL . 'css/filebox.css'
		);
	}

	/**
	 * Enqueue scripts and css
	 * @return void
	 */
	public function enqueue_scripts() {
		if( bp_is_group() ) {
			// Media upload scripts
			add_thickbox();
			wp_enqueue_script( 'media-upload' );

			// Filebox general javascript methods
			wp_enqueue_script( 'filebox' );

			// General css
			wp_enqueue_style( 'filebox' );
		}
	}

	/**
	 * If WP Document Revisions is installed, thumbnail urls
	 * are rewritten to something uncompatible. Therefore,
	 * we'll check the request path and translate it to an
	 * image if there is any.
	 * @param string $template
	 * @return string
	 */
	public function get_correct_thumbnail( $template ) {
		global $post;

		if( ! $post && strpos( $_SERVER[ 'REQUEST_URI' ], '/documents/' ) === 0 ) {
			$dir = wp_upload_dir();
			$filename = $dir[ 'basedir'] . urldecode( substr( $_SERVER[ 'REQUEST_URI' ], 10 ) );

			if( is_file( $filename ) ) {
				status_header( 200 );
				$mime = wp_check_filetype( $filename );

				if( $mime[ 'type' ] === false && function_exists( 'mime_content_type' ) ) {
					$mime[ 'type' ] = mime_content_type( $filename );
				}

				if( $mime[ 'type' ] ) {
					$mimetype = $mime[ 'type' ];
				} else {
					$mimetype = 'image/' . substr( $filename, strrpos( $filename, '.' ) + 1 );
				}

				$last_modified = gmdate( 'D, d M Y H:i:s', filemtime( $filename ) );
				$etag = '"' . md5( $last_modified ) . '"';

				header( 'Content-Type: ' . $mimetype );
				header( 'Content-Length: ' . filesize( $filename ) );
				header( "Last-Modified: $last_modified GMT" );
				header( 'ETag: ' . $etag );
				header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + 100000000 ) . ' GMT' );

				ob_clean();
				flush();
				@set_time_limit( 0 );
				readfile( $filename );
				exit;
			}
		}

		return $template;
	}

	/**
	 * Is a loaded post is a document from a group, then apply security check
	 * @param string $template;
	 * @return string
	 */
	public function handle_file_loading( $template ) {
		global $post, $wp, $wp_query;

		if( is_object( $post ) ) {
			if( property_exists( $post, 'post_type' ) && property_exists( $post, 'ID' ) ) {
				if( $post->post_type == 'document' ) {
					$folder_id = $this->get_folder_by_file( $post->ID );

					if( $folder_id ) {
						if( ! $this->is_allowed( $folder_id ) ) {
							$post->post_type = '';
							$wp_query->posts = array();
							$wp_query->queried_object = null;
							$wp->handle_404();
							return get_404_template();
						}
					}
				}
			}
		}

		return $template;
	}

	/**
	 * Gets a group name
	 * @using groups_get_group
	 * @return string
	 */
	public function get_group_name( $group_id ) {
		$group = groups_get_group( array( 'group_id' => $group_id ) );
		if ( ! $group ) return false;
		return $group->name;
	}

	/**
	 * Get group id by file
	 * @param int $file_id
	 * @return int
	 */
	public function get_group_by_file( $file_id ) {
		return $this->get_group_by_folder( $this->get_folder_by_file( $file_id ) );
	}

	/**
	 * Get group id by folder
	 * @param int $folder_id
	 * @return int
	 */
	public function get_group_by_folder( $folder_id ) {
		global $bp, $wpdb;

		$parents = $this->get_folder_ancestors( $folder_id );

		if( count( $parents ) ) {
			$folder_id = $parents[ 0 ]->term_id;
		}

		$group_id = $wpdb->get_var( $wpdb->prepare(
			'SELECT `group_id` FROM `'. $bp->groups->table_name_groupmeta . '` WHERE `meta_key` = "filebox_group_folder" AND `meta_value` = %d LIMIT 0,1',
			$folder_id
		) );

		return $group_id;
	}

	/**
	 * Get group folder.
	 * Creates group folder if it does not exist.
	 * @param int $group_id
	 * @return int Return false if there is no group folder and we did not succeed in creating one.
	 */
	public function get_group_folder( $group_id ) {
		$group_name = $this->get_group_name( $group_id );

		if( $group_name === false ) return false;

		$folder_id = groups_get_groupmeta( $group_id, 'filebox_group_folder' );

		if( is_numeric( $folder_id ) && $folder_id ) {
			$folder = get_term( $folder_id, 'fileboxfolders' );

			if( $folder ) {
				if( $folder->name != $group_name ) {
					wp_update_term( $folder_id, array(
						'name' => $group_name
					) );
				}

				return $folder_id;
			}
		}

		/**
		 * If folder been found, it's already returned.
		 * Therefore we're at this point sure there are
		 * no folder for specified group.
		 */

		// Check if there's already a term with group name
		$folder_name = $group_name;
		$folder = get_term_by( 'name', $folder_name, 'fileboxfolders' );
		$folder_id = 0;

		if( $folder ) {
			// Is there any group using this term?
			if( $this->get_group_by_folder( $folder->term_id ) ) {

				// Then create another folder name
				while( term_exists( $folder_name, 'fileboxfolders' ) ) {
					if( preg_match( '/-([0-9]+)$/', $folder_name, $match ) ) {
						$folder_name = preg_replace( '/-[0-9]+$/', '-' . ( ( ( int ) $match[ 1 ] ) + 1 ), $folder_name );
					} else {
						$folder_name .= '-1';
					}
				}
			} else {
				$folder_id = $folder->term_id;
			}
		}

		if( ! $folder_id ) {
			$folder = wp_insert_term( $folder_name, 'fileboxfolders', array(
				'description' => sprintf( __( '%s group folder', 'filebox' ), $group_name )
			) );

			if( is_array( $folder ) ) {
				$folder_id = $folder[ 'term_id' ];
			}
		}

		if( $folder_id ) {
			groups_update_groupmeta( $group_id, 'filebox_group_folder', $folder_id );
			return $folder_id;
		} else {
			return false;
		}
	}

	/**
	 * Gets a folder id for forum attachments
	 * Creates folder if it doesn't exist.
	 * @param int $group_id
	 * @return int
	 */
	public function get_topics_folder( $group_id ) {
		$parent = $this->get_group_folder( $group_id );

		if( ! $parent ) return false;

		$folder_id = groups_get_groupmeta( $group_id, 'filebox_topics_folder' );

		if( is_numeric( $folder_id ) && $folder_id ) {
			$folder = get_term( ( int ) $folder_id, 'fileboxfolders' );

			if( $folder ) {
				if( $folder->name != $this->options[ 'topics-folder-name' ] ) {
					wp_update_term( $folder_id, 'fileboxfolders', array(
						'name' => $this->options[ 'topics-folder-name' ]
					) );
				}

				return $folder_id;
			}
		}

		/**
		 * If folder been found, it's already returned.
		 * Therefore we're at this point sure there are
		 * no folder for specified group.
		 */

		$folder = wp_insert_term(
			$this->options[ 'topics-folder-name' ],
			'fileboxfolders',
			array(
				'parent' => $parent,
				'description' => sprintf( __( '%s forum attachments folder', 'filebox' ), $this->get_group_name( $group_id ) )
			)
		);

		if( is_array( $folder ) ) {
			$folder_id = $folder[ 'term_id' ];

			// So we now what folder belongs to where
			groups_update_groupmeta( $group_id, 'filebox_topics_folder', $folder_id );
			// Fetch all forum attachments
			$this->index_group_forum_attachments( $group_id );
		}

		return $folder_id;
	}

	/**
	 * Gets a folder
	 * @using get_term
	 * @param int $folder_id
	 * @return object
	 */
	public function get_folder( $folder_id ) {
		return get_term( $folder_id, 'fileboxfolders' );
	}

	/**
	 * Gets a folder name
	 * @return string
	 */
	public function get_folder_name( $folder_id ) {
		$folder = $this->get_folder( $folder_id );

		if( $folder ) {
			return $folder->name;
		}

		return 'Error';
	}

	/**
	 * Gets a folders URL
	 * @param $folder_id
	 * @return string
	 */
	public function get_folder_url( $folder_id ) {
		$url = bp_get_group_permalink( groups_get_group( array( 'group_id' => $this->get_group_by_folder( $folder_id ) ) ) );
		$url .= 'filebox';

		$folders = array_merge(
			$this->get_folder_ancestors( $folder_id ),
			array( $this->get_folder( $folder_id ) )
		);
		array_shift( $folders );

		foreach( $folders as $folder ) {
			$url .= '/' . $folder->slug;
		}

		return $url;
	}

	/**
	 * Get all folders by group
	 * @param int $group_id
	 * @return array
	 */
	public function get_all_folders( $group_id ) {
		$result = array( $this->get_folder( $this->get_group_folder( $group_id ) ) );

		if( $result[ 0 ] ) {
			$result = array_merge( $result, get_terms( 'fileboxfolders', array(
				'child_of' => $result[ 0 ]->term_id,
				'hide_empty' => false,
				'pad_counts' => true
			) ) );
		}

		return $result;
	}

	/**
	 * Get all subfolders by folder
	 * @param int $folder_id
	 * @return array
	 */
	public function get_subfolders( $folder_id ) {
		$response = array();

		$folders = get_terms( 'fileboxfolders', array(
			'parent' => $folder_id,
			'hide_empty' => false,
			'pad_counts' => true
		) );

		foreach( $folders as $folder ) {
			$folder->childs = count( get_terms( 'fileboxfolders', array(
				'parent' => $folder->term_id,
				'hide_empty' => false
			) ) );
			$response[ $folder->term_id ] = $folder;
		}

		return $response;
	}

	/**
	 * Get folder id by file
	 * @param int $file_id
	 * @return int
	 */
	public function get_folder_by_file( $file_id ) {
		$folder = wp_get_object_terms( $file_id, 'fileboxfolders', array( 'fields' => 'ids' ) );

		if( is_array( $folder ) ) {
			return reset( $folder );
		} else {
			return $folder;
		}
	}

	/**
	 * If specified folder is group root folder
	 * @param int $folder_id
	 * @return int
	 */
	public function is_root_folder( $folder_id ) {
		$group_id = $this->get_group_by_folder( $folder_id );
		$root_id = $this->get_group_folder( $group_id );
		return( $group_id == $root_id );
	}

	/**
	 * Get folder ancestors
	 * @param int $folder_id
	 * @return array
	 */
	public function get_folder_ancestors( $folder_id ) {
		$result = array();
		$folder = get_term( $folder_id, 'fileboxfolders' );

		if( $folder ) {
			while( $folder->parent ) {
				$folder = get_term( $folder->parent, 'fileboxfolders' );

				if( $folder ) {
					$result = array_merge( array( $folder ), $result );
				} else {
					$folder = ( object ) array( 'parent' => 0 );
				}
			}
		}

		return $result;
	}

	/**
	 * Get folder parent
	 * @param int $folder_id
	 * @return int
	 */
	public function get_parent_folder( $folder_id ) {
		$folder = get_term( $folder_id, 'fileboxfolders' );

		if( $folder ) {
			return $folder->parent;
		} else {
			return false;
		}
	}

	/**
	 * Get file by id
	 * @param int $file_id
	 * @return object
	 */
	public function get_file( $file_id ) {
		$file = get_post( $file_id );

		if( $file ) {
			$file->user = get_userdata( $file->post_author );
			$file->attachments = get_children( array(
				'post_parent' => $file_id,
				'post_type' => 'attachment'
			) );
			return $file;
		}

		return null;
	}

	/**
	 * Get all files from specified folder
	 * @param int $folder_id
	 * @param boolean $include_children Get all childs
	 * @return array
	 */
	public function get_files( $folder_id, $include_children = false ) {
		$results = array();
		$files = new WP_Query( array(
			'post_type' => array( 'document' ),
			'tax_query' => array(
				array(
					'taxonomy' => 'fileboxfolders',
					'fields' => 'id',
					'terms' => $folder_id,
					'include_children' => $include_children
				)
			),
			'posts_per_page' => -1
		) );

		while( $files->have_posts() ) {
			$files->the_post();
			$files->post->user = get_userdata( $files->post->post_author );
			$files->post->attachments = get_children( array(
				'post_parent' => $files->post->ID,
				'post_type' => 'attachment'
			) );
			$results[ $files->post->ID ] = $files->post;
		}

		return $results;
	}

	/**
	 * Gets a file URL
	 * @uses wp_get_attachment_url
	 * @param int $file_id Post ID
	 * @return string Post attachment URL
	 */
	public function get_file_url( $file_id ) {
		$attachments = get_children( array( 'parent' => $file_id, 'post_type' => 'attachment' ) );

		if( is_array( $attachments ) ) {
			return wp_get_attachment_url( reset( $attachments )->ID );
		} else {
			return '';
		}
	}

	/**
	 * Get trash count
	 * @param int $group_id
	 * @return int
	 */
	public function trash_count( $group_id ) {
		$files = new WP_Query( array(
			'post_type' => array( 'document' ),
			'post_status' => 'trash',
			'tax_query' => array(
				array(
					'taxonomy' => 'fileboxfolders',
					'fields' => 'id',
					'terms' => $this->get_group_folder( $group_id ),
					'include_children' => true
				)
			),
			'posts_per_page' => -1
		) );
		return $files->found_posts;
	}

	/**
	 * Get all files from trash
	 * @param int $group_id
	 * @return array
	 */
	public function get_trash( $group_id ) {
		$results = array();
		$files = new WP_Query( array(
			'post_type' => array( 'document' ),
			'post_status' => 'trash',
			'tax_query' => array(
				array(
					'taxonomy' => 'fileboxfolders',
					'fields' => 'id',
					'terms' => $this->get_group_folder( $group_id ),
					'include_children' => true
				)
			),
			'posts_per_page' => -1
		) );

		while( $files->have_posts() ) {
			$files->the_post();
			$files->post->attachments = get_children( array(
				'post_parent' => $files->post->ID,
				'post_type' => 'attachment'
			) );
			$results[ $files->post->ID ] = $files->post;
		}

		return $results;
	}

	/**
	 * Get attachments by post id
	 * @param int $post_id
	 * @return array
	 */
	public function get_attachments( $post_id ) {
		$result = array();
		$query = new WP_Query( array(
			'post_type' => 'attachment',
			'post_status' => 'inherit',
			'post_parent' => $post_id,
		) );

		while( $query->have_posts() ) {
			$query->the_post();
			$result[] = $query->post;
		}

		return $result;
	}

	/**
	 * Returns true if current user is allowed to upload
	 * or modify anything in specified folder.
	 * @param int $folder_id
	 * @param int $user_id optional
	 * @param boolean $make_changes optional If user is about to make changes
	 * @return boolean
	 */
	public function is_allowed( $folder_id, $user_id = 0, $make_changes = false ) {
		if( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if( is_super_admin( $user_id ) ) {
			return true;
		}

		$group_id = $this->get_group_by_folder( $folder_id );

		if( $group_id ) {
			if(
				groups_is_user_admin( $user_id, $group_id )
				|| groups_is_user_mod( $user_id, $group_id )
				|| groups_is_user_member( $user_id, $group_id )
			) {
				if( $make_changes ) {
					$options = groups_get_groupmeta( $group_id, 'filebox' );

					if( is_array( $options ) ) {
						if( array_key_exists( 'permissions', $options ) ) {
							if(
								$options[ 'permissions' ] == 'admin'
								&& ! groups_is_user_admin( $user_id, $group_id )
							) {
								return false;
							} elseif(
								$options[ 'permissions' ] == 'mods'
								&& ! groups_is_user_admin( $user_id, $group_id )
								&& ! groups_is_user_mod( $user_id, $group_id )
							) {
								return false;
							}
						}
					}
				}

				return true;
			}
		}

		return false;
	}

	/**
	 * Handles the action for a new forum topic
	 * @param int $topic_id
	 * @param int $forum_id
	 * @param string $anonymous_data ???
	 * @param int $topic_author author user id
	 * @return void
	 */
	public function handle_new_forum_topic( $topic_id, $forum_id, $anonymous_data = '', $topic_author ) {
		$groups = get_post_meta( $forum_id, '_bbp_group_ids', array() );

		foreach( $groups as $group_ids ) {
			if( ! is_array( $group_ids ) ) {
				$group_ids = array( $group_ids );
			}

			foreach( $group_ids as $group_id ) {
				$this->index_group_forum_attachments( $group_id );
			}
		}
	}

	/**
	 * Handles the action for a new forum reply
	 * @param int $reply_id
	 * @param int $topic_id
	 * @param int $forum_id
	 * @param string $anonymous_data ???
	 * @param int $topic_author author user id
	 * @return void
	 */
	public function handle_new_forum_reply( $reply_id, $topic_id, $forum_id, $anonymous_data = '', $topic_author ) {
		$groups = get_post_meta( $forum_id, '_bbp_group_ids', array() );

		foreach( $groups as $group_ids ) {
			if( ! is_array( $group_ids ) ) {
				$group_ids = array( $group_ids );
			}

			foreach( $group_ids as $group_id ) {
				$this->index_group_forum_attachments( $group_id );
			}
		}
	}

	/**
	 * Finds attachments in Buddypress group forum threads
	 * and store a way to find them in a folder linked to
	 * specified group
	 * @param int $group_id
	 */
	public function index_group_forum_attachments( $group_id ) {
		global $wpdb, $bbdb;
		do_action( 'bbpress_init' );

		$group_folder = $this->get_group_folder( $group_id );
		$topics_folder = $this->get_topics_folder( $group_id );
		$forum_id = groups_get_groupmeta( $group_id, 'forum_id' );

		if( is_array( $forum_id ) ) {
			$forum_id = reset( $forum_id );
		}

		if( $forum_id ) {
			$topic_query = new WP_Query( array(
				'post_type' => bbp_get_topic_post_type(),
				'post_parent' => $forum_id,
				'posts_per_page' => -1,
				'post_status' => join( ',', array( bbp_get_public_status_id(), bbp_get_closed_status_id() ) ),
			) );

			while( $topic_query->have_posts() ) {
				$topic_query->the_post();
				$topic_id = bbp_get_topic_id( $topic_query->post->ID );

				$attachments = $this->get_attachments( $topic_id );

				$reply_query = new WP_Query( array(
					'post_type' => bbp_get_reply_post_type(),
					'post_parent' => $topic_id,
					'posts_per_page' => -1
				) );

				while( $reply_query->have_posts() ) {
					$reply_query->the_post();
					$reply_id = bbp_get_reply_id( $reply_query->post->ID );
					$attachments = array_merge( $attachments, $this->get_attachments( $reply_id ) );
				}

				foreach( $attachments as $attachment ) {
					$post_query = new WP_Query( array(
						'post_type' => 'document',
						'meta_key' => 'filebox_forum_imported',
						'meta_value' => $attachment->ID
					) );

					if( ! $post_query->have_posts() ) {
						$filename = get_attached_file( $attachment->ID );

						if( $filename && is_file( $filename ) ) {
							$finfo = new finfo( FILEINFO_MIME );
							$type = $finfo->file( $filename );

							if( strpos( $type, ';' ) ) {
								$type = substr( $type, 0, strpos( $type, ';' ) );
							}

							$file = $this->upload_file( array(
								'folder_id' => $topics_folder,
								'file_upload' => array(
									'name' => substr( $filename, strrpos( $filename, '/' ) + 1 ),
									'tmp_name' => $filename,
									'type' => $type
								),
								'comment' => __( 'Imported from group forum', 'filebox' ),
								'user_id' => $attachment->post_author
							), ARRAY_A );

							if( $file[ 'file_id' ] ) {
								update_post_meta( $file[ 'file_id' ], 'filebox_forum_imported', $attachment->ID );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Sets message-term and folder-term on latest revision
	 * @param int $file_id
	 * @param string $message
	 * @return void
	 */
	public function record_change( $file_id, $message ) {
		$revisions = get_children( array(
			'post_parent' => $file_id,
			'post_type' => 'revision',
			'post_status' => 'inherit',
			'numberposts' => 1,
			'orderby' => 'post_date',
			'order' => 'DESC'
		) );

		if( is_array( $revisions ) ) {
			$terms = wp_get_object_terms( $file_id, array( 'fileboxcommits', 'fileboxfolders' ) );

			if( is_array( $terms ) ) {
				foreach( $terms as $term ) {
					wp_set_object_terms( reset( $revisions )->ID, ( int ) $term->term_id, $term->taxonomy );
				}
			 }
		}

		wp_set_object_terms( $file_id, $message, 'fileboxcommits' );
		update_post_meta( $file_id, '_edit_last', get_current_user_id() );
	}

	// ---------------------
	// AJAX FRIENDLY METHODS
	// ---------------------

	/**
	 * Get an ajax-friendly argument array.
	 * It will also check for nonce if arguments are fetched from an post request.
	 * @param array $args
	 * @param array $defaults
	 * @param string $nonce The nonce to check if this is an ajax-request
	 * @return array
	 */
	public function get_ajax_arguments( $args, $defaults, $nonce = '' ) {
		$using_nonce = false;

		if( ! is_array( $args ) ) {
			$args = array();
		}

		foreach( $defaults as $arg => $default ) {
			if( ! array_key_exists( $arg, $args ) ) {
				if( array_key_exists( $arg, $_POST ) ) {
					$args[ $arg ] = $_POST[ $arg ];
					$using_nonce = true;
				} else {
					$args[ $arg ] = $default;
				}
			}
		}

		if( $using_nonce ) {
			check_ajax_referer( $nonce, 'security', true );
		}

		return $args;
	}

	/**
	 * Get an ajax-friendly return
	 * @param string $output ARRAY_A, STRING prints json and NULL is NULL
	 * @return array|void
	 */
	public function get_ajax_output( $output, $response = STRING ) {
		if( $output == ARRAY_A ) {
			return $response;
		} elseif( $output == STRING ) {
			echo json_encode( $response );
			exit;
		}
	}

	/**
	 * Get a list of all files and folders.
	 * $args can be filtered by filebox_list_files_and_folders_args filter.
	 * $response can be filtered by filebox_list_files_and_folders_response filter.
	 * @param int|string|array $args array( folder_id => folder id, group_id => group id, folder_slug => string ) Uses $_POST as fallback.
	 * @param boolean $show_all Show all files and folders
	 * @param $output ARRAY_A, STRING prints json and NULL is void
	 * @return array|void
	 */
	public function list_files_and_folders( $args = null, $output = STRING ) {
		$response = array(
			'meta' => array(),
			'folders' => array(),
			'files' => array()
		);

		if( is_numeric( $args ) ) {
			$args = array( 'folder_id' => ( int ) $args );
		} elseif( ! is_array( $args ) ) {
			$args = array();
		}

		$args = $this->get_ajax_arguments( $args, array(
			'folder_id' => 0,
			'group_id' => 0,
			'folder_slug' => ''
		), 'list_files_and_folders' );

		if( ! is_array( $args[ 'folder_slug' ] ) ) {
			$args[ 'folder_slug' ] = array( $args[ 'folder_slug' ] );
		}

		$args = apply_filters( 'filebox_list_files_and_folders_args', $args );

		if( $args[ 'group_id' ] ) {
			$group_folder_id = $this->get_group_folder( $args[ 'group_id' ] );
			$topic_folder_id = $this->get_topics_folder( $args[ 'group_id' ] );

			if( ! empty( $args[ 'folder_slug' ] ) && ! $args[ 'folder_id' ] ) {
				$folder_id = $group_folder_id;

				foreach( $args[ 'folder_slug' ] as $slug ) {
					$folder_id = get_terms( 'fileboxfolders', array(
						'fields' => 'ids',
						'slug' => $slug,
						'parent' => $folder_id,
						'hide_empty' => false
					) );

					if( is_array( $folder_id ) ) {
						$folder_id = reset( $folder_id );
					}
				}

				$args[ 'folder_id' ] = $folder_id;
			} elseif( $args[ 'folder_id' ] ) {
				$ancestors = $this->get_folder_ancestors( $args[ 'folder_id' ] );
				if( ! in_array( $group_folder_id, $ancestors ) ) {
					$args[ 'folder_id' ] = 0;
				}
			} else {
				$args[ 'folder_id' ] = $group_folder_id;
			}

			if( $args[ 'folder_id' ] ) {
				$response[ 'folders' ] = $this->get_subfolders( $args[ 'folder_id' ] );
				$response[ 'files' ] = $this->get_files( $args[ 'folder_id' ] );
				$response[ 'meta' ] = array(
					'id' => $args[ 'folder_id' ],
					'current' => $this->get_folder( $args[ 'folder_id' ] ),
					'breadcrumbs' => $this->get_folder_ancestors( $args[ 'folder_id' ] )
				);

				do_action( 'filebox_list_files_and_folders', $response[ 'meta' ][ 'current' ] );
			} elseif( $args[ 'folder_slug' ][ 0 ] == 'trash' ) {
				$response[ 'files' ] = $this->get_trash( $args[ 'group_id' ] );
				$response[ 'meta' ] = array(
					'id' => 0,
					'trash' => true,
					'current' => $this->get_folder( $group_folder_id )
				);
			}
		}

		asort( $response[ 'folders' ] );
		asort( $response[ 'files' ] );

		$response = apply_filters( 'filebox_list_files_and_folders_response', $response, $args );

		return $this->get_ajax_output( $output, $response );
	}

	// --------------------------
	// FILE AJAX FRIENDLY METHODS
	// --------------------------

	/**
	 * Upload file
	 * Requires an uploaded file in $_FILES if not defined in $args.
	 * $args can be filtered by filebox_upload_file_args filter.
	 * $response can be filtered by filebox_upload_file_response filter.
	 * Action filebox_upload_file will be executed just after file has been saved.
	 * @param array $args array( folder_id => int, file_id => int, comment (optional) => string, file_upload (optional) => array( name => string, tmp_name => string, type => string ) )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function upload_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_id' => 0,
			'file_id' => 0
		), 'upload_file' );

		$args = apply_filters( 'filebox_upload_file_args', $args );

		if( $args[ 'file_id' ] ) {
			$doc = $this->get_file( $args[ 'file_id' ] );
		} else {
			$doc = null;
		}

		if(
			(
				term_exists( ( int ) $args[ 'folder_id' ], 'fileboxfolders' )
				|| $doc
			) && (
				array_key_exists( 'file_upload', $_FILES )
				|| array_key_exists( 'file_upload', $args )
			)
		) {
			if( array_key_exists( 'file_upload', $args ) ) {
				$file = $args[ 'file_upload' ];
			} else {
				$file = $_FILES[ 'file_upload' ];
			}

			$upload = wp_upload_bits( $file[ 'name' ], null, file_get_contents( $file[ 'tmp_name' ] ) );

			if( $upload[ 'error' ] ) {
				$response[ 'error' ] = strip_tags( $upload[ 'error' ] );
			} else {
				if( $doc ) {
					$file_id = $doc->ID;
				} else {
					$file_id = wp_insert_post( array(
						'post_title' => $file[ 'name' ],
						'post_name' => self::sanitize_title( $file[ 'name' ] ),
						'post_content' => '',
						'post_type' => 'document',
						'post_status' => 'publish',
						'post_author' => array_key_exists( 'user_id', $args ) ? $args[ 'user_id' ] : get_current_user_id()
					) );
				}

				wp_set_object_terms(
					$file_id,
					( int ) $args[ 'folder_id' ],
					'fileboxfolders'
				);

				$attach_id = wp_insert_attachment( array(
					'guid' => $upload[ 'url' ],
					'post_mime_type' => $file[ 'type' ],
					'post_title' => $file[ 'name' ],
					'post_content' => '',
					'post_status' => 'inherit',
					'post_author' => array_key_exists( 'user_id', $args ) ? $args[ 'user_id' ] : get_current_user_id()
				), $upload[ 'file' ], $file_id );

				wp_update_post( array(
					'ID' => $file_id,
					'post_content' => $attach_id
				) );

				$this->record_change(
					$file_id,
					array_key_exists( 'comment', $args )
						? $args[ 'comment' ]
						: __( 'Uploaded new file', 'filebox' )
				);

				require_once( ABSPATH . 'wp-admin/includes/image.php');

				$attach_data = wp_generate_attachment_metadata(
					$attach_id,
					$upload[ 'file' ]
				);

				wp_update_attachment_metadata( $attach_id, $attach_data );

				$response[ 'file_id' ] = $file_id;
				$response[ 'file_name' ] = $file[ 'name' ];

				do_action(
					'filebox_file_upload',
					$this->get_file( $file_id ),
					$this->get_folder( $args[ 'folder_id' ] ),
					groups_get_group( array( 'group_id' => $this->get_group_by_folder( $args[ 'folder_id' ] ) ) ),
					$doc ? true : false
				);
			}
		}

		$response = apply_filters( 'filebox_upload_file_response', $response, $args );

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Move file to specified dir.
	 * $args can be filtered by filebox_move_file_args filter.
	 * $response can be filtered by filebox_upload_file_response filter.
	 * Action filebox_move_file is executed when file has been moved.
	 * @param array $args array( file_id => int, folder_id => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function move_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0,
			'folder_id' => 0
		);

		$args = $this->get_ajax_arguments( $args, array(
			'file_id' => 0,
			'folder_id' => 0
		), 'move_file' );

		$args = apply_filters( 'filebox_move_file_args', $args );

		if(
			get_post( $args[ 'file_id' ] )
			&& term_exists( ( int ) $args[ 'folder_id' ], 'fileboxfolders' )
		) {
			wp_update_post( array( 'ID' => $args[ 'file_id' ] ) );

			$this->record_change(
				$args[ 'file_id' ],
				__( 'Moved to folder', 'filebox' )
			);

			wp_set_object_terms(
				$args[ 'file_id' ],
				( int ) $args[ 'folder_id' ],
				'fileboxfolders'
			);

			do_action(
				'filebox_move_file',
				$this->get_file( $args[ 'file_id' ] ),
				$this->get_folder( $args[ 'folder_id' ] ),
				groups_get_group( array( 'group_id' => $this->get_group_folder( $args[ 'folder_id' ] ) ) )
			);

			$response = $args;
		}

		$response = apply_filters( 'filebox_move_file_response', $response, $args );

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Renames a file.
	 * $args can be filtered by filebox_rename_file_args filter.
	 * $response can be filtered by filebox_rename_file_response filter.
	 * Action filebox_rename_file is executed when file has been renamed.
	 * @param array $args array( 'file_id' => int, file_name => string )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function rename_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0,
			'file_name' => '',
			'file_description' => ''
		);

		$args = $this->get_ajax_arguments( $args, array(
			'file_id' => 0,
			'file_name' => '',
			'file_description' => ''
		), 'rename_file' );

		$args = apply_filters( 'filebox_rename_file_args', $args );

		$file = get_post( $args[ 'file_id' ] );

		if( $file && ! empty( $args[ 'file_name' ] ) ) {
			wp_update_post( array(
				'ID' => $file->ID,
				'post_title' => $args[ 'file_name' ],
				'post_excerpt' => $args[ 'file_description' ]
			) );

			$this->record_change( $file->ID, __( 'Renamed file', 'filebox' ) );

			do_action(
				'filebox_rename_file',
				$this->get_file( $file->ID ),
				$this->get_folder_by_file( $file->ID ),
				groups_get_group( array( 'group_id' => $this->get_group_folder( $args[ 'folder_id' ] ) ) )
			);

			$response = $args;
		}

		$response = apply_filters( 'filebox_rename_file_response', $response, $args );

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Get revision history for file
	 * @param array $args array( file_id => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function history_file( $args = null, $output = STRING ) {
		$response = array(
			'file_history' => array()
		);

		$args = $this->get_ajax_arguments( $args, array(
			'file_id' => 0
		), 'history_file' );

		if( $args[ 'file_id' ] ) {
			$file = $this->get_file( $args[ 'file_id' ] );
			$folder = wp_get_post_terms( $args[ 'file_id' ], 'fileboxfolders' );
			$workflow = wp_get_post_terms( $args[ 'file_id' ], 'fileboxcommits' );

			if( $file && $folder && $workflow ) {
				$response[ 'file_history' ][] = array(
					'id' => $file->ID,
					'date' => $file->post_modified,
					'title' => $file->post_title,
					'description' => $file->post_excerpt,
					'author' => get_userdata( get_post_meta( $file->ID, '_edit_last', true ) ),
					'comment' => reset( $workflow )->name,
					'folder' => reset( $folder )->name,
					'link' => get_permalink( $file->ID )
				);
			}

			$revisions = get_children( array(
				'post_parent' => $args[ 'file_id' ],
				'post_type' => 'revision',
				'post_status' => 'inherit',
				'numberposts' => -1,
				'orderby' => 'post_date',
				'order' => 'DESC'
			) );
			$version = count( $revisions );

			if( count( $revisions ) && count( $response[ 'file_history' ] ) ) {
				if( ! $response[ 'file_history' ][ 0 ][ 'author' ] ) {
					$response[ 'file_history' ][ 0 ][ 'author' ] = get_userdata( reset( $revisions )->post_author );
				}
			}

			foreach( $revisions as $rev ) {
				$folder = wp_get_post_terms( $rev->ID, 'fileboxfolders' );
				$workflow = wp_get_post_terms( $rev->ID, 'fileboxcommits' );

				if( $workflow && $folder ) {
					$response[ 'file_history' ][] = array(
						'id' => $rev->ID,
						'date' => $rev->post_modified,
						'title' => $rev->post_title,
						'description' => $rev->post_excerpt,
						'author' => get_userdata( $rev->post_author ),
						'comment' => is_array( $workflow ) ? reset( $workflow )->name : '',
						'folder' => is_array( $folder ) ? reset( $folder )->name : '',
						'version' => $version,
						'link' => get_permalink( $file->ID ) . '?revision=' . $version
					);
				}

				$version--;
			}
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Trash a file
	 * @param array $args array( file_id => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function trash_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0,
		);

		$args = $this->get_ajax_arguments( $args, array(
			'file_id' => 0
		), 'trash_file' );

		if( $args[ 'file_id' ] ) {
			wp_update_post( array(
				'ID' => $args[ 'file_id' ],
				'post_status' => 'trash'
			) );

			$response[ 'file_id' ] = $args[ 'file_id' ];
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Reset a file
	 * @param array $args array( file_id => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function reset_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0,
		);

		$args = $this->get_ajax_arguments( $args, array(
			'file_id' => 0
		), 'reset_file' );

		if( $args[ 'file_id' ] ) {
			wp_update_post( array(
				'ID' => $args[ 'file_id' ],
				'post_status' => 'publish'
			) );

			$response[ 'file_id' ] = $args[ 'file_id' ];
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Deletes a file
	 * @param array $args array( file_id => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function delete_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0,
		);

		$args = $this->get_ajax_arguments( $args, array(
			'file_id' => 0
		), 'delete_file' );

		if( $args[ 'file_id' ] ) {
			$file = $this->get_file( $args[ 'file_id' ] );

			if( $file ) {
				foreach( $file->attachments as $attach ) {
					wp_delete_post( $attach->ID, true );
				}

				wp_delete_post( $file->ID, true );
				$response[ 'file_id' ] = $file->ID;
			}
		}

		return $this->get_ajax_output( $output, $response );
	}


	// ----------------------------
	// FOLDER AJAX FRIENDLY METHODS
	// ----------------------------

	/**
	 * Add a folder
	 * @param array $args array( folder_name => string, folder_parent => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void array( folder_id => int, folder_name => string )
	 */
	public function add_folder( $args = null, $output = STRING ) {
		$response = array(
			'folder_id' => 0,
			'folder_name' => '',
			'folder_description' => ''
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_name' => '',
			'folder_parent' => 0,
			'folder_description' => ''
		), 'add_folder' );

		if(
			! empty( $args[ 'folder_name' ] )
			&& trim( strtolower( $args[ 'folder_name' ] ) ) != 'trash'
			&& term_exists( ( int ) $args[ 'folder_parent' ], 'fileboxfolders' )
		) {
			$folder = wp_insert_term( $args[ 'folder_name' ], 'fileboxfolders', array(
				'parent' => $args[ 'folder_parent' ],
				'description' => $args[ 'folder_description' ]
			) );

			if( is_array( $folder ) ) {
				$response[ 'folder_id' ] = $folder[ 'term_id' ];
				$response[ 'folder_name' ] = $args[ 'folder_name' ];
				$response[ 'folder_description' ] = $args[ 'folder_description' ];
			}
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Move a folder
	 * @param array $args array( folder_id => int, folder_parent => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function move_folder( $args = null, $output = STRING ) {
		$response = array(
			'folder_id' => 0,
			'folder_parent' => 0
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_id' => 0,
			'folder_parent' => 0
		), 'move_folder' );

		if(
			term_exists( ( int ) $args[ 'folder_id' ], 'fileboxfolders' )
			&& term_exists( ( int ) $args[ 'folder_parent' ], 'fileboxfolders' )
		) {
			$folder = wp_update_term( $args[ 'folder_id' ], 'fileboxfolders', array(
				'parent' => $args[ 'folder_parent' ]
			) );

			if( is_array( $folder ) ) {
				$response[ 'folder_id' ] = $folder[ 'term_id' ];
				$response[ 'folder_parent' ] = $args[ 'folder_parent' ];
			}
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Rename a folder
	 * @param array $args array( folder_id => int, folder_name => string )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function rename_folder( $args = null, $output = STRING ) {
		$response = array(
			'folder_id' => 0,
			'folder_name' => '',
			'folder_description' => ''
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_id' => 0,
			'folder_name' => '',
			'folder_description' => ''
		), 'rename_folder' );

		if(
			! empty( $args[ 'folder_name' ] )
			&& trim( strtolower( $args[ 'folder_name' ] ) ) != 'trash'
			&& term_exists( ( int ) $args[ 'folder_id' ], 'fileboxfolders' )
		) {
			$folder = wp_update_term( $args[ 'folder_id' ], 'fileboxfolders', array(
				'name' => $args[ 'folder_name' ],
				'description' => $args[ 'folder_description' ]
			) );

			if( is_array( $folder ) ) {
				$response[ 'folder_id' ] = $folder[ 'term_id' ];
				$response[ 'folder_name' ] = $args[ 'folder_name' ];
				$response[ 'folder_description' ] = $args[ 'folder_description' ];
			}
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Deletes a folder and puts its content in trash
	 * @param array $args array( folder_id => int )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function delete_folder( $args = null, $output = STRING ) {
		$response = array(
			'folder_id' => 0
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_id' => 0
		), 'delete_folder' );

		$group_folder_id = $this->get_group_folder( $this->get_group_by_folder( $args[ 'folder_id' ] ) );

		foreach( $this->get_files( $args[ 'folder_id' ], true ) as $file ) {
			wp_set_object_terms(
				$file->ID,
				( int ) $group_folder_id,
				'fileboxfolders'
			);

			wp_update_post( array(
				'ID' => $file->ID,
				'post_status' => 'trash'
			) );
		}

		wp_delete_term( $args[ 'folder_id' ], 'fileboxfolders' );

		$response[ 'folder_id' ] = $args[ 'folder_id' ];

		return $this->get_ajax_output( $output, $response );
	}

}
