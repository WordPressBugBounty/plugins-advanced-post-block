<?php
namespace APB;

if ( !defined( 'ABSPATH' ) ) { exit; }

/**
 * Options class
 * Handles plugin options and related AJAX requests.
 * 
 * @package APB
 */
class Options {
	/**
	 * Constructor.
	 * Registers options-related hooks.
	 */
	public function __construct() {
		add_action( 'wp_ajax_apbSaveUninstallOption', [ $this, 'saveUninstallOption' ] );
	}

	/**
	 * Retrieves all plugin options, merged with defaults.
	 *
	 * @return array The plugin options.
	 */
	public static function getOptions() {
		$defaults = [
			'delete_data_on_uninstall' => false,
		];

		$options = get_option( APB_OPTIONS_KEY, [] );

		return wp_parse_args( $options, $defaults );
	}

	/**
	 * Updates plugin options.
	 *
	 * @param array $new_options The options to update.
	 * @return bool True on success, false on failure.
	 */
	public static function updateOptions( $new_options ) {
		$options = self::getOptions();
		$updated_options = array_merge( $options, $new_options );
		return update_option( APB_OPTIONS_KEY, $updated_options );
	}

	/**
	 * Saves the "delete data on uninstall" option via AJAX.
	 *
	 * @return void
	 */
	public function saveUninstallOption() {
		check_ajax_referer( 'apbSaveUninstallOption', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'advanced-post-block' ) );
		}

		$enabled = isset( $_POST['enabled'] ) && 'true' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );

		self::updateOptions( [ 'delete_data_on_uninstall' => $enabled ] );

		wp_send_json_success( [
			'enabled' => $enabled,
			'message' => $enabled
				? __( 'All plugin data will be deleted when uninstalled.', 'advanced-post-block' )
				: __( 'Plugin data will be preserved when uninstalled.', 'advanced-post-block' )
		] );
	}
}
new Options();
