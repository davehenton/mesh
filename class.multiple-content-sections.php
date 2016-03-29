<?php
/**
 * Class Multiple_Content_Sections
 */
class Multiple_Content_Sections {

	/**
	 * Store available templates.
	 *
	 * @var array
	 */
	public $templates = array();

	/**
	 * Store all our TinyMCE Editors
	 * @var array
	 */
	public $tinymce_editors = array();

	/**
	 * Store the available blocks per template.
	 *
	 * @since 1.3.5
	 *
	 * @var array
	 */
	public static $template_data = array(
		'mcs-columns-1.php' => array(
			'label' => '1 Columns',
			'blocks' => 1,
			'widths' => array( 12 ),
		),
		'mcs-columns-2.php' => array(
			'label' => '2 Columns',
			'blocks' => 2,
			'widths' => array( 6, 6 ),
		),
		'mcs-columns-3.php' => array(
			'label' => '3 Columns',
			'blocks' => 3,
			'widths' => array( 4, 4, 4 ),
		),
	) ;

	/**
	 * __construct function.
	 *
	 * @access public
	 */
	function __construct() {

		add_action( 'init', array( $this, 'init' ) );

		add_action( 'edit_page_form', array( $this, 'edit_page_form' ) );     // Pages
		add_action( 'edit_form_advanced', array( $this, 'edit_page_form' ) ); // Other Post Types.

		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );

		add_filter( 'content_edit_pre', array( $this, 'the_content' ) );
		add_filter( 'the_content', array( $this, 'the_content' ), 5 );
		add_filter( 'post_class', array( $this, 'post_class' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );

		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			include_once( 'functions-ajax.php' );
		}

		// Adjust TinyMCE and Media Buttons
		add_action( 'media_buttons', array( $this, 'add_media_buttons' ), 999, 1 );
		add_filter( 'tiny_mce_before_init', array( $this, 'tiny_mce_before_init' ) );

		// Add Screen Options.
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
	}

	/**
	 * Add Screen Options to the Plugin
	 */
	function admin_menu() {
		add_action( 'load-post.php', array( $this, 'add_screen_options' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Save our custom display options
	 *
	 * @since 1.4.4
	 *
	 * @param $status save status
	 * @param $option option we're saving
	 * @param $value  value to save
	 *
	 * @return mixed
	 */
	function set_screen_option( $status, $option, $value ) {
		if ( 'linchpin_mcs_section_kitchensink' === $option ) {
			return $value;
		}
	}

	/**
	 * Add Toggleable Options to show or hide controls
	 *
	 * @since 1.4.4
	 */
	function add_screen_options() {
		$screen = get_current_screen();

		if ( ! is_object( $screen ) || $screen->id !== 'page' ) {
			return;
		}

		$args = array(
			'label'   => __( 'Show Extra MCS Section Controls?', LINCHPIN_MCS_PLUGIN_NAME ),
			'default' => 0,
			'option'  => 'linchpin_mcs_section_kitchensink',
		);

		add_screen_option( 'linchpin_mcs_section_kitchensink', $args );
	}

	/**
	 * Update our tiny MCE w/ our own settings
	 * @param $in
	 */
	function tiny_mce_before_init( $in ) {

		global $post;

		// Exclude the default editor from our customizations.
		if ( '#content' === $in['selector'] ) {
			return $in;
		}

		$in['remove_linebreaks'] = false;
		$in['gecko_spellcheck'] = false;
		$in['keep_styles'] = true;
		$in['accessibility_focus'] = true;
		$in['tabfocus_elements'] = 'major-publishing-actions';
		$in['media_strict'] = false;
		$in['paste_remove_styles'] = false;
		$in['paste_remove_spans'] = false;
		$in['paste_strip_class_attributes'] = 'none';
		$in['paste_text_use_dialog'] = true;
		$in['wpeditimage_disable_captions'] = true;
		$in['plugins'] = 'tabfocus,paste,media,wordpress,wpgallery,wplink';
		$in['content_css'] = get_template_directory_uri() . '/editor-style.css';
		$in['wpautop'] = true;
		$in['apply_source_formatting'] = false;
		$in['block_formats'] = "Paragraph=p; Heading 3=h3; Heading 4=h4";
		$in['toolbar1'] = 'bold,italic,strikethrough,bullist,numlist,blockquote,hr,alignleft,aligncenter,alignright,link,unlink,spellchecker ';
		$in['toolbar2'] = 'formatselect,underline,alignjustify,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help ';
		$in['toolbar3'] = '';
		$in['toolbar4'] = '';

		return $in;
	}

	/**
	 * Add media buttons to our block editors
	 *
	 * @param int $editor_id The ID of the editor we want to add buttons to.
	 */
	function add_media_buttons( $editor_id ) {

		$editor_id = (int) str_replace( 'mcs-section-editor-', '', $editor_id );

		if ( 'mcs_section' === get_post_type( $editor_id ) ) :

			$featured_image_id = get_post_thumbnail_id( $editor_id );

			if ( empty( $featured_image_id ) ) : ?>
				<button class="button mcs-block-featured-image-choose dashicons-before dashicons-format-image"><?php esc_attr_e( 'Set Background Image', 'linchpin-mce' ); ?></button>
			<?php else : ?>
				<button class="button mcs-block-featured-image-choose dashicons-before dashicons-edit" data-mcs-section-featured-image="<?php esc_attr_e( $featured_image_id ); ?>"><?php echo get_the_title( $featured_image_id ); ?></button>
				<button class="button mcs-block-featured-image-trash dashicons-before dashicons-trash" data-mcs-section-featured-image="<?php esc_attr_e( $featured_image_id ); ?>"><?php esc_html_e( 'Remove', 'linchpin-mcs' ); ?></button>
			<?php endif; ?>
		<?php endif;
	}

	/**
	 * Init function.
	 *
	 * @access public
	 * @return void
	 */
	function init() {

		$labels = array(
			'name'                => _x( 'Content Section', 'Content Section', 'linchpin-mcs' ),
			'singular_name'       => _x( 'Content Section', 'Content Section', 'linchpin-mcs' ),
			'menu_name'           => __( 'Content Section', 'linchpin-mcs' ),
			'name_admin_bar'      => __( 'Content Section', 'linchpin-mcs' ),
			'parent_item_colon'   => __( 'Parent Content Section:', 'linchpin-mcs' ),
			'all_items'           => __( 'All Content Sections', 'linchpin-mcs' ),
			'add_new_item'        => __( 'Add New Content Section', 'linchpin-mcs' ),
			'add_new'             => __( 'Add New', 'linchpin-mcs' ),
			'new_item'            => __( 'New Content Section', 'linchpin-mcs' ),
			'edit_item'           => __( 'Edit Content Section', 'linchpin-mcs' ),
			'update_item'         => __( 'Update Content Section', 'linchpin-mcs' ),
			'view_item'           => __( 'View Content Section', 'linchpin-mcs' ),
			'search_items'        => __( 'Search Content Sections', 'linchpin-mcs' ),
			'not_found'           => __( 'Not found', 'linchpin-mcs' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'linchpin-mcs' ),
		);

		register_post_type( 'mcs_section', array(
			'label'               => __( 'Content Section', 'linchpin-mcs' ),
			'description'         => __( 'Content Section', 'linchpin-mcs' ),
			'labels'              => $labels,
			'public' => false,
			'hierarchical' => true,
			'supports' => array( 'title','editor','author','thumbnail','excerpt' ),
			'capability_type' => 'post',
			'has_archive' => false,
			'show_in_menus' => false,
			'show_in_nav_menus' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => false,
			'show_ui' => false,
			'rewrite' => null,
		) );
	}

	/**
	 * edit_form_advanced function.
	 *
	 * @access public
	 *
	 * @todo Add the ability to select which post can have Multple Content Sections
	 *
	 * @param object $post WordPress Post Object.
	 *
	 * @return void
	 */
	function edit_page_form( $post ) {
		$content_sections = mcs_get_sections( $post->ID );
		$mcs_notifications = get_user_option( 'linchpin_mcs_notifications', get_current_user_id() );
	?>
		<div id="mcs-container">
			<?php wp_nonce_field( 'mcs_content_sections_nonce', 'mcs_content_sections_nonce' ); ?>

			<?php if ( empty( $content_sections ) ) : ?>
				<div class="notice below-h2 mcs-row mcs-main-ua-row">
					<div class="mcs-columns-6 columns">
						<p class="lead"><?php esc_html_e( 'Multiple Content Sections', 'linchpin-mcs' ); ?></p>
					</div>

					<div class="mcs-columns-6 columns text-right">
						<a href="#" class="button primary mcs-section-add dashicons-before dashicons-plus"><?php esc_html_e( 'Add Section', 'lincpin-mcs' ); ?></a>
					</div>
				</div>
			<?php else : ?>
				<h2 class="mcs-section-controls-container">
					<?php esc_html_e( 'Multiple Content Sections', 'linchpin-mcs' ); ?>
					<?php include LINCHPIN_MCS___PLUGIN_DIR .'admin/controls.php'; ?>
					<span class="spinner mcs-reorder-spinner"></span>
				</h2>

			<?php endif; ?>

			<?php if ( empty( $content_sections ) ) : ?>
				<div id="mcs-description" class="description notice below-h2 text-center lead">
					<p><?php esc_html_e( 'You have no additional Content Sections yet.', 'linchpin-mcs' ); ?></p>
					<p><?php esc_html_e( 'Get started by clicking', 'linchpin-mcs' ); ?></p>
					<p><a href="#" class="button primary mcs-section-add dashicons-before dashicons-plus"><?php esc_html_e( 'Add Section', 'lincpin-mcs' ); ?></a></p>
				</div>
			<?php else : ?>
				<?php if ( empty( $mcs_notifications['intro'] ) ) : ?>
					<div id="mcs-description" class="description notice is-dismissible notice-info below-h2" data-type="intro">
						<p><?php esc_html_e( 'Multiple content sections allow you to easily segment your page\'s contents into different blocks of markup.', 'linchpin-mcs' ); ?></p>
					</div>
				<?php endif; ?>
			<?php endif; ?>

			<div id="multiple-content-sections-container">
				<?php foreach ( $content_sections as $key => $section ) : ?>
					<?php mcs_add_section_admin_markup( $section, true ); ?>
				<?php endforeach; ?>
			</div>

			<?php if ( ! empty( $content_sections ) ) : ?>
				<div id="multiple-content-sections-footer">
					<?php include LINCHPIN_MCS___PLUGIN_DIR . 'admin/controls.php'; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * save_post function.
	 *
	 * @access public
	 *
	 * @param mixed  $post_id
	 * @param object $post
	 *
	 * @return void
	 */
	function save_post( $post_id, $post ) {
		// Skip revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}

		// Users should have the ability to edit listings.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['mcs_content_sections_nonce'] ) || ! wp_verify_nonce( $_POST['mcs_content_sections_nonce'], 'mcs_content_sections_nonce' )  ) {
			return;
		}

		if ( empty( $_POST['mcs-sections'] ) ) {
			return;
		}

		remove_action( 'save_post', array( $this, 'save_post' ), 10 );

		foreach ( $_POST['mcs-sections'] as $section_id => $section_data ) {
			$section = get_post( (int) $section_id );

			if ( 'mcs_section' != $section->post_type ) {
				continue;
			}

			if ( $post_id != $section->post_parent ) {
				continue;
			}

			$status = sanitize_post_field( 'post_status', $section_data['post_status'], $post_id, 'attribute' );

			if ( ! in_array( $status, array( 'publish', 'draft' ) ) ) {
				$status = 'draft';
			}

			if ( empty( $section_data['post_content'] ) ) {
				$section_data['post_content'] = '';
			}

			$updates = array(
				'ID' => (int) $section_id,
				'post_title' => sanitize_text_field( $section_data['post_title'] ),
				'post_content' => wp_kses( $section_data['post_content'], array_merge(
					array(
						'iframe' => array(
							'src' => true,
							'style' => true,
							'id' => true,
							'class' => true,
						),
					),
					wp_kses_allowed_html( 'post' )
				) ),
				'post_status' => $status,
			);

			wp_update_post( $updates );

			// Save Template.
			$template = sanitize_text_field( $section_data['template'] );

			if ( empty( $template ) ) {
				delete_post_meta( $section->ID, '_mcs_template' );
			} else {
				update_post_meta( $section->ID, '_mcs_template', $template );
			}

			// Save CSS Classes.
			$css_classes = explode( ' ', $section_data['css_class'] );
			$sanitized_css_classes = array();

			foreach ( $css_classes as $css ) {
				$sanitized_css_classes[] = sanitize_html_class( $css );
			}

			$sanitized_css_classes = implode( ' ', $sanitized_css_classes );

			if ( empty( $sanitized_css_classes ) ) {
				delete_post_meta( $section->ID, '_mcs_css_class' );
			} else {
				update_post_meta( $section->ID, '_mcs_css_class', $sanitized_css_classes );
			}

			// Save LP Equal.
			$lp_equal = sanitize_text_field( $section_data['lp_equal'] );

			if ( empty( $lp_equal ) ) {
				delete_post_meta( $section->ID, '_mcs_lp_equal' );
			} else {
				update_post_meta( $section->ID, '_mcs_lp_equal', $lp_equal );
			}

			// Save Title Display
			$title_display = $section_data['title_display'];

			if ( empty( $title_display ) ) {
				delete_post_meta( $section->ID, '_mcs_title_display' );
			} else {
				update_post_meta( $section->ID, '_mcs_title_display', $title_display );
			}

			// Save Push / Pull.
			$push_pull = $section_data['push_pull'];

			if ( empty( $push_pull ) ) {
				delete_post_meta( $section->ID, '_mcs_push_pull' );
			} else {
				update_post_meta( $section->ID, '_mcs_push_pull', $push_pull );
			}

			// Save Push / Pull.
			$collapse = $section_data['collapse'];

			if ( empty( $collapse ) ) {
				delete_post_meta( $section->ID, '_mcs_collapse' );
			} else {
				update_post_meta( $section->ID, '_mcs_collapse', $collapse );
			}

			// Process the section's blocks.
			$blocks = array();

			if ( ! empty( $section_data['blocks'] ) ) {
				$blocks = $section_data['blocks'];
			}

			foreach ( $blocks as $block_id => $block_data ) {
				$block = get_post( (int) $block_id );

				if ( empty( $block ) || 'mcs_section' !== $block->post_type || $section->ID !== $block->post_parent ) {
					continue;
				}

				$updates = array(
					'ID' => (int) $block_id,
					'post_content' => wp_kses( $block_data['post_content'], mcs_get_allowed_html() ),
					'post_status' => $status,
				);

				wp_update_post( $updates );

				$block_column_width = (int) $section_data['blocks'][ $block_id ]['columns'];

				// If we don't have a column width defined or we are using a 1 column layout clear our saved widths.
				if ( empty( $block_column_width ) || 'mcs-columns-1.php' === $template ) {
					delete_post_meta( $block_id, '_mcs_column_width' );
				} else {
					update_post_meta( $block_id, '_mcs_column_width', $block_column_width );
				}

				// Save Column Offset.
				$offset = (int) (int) $section_data['blocks'][ $block_id ]['offset'];

				if ( empty( $offset ) ) {
					delete_post_meta( $block_id, '_mcs_offset' );
				} else {
					update_post_meta( $block_id, '_mcs_offset', $offset );
				}

				/**
				 * @todo: optimize this loop into a utility method
				 */

				$block_css_class = $section_data['blocks'][ $block_id ]['css_class'];

				// Save CSS Classes.
				$css_classes = explode( ' ', $block_css_class );
				$sanitized_css_classes = array();

				foreach ( $css_classes as $css ) {
					$sanitized_css_classes[] = sanitize_html_class( $css );
				}

				$sanitized_css_classes = implode( ' ', $sanitized_css_classes );

				if ( empty( $sanitized_css_classes ) ) {
					delete_post_meta( $block_id, '_mcs_css_class' );
				} else {
					update_post_meta( $block_id, '_mcs_css_class', $sanitized_css_classes );
				}
			}
		}

		// Save a block's content into its section, and then into it's page.
		$section_posts = mcs_get_sections( $post_id );

		if ( ! empty( $section_posts ) ) {
			foreach ( $section_posts as $p ) {
				$section_content = array();

				$blocks = mcs_get_section_blocks( $p->ID );

				foreach ( $blocks as $b ) {
					$section_content[] = strip_tags( $b->post_content );
				}

				wp_update_post( array(
					'ID' => $p->ID,
					'post_content' => implode( ' ', $section_content ),
				) );
			}

			// Get the sections again.
			$section_posts = mcs_get_sections( $post_id );
			$page_content_sections = array();
			$page_content_sections[] = '<div id="mcs-section-content">';

			foreach ( $section_posts as $p ) {
				if ( 'publish' !== $p->post_status ) {
					continue;
				}

				$page_content_sections[] = strip_tags( $p->post_title );
				$page_content_sections[] = strip_tags( $p->post_content );
			}

			$page_content_sections[] = '</div>';

			wp_update_post( array(
				'ID' => $post_id,
				'post_content' => $post->post_content . implode( ' ' , $page_content_sections ),
			) );
		}

		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
	}

	/**
	 * Simple loop to get our sections
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	function the_content( $content ) {
		$pos = strpos( $content, '<div id="mcs-section-content">' );

		if ( false !== $pos ) {
			$content = substr( $content, 0, ( strlen( $content ) - $pos ) * -1 );
		}

		return $content;
	}

	/**
	 * post_class function.
	 *
	 * Filter custom classes to section container
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	function post_class( $classes ) {
		if ( $custom_class = get_post_meta( get_the_ID(), '_mcs_css_class', true ) ) {
			$classes[] = esc_attr( $custom_class );
		}

		return $classes;
	}

	/**
	 * admin_enqueue_scripts function.
	 *
	 * @access public
	 * @return void
	 */
	function admin_enqueue_scripts() {
		global $current_screen, $post;

		if ( 'post' !== $current_screen->base ) {
			return;
		}

		wp_enqueue_script( 'admin-mcs', plugins_url( 'assets/js/admin-mcs.js', __FILE__ ), array( 'jquery', 'jquery-ui-draggable', 'jquery-ui-droppable', 'jquery-ui-slider' ), '1.0', true );

		$localized_data = array(
			'post_id' => $post->ID,
			'site_uri' => site_url(),
			'choose_layout_nonce'   => wp_create_nonce( 'mcs_choose_layout_nonce' ),
			'remove_section_nonce'  => wp_create_nonce( 'mcs_remove_section_nonce' ),
			'add_section_nonce'     => wp_create_nonce( 'mcs_add_section_nonce' ),
			'reorder_section_nonce' => wp_create_nonce( 'mcs_reorder_section_nonce' ),
			'featured_image_nonce'  => wp_create_nonce( 'mcs_featured_image_nonce' ),
			'reorder_blocks_nonce'  => wp_create_nonce( 'mcs_reorder_blocks_nonce' ),
			'dismiss_nonce'         => wp_create_nonce( 'mcs_dismiss_notification_nonce' ),
			'content_css'           => apply_filters( 'content_css', get_stylesheet_directory_uri() . '/css/admin-editor.css' , 'editor_path' ),
			'strings' => array(
				'reorder' => __( 'Be sure to save order of your sections once your changes are complete.', 'linchpin-mcs' ),
				'description' => __( 'Multiple content sections allows you to easily segment your page\'s contents into different blocks of markup.', 'linchpin-mcs' ),
				'add_image' => __( 'Set Background Image', 'linchpin-mcs' ),
				'remove_image' => __( 'Remove Background', 'linchpin-mcs' ),
				'expand_all' => __( 'Expand All', 'linchpin-mcs' ),
				'collapse_all' => __( 'Collapse All', 'linchpin-mcs' ),
				'default_title' => __( 'No Title', 'linchpin-mcs' ),
				'select_section_bg' => __( 'Select Section Background', 'linchpin-mcs' ),
				'select_bg' => __( 'Select Background' , 'linchpin-mcs' ),
				'select_block_bg' => __( 'Select Block Background', 'linchpin-mcs' ),
			),
		);

		wp_localize_script( 'admin-mcs', 'mcs_data', $localized_data );
	}

	/**
	 * Enqueue admin styles
	 *
	 * @access public
	 * @return void$push_pull     = get_post_meta( $section->ID, '_mcs_push_pull', true );
	 */
	function admin_enqueue_styles() {
		wp_enqueue_style( 'admin-mcs', plugins_url( 'assets/css/admin-mcs.css', __FILE__ ), array(), '1.0' );
	}

	/**
	 * Scan directory for files.
	 *
	 * @param $path
	 * @param null $extensions
	 * @param int $depth
	 * @param string $relative_path
	 *
	 * @return array|bool
	 */
	public static function scandir( $path, $extensions = null, $depth = 0, $relative_path = '' ) {
		if ( ! is_dir( $path ) ) {
			return false;
		}

		if ( $extensions ) {
			$extensions = (array) $extensions;
			$_extensions = implode( '|', $extensions );
		}

		$relative_path = trailingslashit( $relative_path );

		if ( '/' == $relative_path ) {
			$relative_path = '';
		}

		$results = scandir( $path );
		$files = array();

		foreach ( $results as $result ) {
			if ( '.' == $result[0] ) {
				continue;
			}
			if ( is_dir( $path . '/' . $result ) ) {
				if ( ! $depth || 'CVS' == $result ) {
					continue;
				}
				$found = self::scandir( $path . '/' . $result, $extensions, $depth - 1 , $relative_path . $result );
				$files = array_merge_recursive( $files, $found );
			} elseif ( ! $extensions || preg_match( '~\.(' . $_extensions . ')$~', $result ) ) {
				$files[ $relative_path . $result ] = $path . '/' . $result;
			}
		}

		return $files;
	}
}