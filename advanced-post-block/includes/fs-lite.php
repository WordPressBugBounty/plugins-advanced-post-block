<?php
if ( !defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'apb_fs' ) ) {
	function apb_fs() {
		global $apb_fs;

		if ( !isset( $apb_fs ) ) {
			require_once APB_DIR_PATH . '/vendor/freemius-lite/start.php';

			$apb_fs = fs_lite_dynamic_init( [
				'id'					=> '14262',
				'slug'					=> 'advanced-post-block',
				'__FILE__'				=> APB_DIR_PATH . 'plugin.php',
				'premium_slug'			=> 'advanced-post-block-pro',
				'type'					=> 'plugin',
				'public_key'			=> 'pk_87f141adce326dfb96ba4e12d8a36',
				'is_premium'			=> false,
				'premium_suffix'		=> 'Pro',
				'has_premium_version'	=> true,
				'has_addons'			=> false,
				'has_paid_plans'		=> true,
				'menu'					=> [
					'slug'			=> 'edit.php?post_type=apb',
					'first-path'	=> 'edit.php?post_type=apb&page=advanced-post-block',
					'contact'		=> false,
					'support'		=> false,
					'affiliation'	=> false
				]
			] );
		}

		return $apb_fs;
	}

	apb_fs();
	do_action( 'apb_fs_loaded' );
}
