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
			'topics_folder_name' => __( 'Forum attachments' ),
			'trash_folder_name' => __( 'Trash' )
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
	 * Class constructor
	 */
	public function __construct() {
		$this->options = self::get_options();
		// Maybe create post type (if documents is non-existent)
		add_action( 'init', array( $this, 'maybe_add_post_type' ) );
		// Maybe create taxonomy (if directories is non-existent)
		add_action( 'init', array( $this, 'maybe_add_taxonomy' ) );
		// Add image sizes
		add_action( 'init', array( $this, 'add_image_sizes' ) );
		// Add scripts and css
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// WP Document Revisions filters and actions

		// Fix thumbnail issue if wp-document-revisions is installed
		add_filter( 'template_include', array( $this, 'get_correct_thumbnail' ) );

		// Add action for ajax-calls

		// List all files and folders
		add_action( 'wp_ajax_filebox_list', array( $this, 'list_files_and_folders' ) );

		// File actions

		// Upload file
		add_action( 'wp_ajax_filebox_upload_file', array( $this, 'upload_file' ) );
		// Move file to folder
		add_action( 'wp_ajax_filebox_move_file', array( $this, 'move_file' ) );
		// Rename file
		add_action( 'wp_ajax_filebox_rename_file', array( $this, 'rename_file' ) );
		// File history
		add_action( 'wp_ajax_filebox_history_file', array( $this, 'history_file' ) );
		// Trash file
		add_action( 'wp_ajax_filebox_trash_file', array( $this, 'trash_file' ) );

		// Folder actions

		// Add new folder
		add_action( 'wp_ajax_filebox_add_folder', array( $this, 'add_folder' ) );
		// Move folder
		add_action( 'wp_ajax_filebox_move_folder', array( $this, 'move_folder' ) );
		// Rename folder
		add_action( 'wp_ajax_filebox_rename_folder', array( $this, 'rename_folder' ) );
		// Delete folder
		add_action( 'wp_ajax_filebox_delete_folder', array( $this, 'delete_folder' ) );
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
	 * Taxonomy is used to simulate directories
	 * @uses register_taxonomy
	 * @return void
	 */
	public function maybe_add_taxonomy() {
		//if( ! taxonomy_exists( 'fileboxfolders' ) ) {
			register_taxonomy( 'fileboxfolders', array( 'document' ), array(
				'labels' => array(
					'name' => _x( 'Folders', 'taxonomy general name', 'filebox' ),
					'singular_name' => _x( 'Folder', 'taxonomy singular name', 'filebox' )
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
	}

	public function add_image_sizes() {
		add_image_size( 'filebox-thumbnail', 46, 60, true );
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
			wp_enqueue_script(
				'filebox',
				FILEBOX_PLUGIN_URL . 'js/filebox.js',
				array( 'jquery' )
			);
			wp_localize_script( 'filebox', 'filebox', array(
				'confirm_folder_delete' => __( 'You\'re about to delete this folder? You can not undo this. Do you want to continue?', 'filebox' ),
				'confirm_file_trash' => __( 'You\'re about to trash this file? You can undo this. Do you want to continue?', 'filebox' )
			) );

			// General css
			wp_enqueue_style(
				'filebox',
				FILEBOX_PLUGIN_URL . 'css/filebox.css'
			);
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
			$filename = $dir[ 'basedir' ] . substr( $_SERVER[ 'REQUEST_URI' ], 10 );

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
	 * Gets a folder name
	 * @using get_term
	 * @return string
	 */
	public function get_folder_name( $folder_id ) {
		$folder = get_term( $folder_id, 'fileboxfolders' );

		if( $folder ) {
			return $folder->term;
		}

		return 'Error';
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
				if( $folder->term != $group_name ) {
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
			$folder = get_term( $folder_id, 'fileboxfolders' );

			if( $folder ) {
				if( $folder->term != $this->options[ 'topics_folder_name' ] ) {
					wp_update_term( $folder_id, array(
						'name' => $this->options[ 'topics_folder_name' ]
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

		$folder = get_terms( 'fileboxfolders', array(
			'fields' => 'ids',
			'name' => $this->options[ 'topics_folder_name' ],
			'parent' => $parent,
			'hide_empty' => false
		) );

		if( $folder ) {
			$folder_id = $folder[ 0 ];
		} else {
			$folder = wp_insert_term(
				$this->options[ 'topics_folder_name' ],
				'fileboxfolders',
				array(
					'parent' => $parent,
					'description' => sprintf( __( '%s forum attachments folder', 'filebox' ), $this->get_group_name( $group_id ) )
				)
			);

			if( is_array( $folder ) ) {
				$folder_id = $folder[ 'term_id' ];
			}
		}

		if( $folder_id ) {
			groups_update_groupmeta( $group_id, 'filebox_topics_folder', $folder_id );
			return $folder_id;
		} else {
			return false;
		}
	}

	/**
	 * Get trash folder
	 * Creates folder if it doesn't exist.
	 * @param int $group_id
	 * @return int
	 */
	public function get_trash_folder( $group_id ) {
		$parent = $this->get_group_folder( $group_id );

		if( ! $parent ) return false;

		$folder_id = groups_get_groupmeta( $group_id, 'filebox_trash_folder' );

		if( is_numeric( $folder_id ) && $folder_id ) {
			$folder = get_term( $folder_id, 'fileboxfolders' );

			if( $folder ) {
				if( $folder->term != $this->options[ 'trash_folder_name' ] ) {
					wp_update_term( $folder_id, array(
						'name' => $this->options[ 'trash_folder_name' ]
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

		$folder = get_terms( 'fileboxfolders', array(
			'fields' => 'ids',
			'name' => $this->options[ 'trash_folder_name' ],
			'parent' => $parent,
			'hide_empty' => false
		) );

		if( $folder ) {
			$folder_id = $folder[ 0 ];
		} else {
			$folder = wp_insert_term(
				$this->options[ 'trash_folder_name' ],
				'fileboxfolders',
				array(
					'parent' => $parent,
					'description' => sprintf( __( '%s trash folder', 'filebox' ), $this->get_group_name( $group_id ) )
				)
			);

			if( is_array( $folder ) ) {
				$folder_id = $folder[ 'term_id' ];
			}
		}

		if( $folder_id ) {
			groups_update_groupmeta( $group_id, 'filebox_trash_folder', $folder_id );
			return $folder_id;
		} else {
			return false;
		}
	}

	/**
	 * Get all folders by group
	 * @param int $group_id
	 * @return array
	 */
	public function get_all_folders( $group_id ) {
		$result = array( $this->get_group_folder( $group_id ) );

		if( $result[ 0 ] ) {
			$result = array_merge( $result, get_terms( 'fileboxfolders', array(
				'fields' => 'ids',
				'child_of' => $result[ 0 ],
				'hide_empty' => false
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
			'hide_empty' => false
		) );

		foreach( $folders as $folder ) {
			$response[ $folder->term_id ] = $folder;
		}

		return $response;

	}
	/**
	 * Get all files from specified folder
	 * @param int $folder_id
	 * @return array
	 */
	public function get_files( $folder_id ) {
		$results = array();
		$files = new WP_Query( array(
			'post_type' => array( 'document' ),
			'tax_query' => array(
				array(
					'taxonomy' => 'fileboxfolders',
					'fields' => 'id',
					'terms' => $folder_id,
					'include_children' => false
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
	 * Updates folder to contain specified list of files
	 * @param int $folder_id
	 * @param array $files
	 * @return void
	 */
	public function update_folder( $folder_id, $files ) {
		$current_files = $this->get_files( $folder_id );

		// Move missmatch to trash
		foreach( $current_files as $file_id ) {
			if( ! in_array( $file_id, $files ) ) {
				$this->move_file( array(
					'file_id' => $file_id,
					'folder_id' => $this->get_trash_folder( $this->get_group_by_file( $file_id ) )
				), NULL );
			}
		}

		// Include those who's left out
		foreach( $files as $file_id ) {
			if( ! array_key_exists( $file_id, $current_files ) ) {
				$this->move_file( array(
					'file_id' => $file_id,
					'folder_id' => $folder_id
				), NULL );
			}
		}
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
	 * Get folder id by file
	 * @param int $file_id
	 * @return int
	 */
	public function get_folder_by_file( $file_id ) {
		$folder = get_object_terms( $file_id, 'fileboxfolders', array( 'fields' => 'ids' ) );

		if( is_array( $folder ) ) {
			return reset( $folder );
		} else {
			return $folder;
		}
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
			$folder_id = $parents[ 0 ]->parent;
		}

		$group_id = $wpdb->get_var( $wpdb->prepare(
			'SELECT `group_id` FROM `'. $bp->groups->table_name_groupmeta . '` WHERE `meta_key` = "filebox_group_folder" AND `meta_value` = %d LIMIT 0,1',
			$folder_id
		) );

		return $group_id;
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
			$result[] = $query->post->ID;
		}

		return $result;
	}

	/**
	 * Returns true if current user is allowed to upload
	 * or modify anything in specified folder.
	 * @param int $folder_id
	 * @param int $user_id optional
	 * @return boolean
	 */
	public function is_allowed( $folder_id, $user_id = 0 ) {
		return true;
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
		$trash_folder = $this->get_trash_folder( $group_id );

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
						'meta_value' => $attachment
					) );

					if( ! $post_query->have_posts() ) {
						$filename = get_attached_file( $attachment );
						$finfo = new finfo( FILEINFO_MIME );
						$file = $this->upload_file( array(
							'folder_id' => $topics_folder,
							'file_upload' => array(
								'name' => substr( $filename, strrpos( $filename, '/' ) + 1 ),
								'tmp_name' => $filename,
								'type' => $finfo->file( $filename )
							),
							'comment' => __( 'Imported from group forum' )
						), ARRAY_A );

						if( $file[ 'file_id' ] ) {
							update_post_meta( $file[ 'file_id' ], 'filebox_forum_imported', $attachment );
						}
					}
				}
			}
		}
	}

	// ---------------------
	// AJAX FRIENDLY METHODS
	// ---------------------

	/**
	 * Get an ajax-friendly argument array
	 * @param array $args
	 * @param array $defaults
	 * @return array
	 */
	public function get_ajax_arguments( $args, $defaults ) {
		foreach( $defaults as $arg => $default ) {
			if( ! array_key_exists( $arg, $args ) ) {
				if( array_key_exists( $arg, $_POST ) ) {
					$args[ $arg ] = $_POST[ $arg ];
				} else {
					$args[ $arg ] = $default;
				}
			}
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
	 * Get a list of all files and folders
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
		) );

		if( $args[ 'group_id' ] ) {
			$this->index_group_forum_attachments( $args[ 'group_id' ] );
			$group_folder_id = $this->get_group_folder( $args[ 'group_id' ] );

			if( ! empty( $args[ 'folder_slug' ] ) && ! $args[ 'folder_id' ] ) {
				$args[ 'folder_id' ] = get_terms( 'fileboxfolders', array(
					'fields' => 'ids',
					'slug' => $args[ 'folder_slug' ],
					'child_of' => $group_folder_id,
					'hide_empty' => false
				) );

				if( is_array( $args[ 'folder_id' ] ) ) {
					$args[ 'folder_id' ] = reset( $args[ 'folder_id' ] );
				} else {
					$args[ 'folder_id' ] = 0;
				}
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
					'id' => $args[ 'folder_id' ]
				);
			}
		}

		if( array_key_exists( 'id', $response[ 'meta' ] ) ) {
			$response[ 'meta' ][ 'parent' ] = $this->get_parent_folder( $response[ 'meta' ][ 'id' ] );
			//$response[ 'meta' ][ 'readonly' ] = $this->is_read_only( $response[ 'meta' ][ 'id' ] );
			//$response[ 'meta' ][ 'group' ] = $this->get_group_by_folder( $response[ 'meta' ][ 'id' ] );
			$response[ 'meta' ][ 'group' ] = $args[ 'group_id' ];
			$response[ 'meta' ][ 'breadcrumbs' ] = $this->get_folder_ancestors( $response[ 'meta' ][ 'id' ] );
			$response[ 'meta' ][ 'topicfolder' ] = $this->get_topics_folder( $response[ 'meta' ][ 'group' ] );
			$response[ 'meta' ][ 'trashcan' ] = $this->get_trash_folder( $response[ 'meta' ][ 'group' ] );
		}

		$response[ 'meta' ][ 'url' ] = array();
		foreach( $response[ 'files' ] as $key => $val ) {
			$response[ 'meta' ][ 'url' ][ $key ] = $this->get_file_url( $key );
		}

		asort( $response[ 'folders' ] );
		asort( $response[ 'files' ] );

		return $this->get_ajax_output( $output, $response );
	}

	// --------------------------
	// FILE AJAX FRIENDLY METHODS
	// --------------------------

	/**
	 * Upload file
	 * Requires an uploaded file in $_FILES if not defined in $args.
	 * @param array $args array( folder_id => int, comment (optional) => string, file_upload (optional) => array( name => string, tmp_name => string, type => string ) )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function upload_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_id' => 0
		) );

		if(
			term_exists( ( int ) $args[ 'folder_id' ], 'fileboxfolders' )
			&& (
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
				$file_id = wp_insert_post( array(
					'post_title' => $file[ 'name' ],
					'post_content' => '',
					'post_excerpt' => array_key_exists( 'comment', $args ) ? $args[ 'comment' ] : __( 'Uploaded new file' ),
					'post_type' => 'document',
					'post_status' => 'publish'
				) );

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
					'post_status' => 'inherit'
				), $upload[ 'file' ], $file_id );

				wp_update_post( array(
					'ID' => $file_id,
					'post_content' => $attach_id
				) );

				require_once( ABSPATH . 'wp-admin/includes/image.php');

				$attach_data = wp_generate_attachment_metadata(
					$attach_id,
					$upload[ 'file' ]
				);

				wp_update_attachment_metadata( $attach_id, $attach_data );

				$response[ 'file_id' ] = $file_id;
				$response[ 'file_name' ] = $file[ 'name' ];
			}
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Move file to specified dir
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
		) );

		if(
			get_post( $args[ 'file_id' ] )
			&& term_exists( ( int ) $args[ 'folder_id' ], 'fileboxfolders' )
		) {
			$folder = wp_set_object_terms(
				$args[ 'file_id' ],
				$args[ 'folder_id' ],
				'fileboxfolders'
			);

			if( is_array( $folder ) ) {
				$folder = get_term( $args[ 'folder_id' ], 'fileboxfolders' );
				// Add history
				wp_update_post( array(
					'ID' => $args[ 'file_id' ],
					'post_excerpt' => sprintf( __( 'Moved to %s', 'filebox' ), $folder->term )
				) );

				$response = $args;
			}
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Renames a file
	 * @param array $args array( 'file_id' => int, file_name => string )
	 * @param string $output ARRAY_A, STRING prints json, NULL is void
	 * @return array|void
	 */
	public function rename_file( $args = null, $output = STRING ) {
		$response = array(
			'file_id' => 0,
			'file_name' => ''
		);

		$args = $this->get_ajax_arguments( $args, array(
			'file_id' => 0,
			'file_name' => ''
		) );

		$file = get_post( $args[ 'file_id' ] );

		if( $file && ! empty( $args[ 'file_name' ] ) ) {
			wp_update_post( array(
				'ID' => $file->ID,
				'post_title' => $args[ 'file_name' ],
				'post_excerpt' => sprintf(
					__( 'Renamed from %s to %s', 'filebox' ),
					$file->post_title,
					$args[ 'file_name' ]
				)
			) );
			$response = $args;
		}

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
		) );

		if( $args[ 'file_id' ] ) {
			$revisions = get_posts( array(
				'post_parent' => $args[ 'file_id' ],
				'post_type' => 'revision',
				'post_status' => 'any',
				'numberposts' => -1
			) );

			foreach( $revisions as $rev ) {
				$response[ 'file_history' ][] = array(
					'id' => $rev->ID,
					'comment' => $rev->post_excerpt
				);
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
		) );

		if( $args[ 'file_id' ] ) {
			wp_update_post( array(
				'ID' => $args[ 'file_id' ],
				'post_status' => 'trash'
			) );

			$response[ 'file_id' ] = $args[ 'file_id' ];
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
			'folder_name' => ''
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_name' => '',
			'folder_parent' => 0,
		) );

		if(
			! empty( $args[ 'folder_name' ] )
			&& term_exists( ( int ) $args[ 'folder_parent' ], 'fileboxfolders' )
		) {
			$folder = wp_insert_term( $args[ 'folder_name' ], 'fileboxfolders', array(
				'parent' => $args[ 'folder_parent' ]
			) );

			if( is_array( $folder ) ) {
				$response[ 'folder_id' ] = $folder[ 'term_id' ];
				$response[ 'folder_name' ] = $args[ 'folder_name' ];
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
		) );

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
			'folder_name' => ''
		);

		$args = $this->get_ajax_arguments( $args, array(
			'folder_id' => 0,
			'folder_name' => ''
		) );

		if(
			! empty( $args[ 'folder_name' ] )
			&& $term_exists( ( int ) $args[ 'folder_id' ], 'fileboxfolders' )
		) {
			$folder = wp_update_term( $args[ 'folder_id' ], 'fileboxfolders', array(
				'name' => $args[ 'folder_name' ]
			) );

			if( is_array( $folder ) ) {
				$response[ 'folder_id' ] = $folder[ 'term_id' ];
				$response[ 'folder_name' ] = $args[ 'folder_name' ];
			}
		}

		return $this->get_ajax_output( $output, $response );
	}

	/**
	 * Deletes a folder and puts content in trash
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
		) );

		$group_folder_id = $this->get_group_folder( $this->get_group_by_folder( $args[ 'folder_id' ] ) );

		foreach( $this->get_files( $args[ 'folder_id' ] ) as $file ) {
			wp_set_object_terms(
				$file->ID,
				$group_folder_id,
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
