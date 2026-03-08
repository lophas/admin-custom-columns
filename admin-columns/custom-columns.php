<?php
/**
 * Admin Custom Columns class.
 *
 * Adds post columns and dropdown filters for custom types and taxonomies.
 *
 * @version 4.4
 */
class admin_custom_columns {
	const OPTIONS   = 'custom_columns';
	const ADMINSLUG = __CLASS__;

	/**
	 * Taxonomies data.
	 *
	 * @var array
	 */
	private $taxonomies;

	/**
	 * Singleton instance.
	 *
	 * @var admin_custom_columns
	 */
	private static $_instance;

	/**
	 * Get singleton instance.
	 *
	 * @return admin_custom_columns
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
		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_head-edit.php', array( $this, 'admin_head_edit' ) );
		add_action( 'admin_head-upload.php', array( $this, 'admin_head_edit' ) );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}
	/**
	 * Admin init.
	 */
	public function admin_init() {
		global $typenow, $pagenow;
		if ( 'upload.php' == $pagenow ) {
			$typenow = 'attachment';
		} elseif ( empty( $typenow ) && ( defined( 'DOING_AJAX' ) || 'edit.php' == $pagenow ) ) {
			$typenow = empty( $_REQUEST['post_type'] ) ? 'post' : $_REQUEST['post_type'];
		}
		$this->get_all_taxonomies();
		if ( 'attachment' == $typenow ) {
			add_filter( 'manage_media_columns', array( $this, 'columnsname' ), 100 );
			add_action( 'manage_media_custom_column', array( $this, 'columnsdata' ), 10, 2 );
		} else {
			add_filter( 'manage_edit-' . $typenow . '_columns', array( $this, 'columnsname' ), 100 );
			add_action( 'manage_' . $typenow . '_posts_custom_column', array( $this, 'columnsdata' ), 10, 2 );
		}
	}

	/**
	 * Admin head edit.
	 */
	public function admin_head_edit() {
		add_filter( 'wp_dropdown_cats', array( $this, 'wp_dropdown_cats_category_disable' ), 10, 2 );
		$taxonomies = $this->get_taxonomies( $GLOBALS['typenow'] );
		if ( ! empty( $taxonomies ) ) {
			add_action( 'restrict_manage_posts', array( $this, 'wp_dropdown_taxes' ), 1 );
		}
	}


	/**
	 * Get all taxonomies.
	 *
	 * @return array
	 */
	public function get_all_taxonomies() {
		if ( ! is_array( $this->taxonomies ) ) {
			$this->taxonomies = array();
			$args             = array(
				// 'public'   => true,
				// 'publicly_queryable'   => true,
				'show_ui' => true,
			);
			// echo '<pre>'.var_export(get_post_types( $args, 'names' ),true).'</pre>';
			foreach ( get_post_types( $args, 'names' ) as $post_type ) {
				$this->get_taxonomies( $post_type );
			}
			// if(is_super_admin()) die('<pre>'.var_export($this->taxonomies,true).'</pre>');
		}
		// if(is_super_admin()) echo ('<pre>'.var_export($this->taxonomies,true).'</pre><hr>');
		return $this->taxonomies;
	}

	/**
	 * Get taxonomies for a post type.
	 *
	 * @param string $post_type Post type.
	 * @return array
	 */
	public function get_taxonomies( $post_type ) {
		// echo $post_type.'<hr>';
		global $wp_taxonomies;
		if ( ! isset( $this->taxonomies[ $post_type ] ) ) {
			$this->taxonomies[ $post_type ] = array();
			$columns                        = $this->get_option( 'disabled', array() );
			// if(is_super_admin()) echo '<pre>'.var_export($columns[$post_type],true).'</pre><hr>';
			if ( ! isset( $columns[ $post_type ] ) ) {
				$cols = array();
			}
			foreach ( get_object_taxonomies( $post_type ) as $slug ) {
				if ( 'post' === $post_type ) {
					if ( 'post_format' === $slug ) {
						if ( ! current_theme_supports( 'post-formats' ) ) {
							continue;
						}
					}
					// if(in_array($slug, array('category'))) continue;
				}
				$tax = get_taxonomy( $slug );
				// if($slug == 'post_format') if(is_super_admin()) echo '<pre>'.var_export($tax,true).'</pre><hr>';
				if ( ! $tax->public || ! $tax->publicly_queryable ) {
					continue;
				}
				// if(!$tax->show_ui) continue;
				$this->taxonomies[ $post_type ][ $tax->name ] = $tax;
				if ( isset( $columns[ $post_type ] ) ) {
					$show_admin_column = ! in_array( $this->column_key( $tax->name ), $columns[ $post_type ] );
					if ( (bool) $tax->show_admin_column !== $show_admin_column ) {
						// $args = (array)$tax;
						// $args['show_admin_column']=$show_admin_column;
						// register_taxonomy( $tax->name, array( $post_type ), $args );
						$wp_taxonomies[ $slug ]->show_admin_column = $show_admin_column;
						$this->taxonomies[ $post_type ][ $tax->name ]->show_admin_column = $show_admin_column;
					}
				} elseif ( $tax->show_admin_column ) {
					$cols[] = $this->column_key( $tax->name );
				}
			}
			if ( ! isset( $columns[ $post_type ] ) ) {
				$columns[ $post_type ] = $cols;
				// $this->update_option('columns',$columns);
			}
			// if(is_super_admin()) echo '<pre>'.var_export($columns[$post_type],true).'</pre><hr>';
		}
		return $this->taxonomies[ $post_type ];
	}
       /*
       function wp_dropdown_cats_option_all_fix($output, $r) {
       //die(htmlspecialchars(str_replace("<option value='0'","<option value=''",$output)));
           if(!empty($r['show_option_all'])) $output = str_replace("<option value='0'","<option value=''",$output);
           return $output;
       }
       */

	/**
	 * Get column key for taxonomy.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string
	 */
	public function column_key( $taxonomy ) {
		switch ( $taxonomy ) {
			case 'category':
				return 'xcategories';
			case 'post_tag':
				return 'xtags';
			default:
				return 'xtaxonomy-' . $taxonomy;
		}
	}

	/**
	 * WP dropdown taxes.
	 */
	public function wp_dropdown_taxes() {
		// add_filter( 'wp_dropdown_cats', array($this, 'wp_dropdown_cats_option_all_fix'),10,2 );
		global $typenow;
		foreach ( $this->taxonomies[ $typenow ] as $name => $tax ) {
			// if(is_super_admin()) echo '<pre>'.var_export([$tax->query_var, $tax->name],true).'</pre>';
			if ( $this->taxonomies[ $typenow ][ $tax->name ]->show_admin_column ) {
				$name = $tax->query_var ? $tax->query_var : $tax->name;
				if ( ! in_array( $this->column_key( $tax->name ), get_hidden_columns( get_current_screen() ) ) && $tax->hierarchical ) {
					$term = isset( $_GET[ $name ] ) ? $_GET[ $name ] : ( $_GET['taxonomy'] === $name ? $_GET['term'] : '' );
					wp_dropdown_categories(
						array(
							'show_option_none' => __( $tax->labels->all_items ? $tax->labels->all_items : $tax->label ),
							'option_none_value' => '',
							'taxonomy'         => $tax->name,
							'name'             => $name, // $tax->query_var ? $tax->query_var : $tax->name,
							'orderby'          => 'name',
							'selected'         => $term,
							'hierarchical'     => $tax->hierarchical,
							'show_count'       => true,
							'hide_empty'       => $_GET['post_status'] === 'publish' ? true : false,
							'value_field'      => 'slug',
						)
					);
				}
			}
		}
		// remove_filter( 'wp_dropdown_cats', array($this, 'wp_dropdown_cats_option_all_fix'),10,2 );
	}


	/**
	 * WP dropdown cats category disable.
	 *
	 * @param string $output Output.
	 * @param array  $r      Arguments.
	 * @return string
	 */
	public function wp_dropdown_cats_category_disable( $output, $r ) {
		if ( 'category' !== $r['taxonomy'] ) {
			return $output;
		}
		// if(!$this->is_called_from('extra_tablenav')) return $output;
		// echo '<pre>'.var_export($r,true).'</pre>';
		remove_filter( 'wp_dropdown_cats', array( $this, __FUNCTION__ ), 10, 2 );
		return '';
	}


       /*
       function is_called_from($function) {
          return in_array($function,array_map(function($a) {return $a['function'];},debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)));
       }
       */

	/**
	 * Override default taxonomy columns to add post_status and date args to term links.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function columnsname( $columns ) {
		$keys = array_keys( $columns );
		foreach ( $keys as $i => $key ) {
			if ( in_array( $key, array( 'tags', 'categories' ) ) || substr( $key, 0, 9 ) === 'taxonomy-' ) {
				$keys[ $i ] = 'x' . $key;
			}
		}
		$columns = array_combine( $keys, $columns );
		// echo '<pre>'.var_export($columns,true).'</pre>';
		return $columns;
	}

	/**
	 * Columns data.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     Post ID.
	 */
	public function columnsdata( $column_name, $post_id ) {
		$post = get_post( $post_id );
		if ( 'xcategories' === $column_name ) {
			$taxonomy = 'category';
			$taxname  = 'category_name';
		} elseif ( 'xtags' === $column_name ) {
			$taxonomy = 'post_tag';
			$taxname  = 'tag';
		} elseif ( 0 === strpos( $column_name, 'xtaxonomy-' ) ) {
			$taxname  = $taxonomy = substr( $column_name, 10 );
		} else {
			$taxonomy = false;
		}
		if ( $taxonomy ) {
			$taxonomy_object = get_taxonomy( $taxonomy );
			$terms           = get_the_terms( $post->ID, $taxonomy );
			if ( is_array( $terms ) ) {
				$out = array();
				foreach ( $terms as $t ) {
					$posts_in_term_qv = array();
					if ( ! in_array( $post->post_type, array( 'post', 'attachment' ) ) ) {
						$posts_in_term_qv['post_type'] = $post->post_type;
					}
					if ( $taxonomy_object->query_var ) {
						$posts_in_term_qv[ $taxonomy_object->query_var ] = $t->slug;
					} else {
						$posts_in_term_qv[ $taxonomy ] = $t->slug;
						// $posts_in_term_qv['taxonomy'] = $taxonomy;
						// $posts_in_term_qv['term'] = $t->slug;
					}

					$label = esc_html( sanitize_term_field( 'name', $t->name, $t->term_id, $taxonomy, 'display' ) );
					if ( ! defined( 'DOING_AJAX' ) ) {
						if ( $_GET[ $taxname ] === $t->slug || ( $_GET['taxonomy'] === $taxonomy && $_GET['term'] === $t->slug ) ) {
							$label = '<a href="' . remove_query_arg( array( 'taxonomy', 'term', $taxname ) ) . '"><b>' . $label . '</b></a>';
						} else {
							$label = $this->get_edit_link( $posts_in_term_qv, $label );
						}
					}
					$out[] = $label;
				}
				/* translators: used between list items, there is a space after the comma */
				echo join( __( ', ' ), $out );
			} else {
				echo '<span aria-hidden="true">&#8212;</span><span class="screen-reader-text">' . $taxonomy_object->labels->no_terms . '</span>';
			}
			return;
		}
	}

	/**
	 * Get edit link.
	 *
	 * @param array  $args  Arguments.
	 * @param string $label Label.
	 * @param string $class Class.
	 * @return string
	 */
	protected function get_edit_link( $args, $label, $class = '' ) {
		// $url = add_query_arg( $args, 'edit.php' );
		$url = remove_query_arg( array( 'paged', 's', '_ajax_nonce' ), add_query_arg( $args ) );

		$class_html    = $aria_current = '';
		if ( ! empty( $class ) ) {
			$class_html = sprintf(
				' class="%s"',
				esc_attr( $class )
			);

			if ( 'current' === $class ) {
				$aria_current = ' aria-current="page"';
			}
		}

		return sprintf(
			'<a href="%s"%s%s>%s</a>',
			esc_url( $url ),
			$class_html,
			$aria_current,
			$label
		);
	}




	/**
	 * Admin menu.
	 */
	public function admin_menu() {
		add_filter( 'plugin_action_links_' . self::ADMINSLUG, array( $this, 'add_settings_link' ) );
		$plugin_page = add_options_page( __( 'Columns' ), __( 'Custom columns' ), 'manage_options', self::ADMINSLUG, array( $this, 'options_page' ) );
		if ( class_exists( 'download_plugin' ) ) {
			new download_plugin( $plugin_page );
		}
		register_setting( self::ADMINSLUG, self::OPTIONS, array( $this, 'validate_options' ) );
		add_settings_section( 'default', '', false, self::ADMINSLUG );
		add_settings_field( 'columns_id', __( 'Enable admin columns' ), array( $this, 'columns_field' ), self::ADMINSLUG, 'default' );
	}

	/**
	 * Add settings link.
	 *
	 * @param array $links Links.
	 * @return array
	 */
	public function add_settings_link( $links ) {
		$url     = admin_url( 'options-general.php?page=' . self::ADMINSLUG );
		$links[] = '<a href="' . $url . '">' . __( 'Settings' ) . '</a>';
		return $links;
	}

	/**
	 * Options page.
	 */
	public function options_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h1><?php _e( 'Custom columns Options' ); ?></h1>
			<form method="POST" action="options.php">
				<?php
				settings_fields( self::ADMINSLUG );
				do_settings_sections( self::ADMINSLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
		$options = self::get_option();
		// echo '<pre>'.var_export($options,true).'</pre>';
		// echo '<pre>'.var_export($this->get_all_taxonomies(),true).'</pre>';
	}

	/**
	 * Validate options.
	 *
	 * @param array $options Options.
	 * @return array
	 */
	public function validate_options( $options ) {
		// echo '<pre>'.var_export($options,true).'</pre>';//die();
		$columns = array_map(
			function ( $taxes ) {
				return array_keys( $taxes );
			},
			$this->get_all_taxonomies()
		);
		// echo '<pre>'.var_export($columns,true).'</pre>';die();
		foreach ( $columns as $post_type => $taxes ) {
			$options['disabled'][ $post_type ] = array_values(
				array_diff(
					array_map(
						function ( $tax ) {
							return $this->column_key( $tax );
						},
						(array) $columns[ $post_type ]
					),
					(array) $options['disabled'][ $post_type ]
				)
			);
		}
		// echo '<pre>'.var_export($options,true).'</pre>';die();
		return $options;
	}
	/**
	 * Columns field.
	 */
	public function columns_field() {
		// delete_option(self::OPTIONS);//die('<pre>'.var_export(get_option(self::OPTIONS, array()),true).'</pre>');
		// $this->get_all_taxonomies();
		$columns = $this->get_option( 'disabled', array() );
		// echo '<pre>'.var_export($this->taxonomies,true).'</pre>';
		$id   = 'columns_id';
		$name = self::OPTIONS . '[disabled]';
		foreach ( $this->taxonomies as $post_type => $taxonomies ) :
			if ( ! empty( $taxonomies ) ) :
				// if(is_super_admin()) echo ('<pre>'.var_export($taxonomies,true).'</pre>');
				$post_type_object = get_post_type_object( $post_type );
				?>
				<p><b><?php echo __( $post_type_object->labels->name ); ?>:</b></p>
				<?php
				foreach ( $taxonomies as $tax ) :
					?>
					<p><input class="checkbox" type="checkbox"<?php checked( ! in_array( $this->column_key( $tax->name ), (array) $columns[ $post_type ] ) ); ?> name="<?php echo $name; ?>[<?php echo $post_type; ?>][]" value="<?php echo $this->column_key( $tax->name ); ?>" /><b><?php echo $tax->labels->name; ?></b></p>
					<?php
				endforeach;
			endif;
		endforeach;
	}

	/**
	 * Get option.
	 *
	 * @param string $field   Field.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	public function get_option( $field = null, $default = null ) {
		// delete_option(self::OPTIONS);
		$options = get_option( self::OPTIONS );
		if ( empty( $options ) ) {
			$options = array( 'disabled' => array() );
			update_option( self::OPTIONS, $options );
		}
		if ( isset( $options['columns'] ) && ! isset( $options['disabled'] ) ) {
			$columns = array_map(
				function ( $taxes ) {
					return array_keys( $taxes );
				},
				$this->get_all_taxonomies()
			);
			// echo '<pre>'.var_export($columns,true).'</pre>';
			foreach ( $options['columns'] as $post_type => $taxes ) {
				$options['disabled'][ $post_type ] = array_values(
					array_diff(
						array_map(
							function ( $tax ) {
								return $this->column_key( $tax );
							},
							(array) $columns[ $post_type ]
						),
						$options['columns'][ $post_type ]
					)
				);
			}
			unset( $options['columns'] );
			update_option( self::OPTIONS, $options );
		}
		return $field ? ( isset( $options[ $field ] ) ? $options[ $field ] : $default ) : $options;
	}
	/**
	 * Update option.
	 *
	 * @param string $field Field.
	 * @param mixed  $value Value.
	 */
	public function update_option( $field, $value ) {
		$options         = $this->get_option();
		$options[ $field ] = $value;
		$options         = update_option( self::OPTIONS, $options );
	}
}
admin_custom_columns::instance();
