<?php

// Enable error reporting and display errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
function hale_dash_setup()
{
}

add_action('after_setup_theme', 'hale_dash_setup');

// function hale_dash_enqueue_styles()
// {
//     wp_enqueue_style('hale-dash-style', hale_dash_mix_asset('/css/hale-dash-style.min.css'));
// }

// add_action('wp_enqueue_scripts', 'hale_dash_enqueue_styles');



add_action( 'wp_enqueue_scripts', 'hale_dash_enqueue_styles' );


function hale_dash_enqueue_styles() {
	wp_enqueue_style( 'hale-dash-style',
        hale_dash_mix_asset('/css/hale-dash-style.min.css'),
		array( 'hale-style' ),
		wp_get_theme()->get( 'Version' ) // This only works if you have Version defined in the style header.
	);
}


/**
 * @param $filename
 * @return string
 */
function hale_dash_mix_asset($filename)
{
    $manifest = file_get_contents(get_stylesheet_directory_uri() . '/dist/mix-manifest.json');

    $manifest = json_decode($manifest, true);

    if (!isset($manifest[$filename])) {
        error_log("Mix asset '$filename' does not exist in manifest.");
    }

    $x = $manifest;

    return get_stylesheet_uri() . '/dist' . $x;
}