<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

class Tracker {
	public function __construct() {
		add_action( 'wp_head', [ $this, 'wpHead' ] );
		add_action( 'wp_ajax_apb_post_view', [ $this, 'incrementView' ] );
		add_action( 'wp_ajax_nopriv_apb_post_view', [ $this, 'incrementView' ] );
	}

	public function wpHead() {
		if ( ! is_single() ) {
			return;
		}

		global $post;
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		// Check if already viewed in this session
		if ( isset( $_COOKIE['apb_post_viewed_' . $post->ID] ) ) {
			return;
		}

		$ajax_url = admin_url( 'admin-ajax.php' );
		$nonce    = wp_create_nonce( 'apb_post_view_nonce' );
		$post_id  = $post->ID;

		?>
		<script type='text/javascript'>
			(function() {
				// Safety check to prevent double execution if multiple trackers exist
				if (window.apbViewTracked) return;
				window.apbViewTracked = true;

				document.addEventListener('DOMContentLoaded', function() {
					var xhr = new XMLHttpRequest();
					xhr.open('POST', '<?php echo esc_url( $ajax_url ); ?>', true);
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					xhr.onload = function() {
						if (xhr.status === 200) {
							// Successfully tracked
						}
					};
					xhr.send('action=apb_post_view&post_id=<?php echo (int) $post_id; ?>&nonce=<?php echo esc_js( $nonce ); ?>');
				});
			})();
		</script>
		<?php
	}

	public function incrementView() {
		check_ajax_referer( 'apb_post_view_nonce', 'nonce' );

		$post_id = isset( $_POST['post_id'] ) ? (int) $_POST['post_id'] : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'Invalid Post ID' );
		}

		// Security Check: Ensure post exists and is published
		$post_status = get_post_status( $post_id );
		if ( 'publish' !== $post_status ) {
			wp_send_json_error( 'Post is not published' );
		}

		// Security Check: Ensure post type is viewable
		$post_type = get_post_type( $post_id );
		if ( ! is_post_type_viewable( $post_type ) ) {
			wp_send_json_error( 'Post type is not viewable' );
		}

		// Prevent duplicate counts even if JS fires twice or bypasses check
		if ( isset( $_COOKIE['apb_post_viewed_' . $post_id] ) ) {
			wp_send_json_success( 'Already viewed' );
		}

		$views = get_post_meta( $post_id, 'apb_post_views_count', true );
		$views = $views ? (int) $views : 0;
		update_post_meta( $post_id, 'apb_post_views_count', $views + 1 );

		// Set cookie for 24 hours (86400 seconds)
		setcookie( 'apb_post_viewed_' . $post_id, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN );

		wp_send_json_success( 'View incremented' );
	}
}
new Tracker();
