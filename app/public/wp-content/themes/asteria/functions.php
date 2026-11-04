
<?php
/**
 * Asteria Theme Functions
 */

if (!defined('ABSPATH')) {
    exit;
}

function asteria_setup() {
    add_theme_support('editor-styles');

    add_editor_style(array(
        'assets/css/main.css',
        'assets/css/phase3.css',
        'assets/css/components.css'
    ));
}
add_action('after_setup_theme', 'asteria_setup');

function asteria_enqueue_assets() {
    $version = wp_get_theme()->get('Version');

    wp_enqueue_style(
        'asteria-base',
        get_stylesheet_uri(),
        array(),
        $version
    );

    $styles = array(
        'main.css',
        'phase3.css',
        'components.css'
    );

    $dependency = 'asteria-base';

    foreach ($styles as $file) {
        $path = '/assets/css/' . $file;

        if (!file_exists(get_theme_file_path($path))) {
            continue;
        }

        $handle = 'asteria-' .
            sanitize_title(str_replace('.css', '', $file));

        wp_enqueue_style(
            $handle,
            get_theme_file_uri($path),
            array($dependency),
            filemtime(get_theme_file_path($path))
        );

        $dependency = $handle;
    }
}
add_action('wp_enqueue_scripts', 'asteria_enqueue_assets');

/**
 * Organize Asteria patterns into categories.
 */
function asteria_pattern_categories() {
    $categories = array(
        'about' => 'About Sections',
        'services' => 'Service Sections',
        'portfolio' => 'Portfolio Sections',
        'call-to-action' => 'Contact & CTA Sections'
    );

    foreach ($categories as $slug => $label) {
        if (!WP_Block_Pattern_Categories_Registry::get_instance()
            ->is_registered($slug)) {
            register_block_pattern_category(
                $slug,
                array('label' => $label)
            );
        }
    }
}
add_action('init', 'asteria_pattern_categories');
