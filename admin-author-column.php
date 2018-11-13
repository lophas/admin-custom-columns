<?php
/*
    Plugin Name: Post author column and dropdown filter
    Description: Adds improved post author column and dropdown to edit.php and upload.php
    Version: 1.0
    Plugin URI: https://github.com/lophas/admin-custom-columns
    GitHub Plugin URI: https://github.com/lophas/admin-custom-columns
    Author: Attila Seres
    Author URI:
*/
if (!class_exists('author_column')) :
class author_column
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
            add_action('load-edit.php', [$this, 'load']);
            add_action('load-upload.php', [$this, 'load']);
            add_action('admin_init', array($this,'admin_init'));
        }
    }
    public function load()
    {
        add_action('admin_head', array($this, 'admin_head'));
        add_filter('posts_clauses', array($this, 'posts_clauses'));
    }
    public function admin_init()
    {
        if (empty($GLOBALS['typenow'])) {
            if (defined('DOING_AJAX') || in_array($GLOBALS['pagenow'], ['edit.php','upload.php'])) {
                $GLOBALS['typenow'] = empty($_REQUEST['post_type']) ? ($GLOBALS['pagenow'] == 'upload.php' ? 'attachment' : 'post') : $_REQUEST['post_type'];
            }
        }
        global $typenow;
        if ($typenow == 'attachment') {
            add_filter('manage_upload_sortable_columns', array($this,'sort_author_column'));
            add_filter('manage_media_columns', array($this,'manage_screen_columns'), 100);
            add_action('manage_media_custom_column', array($this,'column_default'), 10, 2);
        } else {
            add_filter('manage_edit-'.$typenow.'_sortable_columns', array($this,'sort_author_column'));
            add_filter("manage_edit-".$typenow."_columns", [$this, 'manage_screen_columns'], 100);
            add_action('manage_'.$typenow.'_posts_custom_column', array($this,'column_default'), 10, 2);
        }
    }

    public function admin_head()
    {
        add_action('restrict_manage_posts', function ($post_type, $which) {
            $screen = get_current_screen();
            if (in_array('xauthor', get_hidden_columns($screen)) || in_array('author', get_hidden_columns($screen))) {
                return;
            }
            if (!isset(get_column_headers($screen)['xauthor']) && !isset(get_column_headers($screen)['author'])) {
                return;
            }
            global $wpdb;
//            $sql = 'SELECT DISTINCT '.$wpdb->posts.'.post_author as ID, IF('.$wpdb->users.'.display_name IS NULL or '.$wpdb->users.'.display_name = "", '.$wpdb->users.'.user_login, '.$wpdb->users.'.display_name) as display_name from '.$wpdb->posts
            $sql = 'SELECT DISTINCT '.$wpdb->posts.'.post_author as ID, '.$wpdb->users.'.display_name from '.$wpdb->posts
                  .' INNER JOIN '.$wpdb->users.' ON '.$wpdb->users.'.ID = '.$wpdb->posts.'.post_author'
                  .' WHERE '.$wpdb->posts.'.post_type = "'.$post_type.'" AND '.$wpdb->posts.'.post_author > 0'
                  .' ORDER BY '.$wpdb->users.'.display_name';
//      .' ORDER BY display_name';
            $users = $wpdb->get_results($sql);
            $output = '';
            if (count($users) <= 1) {
                return;
            }
            $name = 'author' ;
            $id = 'author' ;
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

    //override default taxonomy columns to add post_status and date args to term links
    public function manage_screen_columns($columns)
    {
        $keys = array_keys($columns);
        foreach ($keys as $i => $key) {
            if (in_array($key, ['author'])) {
                $keys[$i] = 'x'.$key;
            }
        }
        $columns = array_combine($keys, $columns);
        //			echo '<pre>'.var_export($columns,true).'</pre>';
        return $columns;
    }

    public function column_default($column_name, $post_id)
    {
        $post = get_post($post_id);
        if ('xauthor' === $column_name || 'author' === $column_name) {
            $args = array(
        'post_type' => $post->post_type,
        'author' => get_the_author_meta('ID')
      );
            echo $this->get_edit_link($args, get_the_author());
        }
    }

    protected function get_edit_link($args, $label, $class = '')
    {
//    if(!empty($_REQUEST['author'])) $args['author'] = $_REQUEST['author'];

//    $url = add_query_arg( $args, 'edit.php' );
        $url = add_query_arg($args);

        $class_html = $aria_current = '';
        if (! empty($class)) {
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
    public function sort_author_column($columns)
    {
        $columns['xauthor'] = array('author',0);
        return $columns;
    }
    public function posts_clauses($clauses)
    {
        global $wpdb;
        if (strpos($clauses['orderby'], $wpdb->posts.'.post_author') === false) {
            return $clauses;
        }
//        $clauses['fields'] .= ', IF('.$wpdb->users.'.display_name IS NULL or '.$wpdb->users.'.display_name = "", '.$wpdb->users.'.user_login, '.$wpdb->users.'.display_name) as display_name';
        $clauses['fields'] .= ', '.$wpdb->users.'.display_name';
        $clauses['join']  .= ' LEFT JOIN ' . $wpdb->users . ' ON '.$wpdb->posts.'.post_author = ' . $wpdb->users . '.ID ';
//        $clauses['orderby'] = 'display_name '.$_GET['order'];
        $clauses['orderby'] = $wpdb->users.'.display_name '.$_GET['order'];
        //if(is_super_admin()) echo '<pre>'.var_export($clauses,true).'</pre>';
        return $clauses;
    }
}
author_column::instance();
endif;
