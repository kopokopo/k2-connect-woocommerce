<?php
/**
 * Block integration class.
 *
 * @package Kopo_Kopo_for_WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;
/**
 * Handles the addition of block support for Kopo Kopo for WooCommerce.
 *
 * Registers server-side data, custom scripts and styles to be used with the block based editor and frontend.
 */
class KKWoo_Block_Integration implements IntegrationInterface {

	/**
	 * Returns the unique integration name.
	 *
	 * @return string Integration identifier.
	 */
	public function get_name(): string {
		return 'kkwoo-block-integration';
	}

	/**
	 * Initialize the block integration.
	 *
	 * Enqueues block styles and registers block scripts, including
	 * localizing plugin-specific data for JavaScript.
	 *
	 * @return void
	 */
	public function initialize(): void {
		$style_path  = 'build/style-index.css';
		$script_path = 'build/index.js';

		$script_url = KKWOO_PLUGIN_URL . $script_path;
		$style_url  = KKWOO_PLUGIN_URL . $style_path;

		$asset_path = KKWOO_PLUGIN_PATH . 'build/index.asset.php';
		$asset      = file_exists( $asset_path ) ? require $asset_path : array(
			'dependencies' => array(),
			'version'      => time(),
		);

		wp_enqueue_style(
			'kopo-kopo-blocks-style',
			$style_url,
			array(),
			filemtime( KKWOO_PLUGIN_PATH . $style_path )
		);

		wp_register_script(
			'kopo-kopo-blocks-script',
			$script_url,
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_localize_script(
			'kopo-kopo-blocks-script',
			'KKWooData',
			array(
				'plugin_url'            => plugins_url( '', __FILE__ ),
				'k2_logo_with_name_img' => plugins_url( 'images/svg/k2-logo-with-name.svg', __FILE__ ),
				'kenyan_flag_img'       => plugins_url( 'images/kenyan-flag.png', __FILE__ ),
			)
		);
	}

	/**
	 * Returns the script handles used by the front-end block.
	 *
	 * @return string[] Array of script handle identifiers.
	 */
	public function get_script_handles(): array {
		return array( 'kopo-kopo-blocks-script' );
	}

	/**
	 * Returns the script handles used by the block editor.
	 *
	 * @return string[] Array of editor script handle identifiers.
	 */
	public function get_editor_script_handles(): array {
		return array( 'kopo-kopo-blocks-script' );
	}

	/**
	 * Returns data passed to the block scripts.
	 *
	 * @return array Block script data array.
	 */
	public function get_script_data(): array {
		return array();
	}
}
