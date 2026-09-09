<?php

/**
 * Cost & time savings — shared constants.
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Option holding the per-block figures, "other block names" and excluded sites. */
const HALE_DASH_SAVINGS_OPTION = 'hale_dash_block_savings';

/** Transient: the network content scan (block => sites using it). Rebuilt daily. */
const HALE_DASH_SCAN_TRANSIENT = 'hale_dash_network_block_scan';

/** Transient: block name => title harvested from plugin/theme files. Rebuilt weekly. */
const HALE_DASH_DISK_TITLES_TRANSIENT = 'hale_dash_disk_block_titles';

/** The page template that renders the public Cost & Time Savings report. */
const HALE_DASH_SAVINGS_TEMPLATE = 'page-savings.php';

/** Default hours in a working day (overridable on the settings screen). */
const HALE_DASH_HOURS_PER_DAY = 7.5;

/**
 * Where hale_dash_disk_block_titles() looks for human block titles — plugin
 * folder slugs (under wp-content/plugins) and theme folder slugs (under the
 * themes root). Deliberately narrow: walking the whole wp-content tree was the
 * main cost of that scan. Add a slug here if another plugin/theme starts
 * registering blocks worth pricing, rather than widening back to everything.
 */
const HALE_DASH_SAVINGS_TITLE_PLUGINS = ['wp-moj-blocks', 'website-builder-blocks'];
const HALE_DASH_SAVINGS_TITLE_THEMES  = ['justice'];
