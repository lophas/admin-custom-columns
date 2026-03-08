<?php
/**
 * Menu Order Column class.
 *
 * Handles menu order column functionality.
 *
 * @version 1.0
 */
class menu_order_columns {
	/**
	 * Singleton instance.
	 *
	 * @var menu_order_columns
	 */
	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return menu_order_columns
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
		add_action('admin_init', array($this, 'admin_init'), PHP_INT_MAX);
	}

	/**
	 * Initialize admin hooks.
	 */
	public function admin_init() {
		global $pagenow, $typenow;
		if (!defined('DOING_AJAX') && $pagenow !== 'edit.php') {
			return;
		}
		if (empty($typenow)) {
			$typenow = empty($_REQUEST['post_type']) ? 'post' : $_REQUEST['post_type'];
		}
		if (!post_type_supports($typenow, 'page-attributes')) {
			return;
		}
		add_filter('manage_edit-' . $typenow . '_columns', array($this, 'columnsname'), 1);
		add_action('manage_' . $typenow . '_posts_custom_column', array($this, 'columnsdata'), 10, 2);
		add_filter('manage_edit-' . $typenow . '_sortable_columns', array($this, 'sortable'));
		add_action('load-' . $pagenow, array($this, 'load'));
	}

	/**
	 * Load additional hooks.
	 */
	public function load() {
		global $typenow;
		add_action('posts_clauses', array($this, 'posts_clauses'), 10, 2);
		add_filter('default_hidden_columns', function ($hidden, $screen) {
			array_push($hidden, 'menu_order');
			return $hidden;
		}, 10, 2);
		add_action('bulk_edit_custom_box', array($this, 'bulk_edit_custom_box'), 10, 2);
		add_action('check_admin_referer', array($this, 'bulk_edit_update'), 10, 2);
	}

	/**
	 * Modify columns.
	 *
	 * @param array $columns Columns array.
	 * @return array
	 */
	public function columnsname($columns) {
		if (($key = array_search('title', array_keys($columns))) !== false) {
			$columns1 = array_combine(array_slice(array_keys($columns), 0, $key + 1), array_slice(array_values($columns), 0, $key + 1));
			$columns2 = array_combine(array_slice(array_keys($columns), $key + 1), array_slice(array_values($columns), $key + 1));
			$columns = array_merge($columns1, array(
				'menu_order' => __('Order'),
			), $columns2);
		}
		return $columns;
	}

	/**
	 * Display column data.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public function columnsdata($column, $post_id) {
		global $post;
		switch ($column) {
			case 'menu_order':
				echo intval($post->menu_order);
				break;
		}
	}

	/**
	 * Make column sortable.
	 *
	 * @param array $columns Columns array.
	 * @return array
	 */
	public function sortable($columns) {
		$columns['menu_order'] = array('menu_order', 0);
		return $columns;
	}

	/**
	 * Modify posts clauses for sorting.
	 *
	 * @param array  $clauses Query clauses.
	 * @param object $query   WP_Query object.
	 * @return array
	 */
	public function posts_clauses($clauses, $query) {
		if (!$query->is_main_query()) {
			return $clauses;
		}
		global $wpdb;
		$orderby = $_GET['orderby'];
		if ($orderby == 'menu_order') {
			$order = $_GET['order'] ? $_GET['order'] : 'ASC';
			$clauses['orderby'] = $wpdb->posts . '.post_parent ' . $order . ', ' . $wpdb->posts . '.menu_order ' . $order;
		}
		if (is_numeric($_GET['parent_selector'])) {
			$clauses['where'] .= ' AND (' . $wpdb->posts . '.post_parent = ' . $_GET['parent_selector'] . ' OR ' . $wpdb->posts . '.ID = ' . $_GET['parent_selector'] . ')';
			if (empty($orderby)) {
				$clauses['orderby'] = $wpdb->posts . '.menu_order ASC, ' . $wpdb->posts . '.post_date DESC';
			}
		}
		return $clauses;
	}

	/**
	 * Add bulk edit custom box.
	 *
	 * @param string $column_name Column name.
	 * @param string $post_type   Post type.
	 */
	public function bulk_edit_custom_box($column_name, $post_type) {
		if (in_array('menu_order', get_hidden_columns(get_current_screen()))) {
			return;
		}
		switch ($column_name) {
			case 'menu_order':
				if (is_numeric($_GET['parent_selector']) || !is_post_type_hierarchical($GLOBALS['typenow'])) :
					?>
					<fieldset class="inline-edit-col-right">
						<div class="inline-edit-col">
							<label class="inline-edit-menu_order">
								<span class="title"><?php echo __('Order'); ?></span>
								<input id="reorder" name="reorder" class="text" type="number" step="1" value="" placeholder="<?php _e('&mdash; No Change &mdash;'); ?>"> <?php _e('From'); ?>
								<input id="steporder" name="steporder" class="text" type="number" step="1" value="1"> <?php _e('Step'); ?>
							</label>
						</div>
					</fieldset>
					<?php
				endif;
				break;
		}
	}

	/**
	 * Update bulk edit.
	 *
	 * @param string $action Action.
	 * @param bool   $result Result.
	 */
	public function bulk_edit_update($action, $result) {
		if ('bulk-posts' !== $action || !is_numeric($_REQUEST['reorder'])) {
			return;
		}
		$post_ids = isset($_REQUEST['post']) ? array_map('intval', (array) $_REQUEST['post']) : explode(',', $_REQUEST['ids']);
		if (empty($post_ids)) {
			return;
		}
		$menu_order = intval($_REQUEST['reorder']);
		$step_order = intval($_REQUEST['steporder']);
		foreach ($post_ids as $post_id) {
			wp_update_post(array('ID' => $post_id, 'menu_order' => $menu_order));
			$menu_order += $step_order;
		}
	}
}

menu_order_columns::instance();
