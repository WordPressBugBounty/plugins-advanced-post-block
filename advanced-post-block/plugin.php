<?php
/**
 * Plugin Name: Advanced Post Block
 * Description: Enhance your WordPress posts with customizable layouts, responsive design, and feature-rich elements.
 * Version: 1.14.8
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: advanced-post-block
   */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'apb_fs' ) || function_exists( 'apb_init' ) ) {
	register_activation_hook( __FILE__, function () {
		if ( is_plugin_active( 'advanced-post-block/plugin.php' ) ){
			deactivate_plugins( 'advanced-post-block/plugin.php' );
		}
		if ( is_plugin_active( 'advanced-post-block-pro/plugin.php' ) ){
			deactivate_plugins( 'advanced-post-block-pro/plugin.php' );
		}
	} );
}else{
	define( 'APB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.14.8' );
	define( 'APB_DIR_PATH', plugin_dir_path( __FILE__ ) );
	define( 'APB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'APB_HAS_FREE', 'advanced-post-block/plugin.php' === plugin_basename( __FILE__ ) );
	define( 'APB_HAS_PRO', 'advanced-post-block-pro/plugin.php' === plugin_basename( __FILE__ ) );

	if( APB_HAS_FREE ){
		if( !function_exists( 'apb_init' ) ) {
			function apb_init() {
				global $apb_bs;
				require_once( APB_DIR_PATH . 'bplugins_sdk/init.php' );
				$apb_bs = new BPlugins_SDK( __FILE__ );
			}
			apb_init();
		}else {
			$apb_bs->uninstall_plugin( __FILE__ );
		}
	}

	if ( APB_HAS_PRO ) {
		require_once APB_DIR_PATH . 'inc/fs-init.php';

		if( function_exists( 'apb_fs' ) ){
			apb_fs()->set_basename( false, __FILE__ );
		}
	}

	function apbIsPremium(){
		if( APB_HAS_FREE ){
			global $apb_bs;
			return $apb_bs->can_use_premium_feature();
		}

		if ( APB_HAS_PRO ) {
			return apb_fs()->can_use_premium_code();
		}
	}

	// Advanced Post Block
	class APBPlugin{
		function __construct(){
			add_action( 'wp_ajax_apbPipeChecker', [$this, 'apbPipeChecker'] );
			add_action( 'wp_ajax_nopriv_apbPipeChecker', [$this, 'apbPipeChecker'] );
			add_action( 'admin_init', [$this, 'registerSettings'] );
			add_action( 'rest_api_init', [$this, 'registerSettings']);

			add_filter( 'block_categories_all', [$this, 'blockCategories'] );
		}

		function apbPipeChecker(){
			$nonce = sanitize_text_field( $_POST['_wpnonce'] ) ?? null;

			if( !wp_verify_nonce( $nonce, 'wp_ajax' )){
				wp_send_json_error( 'Invalid Request' );
			}

			wp_send_json_success( [
				'isPipe' => apbIsPremium()
			] );
		}

		function registerSettings(){
			register_setting( 'apbUtils', 'apbUtils', [
				'show_in_rest'		=> [
					'name'			=> 'apbUtils',
					'schema'		=> [ 'type' => 'string' ]
				],
				'type'				=> 'string',
				'default'			=> wp_json_encode( [ 'nonce' => wp_create_nonce( 'wp_ajax' ) ] ),
				'sanitize_callback'	=> 'sanitize_text_field'
			] );
		}

		function blockCategories( $categories ){
			return array_merge( [ [
				'slug'	=> 'APBlock',
				'title'	=> 'Advanced Post Block'
			] ], $categories );
		}

		function query( $attributes ){
			extract( $attributes );
			$selectedTaxonomies = $selectedTaxonomies ?? [];
			$selectedCategories = $selectedCategories ?? [];

			$termsQuery = ['relation' => 'AND'];
			foreach ( $selectedTaxonomies as $taxonomy => $terms ){
				if( count( $terms ) ){
					$termsQuery[] = [
						'taxonomy'	=> $taxonomy,
						'field'		=> 'term_id',
						'terms'		=> $terms,
					];
				}
			}

			$defaultPostQuery = 'post' === $postType ? [
				'category__in'	=> $selectedCategories,
				'tag__in'		=> $selectedTags ?? []
			] : [];

			$postsInclude = APB\Inc\Functions::filterNaN( $postsInclude ?? [] );
			$post__in = !empty( $postsInclude ) ? [ 'post__in' => $postsInclude ] : [];
			$postsExclude = APB\Inc\Functions::filterNaN( $postsExclude ?? [] );

			$query = array_merge( [
				'post_type'			=> $postType,
				'posts_per_page'	=> $isPostsPerPageAll ? -1 : $postsPerPage,
				'orderby'			=> $postsOrderBy,
				'order'				=> $postsOrder,
				'tax_query'			=> $termsQuery,
				'offset'			=> $isPostsPerPageAll ? 0 : $postsOffset,
				'post__not_in'		=> $isExcludeCurrent ? array_merge( [ get_the_ID() ], $postsExclude ) : $postsExclude,
				'has_password'		=> false,
				'post_status'		=> 'publish'
			], $post__in, $defaultPostQuery );

			if( apbIsPremium() ) {
				$query = apply_filters( 'apb_query', $query );
			}

			return $query;
		}

		function getPosts( $attributes, $pageNumber = 1 ){
			extract( $attributes );

			$attributes['isPostsPerPageAll'] = 'true' === $isPostsPerPageAll;
			$attributes['isExcludeCurrent'] = 'true' === $isExcludeCurrent;

			$newArgs = wp_parse_args( [ 'offset' => ( $postsPerPage * ( $pageNumber - 1 ) ) + $postsOffset ], $this->query( $attributes ) );
			$posts = APB\Inc\Functions::arrangedPosts(
				get_posts( $newArgs ),
				$postType,
				$fImgSize,
				$metaDateFormat,
				$excerptLength
			);

			return $posts;
		}
	}
	new APBPlugin;

	require_once APB_DIR_PATH . '/inc/block.php';
	require_once APB_DIR_PATH . '/inc/functions.php';
	require_once APB_DIR_PATH . '/inc/CustomPost.php';
	require_once APB_DIR_PATH . '/inc/HelpPage.php';

	if( APB_HAS_FREE ){
		require_once APB_DIR_PATH . '/inc/UpgradePage.php';
	}
	if( APB_HAS_PRO ){
		require_once APB_DIR_PATH . '/inc/Premium.php';
	}
}