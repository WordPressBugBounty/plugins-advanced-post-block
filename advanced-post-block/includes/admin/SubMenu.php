<?php
namespace APB\Admin;

if ( !defined( 'ABSPATH' ) ) { exit; }

class SubMenu {
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'adminMenu' ] );
	}

	function adminMenu(){
		add_submenu_page(
			'tools.php',
			__('Advanced Posts - bPlugins', 'advanced-post-block'),
			__('Advanced Posts', 'advanced-post-block'),
			'manage_options',
			'advanced-post-block',
			[ \APBPlugin::class, 'renderDashboard' ]
		);
	}
}
new SubMenu();