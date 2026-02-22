<?php
/**
 * Plugin Name: Advanced Post Block
 * Description: Enhance your WordPress posts with customizable layouts, responsive design, and feature-rich elements.
 * Version: 2.0.5
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * Plugin URI: https://bplugins.com/products/advanced-post-block
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: advanced-post-block
   */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'apb_fs' ) ) {
	apb_fs()->set_basename( false, __FILE__ );
}else{
	define( 'APB_VERSION', isset( $_SERVER['HTTP_HOST'] ) && ( 'localhost' === $_SERVER['HTTP_HOST'] || 'plugins.local' === $_SERVER['HTTP_HOST'] ) ? time() : '2.0.5' );
	define( 'APB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'APB_DIR_PATH', plugin_dir_path( __FILE__ ) );
	define( 'APB_HAS_PRO', file_exists( APB_DIR_PATH . '/vendor/freemius/start.php' ) );

	if ( APB_HAS_PRO ) {
		require_once APB_DIR_PATH . 'includes/fs.php';
	}else{
		require_once APB_DIR_PATH . 'includes/fs-lite.php';
	}

	require_once APB_DIR_PATH . 'includes/admin/CPT.php';
	require_once APB_DIR_PATH . 'includes/Posts.php';

	function apbIsPremium(){
		return APB_HAS_PRO ? apb_fs()->can_use_premium_code() : false;
	}

	if( APB_HAS_PRO ){
		require_once APB_DIR_PATH . 'includes/LicenseActivation.php';
	}

	if( apbIsPremium() ){
		require_once APB_DIR_PATH . 'includes/Ajax.php';
	}

	class APBPlugin{
		function __construct(){
			add_filter( 'plugin_row_meta', [$this, 'pluginRowMeta'], 10, 2 );
			add_action( 'init', [$this, 'onInit'] );
			add_filter( 'block_categories_all', [$this, 'blockCategories'] );
			add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
			add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
			add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );

			add_filter( 'plugin_action_links', [$this, 'pluginActionLinks'], 10, 2 ); 
		}

		function pluginActionLinks( $links, $file ) {
			if( plugin_basename( __FILE__ ) === $file ) {
				$helpDemosLink = admin_url( 'edit.php?post_type=apb&page=advanced-post-block' );

				$links['help-and-demos'] = sprintf( '<a href="%s" style="%s">%s</a>', $helpDemosLink, 'color:#FF7A00;font-weight:bold', __( 'Help & Demos', 'advanced-post-block' ) );
			}
 
			return $links;
		}

		function pluginRowMeta( $plugin_meta, $plugin_file ) {
			if ( strpos( $plugin_file, 'advanced-post-block' ) !== false && time() < strtotime( '2025-12-06' ) ) {
				$new_links = array(
					'deal' => "<a href='https://bplugins.com/coupons/?from=plugins.php&plugin=advanced-post-block' target='_blank' style='font-weight: 600; color: #146ef5;'>🎉 Black Friday Sale - Get up to 80% OFF Now!</a>"
				);

				$plugin_meta = array_merge( $plugin_meta, $new_links );
			}

			return $plugin_meta;
		}

		function onInit(){
			register_block_type( __DIR__ . '/build' );
		}

		function blockCategories( $categories ){
			return array_merge( [ [
				'slug'	=> 'APBlock',
				'title'	=> 'Advanced Post Block'
			] ], $categories );
		}

		function adminEnqueueScripts( $hook ) {
			if( strpos( $hook, 'advanced-post-block' ) ){
				wp_enqueue_style( 'apb-admin-dashboard', APB_DIR_URL . 'build/admin/dashboard.css', [], APB_VERSION );

				$asset_file = include APB_DIR_PATH . 'build/admin/dashboard.asset.php';
				wp_enqueue_script( 'apb-admin-dashboard', APB_DIR_URL . 'build/admin/dashboard.js', array_merge( $asset_file['dependencies'], [ 'wp-util' ] ), APB_VERSION, true );
				wp_set_script_translations( 'apb-admin-dashboard', 'advanced-post-block', APB_DIR_PATH . 'languages' );
			}
		}

		function enqueueBlockEditorAssets(){
			wp_add_inline_script( 'ap-block-posts-editor-script', 'const apbpipecheck = ' . wp_json_encode( apbIsPremium() ) .'; const apbpricingurl = "'. admin_url( 'edit.php?post_type=apb&page=advanced-post-block#/pricing' ) .'";', 'before' );
		}

		function enqueueBlockAssets(){
			wp_register_script( 'easyTicker', APB_DIR_URL . 'public/js/easy-ticker.min.js', [ 'jquery' ], '3.2.1', true );
			wp_set_script_translations( 'easyTicker', 'advanced-post-block', APB_DIR_PATH . 'languages' );
		}

		static function renderDashboard(){ ?>
			<div
				id='apbDashboard'
				data-info='<?php echo esc_attr( wp_json_encode( [
					'version' => APB_VERSION,
					'isPremium' => apbIsPremium(),
					'hasPro' => APB_HAS_PRO,
					// 'nonce' => wp_create_nonce( 'apbCreatePage' ),
					'licenseActiveNonce' => wp_create_nonce( 'bPlLicenseActivation' )
				] ) ); ?>'
			></div>
		<?php }
	}
	new APBPlugin();
}