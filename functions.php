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

function get_fav_icon($src) {

    if (empty($src)) {
        return
        '<svg class="website__favicon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32">
            <style>
            </style>
            <path class="a" fill="#0b0c0c" fill-rule="evenodd" d="m19.94,10.78c-.36-.88.06-1.89.94-2.26.87-.35,1.89.07,2.25.95.36.88-.06,1.89-.93,2.24-.89.36-1.89-.06-2.26-.93Zm-4.55,3.97c-.89.36-1.31,1.38-.94,2.26.36.88,1.37,1.3,2.26.93.87-.35,1.29-1.37.93-2.24-.36-.88-1.37-1.31-2.25-.95Zm11.44-.28c.87-.35,1.29-1.37.93-2.24-.36-.88-1.38-1.31-2.25-.95-.89.36-1.31,1.38-.94,2.26.36.88,1.37,1.3,2.26.93Zm.82,3.64c.36.88,1.37,1.3,2.26.93.88-.35,1.29-1.37.93-2.24-.36-.88-1.38-1.31-2.25-.95-.89.36-1.31,1.38-.94,2.26Zm-17.78-6.39c.89.36,1.89-.06,2.26-.93s-.06-1.89-.94-2.26c-.87-.35-1.89.07-2.25.95-.36.88.06,1.89.93,2.24Zm-4.63,2.75c.89.36,1.89-.06,2.26-.93.36-.88-.06-1.89-.94-2.26-.87-.35-1.89.07-2.25.95-.36.88.06,1.89.93,2.24Zm-3.08,4.57c.89.36,1.89-.06,2.26-.93s-.06-1.89-.94-2.26c-.87-.35-1.89.07-2.25.95-.36.88.06,1.89.93,2.24ZM15.12,6.37c.07.08.14.16.22.22l-1.25,3.77v.02c-.06.19-.09.39-.09.6,0,1.02.76,1.87,1.74,2.01h.05c.08,0,.17.02.25.02s.17,0,.25-.02h.05c.98-.15,1.74-.99,1.74-2.01,0-.21-.03-.41-.09-.6v-.02l-1.25-3.77c.08-.07.16-.14.22-.22s2.17,1.14,2.17,1.14v-3.21l-2.17.69c-.06-.08-.13-.16-.21-.22s.88-2.76.88-2.76h-3.14l.88,2.75c-.07.07-.15.14-.21.22s-2.17-.68-2.17-.68v3.21l2.17-1.15h-.02Zm15.85,15.41c-1.24,1.69-2.45,4.07-3.43,8.22-3.48-.49-7.38-.77-11.51-.77s-8.03.28-11.5.77c-.99-4.15-2.19-6.53-3.43-8.22l2.33-.83c-.23,1.19-.27,1.74,0,2.5.38-.37.75-1.07,1.04-2.13l1.13,3.77c-.69-.48-1.22-.78-1.83-.79,1.07,2.32,2.43,2.92,3.29,2.75,1.06-.2,1.56-1.15,1.39-1.95-.24-1.14-1.42-1.44-1.96-.11-1.05-2.14.73-2.8,1.87-2.17-1.75-1.75-1.95-3.3-.54-5.19,1.97,1.51,2,3.01,1.11,5.11,1.15-1.32,2.95-.61,2.3,1.52-.83-1.29-1.93-.48-1.75.75.15,1.06,1.55,1.92,3.3,1.77,2.51-.22,2.66-1.96,2.73-3.39-.62-.11-1.73.46-2.68,1.79l-.35-3.99c1.03,1.07,1.97,1.28,3.01,1.31-.35-1.08-1.94-2.85-1.94-2.85h4.99s-1.59,1.77-1.94,2.85c1.04-.04,1.98-.23,3.01-1.31l-.35,3.99c-.94-1.33-2.05-1.9-2.68-1.79.07,1.44.21,3.16,2.73,3.39,1.75.15,3.15-.71,3.3-1.77.18-1.22-.92-2.03-1.75-.75-.64-2.13,1.15-2.84,2.3-1.52-.89-2.1-.87-3.59,1.11-5.11,1.42,1.89,1.21,3.43-.54,5.19,1.14-.63,2.92.04,1.87,2.17-.55-1.33-1.72-1.03-1.96.11-.17.8.33,1.75,1.39,1.95.87.16,2.22-.44,3.29-2.75-.61.02-1.14.32-1.83.79l1.13-3.77c.29,1.05.65,1.75,1.04,2.13.26-.77.22-1.32,0-2.5l2.33.83h0Z"/>
        </svg>';
    };

    return '<img class="website__favicon" src="' . $src . '" alt=""/>';
}

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

function get_language($code) {
    if ($code == "cy" || $code == "cy_GB") {
        return "<p class='govuk-body govuk-hint govuk-!-margin-bottom-0 website__explanation'>Welsh site 🏴󠁧󠁢󠁷󠁬󠁳󠁿</p>";
    } elseif ($code == "en_US" || empty($code)) { //WP uses "" to denote en-US
        return "<p class='govuk-body govuk-hint govuk-!-margin-bottom-0 website__explanation'>US English 🇺🇸</p>";
    } elseif ($code != "en_GB" && $code != "en") {
        return "<p class='govuk-body govuk-hint govuk-!-margin-bottom-0 website__explanation'>Language: $code</p>";
    }
}
