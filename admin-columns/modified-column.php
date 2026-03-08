<?php
/**
 * Modified Column class for displaying last modified date in admin columns.
 *
 * @version 1.0
 */
class modified_column {

	/**
	 * Instance property.
	 *
	 * @var modified_column
	 */
	private static $_instance;

	/**
	 * Get instance.
	 *
	 * @return modified_column
	 */
	public static function instance() {
		if ( ! isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
		}
	}

	/**
	 * Admin init.
	 */
	public function admin_init() {
		global $typenow, $pagenow;
		if ( 'upload.php' == $pagenow ) {
			$typenow = 'attachment';
		} elseif ( empty( $typenow ) && ( defined( 'DOING_AJAX' ) || 'edit.php' == $pagenow ) ) {
			$typenow = empty( $_REQUEST['post_type'] ) ? 'post' : $_REQUEST['post_type'];
		}
		add_filter( 'manage_media_columns', array( $this, 'last_modified_column_head' ), 11 );
		add_filter( 'manage_upload_sortable_columns', array( $this, 'sort_last_modified_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'do_last_modified_column' ), 10, 2 );
		add_filter( 'manage_' . $typenow . '_posts_columns', array( $this, 'last_modified_column_head' ), 11 );
		add_filter( 'manage_edit-' . $typenow . '_sortable_columns', array( $this, 'sort_last_modified_column' ) );
		add_action( 'manage_' . $typenow . '_posts_custom_column', array( $this, 'do_last_modified_column' ), 10, 2 );
	}

	/**
	 * Add last modified column head.
	 *
	 * @param array $columns Columns.
	 * @return array Modified columns.
	 */
	public function last_modified_column_head( $columns ) {
		$modified = array( 'modified' => __( 'Last Modified' ) );
		if ( ( $pos = array_search( 'date', array_keys( $columns ) ) ) !== false ) {
			$columns = array_merge(
				array_slice( $columns, 0, $pos + 1 ),
				$modified,
				array_slice( $columns, $pos + 1 )
			);
		} else {
			$columns = array_merge( $columns, $modified );
		}
		?>
		<style>.column-modified{width:10%}</style>
		<?php
		return $columns;
	}

	/**
	 * Display last modified column.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function do_last_modified_column( $column_name, $post_id ) {
		if ( 'modified' !== $column_name ) {
			return;
		}
		//			get_post_field( 'post_modified', $post_id, 'raw' );
		$post = get_post( $post_id );
		if ( '0000-00-00 00:00:00' === $post->post_modified ) {
			$t_time = $h_time = __( 'Unpublished' );
			$time_diff = 0;
			echo $t_time;
			return;
		} else {
			$t_time = get_the_modified_date( __( 'Y-m-d H:i' ) );
			$time = get_post_modified_time( 'G', true, $post );

			$time_diff = time() - $time;

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$date = sprintf( __( '%s ago' ), human_time_diff( $time ) );
			} else {
				$date = $t_time;
			}
		}
		if ( defined( 'DOING_AJAX' ) ) {
			echo $date;
		} else {
			$link = remove_query_arg( 'paged', add_query_arg( array( 'orderby' => 'modified', 'm' => substr( $post->post_modified, 0, 4 ) . substr( $post->post_modified, 5, 2 ), 'day' => substr( $post->post_modified, 8, 2 ) ) ) );
			//$link = get_day_link( substr($post->post_date,0,4), substr($post->post_date,5,2), substr($post->post_date,8,2) );
			echo '<a href="' . esc_url( $link ) . '" rel="bookmark" title="' . $t_time . '">' . $date . '</a>';
		}
	}

	/**
	 * Make modified column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sort_last_modified_column( $columns ) {
		$columns['modified'] = array( 'modified', 1 );
		return $columns;
	}
}

modified_column::instance();
