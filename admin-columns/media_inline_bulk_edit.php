<?php
/**
 * Media Inline Bulk Edit class.
 *
 * Handles inline and bulk editing for media.
 *
 * @version 3.2
 */
class media_inline_bulk_edit {
	/**
	 * Singleton instance.
	 *
	 * @var media_inline_bulk_edit
	 */
	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return media_inline_bulk_edit
	 */
	public static function instance() {
		if (!isset(self::$_instance)) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'register_taxonomy_args', function( $args, $name, $object_type ) {
			if ( in_array( 'attachment', $object_type ) ) {
				if ( empty( $args['update_count_callback'] ) ) {
					$args['update_count_callback'] = '_update_generic_term_count';
				}
			}
			return $args;
		}, 10, 3 );

		add_action( 'admin_init', function() {
			add_action( 'wp_ajax_inline-save', array( $this, 'wp_ajax_inline_media_save' ), 1 );
			add_action( 'wp_ajax_nopriv_inline-save', array( $this, 'wp_ajax_inline_media_save' ), 1 );

			add_filter( 'media_row_actions', array( $this, 'media_row_actions' ), 10, 3 );

			add_filter( 'manage_media_columns', array( $this, 'columns' ) );
			add_action( 'manage_media_custom_column', array( $this, 'custom_column' ), 10, 2 );
			add_filter( 'manage_upload_sortable_columns', array( $this, 'sortable_columns' ) );
			add_action( 'pre_get_posts', array( $this, 'sortable' ) );

			add_action( 'load-upload.php', array( $this, 'load_upload' ) );
			add_action( 'load-edit-tags.php', array( $this, 'load_edit_tags' ) );
//			add_filter( 'wp_insert_attachment_data', array( $this, 'wp_insert_post_data' ), 10, 3 );
		}, 100 );

		add_action( 'edit_attachment', function( $post_id ) {
			if ( $post = get_post( $post_id ) ) {
				do_action( 'save_post', $post_id, $post, true );
			}
		} );
		add_action( 'add_attachment', function( $post_id ) {
			if ( $post = get_post( $post_id ) ) {
				do_action( 'save_post', $post_id, $post, false );
			}
		} );
	}
	/**
	 * Load edit tags functionality.
	 */
	public function load_edit_tags() {
		$taxonomy = $_REQUEST['taxonomy'];
		if ( empty( $taxonomy ) ) {
			return;
		}
		$tax = get_taxonomy( $taxonomy );
		if ( ! in_array( 'attachment', $tax->object_type ) ) {
			return;
		}
		add_filter( $taxonomy . '_row_actions', function( $actions, $tag ) {
			$actions['view'] = sprintf(
				'<a href="%s" aria-label="%s">%s</a>',
				add_query_arg( array(
					'post_type' => 'attachment',
					$tag->taxonomy => $tag->slug,
				), 'upload.php' ),
				esc_attr( sprintf( __( 'View &#8220;%s&#8221; archive' ), $tag->name ) ),
				__( 'View' )
			);
			return $actions;
		}, 10, 2 );
	}
/**
	 * Load upload functionality.
	 */
	public function load_upload() {
		if ( empty( $_GET['mode'] ) ) {
			$_GET['mode'] = 'list';
		}
		if ( $_GET['mode'] == 'grid' ) {
			return;
		}
		add_filter( 'bulk_actions-' . get_current_screen()->id, array( $this, 'add_bulk_action' ) );
		add_filter( 'handle_bulk_actions-' . get_current_screen()->id, array( $this, 'handle_bulk_action' ), 10, 3 );
		add_action( 'admin_head', function() {
			?>
<style>
	.inline-edit-status {
		display:none!important
	}
	.fixed .column-meta, .fixed .column-filesize {
		width:10%;
	}
</style>
			<?php
		} );
		add_action( 'admin_enqueue_scripts', function() {
			wp_enqueue_script( 'inline-edit-post' );
			wp_enqueue_script( 'tags-suggest' );
		} );
		add_action( 'admin_footer', function() {
			global $wp_list_table;
			if ( $wp_list_table->has_items() ) {
				$this->inline_edit();
			}
		} );

	} //load




	/**
	 * Add custom columns to the media list table.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public function columns( $columns ) { // Create the column
		$columns['meta'] = 'Meta';
		$columns['filesize'] = __( 'Size' );
//		$columns['status'] = 'status';
		return $columns;
	}
	/**
	 * Display custom column content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function custom_column( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'meta':
				if ( wp_attachment_is_image( $post_id ) ) {
					$meta = wp_get_attachment_metadata( $post_id );
					echo $meta['width'] . ' x ' . $meta['height'];
					if ( $camera = $meta['image_meta']['camera'] ) {
						echo '<br />' . $camera;
					}
					if ( $timestamp = $meta['image_meta']['created_timestamp'] ) {
						echo '<br />' . wp_date( 'Y-m-d H:i:s', $timestamp );
					}
				}
				break;
			case 'filesize':
				if ( ! is_numeric( $filesize = get_post_meta( $post_id, 'filesize', true ) ) ) {
					$file = get_attached_file( $post_id );
					$filesize = intval( @filesize( $file ) );
					update_post_meta( $post_id, 'filesize', $filesize );
				}
				$filesize = size_format( $filesize, $filesize < 1024 * 1024 ? 0 : 2 );
				echo $filesize;
				break;
		}
	}
	/**
	 * Make columns sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable_columns( $columns ) {
		$columns['filesize'] = array( 'filesize', 1 );
		return $columns;
	}
	/**
	 * Handle sorting for custom columns.
	 *
	 * @param WP_Query $query The query object.
	 */
	public function sortable( $query ) {
		global $pagenow;
		if ( is_admin() && 'upload.php' == $pagenow && $query->is_main_query() && 'filesize' == $_REQUEST['orderby'] ) {
			$query->set( 'meta_key', 'filesize' );
			$query->set( 'meta_query', array(
				'relation' => 'OR',
				array(
					'key'     => 'filesize',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => 'filesize',
					'compare' => 'NOT EXISTS'
				)
			) );
			$query->set( 'orderby', 'meta_value_num' );
			$query->set( 'order', 'desc' == $_REQUEST['order'] ? 'DESC' : 'ASC' );
		}
	}
  
	/**
	 * Modify media row actions.
	 *
	 * @param array   $actions Actions array.
	 * @param WP_Post $post    Post object.
	 * @param bool    $detached Whether the post is detached.
	 * @return array Modified actions.
	 */
	public function media_row_actions( $actions, $post, $detached ) {
		unset( $actions['view'] );
		$can_edit_post = current_user_can( 'edit_post', $post->ID );
		if ( ! $can_edit_post || 'trash' === $post->post_status ) {
			return $actions;
		}

		$qebutton = sprintf(
			'<button type="button" class="button-link editinline" aria-label="%s" aria-expanded="false">%s</button>',
			esc_attr( sprintf( __( 'Quick edit &#8220;%s&#8221; inline' ), get_the_title( $post ) ) ),
			__( 'Quick&nbsp;Edit' )
		);
		ob_start();
		get_inline_data( $post );
		$qebutton .= ob_get_clean();

		$keys = array_keys( $actions );
		$vals = array_values( $actions );
		array_splice( $keys, 1, 0, array( 'inline hide-if-no-js' ) );
		array_splice( $vals, 1, 0, array( $qebutton ) );
		$actions = array_combine( $keys, $vals );
		return $actions;
	}


	/**
	 * Add bulk action for editing.
	 *
	 * @param array $actions Bulk actions.
	 * @return array Modified bulk actions.
	 */
	public function add_bulk_action( $actions ) {
		$keys = array_keys( $actions );
		$vals = array_values( $actions );
		array_splice( $keys, 0, 0, array( 'edit' ) );
		array_splice( $vals, 0, 0, array( __( 'Edit' ) ) );
		$actions = array_combine( $keys, $vals );
		return $actions;
	}




	/**
	 * Handle bulk action.
	 *
	 * @param string $sendback Redirect URL.
	 * @param string $doaction Action name.
	 * @param array  $post_ids Post IDs.
	 * @return string Redirect URL.
	 */
	public function handle_bulk_action( $sendback, $doaction, $post_ids ) {
		if ( empty( $post_ids ) || $doaction !== 'edit' ) {
			return $sendback;
		}
		$post_data = $_REQUEST;
		if ( isset( $post_data['bulk_edit'] ) ) {
			$post_data['post'] = $post_data['media'];
			unset( $post_data['media'] );
			$post_data['post_type'] = 'attachment';
			$done = $this->bbulk_edit_posts( $post_data );
			if ( is_array( $done ) ) {
				$done['updated'] = count( $done['updated'] );
				$done['skipped'] = count( $done['skipped'] );
				$done['locked'] = count( $done['locked'] );
				$sendback = add_query_arg( $done, $sendback );
			}
		}
		wp_redirect( $sendback );
		exit;
	}




	/**
	 * Display inline edit form.
	 */
	public function inline_edit() {
		global $mode, $wp_list_table;

		$screen = $wp_list_table->screen;

		$post = get_default_post_to_edit( $screen->post_type );
		$post_type_object = get_post_type_object( $screen->post_type );

		$taxonomy_names = get_object_taxonomies( $screen->post_type );
		$hierarchical_taxonomies = array();
		$flat_taxonomies = array();

		foreach ( $taxonomy_names as $taxonomy_name ) {

			$taxonomy = get_taxonomy( $taxonomy_name );

			$show_in_quick_edit = $taxonomy->show_in_quick_edit;

			/**
			 * Filters whether the current taxonomy should be shown in the Quick Edit panel.
			 *
			 * @since 4.2.0
			 *
			 * @param bool   $show_in_quick_edit Whether to show the current taxonomy in Quick Edit.
			 * @param string $taxonomy_name      Taxonomy name.
			 * @param string $post_type          Post type of current Quick Edit post.
			 */
			if ( ! apply_filters( 'quick_edit_show_taxonomy', $show_in_quick_edit, $taxonomy_name, $screen->post_type ) ) {
				continue;
			}

			if ( $taxonomy->hierarchical ) {
				$hierarchical_taxonomies[] = $taxonomy;
			} else {
				$flat_taxonomies[] = $taxonomy;
			}
		}

		$m = ( isset( $mode ) && 'excerpt' === $mode ) ? 'excerpt' : 'list';
		$can_publish = current_user_can( $post_type_object->cap->publish_posts );
		$core_columns = array(
			'cb'         => true,
			'date'       => true,
			'title'      => true,
			'categories' => true,
			'tags'       => true,
			'comments'   => true,
			'author'     => true,
		);

		?>

		<form method="get">
		<table style="display: none"><tbody id="inlineedit">
		<?php
		$hclass              = 'post';//count( $hierarchical_taxonomies ) ? 'post' : 'page';
		$inline_edit_classes = "inline-edit-row inline-edit-row-$hclass";
		$bulk_edit_classes   = "bulk-edit-row bulk-edit-row-$hclass bulk-edit-{$screen->post_type}";
		$quick_edit_classes  = "quick-edit-row quick-edit-row-$hclass inline-edit-{$screen->post_type}";

		$bulk = 0;
		while ( $bulk < 2 ) :
			$classes  = $inline_edit_classes . ' ';
			$classes .= $bulk ? $bulk_edit_classes : $quick_edit_classes;
			?>
			<tr id="<?php echo $bulk ? 'bulk-edit' : 'inline-edit'; ?>" class="<?php echo $classes; ?>" style="display: none">
			<td colspan="<?php echo $wp_list_table->get_column_count(); ?>" class="colspanchange">
			<div class="inline-edit-wrapper" role="region" aria-labelledby="quick-edit-legend">
			<fieldset class="inline-edit-col-left">
				<legend class="inline-edit-legend"><?php echo $bulk ? __( 'Bulk Edit' ) : __( 'Quick Edit' ); ?></legend>
				<div class="inline-edit-col">

				<?php if ( post_type_supports( $screen->post_type, 'title' ) ) : ?>

					<?php if ( $bulk ) : ?>

						<div id="bulk-title-div">
							<div id="bulk-titles"></div>
						</div>

					<?php else : // $bulk ?>

						<label>
							<span class="title"><?php _e( 'Title' ); ?></span>
							<span class="input-text-wrap"><input type="text" name="post_title" class="ptitle" value="" /></span>
						</label>

						<?php if ( is_post_type_viewable( $screen->post_type ) ) : ?>

							<label>
								<span class="title"><?php _e( 'Slug' ); ?></span>
								<span class="input-text-wrap"><input type="text" name="post_name" value="" /></span>
							</label>

						<?php endif; // is_post_type_viewable() ?>

					<?php endif; // $bulk ?>

				<?php endif; // post_type_supports( ... 'title' ) ?>

				<?php if ( ! $bulk ) : ?>
					<fieldset class="inline-edit-date">
						<legend><span class="title"><?php _e( 'Date' ); ?></span></legend>
						<?php touch_time( 1, 1, 0, 1 ); ?>
					</fieldset>
					<br class="clear" />
				<?php endif; // $bulk ?>

				<?php
			if ( post_type_supports( $screen->post_type, 'author' ) ) {
				$authors_dropdown = '';

				if ( current_user_can( $post_type_object->cap->edit_others_posts ) ) {
					$dropdown_name  = 'post_author';
					$dropdown_class = 'authors';
					if ( wp_is_large_user_count() ) {
						$authors_dropdown = sprintf( '<select name="%s" class="%s hidden"></select>', esc_attr( $dropdown_name ), esc_attr( $dropdown_class ) );
					} else {
						$users_opt = array(
							'hide_if_only_one_author' => false,
							'capability'              => array( $post_type_object->cap->edit_posts ),
							'name'                    => $dropdown_name,
							'class'                   => $dropdown_class,
							'multi'                   => 1,
							'echo'                    => 0,
							'show'                    => 'display_name_with_login',
						);

						if ( $bulk ) {
							$users_opt['show_option_none'] = __( '&mdash; No Change &mdash;' );
						}

						/**
						 * Filters the arguments used to generate the Quick Edit authors drop-down.
						 *
						 * @since 5.6.0
						 *
						 * @see wp_dropdown_users()
						 *
						 * @param array $users_opt An array of arguments passed to wp_dropdown_users().
						 * @param bool $bulk A flag to denote if it's a bulk action.
						 */
						$users_opt = apply_filters( 'quick_edit_dropdown_authors_args', $users_opt, $bulk );

						$authors = wp_dropdown_users( $users_opt );

						if ( $authors ) {
							$authors_dropdown  = '<label class="inline-edit-author">';
							$authors_dropdown .= '<span class="title">' . __( 'Author' ) . '</span>';
							$authors_dropdown .= $authors;
							$authors_dropdown .= '</label>';
						}
					}
				} // current_user_can( 'edit_others_posts' )

				if ( ! $bulk ) {
					echo $authors_dropdown;
				}
			} // post_type_supports( ... 'author' )
				?>

				</div>
			</fieldset>

			<?php if ( count( $hierarchical_taxonomies ) && ! $bulk ) : ?>

				<fieldset class="inline-edit-col-center inline-edit-categories">
					<div class="inline-edit-col">

					<?php foreach ( $hierarchical_taxonomies as $taxonomy ) : ?>
						<span class="title inline-edit-categories-label"><?php echo esc_html( $taxonomy->labels->name ); ?></span>
						<input type="hidden" name="<?php echo ( 'category' === $taxonomy->name ) ? 'post_category[]' : 'tax_input[' . esc_attr( $taxonomy->name ) . '][]'; ?>" value="0" />
						<ul class="cat-checklist <?php echo esc_attr( $taxonomy->name ); ?>-checklist">
							<?php wp_terms_checklist( null, array( 'taxonomy' => $taxonomy->name ) ); ?>
						</ul>

					<?php endforeach; // $hierarchical_taxonomies as $taxonomy ?>

					</div>
				</fieldset>

			<?php endif; // count( $hierarchical_taxonomies ) && ! $bulk ?>

			<fieldset class="inline-edit-col-right">

		<div class="inline-edit-col">
				<?php
				if ( post_type_supports( $screen->post_type, 'author' ) && $bulk ) {
					echo $authors_dropdown;
				}
				?>

				<?php //if ( count( $flat_taxonomies ) && ! $bulk ) : ?>
				<?php if ( count( $flat_taxonomies ) ) : ?>

					<?php foreach ( $flat_taxonomies as $taxonomy ) : ?>

						<?php if ( current_user_can( $taxonomy->cap->assign_terms ) ) : ?>
							<?php $taxonomy_name = esc_attr( $taxonomy->name ); ?>

							<label class="inline-edit-tags">
								<span class="title"><?php echo esc_html( $taxonomy->labels->name ); ?></span>
								<textarea data-wp-taxonomy="<?php echo $taxonomy_name; ?>" cols="22" rows="1" name="tax_input[<?php echo $taxonomy_name; ?>]" class="tax_input_<?php echo $taxonomy_name; ?>" <?php if($bulk) echo 'placeholder="'.__("&mdash; No Change &mdash;").'"' ?>></textarea>
							</label>

						<?php endif; // current_user_can( 'assign_terms' ) ?>

					<?php endforeach; // $flat_taxonomies as $taxonomy ?>

				<?php endif; // count( $flat_taxonomies ) && ! $bulk ?>

				<?php if ( post_type_supports( $screen->post_type, 'comments' ) || post_type_supports( $screen->post_type, 'trackbacks' ) ) : ?>

					<?php if ( $bulk ) : ?>

						<div class="inline-edit-group wp-clearfix">

						<?php if ( post_type_supports( $screen->post_type, 'comments' ) ) : ?>

							<label class="alignleft">
								<span class="title"><?php _e( 'Comments' ); ?></span>
								<select name="comment_status">
									<option value=""><?php _e( '&mdash; No Change &mdash;' ); ?></option>
									<option value="open"><?php _e( 'Allow' ); ?></option>
									<option value="closed"><?php _e( 'Do not allow' ); ?></option>
								</select>
							</label>

						<?php endif; ?>

						<?php if ( post_type_supports( $screen->post_type, 'trackbacks' ) ) : ?>

							<label class="alignright">
								<span class="title"><?php _e( 'Pings' ); ?></span>
								<select name="ping_status">
									<option value=""><?php _e( '&mdash; No Change &mdash;' ); ?></option>
									<option value="open"><?php _e( 'Allow' ); ?></option>
									<option value="closed"><?php _e( 'Do not allow' ); ?></option>
								</select>
							</label>

						<?php endif; ?>

						</div>

					<?php else : // $bulk ?>

						<div class="inline-edit-group wp-clearfix">

						<?php if ( post_type_supports( $screen->post_type, 'comments' ) ) : ?>

							<label class="alignleft">
								<input type="checkbox" name="comment_status" value="open" />
								<span class="checkbox-title"><?php _e( 'Allow Comments' ); ?></span>
							</label>

						<?php endif; ?>

						<?php if ( post_type_supports( $screen->post_type, 'trackbacks' ) ) : ?>

							<label class="alignleft">
								<input type="checkbox" name="ping_status" value="open" />
								<span class="checkbox-title"><?php _e( 'Allow Pings' ); ?></span>
							</label>

						<?php endif; ?>

						</div>

					<?php endif; // $bulk ?>

				<?php endif; // post_type_supports( ... comments or pings ) ?>

					<div class="inline-edit-group wp-clearfix">

						<label class="inline-edit-status alignleft">
							<span class="title"><?php _e( 'Status' ); ?></span>
							<select name="_status">
								<?php if ( $bulk ) : ?>
									<option value="-1"><?php _e( '&mdash; No Change &mdash;' ); ?></option>
								<?php endif; // $bulk ?>

								<?php if ( $can_publish ) : // Contributors only get "Unpublished" and "Pending Review". ?>
									<option value="publish"><?php _e( 'Published' ); ?></option>
									<option value="future"><?php _e( 'Scheduled' ); ?></option>
									<?php if ( $bulk ) : ?>
										<option value="private"><?php _e( 'Private' ); ?></option>
									<?php endif; // $bulk ?>
								<?php endif; ?>

								<option value="pending"><?php _e( 'Pending Review' ); ?></option>
								<option value="draft"><?php _e( 'Draft' ); ?></option>
							</select>
						</label>

						<?php if ( 'post' === $screen->post_type && $can_publish && current_user_can( $post_type_object->cap->edit_others_posts ) ) : ?>

							<?php if ( $bulk ) : ?>

								<label class="alignright">
									<span class="title"><?php _e( 'Sticky' ); ?></span>
									<select name="sticky">
										<option value="-1"><?php _e( '&mdash; No Change &mdash;' ); ?></option>
										<option value="sticky"><?php _e( 'Sticky' ); ?></option>
										<option value="unsticky"><?php _e( 'Not Sticky' ); ?></option>
									</select>
								</label>

							<?php else : // $bulk ?>

								<label class="alignleft">
									<input type="checkbox" name="sticky" value="sticky" />
									<span class="checkbox-title"><?php _e( 'Make this post sticky' ); ?></span>
								</label>

							<?php endif; // $bulk ?>

						<?php endif; // 'post' && $can_publish && current_user_can( 'edit_others_posts' ) ?>

					</div>

				</div>
			</fieldset>

			<?php
			list( $columns ) = $wp_list_table->get_column_info();

			foreach ( $columns as $column_name => $column_display_name ) {
				if ( isset( $core_columns[ $column_name ] ) ) {
					continue;
				}

				if ( $bulk ) {

					/**
					 * Fires once for each column in Bulk Edit mode.
					 *
					 * @since 2.7.0
					 *
					 * @param string $column_name Name of the column to edit.
					 * @param string $post_type   The post type slug.
					 */
					do_action( 'bulk_edit_custom_box', $column_name, $screen->post_type );
				} else {

					/**
					 * Fires once for each column in Quick Edit mode.
					 *
					 * @since 2.7.0
					 *
					 * @param string $column_name Name of the column to edit.
					 * @param string $post_type   The post type slug, or current screen name if this is a taxonomy list table.
					 * @param string $taxonomy    The taxonomy name, if any.
					 */
					do_action( 'quick_edit_custom_box', $column_name, $screen->post_type, '' );
				}
			}
			?>

			<div class="submit inline-edit-save">
				<button type="button" class="button cancel alignleft"><?php _e( 'Cancel' ); ?></button>

				<?php if ( ! $bulk ) : ?>
					<?php wp_nonce_field( 'inlineeditnonce', '_inline_edit', false ); ?>
					<button type="button" class="button button-primary save alignright"><?php _e( 'Update' ); ?></button>
					<span class="spinner"></span>
				<?php else : ?>
					<?php submit_button( __( 'Update' ), 'primary alignright', 'bulk_edit', false ); ?>
				<?php endif; ?>

				<input type="hidden" name="post_view" value="<?php echo esc_attr( $m ); ?>" />
				<input type="hidden" name="screen" value="<?php echo esc_attr( $screen->id ); ?>" />
				<?php if ( ! $bulk && ! post_type_supports( $screen->post_type, 'author' ) ) : ?>
					<input type="hidden" name="post_author" value="<?php echo esc_attr( $post->post_author ); ?>" />
				<?php endif; ?>
				<br class="clear" />

				<div class="notice notice-error notice-alt inline hidden">
					<p class="error"></p>
				</div>
			</div>
			</div>

			</td></tr>

			<?php
			$bulk++;
		endwhile;
		?>
		</tbody></table>
		</form>
		<?php
	}
	/**
	 * Bulk edit posts.
	 *
	 * @param array $post_data Post data.
	 * @return array Results of bulk edit.
	 */
	public function bbulk_edit_posts( $post_data = null ) {
		global $wpdb;

		if ( empty( $post_data ) ) {
			$post_data = &$_POST;
		}

		if ( isset( $post_data['post_type'] ) ) {
			$ptype = get_post_type_object( $post_data['post_type'] );
		} else {
			$ptype = get_post_type_object( 'post' );
		}

		if ( ! current_user_can( $ptype->cap->edit_posts ) ) {
			if ( 'page' === $ptype->name ) {
				wp_die( __( 'Sorry, you are not allowed to edit pages.' ) );
			} else {
				wp_die( __( 'Sorry, you are not allowed to edit posts.' ) );
			}
		}
	unset( $post_data['_status'], $post_data['post_status'] );

		$post_IDs = array_map( 'intval', (array) $post_data['post'] );

		$reset = array(
			'post_author',
			'post_status',
			'post_password',
			'post_parent',
			'page_template',
			'comment_status',
			'ping_status',
			'keep_private',
			'tax_input',
			'post_category',
			'sticky',
			'post_format',
		);

		foreach ( $reset as $field ) {
			if ( isset( $post_data[ $field ] ) && ( '' === $post_data[ $field ] || -1 == $post_data[ $field ] ) ) {
				unset( $post_data[ $field ] );
			}
		}

		if ( isset( $post_data['post_category'] ) ) {
			if ( is_array( $post_data['post_category'] ) && ! empty( $post_data['post_category'] ) ) {
				$new_cats = array_map( 'absint', $post_data['post_category'] );
			} else {
				unset( $post_data['post_category'] );
			}
		}

		$tax_input = array();
		if ( isset( $post_data['tax_input'] ) ) {
			foreach ( $post_data['tax_input'] as $tax_name => $terms ) {
				if ( empty( $terms ) ) {
					continue;
				}
				if ( is_taxonomy_hierarchical( $tax_name ) ) {
					$tax_input[ $tax_name ] = array_map( 'absint', $terms );
				} else {
					$comma = _x( ',', 'tag delimiter' );
					if ( ',' !== $comma ) {
						$terms = str_replace( $comma, ',', $terms );
					}
					$tax_input[ $tax_name ] = explode( ',', trim( $terms, " \n\t\r\0\x0B," ) );
				}
			}
		}
		unset( $post_data['tax_input'] );
  
if ( isset( $post_data['post_parent'] ) && (int) $post_data['post_parent'] ) {
			$parent = (int) $post_data['post_parent'];
			$pages = $wpdb->get_results( "SELECT ID, post_parent FROM $wpdb->posts WHERE post_type = 'page'" );
			$children = array();

			for ( $i = 0; $i < 50 && $parent > 0; $i++ ) {
				$children[] = $parent;

				foreach ( $pages as $page ) {
					if ( (int) $page->ID === $parent ) {
						$parent = (int) $page->post_parent;
						break;
					}
				}
			}
		}

		$updated = array();
		$skipped = array();
		$locked = array();
		$shared_post_data = $post_data;
		// Prevent wp_insert_post() from overwriting post format with the old data.

		foreach ( $post_IDs as $post_ID ) {
			// Start with fresh post data with each iteration.
			$post_data = $shared_post_data;

			$post_type_object = get_post_type_object( get_post_type( $post_ID ) );

			if ( ! isset( $post_type_object )
				|| ( isset( $children ) && in_array( $post_ID, $children, true ) )
				|| ! current_user_can( 'edit_post', $post_ID )
			) {
				$skipped[] = $post_ID;
				continue;
			}

			if ( wp_check_post_lock( $post_ID ) ) {
				$locked[] = $post_ID;
				continue;
			}

			$post = get_post( $post_ID );

			$post_data['post_ID'] = $post_ID;
			$post_data['post_type'] = $post->post_type;
			$post_data['post_mime_type'] = $post->post_mime_type;

			foreach ( array( 'comment_status', 'ping_status', 'post_author' ) as $field ) {
				if ( ! isset( $post_data[ $field ] ) ) {
					$post_data[ $field ] = $post->$field;
				}
			}
			$post_data = _wp_translate_postdata( true, $post_data );
			if ( is_wp_error( $post_data ) ) {
				$skipped[] = $post_ID;
				continue;
			}
			$post_data = _wp_get_allowed_postdata( $post_data );

			if ( isset( $shared_post_data['post_format'] ) ) {
				set_post_format( $post_ID, $shared_post_data['post_format'] );
			}

			$updated[] = wp_update_post( $post_data );
			if ( ! empty( $tax_input ) ) {
				foreach ( $tax_input as $taxonomy => $terms ) {
					wp_set_object_terms( $post_ID, $terms, $taxonomy, true );
				}
			}

			if ( isset( $post_data['sticky'] ) && current_user_can( $ptype->cap->edit_others_posts ) ) {
				if ( 'sticky' === $post_data['sticky'] ) {
					stick_post( $post_ID );
				} else {
					unstick_post( $post_ID );
				}
			}
		}

		return array(
			'updated' => $updated,
			'skipped' => $skipped,
			'locked'  => $locked,
		);
	}



/**
 * Handle AJAX request for inline media save.
 */
function wp_ajax_inline_media_save() {
	global $mode;

	check_ajax_referer( 'inlineeditnonce', '_inline_edit' );

	if ( ! isset( $_POST['post_ID'] ) || ! (int) $_POST['post_ID'] ) {
		wp_die();
	}

	$post_ID = (int) $_POST['post_ID'];
	if ( get_post_type( $post_ID ) !== 'attachment' ) {
		return;
	}
	remove_all_actions( current_action() );

	if ( ! current_user_can( 'edit_post', $post_ID ) ) {
		wp_die( __( 'Sorry, you are not allowed to edit this post.' ) );
	}

	$data = &$_POST;

	$post = get_post( $post_ID, ARRAY_A );

	// Since it's coming from the database.
	$post = wp_slash( $post );

	$data['content'] = $post['post_content'];
	$data['excerpt'] = $post['post_excerpt'];

	// Rename.
	$data['user_ID'] = get_current_user_id();

	if ( isset( $data['post_parent'] ) ) {
		$data['parent_id'] = $data['post_parent'];
	}
	// Exclude terms from taxonomies that are not supposed to appear in Quick Edit.
	if ( ! empty( $data['tax_input'] ) ) {
		foreach ( $data['tax_input'] as $taxonomy => $terms ) {
			$tax_object = get_taxonomy( $taxonomy );
			/** This filter is documented in wp-admin/includes/class-wp-posts-list-table.php */
			if ( ! apply_filters( 'quick_edit_show_taxonomy', $tax_object->show_in_quick_edit, $taxonomy, $post['post_type'] ) ) {
				unset( $data['tax_input'][ $taxonomy ] );
			}
		}
	}

	// Update the post.
	edit_post();

	//	$wp_list_table = _get_list_table( 'WP_Posts_List_Table', array( 'screen' => $_POST['screen'] ) );
	$wp_list_table = _get_list_table( 'WP_Media_List_Table', array( 'screen' => $_POST['screen'] ) );

	global $post;
	$post = get_post( $post_ID );
	$post_owner = ( get_current_user_id() === (int) $post->post_author ) ? 'self' : 'other';

	?>
	<tr id="post-<?php echo $post->ID; ?>" class="<?php echo trim( ' author-' . $post_owner . ' status-' . $post->post_status ); ?>">
	<?php $wp_list_table->single_row_columns( $post ); ?>
	</tr>
	<?php

	wp_die();
}
	

