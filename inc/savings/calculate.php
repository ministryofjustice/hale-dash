<?php

/**
 * Cost & time savings — the headline figures, and number formatting helpers.
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Headline figures + per-block breakdown for the Cost & Time Savings page.
 *
 * @return array{
 *   rows: array<int, array<string, mixed>>,
 *   total_cost: float, total_time_hours: float, total_time_days: float,
 *   site_count: int, tracked_count: int, calculated_at: int
 * }
 */
function hale_dash_calculate_savings() {
	$tracked = hale_dash_tracked_blocks();
	$usage   = hale_dash_block_site_counts();
	$titles  = hale_dash_all_block_titles();

	// Peek after block_site_counts() above may have built the scan.
	$scan        = hale_dash_peek_block_scan();
	$block_sites = $scan['block_sites'] ?? [];
	$site_names  = hale_dash_site_name_map();

	$rows       = [];
	$total_cost = 0.0;
	$total_time = 0.0;

	foreach ($tracked as $name => $vals) {
		$ids        = $block_sites[$name] ?? [];
		$sites      = isset($usage[$name]) ? (int) $usage[$name] : count($ids);
		$cost_total = $vals['cost'] * $sites;
		$time_total = $vals['time'] * $sites;

		$total_cost += $cost_total;
		$total_time += $time_total;

		$site_map = [];
		foreach ($ids as $sid) {
			$sid            = (int) $sid;
			$site_map[$sid] = $site_names[$sid] ?? ('#' . $sid);
		}
		ksort($site_map);

		$names = array_values($site_map);
		sort($names, SORT_NATURAL | SORT_FLAG_CASE);

		$rows[] = [
			'name'       => $name,
			'title'      => hale_dash_block_label($name, $titles),
			'cost_each'  => $vals['cost'],
			'time_each'  => $vals['time'],
			'note'       => $vals['note'],
			'sites'      => $sites,
			'site_ids'   => $ids,
			'site_names' => $names,
			'site_map'   => $site_map,
			'cost_total' => $cost_total,
			'time_total' => $time_total,
		];
	}

	usort($rows, static fn($a, $b) => $b['cost_total'] <=> $a['cost_total']);

	return [
		'rows'             => $rows,
		'total_cost'       => $total_cost,
		'total_time_hours' => $total_time,
		'total_time_days'  => $total_time / hale_dash_hours_per_day(),
		'site_count'       => $scan['sites'] ?? count(hale_dash_included_site_ids()),
		'tracked_count'    => count($tracked),
		'calculated_at'    => $scan['at'] ?? 0,
	];
}

/**
 * Format a number as whole GBP.
 */
function hale_dash_gbp($value) {
	return '£' . number_format((float) $value, 0);
}

/**
 * Drop trailing zeros from a small decimal ("0.50" -> "0.5", "8.00" -> "8").
 */
function hale_dash_trim_decimal($value) {
	$s = number_format((float) $value, 2, '.', '');
	return rtrim(rtrim($s, '0'), '.');
}
