<?php

/**
 * Cost & time savings — front-end wiring (body class, script enqueue, page URL).
 * The markup itself is in render.php / page-savings.php.
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Body class so the front-end template (and its print styles) can target the
 * Cost & Time Savings page specifically.
 */
add_filter('body_class', static function ($classes) {
	if (is_page_template(HALE_DASH_SAVINGS_TEMPLATE)) {
		$classes[] = 'hale-dash-savings-page';
	}
	return $classes;
});

/**
 * Front-end page behaviour (print / drill-down / AJAX recalculate).
 * Only on the Cost & Time Savings template.
 */
add_action('wp_enqueue_scripts', static function () {
	if (!is_page_template(HALE_DASH_SAVINGS_TEMPLATE)) {
		return;
	}
	wp_enqueue_script(
		'hale-dash-savings-frontend',
		get_theme_file_uri() . '/dist/js/savings-frontend.js',
		[],
		hale_dash_asset_version('/js/savings-frontend.js'),
		true
	);
});

/**
 * URL of the published Page using the "Cost & Time Savings" template, if any.
 * Resolved by template so it survives slug/title changes. 
 *
 * @return string Permalink, or '' when no such Page exists.
 */
function hale_dash_savings_page_url() {
	static $url = null;
	if ($url !== null) {
		return $url;
	}

	$pages = get_pages([
		'meta_key'    => '_wp_page_template',
		'meta_value'  => HALE_DASH_SAVINGS_TEMPLATE,
		'number'      => 1,
		'post_status' => 'publish',
	]);

	$url = $pages ? (string) get_permalink($pages[0]->ID) : '';
	return $url;
}
