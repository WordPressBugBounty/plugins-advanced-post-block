<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class APBHelpPage{
	public function __construct(){
		add_action( 'admin_menu', [$this, 'adminMenu'] );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
	}

	function adminMenu(){
		add_submenu_page(
			'edit.php?post_type=apb',
			__( 'Advanced Posts - Help', 'advanced-post-block' ),
			__( 'Help', 'advanced-post-block' ),
			'manage_options',
			'apb-help',
			[$this, 'helpPage']
		);
	}

	function helpPage(){ ?>
		<div id='bplAdminHelpPage'></div>
	<?php }

	function adminEnqueueScripts( $hook ) {
		if( strpos( $hook, 'apb-help' ) ){
			wp_enqueue_style( 'apb-admin-help', APB_DIR_URL . 'dist/admin-help.css', [], APB_VERSION );
			wp_enqueue_script( 'apb-admin-help', APB_DIR_URL . 'dist/admin-help.js', [ 'react', 'react-dom' ], APB_VERSION, true );
			wp_set_script_translations( 'apb-admin-help', 'advanced-post-block', APB_DIR_PATH . 'languages' );
		}
	}
}
new APBHelpPage;