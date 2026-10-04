<?php
namespace APB\Templates;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class Templates {
	public function __construct() {
		add_action( 'wp_ajax_apb_templates_main', [$this, 'apb_templates_main'] );
		add_action( 'wp_ajax_apb_templates', [$this, 'apb_templates'] );
		add_action( 'wp_ajax_apb_template_import', [$this, 'apb_template_import'] );
		add_action( 'wp_ajax_apb_template_counts', [$this, 'apb_template_counts'] );
		add_action( 'wp_ajax_apb_template_favorites', [$this, 'apb_template_favorites'] );
	}

	public function apb_templates_main(){
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'patterns' ) );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( __( 'Invalid Request', 'advanced-post-block' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient Permissions', 'advanced-post-block' ) );
		}

		$typeFilter = 'patterns' === $type ? 'patterns-category': 'pages-category';
		$response = wp_remote_get( self::api_base() . "/wp-json/gutenberg-templates/v1/taxonomy/taxonomies/plugin,type,{$typeFilter}");

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		// Filter categories to only include those that have templates for this plugin
		if ( $body && isset( $body->{$typeFilter} ) ) {
			$categoryFilter = $this->get_plugin_categories( $type );
			$body->{$typeFilter} = array_filter( $body->{$typeFilter}, function( $cat ) use ( $categoryFilter ) {
				return in_array( $cat->name, $categoryFilter, true );
			} );
			$body->{$typeFilter} = array_values( $body->{$typeFilter} );
		}

		wp_send_json_success( $body );
	}

	private function get_plugin_categories( $type ) {
		$typeFilter = 'patterns' === $type ? 'patterns': 'pages';
		$response = wp_remote_get( self::api_base() . "/wp-json/gutenberg-templates/v1/blocks?type=$typeFilter&start=0&end=1&plugin=advanced-post-block&fields=category&limit=1000&end=10000" );

		if ( is_wp_error( $response ) ) {
			return [];
		}

		$body = json_decode( wp_remote_retrieve_body( $response ) );

		if ( ! $body || ! isset( $body->patterns ) ) {
			return [];
		}

		$categories = [];
		foreach ( $body->patterns as $pattern ) {
			if ( isset( $pattern->category ) && is_array( $pattern->category ) ) {
				$categories = array_merge( $categories, $pattern->category );
			}
		}

		return array_unique( $categories );
	}

	public function apb_templates(){
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( __( 'Invalid Request', 'advanced-post-block' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient Permissions', 'advanced-post-block' ) );
		}

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'patterns') ) ;
		$category = sanitize_text_field( wp_unslash( $_POST['category'] ?? 'all' ) );
		$pageNumber = max( 1, absint( wp_unslash( $_POST['pageNumber'] ?? 1 ) ) );
		$perPage = max( 1, absint( wp_unslash( $_POST['perPage'] ?? 12 ) ) );
		$start = $pageNumber - 1;
		$search = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );

		$typeFilter = 'patterns' === $type ? 'patterns': 'pages';

		try {
			$response = wp_remote_get( self::api_base() . "/wp-json/gutenberg-templates/v1/blocks?type=$typeFilter&start=$start&end=$pageNumber&limit=$perPage&plugin=advanced-post-block&category=$category&keywords=$search&fields=ID,category,keywords,original_content,thumbnail,title,type,url,preview_url" );

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			wp_send_json_success( $body );
		} catch (\Throwable $th) {
			wp_send_json_error( $th->getMessage() );
		}
	}

	public function apb_template_import(){
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( __( 'Invalid Request', 'advanced-post-block' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient Permissions', 'advanced-post-block' ) );
		}

		$pattern_id = absint( wp_unslash( $_POST['id'] ?? 0 ) );

		try {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- serialized block markup: kses strips HTML comments, which are the block delimiters, so sanitizing here would break every template. Gated by the nonce and capability checks above.
			$data = wp_unslash( $_POST['original_content'] ?? '' );
			$this->track_event( 'download', $pattern_id );
			wp_send_json_success( $data );
		} catch ( \Throwable $th ) {
			wp_send_json_error( $th->getMessage() );
		}
	}

	public function apb_template_counts(){
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );
		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'patterns' ) );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( __( 'Invalid Request', 'advanced-post-block' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient Permissions', 'advanced-post-block' ) );
		}

		try {
			$typeFilter = 'patterns' === $type ? 'patterns': 'pages';

			// Fetch all templates for this plugin to count free/pro per category
			$response = wp_remote_get( self::api_base() . "/wp-json/gutenberg-templates/v1/blocks?type=$typeFilter&plugin=advanced-post-block&fields=ID,category&limit=10000&start=0&end=10000" );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( __( 'Failed to fetch templates', 'advanced-post-block' ) );
				return;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ) );

			if ( ! $body || ! isset( $body->patterns ) ) {
				wp_send_json_success( [
					'all' => 0,
					'free' => 0,
					'pro' => 0,
					'categories' => []
				] );
				return;
			}

			// Count free/pro per category
			$counts = [
				'all' => 0,
				'free' => 0,
				'pro' => 0,
				'categories' => []
			];

			foreach ( $body->patterns as $template ) {
				$isPro = isset( $template->category ) && is_array( $template->category ) && in_array( 'pro', $template->category, true );

				// Count in all
				$counts['all']++;
				if ( $isPro ) {
					$counts['pro']++;
				} else {
					$counts['free']++;
				}

				// Count per category
				if ( isset( $template->category ) && is_array( $template->category ) ) {
					foreach ( $template->category as $cat ) {
						if ( 'free' !== $cat && 'pro' !== $cat ) {
							if ( ! isset( $counts['categories'][$cat] ) ) {
								$counts['categories'][$cat] = [
									'total' => 0,
									'free' => 0,
									'pro' => 0
								];
							}
							$counts['categories'][$cat]['total']++;
							if ( $isPro ) {
								$counts['categories'][$cat]['pro']++;
							} else {
								$counts['categories'][$cat]['free']++;
							}
						}
					}
				}
			}

			wp_send_json_success( $counts );
		} catch ( \Throwable $th ) {
			wp_send_json_error( $th->getMessage() );
		}
	}
	public function apb_template_favorites(){
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( __( 'Invalid Request', 'advanced-post-block' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Insufficient Permissions', 'advanced-post-block' ) );
		}

		$prefix = sanitize_key( wp_unslash( $_POST['prefix'] ?? 'apb' ) );
		$optionKey = $prefix . 'FavoritesTemplates';

		// Save when a favorites payload is posted, then return the current state
		if ( isset( $_POST['favorites'] ) ) {
			$before = get_option( $optionKey, [ 'patterns' => [], 'pages' => [] ] );

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- a JSON string, and every decoded value is hard-cast through absint() below.
			$raw = json_decode( wp_unslash( $_POST['favorites'] ), true );

			$favorites = [
				'patterns'	=> array_values( array_unique( array_map( 'absint', (array) ( $raw['patterns'] ?? [] ) ) ) ),
				'pages'		=> array_values( array_unique( array_map( 'absint', (array) ( $raw['pages'] ?? [] ) ) ) ),
			];

			update_option( $optionKey, $favorites, false );

			// Tell the pattern library which templates were added or removed.
			foreach ( [ 'patterns', 'pages' ] as $favType ) {
				$was = array_map( 'absint', (array) ( $before[ $favType ] ?? [] ) );

				foreach ( array_diff( $favorites[ $favType ], $was ) as $id ) {
					$this->track_event( 'favorite', $id );
				}

				foreach ( array_diff( $was, $favorites[ $favType ] ) as $id ) {
					$this->track_event( 'unfavorite', $id );
				}
			}
		}

		wp_send_json_success( get_option( $optionKey, [ 'patterns' => [], 'pages' => [] ] ) );
	}

	/**
	 * Base URL of the pattern library. Filterable so a local copy of the
	 * library can be used while developing.
	 *
	 * @return string
	 */
	private static function api_base() {
		return untrailingslashit( apply_filters( 'apb_templates_api_base', 'https://templates.bplugins.com' ) );
	}

	/**
	 * Whether the site owner agreed to share usage data.
	 *
	 * apb_fs() is the Freemius SDK in Pro, and also in Free when another active
	 * plugin loads the full SDK - it knows if the site is registered and allows
	 * tracking. Otherwise it is freemius-lite, which keeps the opt-in in its
	 * accounts option: the site must have finished opting in (not skipped, not
	 * waiting on email confirmation) and still allow site and event tracking.
	 *
	 * @return bool
	 */
	private static function can_track() {
		if ( function_exists( 'apb_fs' ) && method_exists( apb_fs(), 'is_tracking_allowed' ) ) {
			$allowed = apb_fs()->is_tracking_allowed();
		} else {
			$slug		= 'advanced-post-block';
			$accounts	= (array) get_option( 'fs_lite_accounts', [] );
			$data		= (array) ( $accounts['plugin_data'][ $slug ] ?? [] );
			$site		= (object) ( $accounts['sites'][ $slug ] ?? [] );

			$allowed = ! empty( $site->public_key )
				&& empty( ( (array) ( $data['is_anonymous'] ?? [] ) )['is'] )
				&& empty( $accounts['admin_notices'][ $slug ]['activation_pending'] )
				&& ! empty( $data['is_site_tracking_allowed'] )
				&& ! empty( $data['is_events_tracking_allowed'] );
		}

		return (bool) apply_filters( 'apb_templates_tracking_allowed', $allowed );
	}

	/**
	 * Tell the pattern library about an import or a favourite change.
	 *
	 * Fire and forget: the request is non-blocking, so the user never waits
	 * for it, and a failure never affects the import. Nothing is sent unless
	 * the site owner opted in to usage tracking.
	 *
	 * @param string $event      download, favorite or unfavorite.
	 * @param int    $pattern_id Pattern ID from the library.
	 * @return void
	 */
	private function track_event( $event, $pattern_id ) {
		$pattern_id = absint( $pattern_id );

		if ( ! $pattern_id || ! self::can_track() ) {
			return;
		}

		wp_remote_post(
			self::api_base() . '/wp-json/gutenberg-templates/v1/track',
			[
				'blocking'	=> false,
				'timeout'	=> 2,
				'body'		=> [
					'event'		=> $event,
					'id'		=> $pattern_id,
					'plugin'	=> 'advanced-post-block',
				],
			]
		);
	}

}
new Templates();
