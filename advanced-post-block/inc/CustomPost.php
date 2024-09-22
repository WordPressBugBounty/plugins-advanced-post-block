<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class APBCustomPost{
	public $post_type = 'apb';

	public function __construct(){
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
		add_action( 'init', [$this, 'onInit'] );
		add_shortcode( 'apb', [$this, 'onAddShortcode'] );
		add_filter( 'manage_apb_posts_columns', [$this, 'manageAPBPostsColumns'], 10 );
		add_action( 'manage_apb_posts_custom_column', [$this, 'manageAPBPostsCustomColumns'], 10, 2 );
		add_action( 'use_block_editor_for_post', [$this, 'useBlockEditorForPost'], 999, 2 );
		add_filter( 'custom_menu_order', [$this, 'orderSubMenu'] );
	}

	function adminEnqueueScripts( $hook ){
		if( 'edit.php' === $hook || 'post.php' === $hook ){
			wp_enqueue_style( 'apb-admin-post', APB_DIR_URL . 'dist/admin-post.css', [], APB_VERSION );
			wp_enqueue_script( 'apb-admin-post', APB_DIR_URL . 'dist/admin-post.js', [], APB_VERSION, true );
			wp_set_script_translations( 'apb-admin-post', 'advanced-post-block', APB_DIR_PATH . 'languages' );
		}
	}

	function onInit(){
		$menuIcon = "<svg xmlns='http://www.w3.org/2000/svg' width='24' height='24' viewBox='0 0 512 512' fill='#fff'><path d='m294.416 447.644 56.28-62.302-47.401-27.367-25.815 79.891c-3.315 10.257 9.71 17.777 16.936 9.778z' /><path d='m496.995 77.21c-14.349-8.284-32.696-3.368-40.981 10.981l-15 25.981 51.962 30 15-25.981c8.284-14.349 3.368-32.696-10.981-40.981z' /><path d='m301.995 246.396h160v60h-160z' transform='matrix(.5 -.866 .866 .5 -48.368 469.016)' /><path d='m429.495 138.143h30v60h-30z' transform='matrix(.5 -.866 .866 .5 76.632 469.016)' /><path d='m302 0h-247c-30.376 0-55 24.624-55 55v25h302z' /><path d='m131.607 225.393 19.199 19.199 28.194-37.592c6-8 18-8 24 0l33 44v-80h-160v88.787l34.393-34.394c5.858-5.858 15.356-5.858 21.214 0z' /><path d='m97.213 281h47.574l-23.787-23.787z' /><path d='m172.234 266.021 14.979 14.979h33.787l-30-40z' /><path d='m432 69.786v-14.786c0-30.376-24.624-55-55-55h-45v80h94.103z' /><path d='m380.317 397.305-63.64 70.449c-7.603 8.418-18.376 13.246-29.551 13.246-.001 0 0 0-.001 0-12.838 0-25.024-6.214-32.599-16.622-2.991-4.11-5.101-8.641-6.329-13.378h-187.197c-8.284 0-15-6.716-15-15s6.716-15 15-15h190.402l12.925-40h-203.327c-8.284 0-15-6.716-15-15s6.716-15 15-15h213.021l4.102-12.695c.974-3.015 2.264-5.919 3.849-8.663l126.81-219.642h-408.782v347c0 30.376 24.624 55 55 55h322c30.376 0 55-24.624 55-55v-147.215l-46.104 79.855c-1.585 2.744-3.455 5.314-5.579 7.665zm-334.317-241.305c0-8.284 6.716-15 15-15h190c8.284 0 15 6.716 15 15v140c0 8.284-6.716 15-15 15h-190c-8.284 0-15-6.716-15-15z' /></svg>";

		register_post_type( 'apb', [
			'labels'				=> [
				'name'			=> __( 'Advanced Posts', 'advanced-post-block'),
				'singular_name'	=> __( 'Advanced Posts', 'advanced-post-block' ),
				'add_new'		=> __( 'Add New', 'advanced-post-block' ),
				'add_new_item'	=> __( 'Add New', 'advanced-post-block' ),
				'edit_item'		=> __( 'Edit', 'advanced-post-block' ),
				'new_item'		=> __( 'New', 'advanced-post-block' ),
				'view_item'		=> __( 'View', 'advanced-post-block' ),
				'search_items'	=> __( 'Search', 'advanced-post-block'),
				'not_found'		=> __( 'Sorry, we couldn\'t find the that you are looking for.', 'advanced-post-block' )
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
			'template_lock'			=> 'all',
		]); // Register Post Type
	}

	function onAddShortcode( $atts ) {
		$post_id = $atts['id'];

		$post = get_post( $post_id );
		$blocks = parse_blocks( $post->post_content );

		ob_start();
		echo render_block( $blocks[0] );

		return ob_get_clean();
	}

	function manageAPBPostsColumns( $defaults ) {
		unset( $defaults['date'] );
		$defaults['shortcode'] = 'ShortCode';
		$defaults['date'] = 'Date';
		return $defaults;
	}

	function manageAPBPostsCustomColumns( $column_name, $post_ID ) {
		if ( $column_name == 'shortcode' ) {
			echo '<div class="bPlAdminShortcode" id="bPlAdminShortcode-' . esc_attr( $post_ID ) . '">
				<input value="[apb id=' . esc_attr( $post_ID ) . ']" onclick="copyBPlAdminShortcode(\'' . esc_attr( $post_ID ) . '\')">
				<span class="tooltip">' . esc_html__( 'Copy To Clipboard' ) . '</span>
			</div>';
		}
	}

	function useBlockEditorForPost($use, $post){
		if ( $this->post_type === $post->post_type ) {
			return true;
		}
		return $use;
	}

	function orderSubMenu( $menu_ord ){
		global $submenu;

		$sMenu = $submenu['edit.php?post_type=apb'];
		$arr = [];
		if( apbIsPremium() ){
			if( isset( $sMenu[5] ) ){
				$arr[] = $sMenu[5]; // Advanced Posts
			}
			if( isset( $sMenu[10] ) ){
				$arr[] = $sMenu[10]; // Add New
			}
		}
		if( isset( $sMenu[11] ) ){
			$arr[] = $sMenu[11]; // Help
		}
		if( ( !apbIsPremium() || APB_HAS_PRO ) && isset( $sMenu[12] ) ){
			$arr[] = $sMenu[12]; // Upgrade || Pricing || Account
		}
		$submenu['edit.php?post_type=apb'] = $arr;
	
		return $menu_ord;
	}
}
new APBCustomPost();