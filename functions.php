<?php

/**
 * Hale Dash theme functions and definitions
 *
 * @package   Hale Dash
 * @copyright Ministry Of Justice
 * @version   2.0
 */

/**
 * Sets up theme defaults and registers support for various WordPress features.
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function hale_dash_setup() {}

add_action('after_setup_theme', 'hale_dash_setup');

function hale_dash_enqueue_styles() {
	wp_enqueue_style( 'hale-dash-style',
        hale_dash_mix_asset('/css/hale-dash-style.min.css'),
		array( 'hale-style' ),
		wp_get_theme()->get( 'Version' )
	);
}

add_action( 'wp_enqueue_scripts', 'hale_dash_enqueue_styles' );

/**
 * Gett asset path function
 * 
 * @param $filename
 * @return string
 */
function hale_dash_mix_asset($filename)
{
    $manifest = file_get_contents(get_theme_file_path() . '/dist/mix-manifest.json');   
    $manifest = json_decode($manifest, true);

    if (!isset($manifest[$filename])) {
        error_log("Mix asset '$filename' does not exist in manifest.");
    }

    return get_theme_file_uri() . '/dist' . $manifest[$filename];
}

// function fav_icon($image) {
    
//     // From https://stackoverflow.com/questions/5701593/how-to-get-a-websites-favicon-with-php
//     // Read image path, convert to base64 encoding
//     $imageData = base64_encode(file_get_contents($image));

//     if (empty($imageData)) return;

//     // Format the image SRC:  data:{mime};base64,{data};
//     $src = 'data: '.mime_content_type($image).';base64,'.$imageData;

//     // Echo out a sample image
//     return '<img class="website__heading__favicon" src="' . $src . '">';
// }

/**
 * Check if plugin is active or not
 * return true false
 */
function is_plugin_active_on_site($plugin, $site_id = 1) {
    if (is_multisite()) {
        switch_to_blog($site_id);
        $is_active = is_plugin_active($plugin);
        restore_current_blog();
    } else {
        $is_active = is_plugin_active($plugin);
    }
    return $is_active;
}

/**
 * Key computed metrics to display on the dashboard
 */
require get_stylesheet_directory() . '/inc/dashboard-metrics.php';

/**
 * All functions needed for the various notification banners we add ie birthday, events etc
 */
require get_stylesheet_directory() . '/inc/notification-banner.php';