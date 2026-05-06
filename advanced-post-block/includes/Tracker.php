<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

/**
 * Tracker class
 * Handles post view tracking via AJAX.
 * 
 * @package APB
 */
class Tracker {
	/**
	 * Constructor.
	 * Registers hooks for tracking.
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', [ $this, 'wpEnqueueScripts' ] );
		add_action( 'wp_ajax_apb_post_view', [ $this, 'incrementView' ] );
		add_action( 'wp_ajax_nopriv_apb_post_view', [ $this, 'incrementView' ] );
	}

	/**
	 * Enqueues the post view tracking script on single post pages.
	 *
	 * @return void
	 */
	public function wpEnqueueScripts() {
		if ( ! is_single() ) {
			return;
		}

		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Check if already viewed in this session
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only checking existence, value is not used.
		if ( isset( $_COOKIE[ 'apb_post_viewed_' . $post->ID ] ) ) {
			return;
		}

		wp_enqueue_script( 'apb-post-view-tracker', APB_DIR_URL . 'public/js/tracker.js', [], APB_VERSION, true );

		wp_add_inline_script( 'apb-post-view-tracker', 'const apbPostViewTracker = ' . wp_json_encode( [
			'ajaxUrl'	=> admin_url( 'admin-ajax.php' ),
			'nonce'		=> wp_create_nonce( 'apb_post_view_nonce' ),
			'postId'	=> $post->ID
		] ) . ';', 'before' );
	}

	/**
	 * Increments the view count for a post via AJAX.
	 * Validates nonce and post status before updating meta.
	 * 
	 * @return void
	 */
	public function incrementView() {
		check_ajax_referer( 'apb_post_view_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( __( 'Invalid Post ID', 'advanced-post-block' ) );
		}

		// Security Check: Ensure post exists and is published
		$post_status = get_post_status( $post_id );
		if ( 'publish' !== $post_status ) {
			wp_send_json_error( __( 'Post is not published', 'advanced-post-block' ) );
		}

		// Security Check: Ensure post type is viewable
		$post_type = get_post_type( $post_id );
		if ( ! is_post_type_viewable( $post_type ) ) {
			wp_send_json_error( __( 'Post type is not viewable', 'advanced-post-block' ) );
		}

		// Prevent duplicate counts even if JS fires twice or bypasses check
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only checking existence, value is not used.
		if ( isset( $_COOKIE[ 'apb_post_viewed_' . $post_id ] ) ) {
			wp_send_json_success( __( 'Already viewed', 'advanced-post-block' ) );
		}

		$views = get_post_meta( $post_id, 'apb_post_views_count', true );
		$views = $views ? (int) $views : 0;
		update_post_meta( $post_id, 'apb_post_views_count', $views + 1 );

		// Set cookie for 24 hours (86400 seconds)
		setcookie( 'apb_post_viewed_' . $post_id, '1', [
			'expires'	=> time() + 86400,
			'path'		=> COOKIEPATH,
			'domain'	=> COOKIE_DOMAIN,
			'secure'	=> is_ssl(),
			'httponly'	=> true,
			'samesite'	=> 'Lax',
		] );

		wp_send_json_success( 'View incremented' );
	}
}
new Tracker();
