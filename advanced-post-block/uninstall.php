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

// 1. Delete all 'apb' posts and their meta/revisions. Every status, since `any` skips trash.
$apb_post_ids = get_posts( [
	'post_type'			=> 'apb',
	'posts_per_page'	=> -1,
	'fields'			=> 'ids',
	'post_status'		=> array_keys( get_post_stati() ),
] );

if ( ! empty( $apb_post_ids ) ) {
	foreach ( $apb_post_ids as $post_id ) {
		wp_delete_post( $post_id, true ); // Force delete (bypass trash).
	}
}

// 2. Delete post view tracking meta from every post — the helper clears the meta cache too.
delete_post_meta_by_key( 'apb_post_views_count' );

// 3. Delete plugin options.
delete_option( 'apb_options' );
delete_option( 'apbFavoritesTemplates' ); // Template Library favorites.
