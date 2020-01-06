<?php
/*
    Plugin Name: Post date column and day dropdown filter
    Description: Adds improved date column and dropdown to edit.php and upload.php
    Version: 1.1
    Plugin URI: https://github.com/lophas/admin-custom-columns
    GitHub Plugin URI: https://github.com/lophas/admin-custom-columns
    Author: Attila Seres
    Author URI:
*/
if (!class_exists('date_column')) :
  class date_column
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
              add_action('admin_head-upload.php', array($this, 'admin_head'));
              add_action('admin_head-edit.php', array($this, 'admin_head'));
          }
      }

      public function admin_head()
      {
          add_filter('post_date_column_time', array($this,'column_date'), 10, 4);
          add_filter( 'post_date_column_status', '__return_false' );
          if (!has_filter('disable_months_dropdown', '__return_true')) {
              add_filter('disable_months_dropdown', '__return_true');
              add_action('restrict_manage_posts', [$this, 'months_dropdown'], 999);
          }
      }

      //date column
      public function column_date($h_time, $post, $dummy, $mode)
      {
          if ('0000-00-00 00:00:00' === $post->post_date) {
              $t_time = $h_time = __('Unpublished');
              $time_diff = 0;
              return $t_time;
          } else {
              $t_time = get_the_time(__('Y/m/d g:i:s a'));
              $time = get_post_time('G', true, $post);

              $time_diff = time() - $time;

              if ($time_diff > 0 && $time_diff < DAY_IN_SECONDS) {
                  $date = sprintf(__('%s ago'), human_time_diff($time));
              } else {
                  $date = $t_time;
              }
          }
          $link = add_query_arg(array( 'm' => substr($post->post_date, 0, 4).substr($post->post_date, 5, 2), 'day'=> substr($post->post_date, 8, 2) )) ;
          //$link = get_day_link( substr($post->post_date,0,4), substr($post->post_date,5,2), substr($post->post_date,8,2) );
          return '<a href="' . esc_url($link) . '" rel="bookmark" title="'.$t_time.'">'.$date.'</a>';
      }

      public function months_dropdown($post_type = null)
      {
          global $wpdb, $wp_locale;

          if (!$post_type) {
              $post_type = $GLOBALS['typenow'];
          }

          $extra_checks = "AND post_status != 'auto-draft'";
          if (! isset($_GET['post_status']) || 'trash' !== $_GET['post_status']) {
              $extra_checks .= " AND post_status != 'trash'";
          } elseif (isset($_GET['post_status'])) {
              $extra_checks = $wpdb->prepare(' AND post_status = %s', $_GET['post_status']);
          }

          $months = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
                FROM $wpdb->posts
                WHERE post_type = %s
                $extra_checks
                ORDER BY post_date DESC
            ", $post_type));

          /**
           * Filters the 'Months' drop-down results.
           *
           * @since 3.7.0
           *
           * @param object $months    The months drop-down query results.
           * @param string $post_type The post type.
           */
          $months = apply_filters('months_dropdown_results', $months, $post_type);

          $month_count = count($months);

          if (!$month_count || (1 == $month_count && 0 == $months[0]->month)) {
              return;
          }

          $m = isset($_GET['m']) ? (int) $_GET['m'] : 0; ?>
            <label for="filter-by-date" class="screen-reader-text"><?php _e('Filter by date'); ?></label>
            <select name="m" id="filter-by-date">
                <option<?php selected($m, 0); ?> value="0"><?php _e('All dates'); ?></option>
    <?php
            foreach ($months as $arc_row) {
                if (0 == $arc_row->year) {
                    continue;
                }

                $month = zeroise($arc_row->month, 2);
                $year = $arc_row->year;

                printf(
                    "<option %s value='%s'>%s</option>\n",
                    selected($m, $year . $month, false),
                    esc_attr($arc_row->year . $month),
                    /* translators: 1: month name, 2: 4-digit year */
                    sprintf(__('%1$s %2$d'), $wp_locale->get_month($month), $year)
                );
            } ?>
            </select>
    <?php
             $this->days_dropdown();
      }

      public function days_dropdown()
      {
          $day = (int) $_GET['m'] ? (isset($_GET['day']) ? (int) $_GET['day'] : 0) : 0; ?>
            <select name="day" id="filter-by-day">
                <option<?php selected($day, 0); ?> value="0"><?php _e('All days'); ?></option>
    <?php
            for ($i=1;$i<=31;$i++) {
                printf(
                    "<option %s value='%s'>%s</option>\n",
                    selected($day, $i, false),
                    $i,
                    $i
                );
            } ?>
            </select>
    <?php
      }
  }
  date_column::instance();
endif;
