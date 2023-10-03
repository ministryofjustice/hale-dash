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
function hale_dash_setup()
{
}

add_action('after_setup_theme', 'hale_dash_setup');

function hale_dash_enqueue_styles()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
    wp_enqueue_style('child-style', get_stylesheet_directory_uri() . '/style.css', array('parent-style'));

    // Add your custom CSS styles here
    wp_enqueue_style('custom-style', get_stylesheet_directory_uri() . '/custom.css', array('child-style'));
}

add_action('wp_enqueue_scripts', 'hale_dash_enqueue_styles');
