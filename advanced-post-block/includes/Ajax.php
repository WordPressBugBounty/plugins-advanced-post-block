<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

/**
 * Ajax class
 * Handles AJAX requests for fetching posts.
 * 
 * @package APB
 */
class Ajax{
	/**
	 * Constructor.
	 * Registers AJAX actions.
	 */
	public function __construct(){
		add_action( 'wp_ajax_apbPosts', [$this, 'postsAjax'] );
		add_action( 'wp_ajax_nopriv_apbPosts', [$this, 'postsAjax'] );
	}

	/**
	 * Handles the apbPosts AJAX request.
	 * Fetches posts based on query attributes and pagination.
	 * 
	 * @return void
	 */
	public function postsAjax(){
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if( !wp_verify_nonce( $nonce, 'wp_rest' )){
			wp_send_json_error( __( 'Invalid Request', 'advanced-post-block' ) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitize by Functions::sanitize_array
		$queryAttr = isset( $_POST['queryAttr'] ) ? Functions::sanitize_array( wp_unslash( $_POST['queryAttr'] ) ) : [];

		$postType = $queryAttr['postType'] ?? 'post';
		if ( ! is_post_type_viewable( $postType ) ) {
			wp_send_json_error( __( 'Invalid post type', 'advanced-post-block' ) );
		}

		$pageNumber = isset( $_POST['pageNumber'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['pageNumber'] ) ) : 1;

		$allPosts = new \WP_Query( array_merge(
			Posts::arrangeQuery( $queryAttr ?? [] ),
			[ 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => false ]
		) );

		wp_send_json_success( [
			'posts' 		=> Posts::getPosts( $queryAttr, $pageNumber ),
			'totalPosts'	=> (int) $allPosts->found_posts
		] );
	}
}
new Ajax();