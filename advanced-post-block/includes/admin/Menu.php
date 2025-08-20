<?php
namespace APB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

class Menu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
	}

	public function adminMenu() {
		add_menu_page(
			__( 'Advanced Post Block - bPlugins', 'advanced-post-block' ),
			__( 'Advanced Post', 'advanced-post-block' ),
			'manage_options',
			'advanced-post-block',
			'',
			'dashicons-screenoptions',
			20
		);

		add_submenu_page(
			'advanced-post-block',
			__('Dashboard - Advanced Post Block', 'advanced-post-block'),
			__('Dashboard', 'advanced-post-block'),
			'manage_options',
			'advanced-post-block',
			[$this, 'renderDashboardPage'],
			0
		);
	}

	public function renderDashboardPage(){ ?>
		<div
			id='apbDashboard'
			data-info='<?php echo esc_attr( wp_json_encode( [
				'version' => APB_VERSION,
				'isPremium' => apbIsPremium(),
				'hasPro' => APB_HAS_PRO
			] ) ); ?>'
		></div>
	<?php }

	function adminEnqueueScripts( $hook ) {
		if( strpos( $hook, 'advanced-post-block' ) ){
			wp_enqueue_style( 'apb-admin-dashboard', APB_DIR_URL . 'build/admin-dashboard.css', [], APB_VERSION );
			wp_enqueue_script( 'apb-admin-dashboard', APB_DIR_URL . 'build/admin-dashboard.js', [ 'react', 'react-dom' ], APB_VERSION, true );
			wp_set_script_translations( 'apb-admin-dashboard', 'advanced-post-block', APB_DIR_PATH . 'languages' );
		}
	}
}
new Menu();
