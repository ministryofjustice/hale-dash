<?php

/**
 * Cost & time savings — the network content scan.
 *
 * Walks published content on every included site (once), records which sites use
 * each block type, and caches the result for a day. Also the site-name lookups
 * and the "candidate blocks" list the settings screen offers inputs for.
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Walk a parsed-blocks tree and record every block name found.
 *
 * @param array<int, array<string, mixed>> $blocks
 * @param array<string, true>              $found
 */
function hale_dash_collect_block_names(array $blocks, array &$found) {
	foreach ($blocks as $block) {
		if (!empty($block['blockName'])) {
			$found[$block['blockName']] = true;
		}
		if (!empty($block['innerBlocks'])) {
			hale_dash_collect_block_names($block['innerBlocks'], $found);
		}
	}
}

/**
 * Scan published content across EVERY included site on the multisite. Records,
 * per block type, which sites use it (once per site) — `counts` is just the
 * length of each `block_sites` list. Cached for a day.
 *
 * All post types are scanned; the published status and the presence of a block
 * delimiter narrow it down (revisions/autosaves are 'inherit', menu items and
 * wp_global_styles have no block comments, so they fall out naturally).
 *
 * @param bool $force Rebuild even if a cached scan exists.
 * @return array{at: int, sites: int, counts: array<string, int>, block_sites: array<string, int[]>}
 */
function hale_dash_scan_network_blocks($force = false) {
	if (!$force) {
		$cached = get_transient(HALE_DASH_SCAN_TRANSIENT);
		if (is_array($cached) && isset($cached['counts'], $cached['block_sites'])) {
			return $cached;
		}
	}

	global $wpdb;
	$block_sites = [];
	$like        = '%' . $wpdb->esc_like('<!-- wp:') . '%';

	// All network sites, minus any the admin has excluded in settings.
	$site_ids = hale_dash_included_site_ids();

	foreach ($site_ids as $site_id) {
		switch_to_blog($site_id);

		$contents = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT post_content FROM {$wpdb->posts}
				WHERE post_status = 'publish'
				AND post_content LIKE %s",
				$like
			)
		);

		restore_current_blog();

		$site_found = [];
		foreach ($contents as $content) {
			hale_dash_collect_block_names(parse_blocks($content), $site_found);
		}
		foreach (array_keys($site_found) as $name) {
			$block_sites[$name][] = (int) $site_id;
		}
	}

	ksort($block_sites);

	$counts = [];
	foreach ($block_sites as $name => $ids) {
		$counts[$name] = count($ids);
	}

	$result = [
		'at'          => time(),
		'sites'       => count($site_ids),
		'counts'      => $counts,
		'block_sites' => $block_sites,
	];
	set_transient(HALE_DASH_SCAN_TRANSIENT, $result, DAY_IN_SECONDS);
	return $result;
}

/**
 * The cached scan without ever triggering a (slow) rebuild. A cache from an
 * older version that lacks the per-block site lists counts as stale.
 *
 * @return array{at: int, sites: int, counts: array<string, int>, block_sites: array<string, int[]>}|null
 */
function hale_dash_peek_block_scan() {
	$cached = get_transient(HALE_DASH_SCAN_TRANSIENT);
	return is_array($cached) && isset($cached['counts'], $cached['block_sites']) ? $cached : null;
}

/**
 * blog_id => site name, for every site on the network. Memoised per request.
 *
 * @return array<int, string>
 */
function hale_dash_site_name_map() {
	static $map = null;
	if ($map !== null) {
		return $map;
	}
	$map = [];
	foreach (get_sites(['number' => 0]) as $site) {
		$sid       = (int) $site->blog_id;
		$map[$sid] = get_blog_option($sid, 'blogname') ?: untrailingslashit($site->domain . $site->path);
	}
	return $map;
}

/**
 * blog_id => site name for every site using a block, from the cached scan,
 * ordered by site ID ([] if none / no scan).
 *
 * @return array<int, string>
 */
function hale_dash_block_sites($name) {
	$scan = hale_dash_peek_block_scan();
	if (!$scan || empty($scan['block_sites'][$name])) {
		return [];
	}
	$map = hale_dash_site_name_map();
	$out = [];
	foreach ($scan['block_sites'][$name] as $sid) {
		$sid       = (int) $sid;
		$out[$sid] = $map[$sid] ?? ('#' . $sid);
	}
	ksort($out);
	return $out;
}

/**
 * block name => number of sites using it, across the whole network.
 *
 * Uses the cached scan. On a cold cache only a logged-in user triggers the
 * (slow) network-wide rebuild; logged-out visitors get zeros until it is warm.
 *
 * @return array<string, int>
 */
function hale_dash_block_site_counts() {
	$cached = hale_dash_peek_block_scan();
	if ($cached) {
		return $cached['counts'];
	}
	if (!is_user_logged_in()) {
		return [];
	}
	return hale_dash_scan_network_blocks()['counts'];
}

/**
 * Every block name the settings screen can offer an input for: blocks discovered
 * anywhere on the network, blocks with a known title, blocks already saved, plus
 * any "other block names" the admin has typed in. Never triggers a rebuild.
 *
 * @return string[]
 */
function hale_dash_candidate_block_names() {
	$scan  = hale_dash_peek_block_scan();
	$saved = hale_dash_get_block_savings();

	$names = array_keys(hale_dash_all_block_titles());

	if ($scan) {
		$names = array_merge($names, array_keys($scan['counts']));
	}

	$names = array_merge($names, array_keys($saved['blocks']));

	foreach (preg_split('/[\r\n,]+/', (string) $saved['extra_names']) as $extra) {
		$extra = trim($extra);
		if ($extra !== '') {
			$names[] = $extra;
		}
	}

	$names = array_values(array_unique(array_filter($names)));
	sort($names);
	return $names;
}
