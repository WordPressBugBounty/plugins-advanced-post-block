<?php
namespace APB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

class UpgradePage{
	public function __construct(){
		add_action( 'admin_menu', [$this, 'adminMenu'] );
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
		<iframe src='https://checkout.freemius.com/plugin/14262/plan/23856/' width='100%' frameborder='0' style='width: calc(100% - 20px); height: calc(100vh - 60px); margin-top: 15px;'></iframe>
	<?php }
}
new UpgradePage;