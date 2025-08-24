<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?> 
</head>
<body <?php body_class(); ?>>

<div class="k2-block-payment-page">
    <?php
    $html_file = plugin_dir_path(__FILE__) . 'k2-block-payment-page.html';

if (file_exists($html_file)) {
    $html_content = file_get_contents($html_file);

    // Replace relative image paths with absolute plugin URLs
    $plugin_url = plugin_dir_url(dirname(dirname(__FILE__))) . '/';

    // Handle possible relative path patterns
    $replacements = [
        '../../images/' => $plugin_url . 'images/',
    ];

    $html_content = str_replace(array_keys($replacements), array_values($replacements), $html_content);

    // Process with do_blocks
    echo do_blocks($html_content);
} else {
    echo '<p>Payment page content not found.</p>';
    error_log('K2 HTML file not found: ' . $html_file);
}
?>
</div>

<?php wp_footer(); ?> 
</body>
</html>
