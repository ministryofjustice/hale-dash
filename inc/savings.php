<?php

/**
 * Cost & time savings — loader.
 *
 * Estimates the money and delivery time the platform saves by hosting sites
 * centrally, built from the WordPress blocks each site uses. Settings >
 * Cost & time savings configures a £/hours saving per block; the published
 * "Cost & Time Savings" page template (page-savings.php) shows the totals.
 *
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

$hale_dash_savings_dir = get_stylesheet_directory() . '/inc/savings';

require $hale_dash_savings_dir . '/config.php';
require $hale_dash_savings_dir . '/data.php';          // stored settings + sanitize
require $hale_dash_savings_dir . '/block-titles.php';  // block name => human title
require $hale_dash_savings_dir . '/scan.php';          // network content scan
require $hale_dash_savings_dir . '/calculate.php';     // headline figures
require $hale_dash_savings_dir . '/render.php';        // public report body
require $hale_dash_savings_dir . '/recalculate.php';   // AJAX + no-JS recalc
require $hale_dash_savings_dir . '/frontend.php';      // body class, script enqueue
require $hale_dash_savings_dir . '/settings-page.php'; // admin screen

unset($hale_dash_savings_dir);
