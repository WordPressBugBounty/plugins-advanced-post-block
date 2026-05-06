<?php
/**
 * Plugin Name: Advanced Post Block
 * Description: Enhance your WordPress posts with customizable layouts, responsive design, and feature-rich elements.
 * Version: 2.2.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * Plugin URI: https://bplugins.com/products/advanced-post-block
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: advanced-post-block
 * Requires at least: 6.5
 * Requires PHP: 7.1
 */

// ABS PATH
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( function_exists( 'apb_fs' ) ) {
	apb_fs()->set_basename( true, __FILE__ );
}else{
	define( 'APB_VERSION', ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? time() : '2.2.0' );
	define( 'APB_DIR_URL', plugin_dir_url( __FILE__ ) );
	define( 'APB_DIR_PATH', plugin_dir_path( __FILE__ ) );
	define( 'APB_OPTIONS_KEY', 'apb_options' );

	require_once APB_DIR_PATH . 'includes/fs-lite.php';
	require_once APB_DIR_PATH . 'includes/admin/CPT.php';
	require_once APB_DIR_PATH . 'includes/Posts.php';
	require_once APB_DIR_PATH . 'includes/Tracker.php';
	require_once APB_DIR_PATH . 'includes/Ajax.php';
	require_once APB_DIR_PATH . 'includes/Options.php';

	if( !class_exists( 'APBPlugin' ) ){
		/**
		 * APBPlugin class
		 * Main plugin class responsible for initialization, enqueuing assets, and registering block types.
		 * 
		 * @package APB
		 */
		class APBPlugin{
			/**
			 * Constructor.
			 * Registers hooks for plugin initialization and asset enqueuing.
			 */
			public function __construct(){
				add_action( 'init', [$this, 'onInit'] );
				add_filter( 'block_categories_all', [$this, 'blockCategories'] );
				add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
				add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
				add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );

				add_filter( 'plugin_action_links', [$this, 'pluginActionLinks'], 10, 2 );
			}

			/**
			 * Adds custom action links to the plugin entry on the plugins page.
			 *
			 * @param array		$links	The existing action links.
			 * @param string	$file	The plugin file name.
			 * @return array	Modified action links.
			 */
			public function pluginActionLinks( $links, $file ) {
				if( plugin_basename( __FILE__ ) === $file ) {
					$helpDemosLink = admin_url( 'edit.php?post_type=apb&page=advanced-post-block#/welcome' );

					$links['help-and-demos'] = sprintf( '<a href="%s" style="%s">%s</a>', $helpDemosLink, 'color:#FF7A00;font-weight:bold', __( 'Help & Demos', 'advanced-post-block' ) );
				}

				return $links;
			}

			/**
			 * Initializes the plugin by registering the block type and loading textdomain.
			 * 
			 * @return void
			 */
			public function onInit(){
				register_block_type( __DIR__ . '/build' );

				load_plugin_textdomain( 'advanced-post-block', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
			}

			/**
			 * Registers custom block categories.
			 *
			 * @param array $categories The existing block categories.
			 * @return array Modified block categories.
			 */
			public function blockCategories( $categories ){
				return array_merge( [ [
					'slug'	=> 'APBlock',
					'title'	=> 'Advanced Post Block'
				] ], $categories );
			}

			/**
			 * Enqueues scripts and styles for the admin dashboard.
			 *
			 * @param string $hook The current admin page hook.
			 * @return void
			 */
			public function adminEnqueueScripts( $hook ) {
				if( strpos( $hook, 'advanced-post-block' ) ){
					wp_enqueue_style( 'apb-admin-dashboard', APB_DIR_URL . 'build/admin/dashboard.css', [], APB_VERSION );

					$asset_file = include APB_DIR_PATH . 'build/admin/dashboard.asset.php';
					wp_enqueue_script( 'apb-admin-dashboard', APB_DIR_URL . 'build/admin/dashboard.js', array_merge( $asset_file['dependencies'], [ 'wp-util' ] ), APB_VERSION, true );
					wp_set_script_translations( 'apb-admin-dashboard', 'advanced-post-block', APB_DIR_PATH . 'languages' );
				}
			}

			/**
			 * Enqueues assets for the block editor.
			 * 
			 * @return void
			 */
			public function enqueueBlockEditorAssets(){
				wp_add_inline_script( 'ap-block-posts-editor-script', sprintf(
					'const apbpricingurl = %s;',
					wp_json_encode( admin_url( 'edit.php?post_type=apb&page=advanced-post-block#/pricing' ) )
				), 'before' );
			}

			/**
			 * Enqueues assets shared by the editor and frontend.
			 * 
			 * @return void
			 */
			public function enqueueBlockAssets(){
				wp_register_script( 'easyTicker', APB_DIR_URL . 'public/js/easy-ticker.min.js', [ 'jquery' ], '3.2.1', true );
				wp_set_script_translations( 'easyTicker', 'advanced-post-block', APB_DIR_PATH . 'languages' );
			}

			/**
			 * Renders the dashboard container for the React app.
			 * 
			 * @return void
			 */
			public static function renderDashboard(){ ?>
				<div
					id='apbDashboard'
					data-info='<?php echo esc_attr( wp_json_encode( [
						'version' => APB_VERSION,
						'deleteDataOnUninstall' => (bool) \APB\Options::getOptions()['delete_data_on_uninstall'],
						'uninstallNonce' => wp_create_nonce( 'apbSaveUninstallOption' ),
					] ) ); ?>'
				></div>
			<?php }
		}
		new APBPlugin();
	}
}