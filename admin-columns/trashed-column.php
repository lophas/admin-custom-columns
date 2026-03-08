<?php
/**
 * Trashed Column class for displaying trashed date in admin columns.
 *
 * @version 1.0
 */
class trashed_column {

	/**
	 * Instance property.
	 *
	 * @var trashed_column
	 */
	private static $_instance;

	/**
	 * Get instance.
	 *
	 * @return trashed_column
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
		if ( is_admin() && $_GET['post_status'] == 'trash' ) {
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'load-edit.php', array( $this, 'load_edit' ) ); // default sort order
		}
	}

	/**
	 * Load edit.
	 */
	public function load_edit() { // default sort order
		if ( $_GET['orderby'] == 'trashed' ) {
			add_filter( 'posts_clauses', array( $this, 'posts_clauses' ), 10, 2 );
		}
	}

	/**
	 * Admin init.
	 */
	public function admin_init() {
		if ( empty( $GLOBALS['typenow'] ) ) {
			if ( defined( 'DOING_AJAX' ) || in_array( $GLOBALS['pagenow'], array( 'edit.php' ) ) ) {
				$GLOBALS['typenow'] = empty( $_REQUEST['post_type'] ) ? 'post' : $_REQUEST['post_type'];
			}
		}
		global $typenow;
		add_filter( 'manage_' . $typenow . '_posts_columns', array( $this, 'last_trashed_column_head' ), 11 );
		add_filter( 'manage_edit-' . $typenow . '_sortable_columns', array( $this, 'sort_last_trashed_column' ) );
		add_action( 'manage_' . $typenow . '_posts_custom_column', array( $this, 'do_last_trashed_column' ), 10, 2 );
	}

	/**
	 * Add last trashed column head.
	 *
	 * @param array $columns Columns.
	 * @return array Modified columns.
	 */
	public function last_trashed_column_head( $columns ) {
		$trashed = array( 'trashed' => __( 'Trashed' ) );
		if ( ( $pos = array_search( 'date', array_keys( $columns ) ) ) !== false ) {
			$columns = array_merge(
				array_slice( $columns, 0, $pos + 1 ),
				$trashed,
				array_slice( $columns, $pos + 1 )
			);
		} else {
			$columns = array_merge( $columns, $trashed );
		}
		?>
		<style>.column-trashed{width:10%}</style>
		<?php
		return $columns;
	}

	/**
	 * Display last trashed column.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function do_last_trashed_column( $column_name, $post_id ) {
		if ( 'trashed' !== $column_name ) {
			return;
		}
		$time = get_post_meta( $post_id, '_wp_trash_meta_time', true );
		if ( ! $time ) {
			$t_time = $h_time = __( 'Unknown' );
			$time_diff = 0;
			echo $t_time;
			return;
		} else {
			$sql_time = get_date_from_gmt( date( 'Y-m-d H:i:s', $time ) );
			$t_time = get_date_from_gmt( date( 'Y-m-d H:i:s', $time ), __( 'Y-m-d H:i' ) );
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
			$link = remove_query_arg( 'paged', add_query_arg( array( 'orderby' => 'trashed', 'm' => substr( $sql_time, 0, 4 ) . substr( $sql_time, 5, 2 ), 'day' => substr( $sql_time, 8, 2 ) ) ) );
			echo '<a href="' . esc_url( $link ) . '" rel="bookmark" title="' . $t_time . '">' . $date . '</a>';
		}
	}

	/**
	 * Make trashed column sortable.
	 *
	 * @param array $columns Sortable columns.
	 * @return array Modified sortable columns.
	 */
	public function sort_last_trashed_column( $columns ) {
		$columns['trashed'] = array( 'trashed', 1 );
		return $columns;
	}

	/**
	 * Modify posts clauses for sorting.
	 *
	 * @param array    $clauses Clauses.
	 * @param WP_Query $query   Query object.
	 * @return array Modified clauses.
	 */
	public function posts_clauses( $clauses, $query ) {
		if ( ! $query->is_main_query() ) {
			return $clauses;
		}
		global $wpdb;
		if ( strpos( $clauses['fields'], $wpdb->posts . '.*' ) === false || strpos( $clauses['where'], $wpdb->posts . '.post_status = \'trash\'' ) === false ) {
			return $clauses;
		}
		$clauses['where'] = str_replace( $wpdb->posts . '.post_date', 'FROM_UNIXTIME(tdate.meta_value)', $clauses['where'] );
		$orderby = 'trashdate';
		$order = $_GET['order'] ? $_GET['order'] : 'desc';

		$clauses['fields'] .= ', tdate.meta_value as trashdate';
		$clauses['join'] .= ' JOIN ' . $wpdb->postmeta . ' AS tdate ON (' . $wpdb->posts . '.ID = tdate.post_id AND tdate.meta_key = "_wp_trash_meta_time")';
		$clauses['orderby'] = ' trashdate ' . $order . ', ' . $wpdb->posts . '.post_modified ' . $order;
		$clauses['order'] = '';
		$clauses['distinct'] = 'DISTINCT';
		return $clauses;
	}
}

trashed_column::instance();
