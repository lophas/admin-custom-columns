<?php
/**
 * Thumbnail Column class for displaying post thumbnails in admin columns.
 *
 * @version 1.0
 */
class thumbnail_column {

	/**
	 * Instance property.
	 *
	 * @var thumbnail_column
	 */
	private static $_instance;

	/**
	 * Get instance.
	 *
	 * @return thumbnail_column
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
		if ( ( defined( 'DOING_AJAX' ) || $GLOBALS['pagenow'] == 'edit.php' ) && empty( $GLOBALS['typenow'] ) ) {
			$GLOBALS['typenow'] = empty( $_REQUEST['post_type'] ) ? 'post' : $_REQUEST['post_type'];
		}
		global $typenow;
		add_filter( 'manage_' . $typenow . '_posts_columns', array( $this, 'thumbnail_column_head' ), 1000000000000001 );
	}

	/**
	 * Add thumbnail column head.
	 *
	 * @param array $columns Columns.
	 * @return array Modified columns.
	 */
	public function thumbnail_column_head( $columns ) {
		if ( isset( $columns['thumbnail'] ) ) {
			return $columns;
		}
		if ( ! post_type_supports( $GLOBALS['typenow'], 'thumbnail' ) ) {
			return $columns;
		}
		$columns['thumbnail'] = __( 'Thumbnail' );
		add_action( 'manage_posts_custom_column', array( $this, 'do_thumbnail_column' ), 10, 2 );
		?>
		<style>.column-thumbnail{width:10%}.column-thumbnail img.wp-post-image{object-fit:cover}</style>
		<?php
		return $columns;
	}

	/**
	 * Display thumbnail column.
	 *
	 * @param string $name    Column name.
	 * @param int    $post_id Post ID.
	 */
	public function do_thumbnail_column( $name, $post_id ) {
		switch ( $name ) {
			case 'thumbnail':
				$thumbnail = get_the_post_thumbnail( $post_id, array( 100, 100 ) );
				echo $thumbnail;
				break;
		}
	}
}

thumbnail_column::instance();
