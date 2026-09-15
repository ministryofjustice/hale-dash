<?php

/**
 * Cost & time savings — stored settings and the "what counts" model.
 *
 * Storage: option `hale_dash_block_savings` (per-site, on the dashboard site):
 *   blocks         => [ 'core/table' => ['cost' => float, 'time' => float, 'note' => string], ... ]
 *   extra_names    => newline/comma list of block names to add to the settings list
 *   excluded_sites => int[] blog IDs left out of the scan and every total
 *   hours_per_day  => float, hours in a working day (for the "≈ N working days" figure)
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * @return array{blocks: array<string, array{cost: float, time: float, note: string}>, extra_names: string, excluded_sites: int[], hours_per_day: float}
 */
function hale_dash_block_savings_defaults() {
	return [
		'blocks'         => [],
		'extra_names'    => '',
		'excluded_sites' => [],
		'hours_per_day'  => HALE_DASH_HOURS_PER_DAY,
	];
}

/**
 * Saved settings merged over the defaults.
 *
 * @return array{blocks: array<string, array{cost: float, time: float, note: string}>, extra_names: string, excluded_sites: int[], hours_per_day: float}
 */
function hale_dash_get_block_savings() {
	$saved = get_option(HALE_DASH_SAVINGS_OPTION, []);
	if (!is_array($saved)) {
		$saved = [];
	}
	$merged = array_merge(hale_dash_block_savings_defaults(), $saved);
	if (!is_array($merged['blocks'])) {
		$merged['blocks'] = [];
	}
	if (!is_array($merged['excluded_sites'])) {
		$merged['excluded_sites'] = [];
	}
	if (!is_numeric($merged['hours_per_day']) || (float) $merged['hours_per_day'] <= 0) {
		$merged['hours_per_day'] = HALE_DASH_HOURS_PER_DAY;
	}
	return $merged;
}

/**
 * Hours in a working day, as configured on the settings screen (falls back to
 * HALE_DASH_HOURS_PER_DAY). Used to turn saved hours into "≈ N working days".
 *
 * @return float
 */
function hale_dash_hours_per_day() {
	return (float) hale_dash_get_block_savings()['hours_per_day'];
}

/**
 * Site IDs the admin has ticked to leave out of the scan and the totals.
 *
 * @return int[]
 */
function hale_dash_excluded_site_ids() {
	$ids = array_map('intval', hale_dash_get_block_savings()['excluded_sites']);
	return array_values(array_filter($ids, static fn($i) => $i > 0));
}

/**
 * Network site IDs that count towards the savings (all sites minus exclusions).
 *
 * @return int[]
 */
function hale_dash_included_site_ids() {
	$all = array_map('intval', get_sites(['number' => 0, 'fields' => 'ids']));
	return array_values(array_diff($all, hale_dash_excluded_site_ids()));
}

/**
 * Blocks that have at least one positive saving set — the ones that count.
 *
 * @return array<string, array{cost: float, time: float, note: string}>
 */
function hale_dash_tracked_blocks() {
	$out = [];
	foreach (hale_dash_get_block_savings()['blocks'] as $name => $vals) {
		$cost = isset($vals['cost']) ? (float) $vals['cost'] : 0.0;
		$time = isset($vals['time']) ? (float) $vals['time'] : 0.0;
		if ($cost > 0 || $time > 0) {
			$out[$name] = [
				'cost' => $cost,
				'time' => $time,
				'note' => isset($vals['note']) ? (string) $vals['note'] : '',
			];
		}
	}
	return $out;
}

/**
 * Sanitize callback for register_setting().
 *
 * @param mixed $input
 * @return array{blocks: array<string, array{cost: float, time: float, note: string}>, extra_names: string, excluded_sites: int[], hours_per_day: float}
 */
function hale_dash_sanitize_block_savings($input) {
	$clean = hale_dash_block_savings_defaults();

	if (isset($input['extra_names'])) {
		$clean['extra_names'] = sanitize_textarea_field($input['extra_names']);
	}

	if (isset($input['hours_per_day'])) {
		$hpd = (float) $input['hours_per_day'];
		$clean['hours_per_day'] = ($hpd >= 1 && $hpd <= 24) ? $hpd : HALE_DASH_HOURS_PER_DAY;
	}

	if (isset($input['blocks']) && is_array($input['blocks'])) {
		foreach ($input['blocks'] as $name => $vals) {
			$name = sanitize_text_field($name);
			if ($name === '') {
				continue;
			}
			$cost = isset($vals['cost']) ? max(0, (float) $vals['cost']) : 0.0;
			$time = isset($vals['time']) ? max(0, (float) $vals['time']) : 0.0;
			$note = isset($vals['note']) ? sanitize_text_field($vals['note']) : '';

			// Only keep blocks that actually carry a saving — a note with no
			// saving has nothing to be a footnote on, so it's dropped too.
			if ($cost > 0 || $time > 0) {
				$clean['blocks'][$name] = ['cost' => $cost, 'time' => $time, 'note' => $note];
			}
		}
	}

	if (isset($input['excluded_sites']) && is_array($input['excluded_sites'])) {
		$ids = array_filter(array_map('intval', $input['excluded_sites']), static fn($i) => $i > 0);
		$ids = array_values(array_unique($ids));
		sort($ids);
		$clean['excluded_sites'] = $ids;
	}

	// A changed exclusion set changes the figures — force a rescan next load.
	if (hale_dash_get_block_savings()['excluded_sites'] != $clean['excluded_sites']) {
		delete_transient(HALE_DASH_SCAN_TRANSIENT);
	}

	return $clean;
}
