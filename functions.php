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