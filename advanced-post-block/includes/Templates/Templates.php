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
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? null ) );
		$type = sanitize_text_field( $_POST['type'] ?? 'patterns');

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( 'Invalid Request' );
		}

		$typeFilter = 'patterns' === $type ? 'patterns-category': 'pages-category';
		$response = wp_remote_get("https://templates.bplugins.com/wp-json/gutenberg-templates/v1/taxonomy/taxonomies/plugin,type,{$typeFilter}");

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
		$response = wp_remote_get( "https://templates.bplugins.com/wp-json/gutenberg-templates/v1/blocks?type=$typeFilter&start=0&end=1&plugin=advanced-post-block&fields=category&limit=1000&end=10000" );

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
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? null ) );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( 'Invalid Request' );
		}

		$type = sanitize_text_field( wp_unslash( $_POST['type'] ?? 'patterns') ) ;
		$category = sanitize_text_field( wp_unslash( $_POST['category'] ?? 'all' ) );
		$pageNumber = absint( wp_unslash( $_POST['pageNumber'] ) ) ?? 1;
		$perPage = absint( wp_unslash( $_POST['perPage'] ) ) ?? 12;
		$start = $pageNumber - 1;
		$search = sanitize_text_field( wp_unslash( $_POST['search'] ) ) ?? '';

		$typeFilter = 'patterns' === $type ? 'patterns': 'pages';

		try {
			$response = wp_remote_get( "https://templates.bplugins.com/wp-json/gutenberg-templates/v1/blocks?type=$typeFilter&start=$start&end=$pageNumber&limit=$perPage&plugin=advanced-post-block&category=$category&keywords=$search&fields=ID,category,keywords,original_content,thumbnail,title,type,url,preview_url" );

			$body = json_decode( wp_remote_retrieve_body( $response ) );
			wp_send_json_success( $body );
		} catch (\Throwable $th) {
			wp_send_json_error( $th->getMessage() );
		}
	}

	public function apb_template_import(){
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) ?? null;

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( 'Invalid Request' );
		}

		try {
			$data = wp_unslash( $_POST['original_content'] );
			wp_send_json_success( $data );
		} catch ( \Throwable $th ) {
			wp_send_json_error( $th->getMessage() );
		}
	}

	public function apb_template_counts(){
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? null ) );
		$type = sanitize_text_field( $_POST['type'] ?? 'patterns' );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( 'Invalid Request' );
		}

		try {
			$typeFilter = 'patterns' === $type ? 'patterns': 'pages';

			// Fetch all templates for this plugin to count free/pro per category
			$response = wp_remote_get( "https://templates.bplugins.com/wp-json/gutenberg-templates/v1/blocks?type=$typeFilter&plugin=advanced-post-block&fields=ID,category&limit=10000&start=0&end=10000" );

			if ( is_wp_error( $response ) ) {
				wp_send_json_error( 'Failed to fetch templates' );
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
		$nonce = sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? null ) );

		if( !wp_verify_nonce( $nonce, 'apb_template' )){
			wp_send_json_error( 'Invalid Request' );
		}

		$prefix = sanitize_key( wp_unslash( $_POST['prefix'] ?? 'apb' ) );
		$optionKey = $prefix . 'FavoritesTemplates';

		// Save when a favorites payload is posted, then return the current state
		if ( isset( $_POST['favorites'] ) ) {
			$raw = json_decode( wp_unslash( $_POST['favorites'] ), true );

			$favorites = [
				'patterns'	=> array_values( array_unique( array_map( 'absint', (array) ( $raw['patterns'] ?? [] ) ) ) ),
				'pages'		=> array_values( array_unique( array_map( 'absint', (array) ( $raw['pages'] ?? [] ) ) ) ),
			];

			update_option( $optionKey, $favorites, false );
		}

		wp_send_json_success( get_option( $optionKey, [ 'patterns' => [], 'pages' => [] ] ) );
	}
}
new Templates();
