<?php

/**
 * Cost & time savings — the two "recalculate block usage" entry points.
 *
 * Both clear the content-scan transient so the next figures are fresh. They
 * deliberately leave the disk block-title harvest alone (a much slower
 * filesystem walk, only worth repeating via "Rescan network" after a deploy).
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * AJAX: rebuild the scan, then return the re-rendered report body as HTML so the
 * front-end can swap it in without a full reload. Any logged-in user, nonce-checked.
 */
add_action('wp_ajax_hale_dash_recalculate', static function () {
	check_ajax_referer('hale_dash_refresh_blocks', 'hale_dash_refresh_nonce');

	delete_transient(HALE_DASH_SCAN_TRANSIENT);
	hale_dash_scan_network_blocks(true);

	$savings = hale_dash_calculate_savings();

	ob_start();
	hale_dash_render_savings_content($savings);
	$html = ob_get_clean();

	wp_send_json_success(['html' => $html]);
});

/**
 * No-JS fallback: handle the "Recalculate" form POST on the front-end page.
 * Clearing the transient is enough — the rebuild happens when
 * hale_dash_calculate_savings() next hits the cold cache during this same render.
 *
 * @return bool True when a valid recalculate request was handled.
 */
function hale_dash_maybe_refresh_block_usage() {
	if (
		isset($_SERVER['REQUEST_METHOD'], $_POST['hale_dash_refresh_blocks'], $_POST['hale_dash_refresh_nonce']) &&
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		is_user_logged_in() &&
		wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hale_dash_refresh_nonce'])), 'hale_dash_refresh_blocks')
	) {
		delete_transient(HALE_DASH_SCAN_TRANSIENT);
		return true;
	}
	return false;
}
