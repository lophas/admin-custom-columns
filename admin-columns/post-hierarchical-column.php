<?php
/**
 * Post Hierarchical Column class for displaying parent selector in admin columns.
 *
 * @version 1.0
 */
class post_hierarchical_column {

	/**
	 * Instance property.
	 *
	 * @var post_hierarchical_column
	 */
	private static $_instance;

	/**
	 * Get instance.
	 *
	 * @return post_hierarchical_column
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
		add_action( 'admin_init', array( $this, 'admin_init' ), PHP_INT_MAX );
	}

	/**
	 * Admin init.
	 */
	public function admin_init() {
		global $pagenow, $typenow;
		if ( ! defined( 'DOING_AJAX' ) && 'edit.php' !== $pagenow ) {
			return;
		}
		if ( empty( $typenow ) ) {
			$typenow = empty( $_REQUEST['post_type'] ) ? 'post' : $_REQUEST['post_type'];
		}
		if ( ! is_post_type_hierarchical( $typenow ) ) {
			return;
		}
		add_action( 'load-' . $pagenow, array( $this, 'load' ) );
	}

	/**
	 * Load hooks.
	 */
	public function load() {
		global $typenow;
		if ( is_numeric( $_GET['parent_selector'] ) && intval( $_GET['parent_selector'] ) > 0 ) {
			add_action( 'admin_print_footer_scripts', function() {
				?>
				<script>
				jQuery('#post-<?php echo $_GET['parent_selector']; ?> th').html('&nbsp;');
				</script>
				<?php
			} );
		}
		add_action( 'restrict_manage_posts', array( $this, 'parentSelector' ) );
	}

	/**
	 * Parent selector.
	 */
	public function parentSelector() {
		if ( in_array( 'menu_order', get_hidden_columns( get_current_screen() ) ) ) {
			return;
		}
		global $typenow;
		global $wpdb;
		$sql = 'SELECT DISTINCT post_parent FROM ' . $wpdb->posts . ' WHERE post_type="' . $typenow . '"';
		$parents = $wpdb->get_col( $sql );
		$args = array(
			'showposts' => '-1',
			'include'   => $parents,
			'post_type' => $typenow,
			'orderby'   => array(
				'title'      => 'ASC',
				'menu_order' => 'ASC',
			),
			'post_status' => array( 'publish', 'pending', 'draft', 'future' ),
		);
		$posts = get_posts( $args );
		$posttree = array();
		foreach ( $posts as $post ) {
			$posttree[ intval( $post->post_parent ) ][] = $post;
		}
		echo '<select name="parent_selector">';
		echo '<option value="">' . __( 'Parent' ) . '</option>';
		echo '<option value=0 ' . selected( 0, $_GET['parent_selector'] ) . '>' . __( 'Main Page (no parent)' ) . '</option>';
		$this->parentSelector_dropdown( $posttree );
		echo '</select>';
	}

	/**
	 * Parent selector dropdown.
	 *
	 * @param array $posttree Post tree.
	 * @param int   $parent   Parent ID.
	 * @param int   $indent   Indent level.
	 */
	public function parentSelector_dropdown( $posttree, $parent = 0, $indent = 0 ) {
		if ( empty( $posttree[ $parent ] ) ) {
			return;
		}
		foreach ( $posttree[ $parent ] as $post ) :
			?>
			<option value="<?php echo $post->ID; ?>" <?php selected( $post->ID, $_GET['parent_selector'] ); ?>><?php echo str_repeat( '&nbsp;&nbsp;', $indent ) . ( $post->post_title ? esc_attr( $post->post_title ) : __( 'Untitled' ) ) . ( 'publish' !== $post->post_status ? ' (' . __( $post->post_status ) . ')' : '' ); ?></option>
			<?php
			$this->parentSelector_dropdown( $posttree, $post->ID, $indent + 1 );
		endforeach;
	}
}

post_hierarchical_column::instance();
