<?php
/*
    Plugin Name: Admin columns
    Description: Adds custom columns to the WordPress admin edit screen
    Version: 1.5
    Plugin URI:
    Author: Attila Seres
    Author URI: //lophas.github.io
    License: GPL2
*/
if (!class_exists('admin_columns')) :
/**
 * Main plugin class for Admin Columns.
 *
 * Adds custom fields to quick edit and bulk edit.
 */
class admin_columns {
	/**
	 * Singleton instance.
	 *
	 * @var admin_columns
	 */
	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return admin_columns
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
		add_action('admin_init', function() {
			add_action('admin_footer-edit.php', array($this, 'admin_footer'));
			add_action('admin_footer-upload.php', array($this, 'admin_footer'));
		});
	}

	/**
	 * Add custom fields to admin footer.
	 */
	public function admin_footer() {
		$filter_keys = array_filter(array_diff(array_keys($_GET), array('post_type', 'mode', 'post_status', 'all_posts')));
		?>
		<div id="inline-custom-fields" style="display:none">
		<?php do_action('quick_edit_custom_box_fields', $GLOBALS['typenow']) ?>
		</div>
		<div id="bulk-custom-fields" style="display:none">
		<?php do_action('bulk_edit_custom_box_fields', $GLOBALS['typenow']) ?>
		</div>
		<script>
		jQuery('#inline-edit').find('.inline-edit-col').last().append(jQuery('#inline-custom-fields').html());
		jQuery('#inline-custom-fields').remove();
		jQuery('#bulk-edit').find('.inline-edit-col').last().append(jQuery('#bulk-custom-fields').html());
		jQuery('#bulk-custom-fields').remove();
		<?php if (!empty($filter_keys)) : ?>
		jQuery('input[name=filter_action]').after('<a href="<?php echo esc_url(remove_query_arg($filter_keys)) ?>" class="button"><?php _e('Clear current filters') ?></a>');
		<?php endif ?>
		</script>
		<?php
	}
}

admin_columns::instance();

$incdir = substr(__FILE__, 0, -4);
if (file_exists($incdir)) {
	foreach (glob($incdir . '/*.php') as $file) {
		require_once($file);
	}
}
endif;
