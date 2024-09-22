<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

class APBUpgradePage{
	public function __construct(){
		add_action( 'admin_menu', [$this, 'adminMenu'] );
		add_action( 'admin_enqueue_scripts', [$this, 'adminEnqueueScripts'] );
	}

	function adminMenu(){
		add_submenu_page(
			'edit.php?post_type=apb',
			__( 'Advanced Posts - Upgrade', 'advanced-post-block' ),
			__( 'Upgrade', 'advanced-post-block' ),
			'manage_options',
			'apb-upgrade',
			[$this, 'upgradePage']
		);
	}

	function upgradePage(){ ?>
		<div id='bplUpgradePage'></div>
	<?php }

	function adminEnqueueScripts( $hook ) {
		if( strpos( $hook, 'apb-upgrade' ) ){
			wp_enqueue_script( 'apb-admin-upgrade', APB_DIR_URL . 'dist/admin-upgrade.js', [ 'react', 'react-dom' ], APB_VERSION, true );
			wp_set_script_translations( 'apb-admin-upgrade', 'advanced-post-block', APB_DIR_PATH . 'languages' );
		}
	}
}
new APBUpgradePage;