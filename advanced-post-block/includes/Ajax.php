<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

class Ajax{
	function __construct(){
		add_action( 'wp_ajax_apbPosts', [$this, 'postsAjax'] );
		add_action( 'wp_ajax_nopriv_apbPosts', [$this, 'postsAjax'] );
	}

	function postsAjax(){
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if( !wp_verify_nonce( $nonce, 'wp_rest' )){
			wp_send_json_error( 'Invalid Request' );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitize by Functions::sanitize_array
		$attributes = isset( $_POST['queryAttr'] ) ? Functions::sanitize_array( wp_unslash( $_POST['queryAttr'] ) ) : [];
		$pageNumber = isset( $_POST['pageNumber'] ) ? (int) sanitize_text_field( wp_unslash( $_POST['pageNumber'] ) ) : 1;

		wp_send_json_success( [
			'posts' 		=> Posts::getPosts( $attributes, $pageNumber ),
			'totalPosts'	=> count( get_posts( array_merge( Posts::query( $attributes ), [ 'posts_per_page' => -1 ] ) ) )
		] );
	}
}
new Ajax();