<?php
/*
    Plugin Name: Post modified column
    Description: Adds sortable post modified column to edit.php and upload.php
    Version: 1.3
    Plugin URI: https://github.com/lophas/admin-custom-columns
    GitHub Plugin URI: https://github.com/lophas/admin-custom-columns
    Author: Attila Seres
    Author URI:
*/
if (!class_exists('modified_column')) :
  class modified_column
  {
      private static $_instance;
      public static function instance()
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
          if(empty($GLOBALS['typenow'])) if (defined('DOING_AJAX') || in_array($GLOBALS['pagenow'],['edit.php','upload.php'])) {
              $GLOBALS['typenow'] = empty($_REQUEST['post_type']) ? ($GLOBALS['pagenow'] == 'upload.php' ? 'attachment' : 'post') : $_REQUEST['post_type'];
          }
          global $typenow;
          if($typenow == 'attachment') {
            add_filter('manage_media_columns', array($this,'last_modified_column_head'), 11);
            add_filter('manage_upload_sortable_columns', array($this,'sort_last_modified_column'));
            add_action('manage_media_custom_column', array($this,'do_last_modified_column'), 10, 2);
          } else {
            add_filter('manage_'.$typenow.'_posts_columns', array($this,'last_modified_column_head'), 11);
            add_filter('manage_edit-'.$typenow.'_sortable_columns', array($this,'sort_last_modified_column'));
            add_action('manage_'.$typenow.'_posts_custom_column', array($this,'do_last_modified_column'), 10, 2);
          }
      }

      //post_modified column
      public function last_modified_column_head($columns)
      {
          $modified = ['modified' => __('Last Modified')];
          if (($pos   = array_search('date', array_keys($columns))) !== false) {
              $columns = array_merge(
                    array_slice($columns, 0, $pos+1),
                    $modified,
                    array_slice($columns, $pos+1)
                );
          } else {
              $columns = array_merge($columns, $modified);
          } ?><style>.column-modified{width:10%}</style><?php
      return $columns;
      }
      public function do_last_modified_column($column_name, $post_id)
      {
          if ($column_name != 'modified') {
              return;
          }
          //			get_post_field( 'post_modified', $post_id, 'raw' );
          $post = get_post($post_id);
          if ('0000-00-00 00:00:00' === $post->post_modified) {
              $t_time = $h_time = __('Unpublished');
              $time_diff = 0;
              return $t_time;
          } else {
              $t_time = get_the_modified_date(__('Y/m/d g:i:s a'));
              $time = get_post_modified_time('G', true, $post);

              $time_diff = time() - $time;

              if ($time_diff > 0 && $time_diff < DAY_IN_SECONDS) {
                  $date = sprintf(__('%s ago'), human_time_diff($time));
              } else {
                  $date = $t_time;
              }
          }
          $link = add_query_arg(array( 'orderby' => 'modified', 'm' => substr($post->post_modified, 0, 4).substr($post->post_modified, 5, 2), 'day'=> substr($post->post_modified, 8, 2) )) ;
          //$link = get_day_link( substr($post->post_date,0,4), substr($post->post_date,5,2), substr($post->post_date,8,2) );
          echo '<a href="' . esc_url($link) . '" rel="bookmark" title="'.$t_time.'">'.$date.'</a>';
      }
      public function sort_last_modified_column($columns)
      {
          $columns['modified'] = array('modified',1);
          return $columns;
      }
  }
  modified_column::instance();
endif;
