<?php
namespace APB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

/**
 * CPT class
 * Handles custom post type registration, shortcodes, and admin columns.
 * 
 * @package APB\Admin
 */
class CPT{
	/**
	 * Post type slug.
	 * @var string
	 */
	public $post_type = 'apb';

	/**
	 * Constructor.
	 * Registers hooks for CPT and admin UI.
	 */
	public function __construct(){
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
		add_action( 'admin_notices', [$this, 'renderBFCMBannerNotice'] );
		add_action( 'init', [$this, 'onInit'] );
		add_shortcode( 'apb', [$this, 'onAddShortcode'] );
		add_filter( 'manage_apb_posts_columns', [$this, 'manageAPBPostsColumns'], 10 );
		add_action( 'manage_apb_posts_custom_column', [$this, 'manageAPBPostsCustomColumns'], 10, 2 );
		add_action( 'use_block_editor_for_post', [$this, 'useBlockEditorForPost'], 999, 2 );
	}

	/**
	 * Adds the help and demos submenu page.
	 * 
	 * @return void
	 */
	public function adminMenu() {
		add_submenu_page(
			'edit.php?post_type=' . $this->post_type,
			__( 'Help & Demos - Advanced Posts', 'advanced-post-block' ),
			'<span style="color: #f18500;">' . esc_html__( 'Help & Demos', 'advanced-post-block' ) . '</span>',
			'manage_options',
			'advanced-post-block',
			[ \APBPlugin::class, 'renderDashboard' ]
		);
	}

	/**
	 * Enqueues scripts and styles for the admin area.
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function adminEnqueueScripts( $hook ){
		if( 'edit.php' === $hook || 'post.php' === $hook ){
			wp_enqueue_style( 'apb-admin-post', APB_DIR_URL . 'build/admin/post.css', [], APB_VERSION );
			wp_enqueue_script( 'apb-admin-post', APB_DIR_URL . 'build/admin/post.js', [], APB_VERSION, true );
			wp_set_script_translations( 'apb-admin-post', 'advanced-post-block', APB_DIR_PATH . 'languages' );
		}
	}

	/**
	 * Registers the 'apb' custom post type.
	 * 
	 * @return void
	 */
	public function onInit(){
		$menuIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 512 512' fill='#fff'><path d='m294.416 447.644 56.28-62.302-47.401-27.367-25.815 79.891c-3.315 10.257 9.71 17.777 16.936 9.778z' /><path d='m496.995 77.21c-14.349-8.284-32.696-3.368-40.981 10.981l-15 25.981 51.962 30 15-25.981c8.284-14.349 3.368-32.696-10.981-40.981z' /><path d='m301.995 246.396h160v60h-160z' transform='matrix(.5 -.866 .866 .5 -48.368 469.016)' /><path d='m429.495 138.143h30v60h-30z' transform='matrix(.5 -.866 .866 .5 76.632 469.016)' /><path d='m302 0h-247c-30.376 0-55 24.624-55 55v25h302z' /><path d='m131.607 225.393 19.199 19.199 28.194-37.592c6-8 18-8 24 0l33 44v-80h-160v88.787l34.393-34.394c5.858-5.858 15.356-5.858 21.214 0z' /><path d='m97.213 281h47.574l-23.787-23.787z' /><path d='m172.234 266.021 14.979 14.979h33.787l-30-40z' /><path d='m432 69.786v-14.786c0-30.376-24.624-55-55-55h-45v80h94.103z' /><path d='m380.317 397.305-63.64 70.449c-7.603 8.418-18.376 13.246-29.551 13.246-.001 0 0 0-.001 0-12.838 0-25.024-6.214-32.599-16.622-2.991-4.11-5.101-8.641-6.329-13.378h-187.197c-8.284 0-15-6.716-15-15s6.716-15 15-15h190.402l12.925-40h-203.327c-8.284 0-15-6.716-15-15s6.716-15 15-15h213.021l4.102-12.695c.974-3.015 2.264-5.919 3.849-8.663l126.81-219.642h-408.782v347c0 30.376 24.624 55 55 55h322c30.376 0 55-24.624 55-55v-147.215l-46.104 79.855c-1.585 2.744-3.455 5.314-5.579 7.665zm-334.317-241.305c0-8.284 6.716-15 15-15h190c8.284 0 15 6.716 15 15v140c0 8.284-6.716 15-15 15h-190c-8.284 0-15-6.716-15-15z' /></svg>";

		register_post_type( $this->post_type, [
			'labels'				=> [
				'name'			=> __( 'Advanced Posts', 'advanced-post-block' ),
				'singular_name'	=> __( 'ShortCode', 'advanced-post-block' ),
				'all_items'		=> __( 'ShortCode Generator', 'advanced-post-block' ),
				'add_new'		=> __( 'Add New ShortCode', 'advanced-post-block' ),
				'add_new_item'	=> __( 'Add New ShortCode', 'advanced-post-block' ),
				'edit_item'		=> __( 'Edit ShortCode', 'advanced-post-block' ),
				'new_item'		=> __( 'New ShortCode', 'advanced-post-block' ),
				'view_item'		=> __( 'View ShortCode', 'advanced-post-block' ),
				'search_items'	=> __( 'Search ShortCode', 'advanced-post-block' ),
				'not_found'		=> __( 'Sorry, we couldn\'t find the ShortCode you are looking for.', 'advanced-post-block' )
			],
			'public'				=> false,
			'show_ui'				=> true,
			'show_in_rest'			=> true,
			'publicly_queryable'	=> false,
			'exclude_from_search'	=> true,
			'menu_position'			=> 14,
			'menu_icon'				=> 'data:image/svg+xml;base64,' . base64_encode( $menuIcon ),
			'has_archive'			=> false,
			'hierarchical'			=> false,
			'capability_type'		=> 'page',
			'rewrite'				=> [ 'slug' => 'apb' ],
			'supports'				=> [ 'title', 'editor' ],
			'template'				=> [ ['ap-block/posts'] ],
			'template_lock'			=> 'all'
		] ); // Register Post Type
	}

	/**
	 * Renders the 'apb' shortcode.
	 *
	 * @param array		$atts	Shortcode attributes.
	 * @return string	Rendered shortcode content.
	 */
	public function onAddShortcode( $atts ) {
		$atts = shortcode_atts( [ 'id' => 0 ], $atts, 'apb' );
		$post_id = absint( $atts['id'] );
		if ( ! $post_id ) {
			return '';
		}
		$post = get_post( $post_id );

		if ( !$post ) {
			return '';
		}

		if ( post_password_required( $post ) ) {
			return get_the_password_form( $post );
		}

		switch ( $post->post_status ) {
			case 'publish':
				return $this->displayContent( $post );

			case 'private':
				if ( current_user_can( 'read_private_posts' ) ) {
					return $this->displayContent( $post );
				}
				return '';

			case 'draft':
			case 'pending':
			case 'future':
				if ( current_user_can( 'edit_post', $post_id ) ) {
					return $this->displayContent( $post );
				}
				return '';

			default:
				return '';
		}
	}

	/**
	 * Parses and renders the first block of the post content.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string Rendered block content.
	 */
	public function displayContent( $post ){
		$blocks = parse_blocks( $post->post_content );

		global $allowedposttags;
		$allowed_html = wp_parse_args( ['style' => [] ], $allowedposttags );

		return wp_kses( render_block( $blocks[0] ), $allowed_html );
	}

	/**
	 * Modifies the columns for the 'apb' post type list table.
	 *
	 * @param array		$defaults The default columns.
	 * @return array	Modified columns.
	 */
	public function manageAPBPostsColumns( $defaults ) {
		unset( $defaults['date'] );
		$defaults['shortcode'] = __( 'ShortCode', 'advanced-post-block' );
		$defaults['date'] = __( 'Date', 'advanced-post-block' );
		return $defaults;
	}

	/**
	 * Renders custom column content for the 'apb' post type.
	 *
	 * @param string	$column_name	The name of the column.
	 * @param int		$post_ID		The ID of the post.
	 * @return void
	 */
	public function manageAPBPostsCustomColumns( $column_name, $post_ID ) {
		if ( 'shortcode' === $column_name ) {
			echo '<div class="bPlAdminShortcode" id="bPlAdminShortcode-' . esc_attr( $post_ID ) . '">
				<input value="[apb id=' . esc_attr( $post_ID ) . ']" onclick="copyBPlAdminShortcode(\'' . esc_attr( $post_ID ) . '\')">
				<span class="tooltip">' . esc_html__( 'Copy To Clipboard', 'advanced-post-block' ) . '</span>
			</div>';
		}
	}

	/**
	 * Forces the use of the block editor for the 'apb' post type.
	 *
	 * @param bool		$use	Whether to use the block editor.
	 * @param \WP_Post	$post	The post object.
	 * @return bool		True if it's the 'apb' post type, otherwise the original value.
	 */
	public function useBlockEditorForPost( $use, $post ){
		if ( is_object( $post ) && isset( $post->post_type ) && $this->post_type === $post->post_type ) {
			return true;
		}
		return $use;
	}

	/**
	 * Renders the BFCM promotional banner on the CPT list table screen (edit.php?post_type=apb).
	 *
	 * @return void
	 */
	public function renderBFCMBannerNotice() {
		$screen = get_current_screen();
		if ( ! $screen || 'apb' !== $screen->post_type || 'edit' !== $screen->base ) {
			return;
		}

		if ( time() > strtotime( '2026-12-05 23:59:59' ) ) {
			return;
		}

		if ( function_exists( 'apb_fs' ) && apb_fs()->can_use_premium_code() ) {
			return;
		}

		if ( class_exists( '\APBPlugin' ) && \APBPlugin::isBFCMPromotionDismissed() ) {
			return;
		}

		$pricingUrl = 'https://bplugins.com/products/advanced-post-block/pricing/?utm_source=admin&utm_medium=dashboard&utm_campaign=bfcm2026';
		$nonce = wp_create_nonce( 'apb_dismiss_bfcm' );
		?>
		<div class="bPlBFCMBanner apb-cpt-bfcm-banner" id="apbBFCMBanner" data-nonce="<?php echo esc_attr( $nonce ); ?>" style="margin: 16px 20px 16px 0;">
			<div class="bPlBFCMBrand" style="background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);">
				<img src="https://ps.w.org/advanced-post-block/assets/icon-256x256.png" alt="ADVANCED POST BLOCK" class="bPlBFCMBrandLogo" />
				<span class="bPlBFCMBrandTitle">ADVANCED POST BLOCK</span>
			</div>
			<div class="bPlBFCMContent">
				<div class="bPlBFCMHeadingRow">
					<h3 class="bPlBFCMHeading"><?php esc_html_e( 'Black Friday Sale: Upgrade to Advanced Post Block Pro', 'advanced-post-block' ); ?></h3>
					<span class="bPlBFCMBadge"><?php esc_html_e( 'Save 45%', 'advanced-post-block' ); ?></span>
				</div>
				<p class="bPlBFCMDescription"><?php echo wp_kses( __( 'Get 45% off on Advanced Post Block Pro this Black Friday!<br />Adds precision filtering, layout enhancements, and deeper content control.', 'advanced-post-block' ), [ 'br' => [] ] ); ?></p>
				<ul class="bPlBFCMFeatures">
					<li class="bPlBFCMFeatureItem">
						<span class="bPlBFCMCheck">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="14" height="14" fill="#111827"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg>
						</span>
						<span><?php esc_html_e( 'Premium Templates', 'advanced-post-block' ); ?></span>
					</li>
					<li class="bPlBFCMFeatureItem">
						<span class="bPlBFCMCheck">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="14" height="14" fill="#111827"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg>
						</span>
						<span><?php esc_html_e( 'Timeline & Grid 1 Layouts', 'advanced-post-block' ); ?></span>
					</li>
					<li class="bPlBFCMFeatureItem">
						<span class="bPlBFCMCheck">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="14" height="14" fill="#111827"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg>
						</span>
						<span><?php esc_html_e( 'Query Presets & Deeper Filtering', 'advanced-post-block' ); ?></span>
					</li>
					<li class="bPlBFCMFeatureItem">
						<span class="bPlBFCMCheck">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="14" height="14" fill="#111827"><path d="M382-240 154-468l57-57 171 171 367-367 57 57-424 424Z"/></svg>
						</span>
						<span><?php esc_html_e( 'Infinite Scroll', 'advanced-post-block' ); ?></span>
					</li>
				</ul>
			</div>
			<div class="bPlBFCMActions">
				<a href="<?php echo esc_url( $pricingUrl ); ?>" target="_blank" rel="noopener noreferrer" class="bPlBFCMCtaButton">
					<?php esc_html_e( 'Unlock PRO Features →', 'advanced-post-block' ); ?>
				</a>
			</div>
			<button type="button" class="bPlBFCMDismiss" id="apbBFCMDismissBtn" aria-label="<?php esc_attr_e( 'Dismiss BFCM offer', 'advanced-post-block' ); ?>" title="<?php esc_attr_e( 'Dismiss', 'advanced-post-block' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 -960 960 960" width="16" height="16" fill="currentColor"><path d="M480-424 284-228q-11 11-28 11t-28-11q-11-11-11-28t11-28l196-196-196-196q-11-11-11-28t11-28q11-11 28-11t28 11l196 196 196-196q11-11 28-11t28 11q11 11 11 28t-11 28L536-480l196 196q11 11 11 28t-11 28q-11 11-28 11t-28-11L480-424Z"/></svg>
			</button>
		</div>
		<?php
	}
}
new CPT();