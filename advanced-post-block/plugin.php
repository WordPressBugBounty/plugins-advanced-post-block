<?php

/**
 * Plugin Name: Advanced Post Block
 * Description: Enhance your WordPress posts with customizable layouts, responsive design, and feature-rich elements.
 * Version: 2.0.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
 * Plugin URI: https://bplugins.com/products/advanced-post-block
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain: advanced-post-block
 */
// ABS PATH
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( function_exists( 'apb_fs' ) ) {
    apb_fs()->set_basename( false, __FILE__ );
} else {
    define( 'APB_VERSION', ( isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '2.0.0' ) );
    define( 'APB_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'APB_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'APB_HAS_PRO', file_exists( dirname( __FILE__ ) . '/vendor/freemius/start.php' ) );
    if ( !function_exists( 'apb_fs' ) ) {
        function apb_fs() {
            global $apb_fs;
            if ( !isset( $apb_fs ) ) {
                if ( APB_HAS_PRO ) {
                    require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';
                } else {
                    require_once dirname( __FILE__ ) . '/vendor/freemius-lite/start.php';
                }
                $apbConfig = [
                    'id'                  => '14262',
                    'slug'                => 'advanced-post-block',
                    'premium_slug'        => 'advanced-post-block-pro',
                    'type'                => 'plugin',
                    'public_key'          => 'pk_87f141adce326dfb96ba4e12d8a36',
                    'is_premium'          => true,
                    'premium_suffix'      => 'Pro',
                    'has_premium_version' => true,
                    'has_addons'          => false,
                    'has_paid_plans'      => true,
                    'trial'               => [
                        'days'               => 7,
                        'is_require_payment' => false,
                    ],
                    'has_affiliation'     => 'selected',
                    'menu'                => [
                        'slug'       => 'advanced-post-block',
                        'first-path' => 'admin.php?page=advanced-post-block',
                        'contact'    => false,
                        'support'    => false,
                    ],
                ];
                $apb_fs = ( APB_HAS_PRO ? fs_dynamic_init( $apbConfig ) : fs_lite_dynamic_init( $apbConfig ) );
            }
            return $apb_fs;
        }

        apb_fs();
        do_action( 'apb_fs_loaded' );
    }
    function apbIsPremium() {
        return ( APB_HAS_PRO ? apb_fs()->can_use_premium_code() : false );
    }

    require_once APB_DIR_PATH . 'includes/admin/Menu.php';
    require_once APB_DIR_PATH . 'includes/Posts.php';
    if ( apbIsPremium() ) {
        require_once APB_DIR_PATH . 'includes/admin/CPT.php';
        require_once APB_DIR_PATH . '/includes/Ajax.php';
    }
    class APBPlugin {
        function __construct() {
            add_action( 'init', [$this, 'onInit'] );
            add_action( 'enqueue_block_editor_assets', [$this, 'enqueueBlockEditorAssets'] );
            add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
            add_filter( 'block_categories_all', [$this, 'blockCategories'] );
        }

        function onInit() {
            register_block_type( __DIR__ . '/build' );
        }

        function enqueueBlockEditorAssets() {
            wp_add_inline_script( 'ap-block-posts-editor-script', 'const apbpipecheck = ' . wp_json_encode( apbIsPremium() ) . ';', 'before' );
        }

        function enqueueBlockAssets() {
            wp_register_script(
                'easyTicker',
                APB_DIR_URL . 'public/js/easy-ticker.min.js',
                ['jquery'],
                '3.2.1',
                true
            );
            wp_set_script_translations( 'easyTicker', 'advanced-post-block', APB_DIR_PATH . 'languages' );
        }

        function blockCategories( $categories ) {
            return array_merge( [[
                'slug'  => 'APBlock',
                'title' => 'Advanced Post Block',
            ]], $categories );
        }

    }

    new APBPlugin();
}