<?php
/**
 * Uninstall handler for Advanced Post Block.
 *
 * Cleans up plugin data when the plugin is deleted from the admin.
 * Only runs if the user has opted in via the "Delete data on uninstall" setting.
 *
 * @package APB
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$apbOptions		= get_option( 'apb_options', [] );
$isDeleteData	= isset( $apbOptions['delete_data_on_uninstall'] ) ? $apbOptions['delete_data_on_uninstall'] : false;

if ( ! $isDeleteData ) {
	return;
}

global $wpdb;

// 1. Delete all 'apb' custom post type posts and their meta/revisions efficiently.
$apb_post_ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_type = %s", 'apb' ) );

if ( ! empty( $apb_post_ids ) ) {
	foreach ( $apb_post_ids as $post_id ) {
		wp_delete_post( $post_id, true ); // Force delete (bypass trash).
	}
}

// 2. Delete post view tracking meta from all posts.
$wpdb->delete( $wpdb->postmeta, [ 'meta_key' => 'apb_post_views_count' ] ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key

// 3. Delete plugin options.
delete_option( 'apb_options' );
