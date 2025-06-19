<?php

/**
 * Plugin Name: Advanced Post Block
 * Description: Enhance your WordPress posts with customizable layouts, responsive design, and feature-rich elements.
 * Version: 1.16.0
 * Author: bPlugins
 * Author URI: https://bplugins.com
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
    define( 'APB_VERSION', ( isset( $_SERVER['HTTP_HOST'] ) && 'localhost' === $_SERVER['HTTP_HOST'] ? time() : '1.16.0' ) );
    define( 'APB_DIR_PATH', plugin_dir_path( __FILE__ ) );
    define( 'APB_DIR_URL', plugin_dir_url( __FILE__ ) );
    define( 'APB_HAS_PRO', file_exists( dirname( __FILE__ ) . '/freemius/start.php' ) );
    if ( !function_exists( 'apb_fs' ) ) {
        function apb_fs() {
            global $apb_fs;
            if ( !isset( $apb_fs ) ) {
                if ( APB_HAS_PRO ) {
                    require_once dirname( __FILE__ ) . '/freemius/start.php';
                } else {
                    require_once dirname( __FILE__ ) . '/bplugins_sdk/init.php';
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
                    'has_affiliation'     => 'selected',
                    'trial'               => [
                        'days'               => 7,
                        'is_require_payment' => true,
                    ],
                    'menu'                => [
                        'slug'    => 'edit.php?post_type=apb',
                        'contact' => false,
                        'support' => false,
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

    // Advanced Post Block
    class APBPlugin {
        function __construct() {
            add_action( 'init', [$this, 'onInit'] );
            add_action( 'enqueue_block_assets', [$this, 'enqueueBlockAssets'] );
            add_action( 'wp_ajax_apbPipeChecker', [$this, 'apbPipeChecker'] );
            add_action( 'wp_ajax_nopriv_apbPipeChecker', [$this, 'apbPipeChecker'] );
            add_filter( 'block_categories_all', [$this, 'blockCategories'] );
        }

        function onInit() {
            register_block_type( __DIR__ . '/build' );
        }

        function enqueueBlockAssets() {
            wp_register_script(
                'easyTicker',
                APB_DIR_URL . 'assets/js/easy-ticker.min.js',
                ['jquery'],
                '3.2.1',
                true
            );
            wp_set_script_translations( 'easyTicker', 'advanced-post-block', APB_DIR_PATH . 'languages' );
        }

        function apbPipeChecker() {
            $nonce = sanitize_text_field( $_POST['_wpnonce'] ) ?? null;
            if ( !wp_verify_nonce( $nonce, 'wp_rest' ) ) {
                wp_send_json_error( 'Invalid Request' );
            }
            wp_send_json_success( [
                'isPipe' => apbIsPremium(),
            ] );
        }

        function blockCategories( $categories ) {
            return array_merge( [[
                'slug'  => 'APBlock',
                'title' => 'Advanced Post Block',
            ]], $categories );
        }

    }

    new APBPlugin();
    require_once APB_DIR_PATH . 'includes/Posts.php';
    require_once APB_DIR_PATH . 'includes/admin/CustomPost.php';
    require_once APB_DIR_PATH . 'includes/admin/HelpPage.php';
    if ( !APB_HAS_PRO ) {
        require_once APB_DIR_PATH . 'includes/admin/UpgradePage.php';
    }
}