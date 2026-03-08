<?php
/**
 * Excerpt Column class.
 *
 * Handles excerpt in quick edit.
 *
 * @version 1.3
 */
class excerpt_column {
	/**
	 * Singleton instance.
	 *
	 * @var excerpt_column
	 */
	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return excerpt_column
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
			add_action('admin_init', array($this, 'admin_init'));
		}
	}

	/**
	 * Initialize admin hooks.
	 */
	public function admin_init() {
		global $pagenow, $typenow;
		if (!in_array($pagenow, array('edit.php', 'upload.php')) && !defined('DOING_AJAX')) {
			return;
		}
		if ($pagenow == 'upload.php') {
			$typenow = 'attachment';
		} elseif (empty($typenow)) {
			$typenow = empty($_REQUEST['post_type']) ? 'post' : $_REQUEST['post_type'];
		}
		if (!post_type_supports($typenow, 'excerpt') && $typenow !== 'attachment') {
			return;
		}

		add_action('add_inline_data', function ($post, $post_type_object) {
			echo '<div class="post_excerpt">' . esc_textarea(trim($post->post_excerpt)) . '</div>';
		}, 10, 2);

		add_filter('wp_insert_post_data', array($this, 'wp_insert_post_data'), 10, 3);
		add_filter('wp_insert_attachment_data', array($this, 'wp_insert_post_data'), 10, 3);

		if (!in_array($pagenow, array('edit.php', 'upload.php'))) {
			return;
		}
		add_action('quick_edit_custom_box_fields', array($this, 'quick_edit_custom_box_fields'));
		add_action('admin_print_footer_scripts', array($this, 'quick_edit_populate_fields'), 999);
	}

	/**
	 * Insert post data.
	 *
	 * @param array $data              Post data.
	 * @param array $postarr           Post array.
	 * @param array $unsanitized_postarr Unsanitized post array.
	 * @return array
	 */
	public function wp_insert_post_data($data, $postarr, $unsanitized_postarr) {
		if (defined('DOING_AJAX') && $_REQUEST['_inline_edit']) {
			if (isset($_REQUEST['post_excerpt'])) {
				$data['post_excerpt'] = $_REQUEST['post_excerpt'];
			}
		}
		return $data;
	}

	/**
	 * Add quick edit custom box fields.
	 */
	public function quick_edit_custom_box_fields() {
		global $pagenow;
		?>
		<label class="inline-edit-excerpt">
			<span class="excerpt"><?php echo __($pagenow == 'upload.php' ? 'Caption' : 'Excerpt'); ?></span>
			<textarea name="post_excerpt" cols="22" rows="1" class="excerpt"></textarea>
		</label>
		<?php
	}

	/**
	 * Populate quick edit fields.
	 */
	public function quick_edit_populate_fields() {
		?>
		<script>
		(function($) {
			var wp_inline_edit = inlineEditPost.edit;
			inlineEditPost.edit = function( id ) {
				wp_inline_edit.apply( this, arguments );
				var post_id = 0;
				if ( typeof( id ) == 'object' ) {
					post_id = parseInt( this.getId( id ) );
				}
				if ( post_id > 0 ) {
					edit_row = $( '#edit-' + post_id );
					rowData = $('#inline_'+post_id);
					this_field = edit_row.find( '.inline-edit-excerpt' );
					if(this_field.length) {
						this_value = rowData.find('.post_excerpt').text();
						this_field.find('textarea').text(this_value); //instant value
					}
				}
			};
		})(jQuery);
		</script>
		<?php
	}
}

excerpt_column::instance();
