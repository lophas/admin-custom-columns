<?php
/**
 * Meta post columns class.
 *
 * @version 2.10
 */
if ( ! class_exists( 'post_meta_columns' ) ) :
class post_meta_columns {

	/**
	 * Instance property.
	 *
	 * @var post_meta_columns
	 */
	private static $_instance;

	/**
	 * Hidden flag.
	 *
	 * @var bool
	 */
	private $hidden = false;

	/**
	 * Arguments.
	 *
	 * @var array
	 */
	private $args;

	/**
	 * Constructor.
	 *
	 * @param array|string $args Arguments.
	 */
	public function __construct( $args ) {
		if ( ! is_array( $args ) ) {
			$args = array( 'meta' => array( 'key' => $args ) );
		}
		$this->args = array_merge( array(
			'post_type' => array( 'post' ),
			'dropdown'  => false,
			'sortable'  => false,
			'quick_edit' => false,
			'bulk_edit' => false,
		), $args );
		if ( empty( $this->args['meta'] ) ) {
			return;
		}
		if ( ! is_array( $this->args['meta'] ) ) {
			$this->args['meta'] = array( 'key' => $this->args['meta'] );
		}
		if ( empty( $this->args['meta']['label'] ) ) {
			$this->args['meta']['label'] = ucfirst( $this->args['meta']['key'] );
		}
		if ( ! is_array( $this->args['post_type'] ) ) {
			$this->args['post_type'] = (array) $this->args['post_type'];
		}
		add_action( 'admin_init', array( $this, 'admin_init' ), PHP_INT_MAX );
	}
	/**
	 * Admin init.
	 */
	public function admin_init() {
		global $pagenow, $typenow;
		if ( ! defined( 'DOING_AJAX' ) && ! in_array( $pagenow, array( 'edit.php', 'upload.php' ) ) ) {
			return;
		}
		if ( empty( $typenow ) ) {
			if ( 'upload.php' == $pagenow ) {
				$typenow = 'attachment';
			} else {
				$typenow = empty( $_REQUEST['post_type'] ) ? 'post' : $_REQUEST['post_type'];
			}
		}
		if ( ! in_array( $typenow, $this->args['post_type'] ) ) {
			return;
		}
		if ( 'attachment' == $typenow ) {
			add_filter( 'manage_media_columns', array( $this, 'columnsname' ), 10 );
			add_action( 'manage_media_custom_column', array( $this, 'columnsdata' ), 10, 2 );
		} else {
			add_filter( 'manage_' . $typenow . '_posts_columns', array( $this, 'columnsname' ), 10 );
			add_action( 'manage_' . $typenow . '_posts_custom_column', array( $this, 'columnsdata' ), 10, 2 );
		}

		add_action( 'add_inline_data', function( $post, $post_type_object ) {
			echo '<div class="meta-' . $this->args['meta']['key'] . '">' . get_post_meta( $post->ID, $this->args['meta']['key'], true ) . '</div>';
		}, 10, 2 );

		if ( ! in_array( $typenow, $this->args['post_type'] ) ) {
			return;
		}
		add_action( 'save_post', array( $this, 'quick_edit_update' ), 10, 3 );

		if ( $pagenow ) {
			add_action( 'load-' . $pagenow, array( $this, 'load' ) );
		}
	}
	/**
	 * Load hooks.
	 */
	public function load() {
		global $typenow;
		add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
		if ( $this->args['sortable'] ) {
			if ( 'attachment' == $typenow ) {
				add_filter( 'manage_upload_sortable_columns', array( $this, 'sortable' ) );
			} else {
				add_filter( 'manage_edit-' . $typenow . '_sortable_columns', array( $this, 'sortable' ) );
			}
		}
		if ( $this->args['dropdown'] ) {
			add_action( 'restrict_manage_posts', array( $this, 'dropdown' ), 10, 2 );
			add_filter( 'hidden_columns', function ( $hidden, $screen, $use_defaults ) {
				if ( in_array( $this->args['meta']['key'], (array) $hidden ) ) {
					$this->hidden = true;
				}
				return $hidden;
			}, 10, 3 );
		}
		if ( $this->args['bulk_edit'] ) {
			add_action( 'bulk_edit_custom_box_fields', array( $this, 'bulk_edit_custom_box_fields' ) );
			add_action( 'check_admin_referer', array( $this, 'bulk_edit_update' ), 10, 2 );
		}
		if ( $this->args['quick_edit'] ) {
			add_action( 'quick_edit_custom_box_fields', array( $this, 'quick_edit_custom_box_fields' ) );
			add_action( 'admin_print_footer_scripts', array( $this, 'quick_edit_populate_fields' ), 999 );
		}
	}
	/**
	 * Add column name.
	 *
	 * @param array $columns Columns.
	 * @return array Modified columns.
	 */
	public function columnsname( $columns ) {
		$columns[ $this->args['meta']['key'] ] = $this->args['meta']['label'];
		return $columns;
	}
	/**
	 * Display column data.
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function columnsdata( $column, $post_id ) {
		if ( $column !== $this->args['meta']['key'] ) {
			return;
		}
		$value = get_post_meta( $post_id, $this->args['meta']['key'], true );
		if ( '' === $value ) {
			return;
		}
		$selector = $this->args['meta']['key'] . '_selector';
		$output = apply_filters( 'post_meta_columns_data', $value, $this->args );
		$output = apply_filters( 'post_meta_columns_data_' . $this->args['meta']['key'], $output, $this->args );
		if ( $this->args['dropdown'] && ( ! isset( $_GET[ $selector ] ) || '' === $_GET[ $selector ] ) && ! defined( 'DOING_AJAX' ) ) {
			echo '<a href="' . remove_query_arg( 'paged', add_query_arg( $selector, $value ) ) . '">' . $output . '</a>';
		} else {
			echo $output;
		}
	}
	/**
	 * Make column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sortable( $columns ) {
		$columns[ $this->args['meta']['key'] ] = array( $this->args['meta']['key'], 0 );
		return $columns;
	}
	/**
	 * Modify posts clauses for sorting and filtering.
	 *
	 * @param array    $clauses Clauses.
	 * @param WP_Query $query   Query object.
	 * @return array Modified clauses.
	 */
	public function posts_clauses( $clauses, $query ) {
		if ( ! $query->is_main_query() ) {
			return $clauses;
		}
		$selector = $this->args['meta']['key'] . '_selector';
		if ( $_GET['orderby'] === $this->args['meta']['key'] || ( isset( $_GET[ $selector ] ) && '' !== $_GET[ $selector ] ) ) {
			$key = 'm' . str_replace( '-', '_', $this->args['meta']['key'] );
			global $wpdb;
			$clauses['join'] .= ' LEFT JOIN ' . $wpdb->postmeta . ' as ' . $key . ' ON ' . $wpdb->posts . '.ID = ' . $key . '.post_id AND ' . $key . '.meta_key = "' . $this->args['meta']['key'] . '"';
			if ( $_GET['orderby'] === $this->args['meta']['key'] ) {
				$clauses['orderby'] = $key . '.' . ( 'num' === $this->args['sortable'] ? 'meta_value+0' : 'meta_value' ) . ' ' . ( $_GET['order'] ? $_GET['order'] : 'ASC' );
			}
			if ( isset( $_GET[ $selector ] ) && '' !== $_GET[ $selector ] ) {
				$clauses['where'] .= $wpdb->prepare( ' AND ' . $key . '.meta_value = %s', $_GET[ $selector ] );
			}
		}
		return $clauses;
	}
	/**
	 * Display dropdown filter.
	 *
	 * @param string $post_type Post type.
	 * @param string $which     Which.
	 */
	public function dropdown( $post_type, $which ) {
		if ( $this->hidden ) {
			return;
		}
		global $wpdb;
		$sql = 'SELECT m.meta_value, COUNT(*) as count FROM ' . $wpdb->postmeta . ' as m
				JOIN ' . $wpdb->posts . ' as p ON p.ID = m.post_id
				WHERE m.meta_key="' . $this->args['meta']['key'] . '" AND p.post_type = "' . $GLOBALS['typenow'] . '" AND m.meta_value <> ""
                GROUP BY m.meta_value
                ORDER BY m.meta_value ASC';
		$values = $wpdb->get_results( $sql );
		$selector = $this->args['meta']['key'] . '_selector';
		echo '<select name="' . $selector . '">';
		echo '<option value="">' . __( 'All' ) . ' ' . $this->args['meta']['label'] . '</option>';
		foreach ( $values as $value ) {
			$output = apply_filters( 'post_meta_columns_data', $value->meta_value, $this->args );
			$output = apply_filters( 'post_meta_columns_data_' . $this->args['meta']['key'], $output, $this->args );
			echo '<option value="' . $value->meta_value . '" ' . selected( $value->meta_value, $_GET[ $selector ] ) . '>' . $output . ' (' . $value->count . ')</option>';
		}
		echo '</select>';
	}

	/**
	 * Bulk edit custom box fields.
	 *
	 * @param string $post_type Post type.
	 */
	public function bulk_edit_custom_box_fields( $post_type ) {
		if ( $this->hidden ) {
			return;
		}
		$key = $this->args['meta']['key'];
		$output = '<input id="meta-' . $key . '" name="' . $key . '" type="text" value="" placeholder="' . __( '&mdash; No Change &mdash;' ) . '">';
		$output = apply_filters( 'post_meta_columns_bulk_edit', $output, $this->args );
		$output = apply_filters( 'post_meta_columns_bulk_edit_' . $key, $output, $this->args );
		?>
		<label class="inline-edit-meta-<?php echo $key; ?>">
			<span class="meta-<?php echo $key; ?>"><?php echo $this->args['meta']['label']; ?></span>
			<?php echo $output; ?>
		</label>
		<?php
	}
	/**
	 * Bulk edit update.
	 *
	 * @param string $action Action.
	 * @param mixed  $result Result.
	 */
	public function bulk_edit_update( $action, $result ) {
		if ( ! in_array( $action, array( 'bulk-posts', 'bulk-media' ) ) ) {
			return;
		}
		$key = $this->args['meta']['key'];
		$val = apply_filters( 'post_meta_columns_update', $_REQUEST[ $key ], $_REQUEST, $this->args );
		$val = apply_filters( 'post_meta_columns_update_' . $key, $val, $_REQUEST, $this->args );
		if ( ! isset( $val ) || '' === $val ) {
			return;
		}

		$posts = isset( $_REQUEST['media'] ) ? $_REQUEST['media'] : $_REQUEST['post'];
		$post_ids = $posts ? array_map( 'intval', (array) $posts ) : explode( ',', $_REQUEST['ids'] );
		if ( empty( $post_ids ) ) {
			return;
		}
		foreach ( $post_ids as $post_id ) {
			update_post_meta( $post_id, $key, $val );
		}
	}
	/**
	 * Quick edit custom box fields.
	 *
	 * @param string $post_type Post type.
	 */
	public function quick_edit_custom_box_fields( $post_type ) {
		$key = $this->args['meta']['key'];
		$output = '<input id="meta-' . $key . '" name="' . $key . '" type="text" value="">';
		$output = apply_filters( 'post_meta_columns_quick_edit', $output, $this->args );
		$output = apply_filters( 'post_meta_columns_quick_edit_' . $key, $output, $this->args );
		?>
		<label class="inline-edit-meta-<?php echo $key; ?>">
			<span class="meta-<?php echo $key; ?>"><?php echo $this->args['meta']['label']; ?></span>
			<?php echo $output; ?>
		</label>
		<?php
	}
	/**
	 * Quick edit populate fields.
	 */
	public function quick_edit_populate_fields() {
		global $typenow;
		if ( ! in_array( $typenow, $this->args['post_type'] ) ) {
			return;
		}
		$key = $this->args['meta']['key'];
		?>
		<script type="text/javascript">
		(function($) {
		   var wp_inline_edit = inlineEditPost.edit;
		   inlineEditPost.edit = function( id ) {
		      wp_inline_edit.apply( this, arguments );
		      var post_id = 0;
		      if ( typeof( id ) == 'object' ) post_id = parseInt( this.getId( id ) );
		      if ( post_id > 0 ) {
		        var this_field = $( '#edit-' + post_id );
		        if(this_field.length) {
		            var this_value = $( '#inline_' + post_id).find('.meta-<?php echo $key; ?>').text();
		            var this_input = this_field.find('input[name="<?php echo $key; ?>"]:radio,input[name="<?php echo $key; ?>"]:checkbox');
		            if(this_input.length) {
		                    this_input.filter('[value="' + this_value + '"]').prop('checked', true);
		            } else {
		                    this_input = this_field.find('[name="<?php echo $key; ?>"]');
		                    if(this_input.length) {
		                        this_input.val(this_value); //instant value
		                    }
		            }
			    }
			  }
		   };
		})(jQuery);
		</script>
		<?php
	}
	/**
	 * Quick edit update.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an update.
	 */
	public function quick_edit_update( $post_id, $post, $update ) {
		if ( ! defined( 'DOING_AJAX' ) || ! in_array( $post->post_type, $this->args['post_type'] ) ) {
			return;
		}
		$key = $this->args['meta']['key'];
		$val = apply_filters( 'post_meta_columns_update', $_REQUEST[ $key ], $_REQUEST, $this->args );
		$val = apply_filters( 'post_meta_columns_update_' . $key, $val, $_REQUEST, $this->args );
		if ( ! isset( $val ) ) {
			return;
		}
		if ( '' === $val ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $val );
		}
	}
}
endif;
