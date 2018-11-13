<?php
/*
    Plugin Name: Post thumbnail column
    Description: Adds post thumbnail column to edit.php
    Version: 1.0
    Plugin URI: https://github.com/lophas/admin-custom-columns
    GitHub Plugin URI: https://github.com/lophas/admin-custom-columns
    Author: Attila Seres
    Author URI:
*/
if (!class_exists('thumbnail_column')) :
class thumbnail_column
{
    private static $_instance;
    public function instance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance =  new self();
        }
        return self::$_instance;
    }
    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_init', array($this,'admin_init'));
        }
    }
    public function admin_init()
    {
        if ((defined('DOING_AJAX') || $GLOBALS['pagenow'] == 'edit.php') && empty($GLOBALS['typenow'])) {
            $GLOBALS['typenow'] = empty($_REQUEST['post_type']) ? 'post' : $_REQUEST['post_type'];
        }
        global $typenow;
        add_filter('manage_'.$typenow.'_posts_columns', array($this,'thumbnail_column_head'), 1000000000000001);
    }

    //thumbnail column
    public function thumbnail_column_head($columns)
    {
        if (isset($columns['thumbnail'])) {
            return $columns;
        }
        if (!post_type_supports($GLOBALS['typenow'], 'thumbnail')) {
            return $columns;
        }
        $columns['thumbnail'] = __('Thumbnail');
        add_action('manage_posts_custom_column', array($this,'do_thumbnail_column')); ?><style>.column-thumbnail{width:10%}.column-thumbnail img.wp-post-image{object-fit:cover}</style><?php
            return $columns;
    }

    public function do_thumbnail_column($name)
    {
        global $post;
        switch ($name) {
                case 'thumbnail':
                    $thumbnail = get_the_post_thumbnail($post->ID, array(100,100));
                    echo $thumbnail;
            }
    }
}
thumbnail_column::instance();
endif;
