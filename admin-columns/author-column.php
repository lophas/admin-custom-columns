<?php
/**
 * Author Column class.
 *
 * Adds author column functionality.
 *
 * @version 1.1
 */
class author_column {
	/**
	 * Singleton instance.
	 *
	 * @var author_column
	 */
	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return author_column
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
		if (is_admin()) {
			add_action('load-edit.php', array($this, 'load'));
			add_action('load-upload.php', array($this, 'load'));
			add_action('admin_init', array($this, 'admin_init'));
		}
	}

	/**
	 * Load hooks for edit pages.
	 */
	public function load() {
		add_action('admin_head', array($this, 'admin_head'));
		add_filter('posts_clauses', array($this, 'posts_clauses'), 10, 2);
	}

	/**
	 * Initialize admin hooks.
	 */
	public function admin_init() {
		global $typenow, $pagenow;
		if ($pagenow == 'upload.php') {
			$typenow = 'attachment';
		} elseif (empty($typenow) && (defined('DOING_AJAX') || $pagenow == 'edit.php')) {
			$typenow = empty($_REQUEST['post_type']) ? 'post' : $_REQUEST['post_type'];
		}
		add_filter('manage_media_columns', array($this, 'manage_screen_columns'), 100);
		add_action('manage_media_custom_column', array($this, 'column_default'), 10, 2);
		add_action('manage_' . $typenow . '_posts_custom_column', array($this, 'column_default'), 10, 2);
		add_filter("manage_edit-{$typenow}_columns", array($this, 'manage_screen_columns'), 100);
	}

	/**
	 * Add admin head hooks.
	 */
	public function admin_head() {
		global $typenow;
		if ($typenow == 'attachment') {
			add_filter('manage_upload_sortable_columns', array($this, 'sort_author_column'));
		} else {
			add_filter('manage_edit-' . $typenow . '_sortable_columns', array($this, 'sort_author_column'));
		}

		add_action('restrict_manage_posts', function ($post_type, $which) {
			$screen = get_current_screen();
			if (in_array('xauthor', get_hidden_columns($screen)) || in_array('author', get_hidden_columns($screen))) {
				return;
			}
			if (!isset(get_column_headers($screen)['xauthor']) && !isset(get_column_headers($screen)['author'])) {
				return;
			}
			global $wpdb;
			// $sql = 'SELECT DISTINCT '.$wpdb->posts.'.post_author as ID, IF('.$wpdb->users.'.display_name IS NULL or '.$wpdb->users.'.display_name = "", '.$wpdb->users.'.user_login, '.$wpdb->users.'.display_name) as display_name from '.$wpdb->posts
			$sql = 'SELECT DISTINCT ' . $wpdb->posts . '.post_author as ID, ' . $wpdb->users . '.display_name from ' . $wpdb->posts
			      . ' INNER JOIN ' . $wpdb->users . ' ON ' . $wpdb->users . '.ID = ' . $wpdb->posts . '.post_author'
			      . ' WHERE ' . $wpdb->posts . '.post_type = "' . $post_type . '" AND ' . $wpdb->posts . '.post_author > 0'
			      . ' ORDER BY ' . $wpdb->users . '.display_name';
			// .' ORDER BY display_name';
			$users = $wpdb->get_results($sql);
			$output = '';
			if (count($users) <= 1) {
				return;
			}
			$name = 'author';
			$id = 'author';
			$show_option_all = __('Author');
			$output = "<select name='{$name}' id='{$id}'>\n";
			$output .= "\t<option value=''>$show_option_all</option>\n";
			foreach ((array) $users as $user) {
				$_selected = selected($user->ID, $_GET['author'], false);
				$output .= "\t<option value='$user->ID'$_selected>" . esc_html($user->display_name) . "</option>\n";
			}
			$output .= "</select>";
			echo $output;
		}, -999, 2);
	}

	/**
	 * Manage screen columns.
	 *
	 * @param array $columns Columns array.
	 * @return array
	 */
	public function manage_screen_columns($columns) {
		$keys = array_keys($columns);
		foreach ($keys as $i => $key) {
			if (in_array($key, array('author'))) {
				$keys[$i] = 'x' . $key;
			}
		}
		$columns = array_combine($keys, $columns);
		// echo '<pre>'.var_export($columns,true).'</pre>';
		return $columns;
	}

	/**
	 * Display column content.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function column_default($column_name, $post_id) {
		if (!in_array($column_name, array('xauthor', 'author'))) {
			return;
		}
		$author_id = get_post_field('post_author', $post_id);
		$author = get_the_author_meta('display_name', $author_id);
		$post = get_post($post_id);
		$args = array('author' => $author_id);
		if (!in_array($post->post_type, array('post', 'attachment'))) {
			$args['post_type'] = $post->post_type;
		}
		if (!defined('DOING_AJAX')) {
			if ($_GET['author'] == $author_id) {
				$author = '<a href="' . remove_query_arg('author') . '"><b>' . $author . '</b></a>';
			} else {
				$author = $this->get_edit_link($args, $author);
			}
		}
		echo $author;
	}

	/**
	 * Get edit link.
	 *
	 * @param array  $args  Query args.
	 * @param string $label Link label.
	 * @param string $class Link class.
	 * @return string
	 */
	protected function get_edit_link($args, $label, $class = '') {
		// if(!empty($_REQUEST['author'])) $args['author'] = $_REQUEST['author'];

		// $url = add_query_arg( $args, 'edit.php' );
		$url = remove_query_arg('paged', add_query_arg($args));

		$class_html = $aria_current = '';
		if (!empty($class)) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr($class)
			);

			if ('current' === $class) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url($url),
			$class_html,
			$aria_current,
			$label
		);
	}

	/**
	 * Make author column sortable.
	 *
	 * @param array $columns Columns array.
	 * @return array
	 */
	public function sort_author_column($columns) {
		$columns['xauthor'] = array('author', 0);
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
		if (strpos($clauses['orderby'], $wpdb->posts . '.post_author') === false) {
			return $clauses;
		}
		// $clauses['fields'] .= ', IF('.$wpdb->users.'.display_name IS NULL or '.$wpdb->users.'.display_name = "", '.$wpdb->users.'.user_login, '.$wpdb->users.'.display_name) as display_name';
		$clauses['fields'] .= ', ' . $wpdb->users . '.display_name';
		$clauses['join']  .= ' LEFT JOIN ' . $wpdb->users . ' ON ' . $wpdb->posts . '.post_author = ' . $wpdb->users . '.ID ';
		// $clauses['orderby'] = 'display_name '.$_GET['order'];
		$clauses['orderby'] = $wpdb->users . '.display_name ' . $_GET['order'];
		// if(is_super_admin()) echo '<pre>'.var_export($clauses,true).'</pre>';
		return $clauses;
	}
}

author_column::instance();
