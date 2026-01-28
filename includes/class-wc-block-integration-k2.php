
<?php
use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

class WC_Block_Integration_K2 implements IntegrationInterface
{
    public function get_name(): string
    {
        return 'kkwoo-block-integration';
    }

    public function initialize(): void
    {
        $style_path = 'build/style-index.css';
        $script_path = 'build/index.js';

        $script_url = KKWOO_PLUGIN_URL . $script_path;
        $style_url = KKWOO_PLUGIN_URL . $style_path;

        $asset_path = KKWOO_PLUGIN_PATH . 'build/index.asset.php';
        $asset      = file_exists($asset_path) ? require $asset_path : [ 'dependencies' => [], 'version' => time() ];

        wp_enqueue_style(
            'kopo-kopo-blocks-style',
            $style_url,
            [],
            filemtime(KKWOO_PLUGIN_PATH . $style_path)
        );

        wp_register_script(
            'kopo-kopo-blocks-script',
            $script_url,
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_localize_script('kopo-kopo-blocks-script', 'KKWooData', [
            'plugin_url'          => plugins_url('', __FILE__),
            'k2_logo_with_name_img' => plugins_url('images/svg/k2-logo-with-name.svg', __FILE__),
            'kenyan_flag_img'     => plugins_url('images/kenyan-flag.png', __FILE__),
        ]);
    }

    public function get_script_handles(): array
    {
        return [ 'kopo-kopo-blocks-script' ];
    }

    public function get_editor_script_handles(): array
    {
        return [ 'kopo-kopo-blocks-script' ];
    }

    public function get_script_data(): array
    {
        return [];
    }
}
