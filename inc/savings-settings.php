<?php

/**
 * Cost & time savings — block-based model.
 *
 * Settings > Cost & time savings lets an admin set a cost (£) and a time (hours)
 * saving against individual WordPress block types. Any site that uses a given
 * block — anywhere, any number of times — counts once towards that block's
 * saving. Totals are (per-block saving) x (number of sites using that block),
 * summed across every tracked block.
 *
 * Block list: gathered from the serialized content of every site on the
 * multisite (a "network block scan"), unioned with the blocks registered on
 * the dashboard site. The scan is cached for a day and can be re-run from the
 * settings page or the front-end page.
 *
 * NOTE: scaffolding. Nothing is set by default; the per-block figures and the
 * "once per site" rule are the model. Refine the detection / which sites and
 * post types count (see TODO markers) once the approach is agreed.
 *
 * Storage: option `hale_dash_block_savings` (per-site, on the dashboard site).
 *
 * @package   Hale Dash
 * @copyright Ministry Of Justice
 */

if (!defined('ABSPATH')) {
	exit;
}

const HALE_DASH_SAVINGS_OPTION       = 'hale_dash_block_savings';
const HALE_DASH_SCAN_TRANSIENT       = 'hale_dash_network_block_scan';
const HALE_DASH_DISK_TITLES_TRANSIENT = 'hale_dash_disk_block_titles';

/**
 * @return array{blocks: array<string, array{cost: float, time: float}>, extra_names: string, excluded_sites: int[]}
 */
function hale_dash_block_savings_defaults() {
	return [
		'blocks'         => [],
		'extra_names'    => '',
		'excluded_sites' => [],
	];
}

/**
 * Saved settings merged over the defaults.
 *
 * @return array{blocks: array<string, array{cost: float, time: float}>, extra_names: string, excluded_sites: int[]}
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
	return $merged;
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
 * name => human title for every block type registered on THIS site. Used only
 * to give discovered blocks a friendly label where we can.
 *
 * @return array<string, string>
 */
function hale_dash_registered_block_titles() {
	if (!class_exists('WP_Block_Type_Registry')) {
		return [];
	}
	$out = [];
	foreach (WP_Block_Type_Registry::get_instance()->get_all_registered() as $name => $type) {
		$out[$name] = isset($type->title) && $type->title ? $type->title : $name;
	}
	ksort($out);
	return $out;
}

/**
 * name => title harvested from `block.json` files AND from register_block_type()
 * / acf_register_block_type() calls in plugin/theme PHP. Catches blocks that
 * aren't registered on the dashboard site (e.g. mojblocks/*, acf/*).
 *
 * Best effort: only string literals are read, so registrations built from
 * variables are missed — those still fall back to the slug prettifier. Cached
 * for a week (disk only changes on deploy); cleared on a network rescan.
 *
 * @return array<string, string>
 */
function hale_dash_disk_block_titles() {
	$cached = get_transient(HALE_DASH_DISK_TITLES_TRANSIENT);
	if (is_array($cached)) {
		return $cached;
	}

	// Don't walk the filesystem on an anonymous front-end request; those fall
	// back to registered titles + the slug prettifier until an admin warms it.
	if (!is_user_logged_in()) {
		return [];
	}

	// Deliberately narrow, not "every plugin and every theme" — walking the
	// whole wp-content tree was the main cost of this scan. These are the
	// known sources of the custom blocks we care about (moj/*, mojblocks/*).
	// TODO: if another theme or plugin starts registering blocks worth
	// pricing, add its path here rather than widening back to everything.
	$roots = array_unique(array_filter([
		defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR . '/wp-moj-blocks' : null,
		defined('WP_PLUGIN_DIR') ? WP_PLUGIN_DIR . '/website-builder-blocks' : null,
		get_theme_root() . '/justice',
	]));

	// 'assets' catches things like website-builder-blocks' icon library
	// (13,000+ tiny directories, no block code) — pure dead weight to walk.
	$skip = [
		'node_modules', 'vendor', '.git', 'tests', 'test', '__tests__',
		'assets', 'images', 'img', 'icons', 'fonts', 'languages',
	];
	$code = ['php', 'js', 'jsx', 'ts', 'tsx'];
	$out  = [];
	$seen = []; // realpath => true, guards against symlink loops / double scans

	foreach ($roots as $root) {
		if (!is_dir($root)) {
			continue;
		}

		try {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveCallbackFilterIterator(
					// FOLLOW_SYMLINKS: plugins/themes are often symlinked into place.
					new RecursiveDirectoryIterator(
						$root,
						FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
					),
					static function ($current) use ($skip, $code, &$seen) {
						if ($current->isDir()) {
							if (in_array($current->getFilename(), $skip, true)) {
								return false;
							}
							$real = $current->getRealPath();
							if ($real === false || isset($seen[$real])) {
								return false;
							}
							$seen[$real] = true;
							return true;
						}
						if ($current->getFilename() === 'block.json') {
							return true;
						}
						// Code files whose path hints at block registration.
						return in_array(strtolower($current->getExtension()), $code, true)
							&& stripos($current->getPathname(), 'block') !== false;
					}
				),
				RecursiveIteratorIterator::LEAVES_ONLY,
				RecursiveIteratorIterator::CATCH_GET_CHILD
			);
		} catch (Exception $e) {
			continue;
		}

		foreach ($iterator as $file) {
			$path = $file->getPathname();

			if ($file->getFilename() === 'block.json') {
				$data = json_decode((string) @file_get_contents($path), true);
				if (is_array($data) && !empty($data['name']) && is_string($data['name'])) {
					$out[$data['name']] = isset($data['title']) && is_string($data['title'])
						? $data['title']
						: $data['name'];
				}
				continue;
			}

			$src = (string) @file_get_contents($path);

			if (strpos($src, 'register_block_type') !== false) {
				hale_dash_extract_php_block_titles($src, $out);
			}
			if (strpos($src, 'registerBlockType') !== false) {
				hale_dash_extract_js_block_titles($src, $out);
			}
		}
	}

	ksort($out);
	set_transient(HALE_DASH_DISK_TITLES_TRANSIENT, $out, WEEK_IN_SECONDS);
	return $out;
}

/**
 * Pull literal block name/title pairs out of a PHP source string.
 *
 * @param array<string, string> $out Accumulator, modified in place.
 */
function hale_dash_extract_php_block_titles($src, array &$out) {
	$title_re = '/[\'"]title[\'"]\s*=>\s*(?:_[_ex]\(\s*)?[\'"]([^\'"]+)[\'"]/';

	// Look just past each register_block_type('ns/name' ... for a literal title.
	if (preg_match_all(
		'/register_block_type(?:_from_metadata)?\s*\(\s*[\'"]([a-z0-9][a-z0-9-]*\/[a-z0-9][a-z0-9-]*)[\'"]/i',
		$src,
		$matches,
		PREG_SET_ORDER | PREG_OFFSET_CAPTURE
	)) {
		foreach ($matches as $m) {
			$name = $m[1][0];
			if (!isset($out[$name])) {
				$out[$name] = $name;
			}
			$window = hale_dash_block_call_window($src, $m[0][1], 500);
			if (preg_match($title_re, $window, $tm)) {
				$out[$name] = $tm[1];
			}
		}
	}

	// acf_register_block_type( array( 'name' => 'x', 'title' => 'X', ... ) )
	if (
		strpos($src, 'acf_register_block_type') !== false &&
		preg_match_all('/acf_register_block_type(?:_lazy)?\s*\(/i', $src, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE)
	) {
		foreach ($matches as $m) {
			$window = hale_dash_block_call_window($src, $m[0][1], 800);
			if (!preg_match('/[\'"]name[\'"]\s*=>\s*[\'"]([a-z0-9_-]+)[\'"]/i', $window, $nm)) {
				continue;
			}
			$name = strpos($nm[1], '/') !== false ? $nm[1] : 'acf/' . $nm[1];
			if (!isset($out[$name])) {
				$out[$name] = $name;
			}
			if (preg_match($title_re, $window, $tm)) {
				$out[$name] = $tm[1];
			}
		}
	}
}

/**
 * A slice of source starting at $offset, up to $len chars, truncated before the
 * next $needle occurrence so a title can't bleed in from the call after.
 */
function hale_dash_block_call_window($src, $offset, $len, $needle = 'register_block_type') {
	$window = substr($src, $offset, $len);
	$next   = strpos($window, $needle, 5);
	return $next !== false ? substr($window, 0, $next) : $window;
}

/**
 * Pull literal block name/title pairs out of JS/TS source:
 * registerBlockType( 'ns/name', { ... title: 'X' ... } ).
 *
 * @param array<string, string> $out Accumulator, modified in place.
 */
function hale_dash_extract_js_block_titles($src, array &$out) {
	if (!preg_match_all(
		'/registerBlockType\s*\(\s*[\'"`]([a-z0-9][a-z0-9-]*\/[a-z0-9][a-z0-9-]*)[\'"`]/i',
		$src,
		$matches,
		PREG_SET_ORDER | PREG_OFFSET_CAPTURE
	)) {
		return;
	}

	foreach ($matches as $m) {
		$name = $m[1][0];
		if (!isset($out[$name])) {
			$out[$name] = $name;
		}
		$window = hale_dash_block_call_window($src, $m[0][1], 400, 'registerBlockType');
		if (preg_match('/\btitle\s*:\s*(?:__\(\s*)?[\'"`]([^\'"`]+)[\'"`]/', $window, $tm)) {
			$out[$name] = $tm[1];
		}
	}
}

/**
 * name => title from every source. A real registered title (dashboard site) wins;
 * a bare name fallback from the registry only fills a gap the disk harvest left,
 * so a title-less registration can't hide a name we found in block.json / code.
 *
 * @return array<string, string>
 */
function hale_dash_all_block_titles() {
	static $map = null;
	if ($map === null) {
		$map = hale_dash_disk_block_titles();
		foreach (hale_dash_registered_block_titles() as $name => $title) {
			if ($title !== $name || !isset($map[$name]) || $map[$name] === '') {
				$map[$name] = $title;
			}
		}
	}
	return $map;
}

/**
 * Display label for a block name. Uses the registered title when there is one;
 * otherwise prettifies the slug — "mojblocks/featured-news" => "Featured News
 * (mojblocks)", "moj/inline-menu" => "Inline Menu (moj)". Blocks from plugins
 * not active on the dashboard site have no registered title, so this is what
 * they fall back to.
 *
 * @param array<string, string>|null $titles Defaults to hale_dash_all_block_titles().
 */
function hale_dash_block_label($name, ?array $titles = null) {
	$titles = $titles ?? hale_dash_all_block_titles();
	if (isset($titles[$name]) && $titles[$name] !== $name) {
		return $titles[$name];
	}

	$parts     = explode('/', $name, 2);
	$namespace = count($parts) === 2 ? $parts[0] : '';
	$slug      = (string) end($parts);

	$pretty = ucwords(trim(str_replace(['-', '_'], ' ', $slug)));
	if ($pretty === '') {
		return $name;
	}
	if ($namespace !== '' && $namespace !== 'core') {
		$pretty .= ' (' . $namespace . ')';
	}
	return $pretty;
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
 * Scan published content across EVERY site on the multisite. Records, per block
 * type, which sites use it (once per site) — `counts` is just the length of
 * each `block_sites` list. Cached for a day.
 *
 * All post types are scanned; only the published status and the presence of a
 * block delimiter narrow it down (revisions/autosaves are 'inherit', menu items
 * and wp_global_styles have no block comments, so they fall out naturally).
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
 * Every block name we can offer an input for: blocks discovered anywhere on the
 * network, blocks registered on this site, blocks already saved, plus any
 * "other block names" the admin has typed in. Never triggers a rebuild.
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

/**
 * Headline figures for the Cost & Time Savings page.
 *
 * @return array<string, mixed>
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
		'total_time_days'  => $total_time / 7.5, // TODO: confirm hours-per-working-day.
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

/**
 * Renders the dynamic body of the Cost & Time Savings page — everything that
 * depends on $savings (heading through the print footer). Shared by the normal
 * page render and the AJAX recalculate handler, so the two can never drift.
 *
 * @param array<string, mixed> $savings From hale_dash_calculate_savings().
 */
function hale_dash_render_savings_content($savings) {
	?>
	<h1 class="govuk-heading-xl govuk-!-margin-bottom-2">Cost &amp; time savings</h1>

	<?php if ($savings['tracked_count'] === 0) : ?>

		<p class="govuk-body-l">
			No block savings have been configured yet. Set a cost and time saving
			per block under <strong>Settings &rsaquo; Cost &amp; time savings</strong>.
		</p>

	<?php else : ?>

		<p class="govuk-body-l govuk-!-margin-bottom-4">
			Estimated saving across
			<strong><?php echo esc_html(number_format($savings['site_count'])); ?></strong>
			sites, from
			<strong><?php echo esc_html(number_format($savings['tracked_count'])); ?></strong>
			block <?php echo esc_html(_n('type', 'types', $savings['tracked_count'])); ?>.
			Each site is counted once per block, however many times the block appears.
		</p>

		<div class="hale-dash-savings__actions">
			<button type="button" class="hale-dash-savings__button" data-hd-print>
				<svg class="hale-dash-savings__button-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
					<path d="M5 1a1 1 0 0 0-1 1v3h8V2a1 1 0 0 0-1-1zm7 4V2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v3H2a2 2 0 0 0-2 2v4a1 1 0 0 0 1 1h2v1a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h2a1 1 0 0 0 1-1V7a2 2 0 0 0-2-2zM5 11h6v3H5zm7-1.5a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5"/>
				</svg>
				Print
			</button>

			<?php if (is_user_logged_in()) : ?>
				<form method="post" class="hale-dash-savings__refresh" data-hd-recalculate>
					<?php wp_nonce_field('hale_dash_refresh_blocks', 'hale_dash_refresh_nonce'); ?>
					<button type="submit" name="hale_dash_refresh_blocks" value="1" class="hale-dash-savings__button">
						<svg class="hale-dash-savings__button-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
							<path d="M8 3a5 5 0 1 0 4.546 2.914.75.75 0 0 1 1.364-.628A6.5 6.5 0 1 1 8 1.5v1.5z"/>
							<path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466"/>
						</svg>
						Recalculate block usage
					</button>
				</form>
			<?php endif; ?>

			<span class="hale-dash-savings__progress" id="hale-dash-savings-progress" role="status" aria-live="polite" hidden>
				<span class="hale-dash-savings__progress-track"><span class="hale-dash-savings__progress-fill"></span></span>
				<span class="hale-dash-savings__progress-text">Recalculating&hellip;</span>
			</span>
		</div>

		<?php if (!empty($savings['calculated_at'])) : ?>
			<p class="govuk-body-s govuk-hint hale-dash-savings__calculated">
				Block usage last calculated
				<?php echo esc_html(wp_date('j F Y \a\t g:ia', (int) $savings['calculated_at'])); ?>
				(<?php echo esc_html(human_time_diff((int) $savings['calculated_at'])); ?> ago).
			</p>
		<?php endif; ?>

		<div class="hale-dash-metrics hale-dash-savings__metrics">
			<div class="hale-dash-metric">
				<h2 class="govuk-heading-s govuk-!-margin-bottom-1">Total cost saving</h2>
				<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo esc_html(hale_dash_gbp($savings['total_cost'])); ?></span>
			</div>
			<div class="hale-dash-metric">
				<h2 class="govuk-heading-s govuk-!-margin-bottom-1">Total time saving</h2>
				<span class="govuk-heading-l govuk-!-margin-bottom-0"><?php echo esc_html(number_format($savings['total_time_hours'])); ?> hrs</span>
				<p class="govuk-hint govuk-body-s govuk-!-margin-bottom-0">&asymp; <?php echo esc_html(number_format($savings['total_time_days'], 1)); ?> working days</p>
			</div>
		</div>

		<h2 class="govuk-heading-l">Breakdown by block</h2>
		<div class="hale-dash-savings__table-wrap">
			<table class="govuk-table">
				<caption class="govuk-table__caption govuk-table__caption--m govuk-visually-hidden">Savings by block</caption>
				<thead class="govuk-table__head">
					<tr class="govuk-table__row">
						<th scope="col" class="govuk-table__header">Block</th>
						<th scope="col" class="govuk-table__header govuk-table__header--numeric">Saving / site</th>
						<th scope="col" class="govuk-table__header govuk-table__header--numeric">Time / site</th>
						<th scope="col" class="govuk-table__header govuk-table__header--numeric">Sites using</th>
						<th scope="col" class="govuk-table__header govuk-table__header--numeric">Cost total</th>
						<th scope="col" class="govuk-table__header govuk-table__header--numeric">Time total</th>
					</tr>
				</thead>
				<tbody class="govuk-table__body">
					<?php
					$hd_footnotes = [];
					foreach ($savings['rows'] as $row) :
						$hd_detail_id = 'hd-fe-sites-' . sanitize_title($row['name']);
						$hd_fn_num    = null;
						if (!empty($row['note'])) {
							$hd_footnotes[] = ['title' => $row['title'], 'note' => $row['note']];
							$hd_fn_num      = count($hd_footnotes);
						}
						?>
						<tr class="govuk-table__row">
							<th scope="row" class="govuk-table__header">
								<?php echo esc_html($row['title']); ?>
								<?php if ($hd_fn_num) : ?>
									<sup class="hale-dash-savings__fn-ref">
										<a href="#hd-fn-<?php echo esc_attr($hd_fn_num); ?>" id="hd-fnref-<?php echo esc_attr($hd_fn_num); ?>">[<?php echo esc_html($hd_fn_num); ?>]</a>
									</sup>
								<?php endif; ?>
								<span class="hale-dash-savings__block-name"><?php echo esc_html($row['name']); ?></span>
							</th>
							<td class="govuk-table__cell govuk-table__cell--numeric"><?php echo esc_html(hale_dash_gbp($row['cost_each'])); ?></td>
							<td class="govuk-table__cell govuk-table__cell--numeric"><?php echo esc_html(hale_dash_trim_decimal($row['time_each'])); ?> hrs</td>
							<td class="govuk-table__cell govuk-table__cell--numeric">
								<?php if (!empty($row['site_map'])) : ?>
									<button type="button" class="hale-dash-savings__sites-toggle"
										aria-expanded="false" aria-controls="<?php echo esc_attr($hd_detail_id); ?>"
										aria-label="<?php echo esc_attr(sprintf(
											/* translators: 1: number of sites, 2: block name */
											_n('%1$s site uses %2$s — show list', '%1$s sites use %2$s — show list', $row['sites'], 'hale-dash'),
											number_format($row['sites']),
											$row['title']
										)); ?>">
										<?php echo esc_html(number_format($row['sites'])); ?><span class="hale-dash-savings__sites-caret" aria-hidden="true">&#9656;</span>
									</button>
								<?php else : ?>
									<?php echo esc_html(number_format($row['sites'])); ?>
								<?php endif; ?>
							</td>
							<td class="govuk-table__cell govuk-table__cell--numeric"><?php echo esc_html(hale_dash_gbp($row['cost_total'])); ?></td>
							<td class="govuk-table__cell govuk-table__cell--numeric"><?php echo esc_html(number_format($row['time_total'])); ?> hrs</td>
						</tr>
						<?php if (!empty($row['site_map'])) : ?>
							<tr id="<?php echo esc_attr($hd_detail_id); ?>" class="hale-dash-savings__sites-detail" hidden>
								<td colspan="6">
									<p class="govuk-body-s govuk-!-margin-bottom-2"><strong><?php
										echo esc_html(sprintf(
											/* translators: 1: count 2: block */
											_n('%1$s site uses %2$s', '%1$s sites use %2$s', count($row['site_map']), 'hale-dash'),
											number_format(count($row['site_map'])),
											$row['title']
										));
									?></strong></p>
									<table class="govuk-table govuk-table--sites">
										<thead class="govuk-table__head">
											<tr class="govuk-table__row">
												<th scope="col" class="govuk-table__header" style="width:6em">Site ID</th>
												<th scope="col" class="govuk-table__header">Name</th>
											</tr>
										</thead>
										<tbody class="govuk-table__body">
											<?php foreach ($row['site_map'] as $hd_sid => $hd_sname) : ?>
												<tr class="govuk-table__row">
													<td class="govuk-table__cell"><?php echo esc_html((string) $hd_sid); ?></td>
													<td class="govuk-table__cell"><?php echo esc_html($hd_sname); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</td>
							</tr>
						<?php endif; ?>
					<?php endforeach; ?>
				</tbody>
				<tfoot>
					<tr class="govuk-table__row">
						<th scope="row" class="govuk-table__header">Total</th>
						<td class="govuk-table__cell"></td>
						<td class="govuk-table__cell"></td>
						<td class="govuk-table__cell"></td>
						<td class="govuk-table__cell govuk-table__cell--numeric"><strong><?php echo esc_html(hale_dash_gbp($savings['total_cost'])); ?></strong></td>
						<td class="govuk-table__cell govuk-table__cell--numeric"><strong><?php echo esc_html(number_format($savings['total_time_hours'])); ?> hrs</strong></td>
					</tr>
				</tfoot>
			</table>
		</div>

		<?php if ($hd_footnotes) : ?>
			<ol class="hale-dash-savings__footnotes">
				<?php foreach ($hd_footnotes as $hd_i => $hd_fn) :
					$hd_n = $hd_i + 1;
					?>
					<li id="hd-fn-<?php echo esc_attr($hd_n); ?>">
						<?php echo esc_html($hd_fn['note']); ?>
						<a href="#hd-fnref-<?php echo esc_attr($hd_n); ?>" class="hale-dash-savings__fn-back"
							aria-label="<?php echo esc_attr(sprintf(
								/* translators: %s: block title */
								__('Back to %s', 'hale-dash'),
								$hd_fn['title']
							)); ?>">&#8617;</a>
					</li>
				<?php endforeach; ?>
			</ol>
		<?php endif; ?>

		<div class="hale-dash-savings__doc-foot" aria-hidden="true">
			Hale Platform &mdash; Cost &amp; time savings. Figures are indicative and
			derived from WordPress block usage across the platform; each site is
			counted once per block.
		</div>

	<?php endif; ?>
	<?php
}

/**
 * AJAX recalculate: rebuilds the network scan, then returns the re-rendered
 * page body as HTML so the front-end can swap it in without a full reload.
 * Same auth as the no-JS POST fallback — any logged-in user, nonce-checked.
 */
add_action('wp_ajax_hale_dash_recalculate', static function () {
	check_ajax_referer('hale_dash_refresh_blocks', 'hale_dash_refresh_nonce');

	// Just the (bounded, per-site) content scan. Not the disk block-title
	// harvest — that's a much slower filesystem walk, only worth repeating
	// deliberately via "Rescan network" on the settings page after a deploy.
	delete_transient(HALE_DASH_SCAN_TRANSIENT);
	hale_dash_scan_network_blocks(true);

	$savings = hale_dash_calculate_savings();

	ob_start();
	hale_dash_render_savings_content($savings);
	$html = ob_get_clean();

	wp_send_json_success(['html' => $html]);
});

/**
 * Body class so the front-end template (and its print styles) can target the
 * Cost & Time Savings page specifically.
 */
add_filter('body_class', static function ($classes) {
	if (is_page_template('page-savings.php')) {
		$classes[] = 'hale-dash-savings-page';
	}
	return $classes;
});

/**
 * URL of the published Page using the "Cost & Time Savings" template, if any.
 * Resolved by template so it survives slug/title changes. Result is cached for
 * the request.
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
		'meta_value'  => 'page-savings.php',
		'number'      => 1,
		'post_status' => 'publish',
	]);

	$url = $pages ? (string) get_permalink($pages[0]->ID) : '';
	return $url;
}

/**
 * Handle the "Recalculate" button on the front-end page. POST + nonce.
 */
function hale_dash_maybe_refresh_block_usage() {
	if (
		isset($_SERVER['REQUEST_METHOD'], $_POST['hale_dash_refresh_blocks'], $_POST['hale_dash_refresh_nonce']) &&
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		is_user_logged_in() &&
		wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hale_dash_refresh_nonce'])), 'hale_dash_refresh_blocks')
	) {
		// See the AJAX handler above — the disk block-title harvest is
		// deliberately left out of routine recalculation.
		delete_transient(HALE_DASH_SCAN_TRANSIENT);
		return true;
	}
	return false;
}

/* -------------------------------------------------------------------------
 * Settings page
 * ---------------------------------------------------------------------- */

add_action('admin_menu', static function () {
	add_options_page(
		__('Cost & time savings', 'hale-dash'),
		__('Cost & time savings', 'hale-dash'),
		'manage_options',
		'hale-dash-savings',
		'hale_dash_savings_settings_page'
	);
});

add_action('admin_init', static function () {
	register_setting(
		'hale_dash_savings_group',
		HALE_DASH_SAVINGS_OPTION,
		[
			'type'              => 'array',
			'sanitize_callback' => 'hale_dash_sanitize_block_savings',
			'default'           => hale_dash_block_savings_defaults(),
		]
	);
});

/**
 * @param mixed $input
 * @return array{blocks: array<string, array{cost: float, time: float, note: string}>, extra_names: string, excluded_sites: int[]}
 */
function hale_dash_sanitize_block_savings($input) {
	$clean = hale_dash_block_savings_defaults();

	if (isset($input['extra_names'])) {
		$clean['extra_names'] = sanitize_textarea_field($input['extra_names']);
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

/**
 * Settings page markup.
 */
function hale_dash_savings_settings_page() {
	if (!current_user_can('manage_options')) {
		return;
	}

	// "Rescan network blocks" button (posts back to this page).
	$rescanned = false;
	if (
		isset($_POST['hale_dash_rescan'], $_POST['hale_dash_rescan_nonce']) &&
		wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['hale_dash_rescan_nonce'])), 'hale_dash_rescan')
	) {
		delete_transient(HALE_DASH_DISK_TITLES_TRANSIENT);
		hale_dash_scan_network_blocks(true);
		$rescanned = true;
	}

	$saved     = hale_dash_get_block_savings();
	$titles    = hale_dash_all_block_titles();
	$tracked   = hale_dash_tracked_blocks();
	$scan      = hale_dash_peek_block_scan();
	$counts    = $scan['counts'] ?? [];
	$names     = hale_dash_candidate_block_names();
	$excluded  = hale_dash_excluded_site_ids();
	$all_sites = get_sites(['number' => 0, 'orderby' => 'id', 'order' => 'ASC']);

	// Configured blocks first, then blocks in use on the network, then the rest.
	// Within the first two groups, order by number of sites using (most first),
	// ties alphabetical; the last group is alphabetical.
	usort($names, static function ($a, $b) use ($tracked, $counts) {
		$rank = static function ($n) use ($tracked, $counts) {
			if (isset($tracked[$n])) {
				return 0;
			}
			return isset($counts[$n]) ? 1 : 2;
		};
		$ra = $rank($a);
		$rb = $rank($b);
		if ($ra !== $rb) {
			return $ra - $rb;
		}
		if ($ra !== 2) {
			return ($counts[$b] ?? 0) <=> ($counts[$a] ?? 0) ?: strcmp($a, $b);
		}
		return strcmp($a, $b);
	});

	$opt = HALE_DASH_SAVINGS_OPTION;
	?>
	<div class="wrap">
		<h1><?php esc_html_e('Cost & time savings', 'hale-dash'); ?></h1>
		<p>
			<?php esc_html_e(
				'Set a cost and time saving for each block type. Every site that uses a block counts once towards that block\'s saving, however many times the block appears. Leave both values at 0 to ignore a block.',
				'hale-dash'
			); ?>
		</p>
		<?php if ($rescanned) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e('Network block scan complete.', 'hale-dash'); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e('Network scan', 'hale-dash'); ?></h2>
		<p>
			<?php
			if ($scan) {
				printf(
					/* translators: 1: block type count, 2: site count, 3: human time diff */
					esc_html__('Last scan found %1$s block types across %2$s sites, %3$s ago.', 'hale-dash'),
					'<strong>' . esc_html(number_format(count($counts))) . '</strong>',
					'<strong>' . esc_html(number_format((int) $scan['sites'])) . '</strong>',
					esc_html(human_time_diff((int) $scan['at']))
				);
			} else {
				esc_html_e(
					'The network has not been scanned yet — the list below shows blocks registered on this site only. Run a scan to pull in blocks from every site.',
					'hale-dash'
				);
			}
			?>
		</p>
		<form method="post" style="margin-bottom:1.5em">
			<?php wp_nonce_field('hale_dash_rescan', 'hale_dash_rescan_nonce'); ?>
			<button type="submit" name="hale_dash_rescan" value="1" class="button button-secondary">
				<?php echo $scan ? esc_html__('Rescan network blocks', 'hale-dash') : esc_html__('Scan network blocks', 'hale-dash'); ?>
			</button>
		</form>

		<form action="options.php" method="post">
			<?php settings_fields('hale_dash_savings_group'); ?>

			<h2><?php esc_html_e('Excluded sites', 'hale-dash'); ?></h2>
			<?php $hale_dash_site_names = hale_dash_site_name_map(); ?>
			<p class="description">
				<?php esc_html_e(
					'These sites are left out of the scan and every total, including the "across N sites" count. Changing this triggers a rescan on the next page load.',
					'hale-dash'
				); ?>
			</p>
			<?php if (!$all_sites) : ?>
				<p><?php esc_html_e('No sites found.', 'hale-dash'); ?></p>
			<?php else : ?>
				<style>
				.hale-dash-excluded-editor > summary { list-style: none; }
				.hale-dash-excluded-editor > summary::-webkit-details-marker { display: none; }
				.hale-dash-excluded-editor .hd-label--open { display: none; }
				.hale-dash-excluded-editor[open] .hd-label--closed { display: none; }
				.hale-dash-excluded-editor[open] .hd-label--open { display: inline; }
				.hale-dash-excluded-editor[open] > summary { margin-bottom: 10px; }
				</style>

				<?php if ($excluded) : ?>
					<table class="widefat striped" style="max-width:640px;margin-bottom:12px;">
						<thead>
							<tr>
								<th scope="col" style="width:7em"><?php esc_html_e('Site ID', 'hale-dash'); ?></th>
								<th scope="col"><?php esc_html_e('Name', 'hale-dash'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($excluded as $sid) : ?>
								<tr>
									<td><?php echo esc_html((string) $sid); ?></td>
									<td><?php echo esc_html($hale_dash_site_names[$sid] ?? __('(site not found)', 'hale-dash')); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p><em><?php esc_html_e('No sites are excluded.', 'hale-dash'); ?></em></p>
				<?php endif; ?>

				<details class="hale-dash-excluded-editor" style="max-width:640px;">
					<summary class="button">
						<span class="hd-label--closed"><?php esc_html_e('Edit excluded sites', 'hale-dash'); ?></span>
						<span class="hd-label--open"><?php esc_html_e('Done', 'hale-dash'); ?></span>
					</summary>
					<p>
						<input type="search" id="hale-dash-site-search" class="regular-text"
							placeholder="<?php esc_attr_e('Filter sites…', 'hale-dash'); ?>"
							autocomplete="off" onkeydown="return event.key !== 'Enter';">
					</p>
					<div id="hale-dash-site-list" style="max-height:260px;overflow:auto;border:1px solid #c3c4c7;border-radius:4px;padding:8px 12px;background:#fff;">
						<?php foreach ($all_sites as $site) :
							$sid = (int) $site->blog_id;
							$hay = strtolower($hale_dash_site_names[$sid] . ' ' . $site->path . ' #' . $sid);
							?>
							<label class="hale-dash-site-row" data-search="<?php echo esc_attr($hay); ?>" style="display:block;margin:3px 0;">
								<input type="checkbox"
									name="<?php echo esc_attr($opt); ?>[excluded_sites][]"
									value="<?php echo esc_attr($sid); ?>"
									<?php checked(in_array($sid, $excluded, true)); ?>>
								<?php echo esc_html($hale_dash_site_names[$sid]); ?>
								<span class="description">#<?php echo esc_html((string) $sid); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
					<p class="description"><?php esc_html_e('Changes take effect when you press Save Changes below.', 'hale-dash'); ?></p>
				</details>
			<?php endif; ?>

			<h2><?php esc_html_e('Block savings', 'hale-dash'); ?></h2>
			<p>
				<label for="hale-dash-block-search"><strong><?php esc_html_e('Search blocks', 'hale-dash'); ?></strong></label><br>
				<input type="search" id="hale-dash-block-search" class="regular-text"
					placeholder="<?php esc_attr_e('Filter by block title or name…', 'hale-dash'); ?>"
					autocomplete="off" onkeydown="return event.key !== 'Enter';">
				<span id="hale-dash-block-search-count" class="description" aria-live="polite" style="margin-left:.5em"></span>
			</p>

			<table class="widefat striped" id="hale-dash-block-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e('Block', 'hale-dash'); ?></th>
						<th scope="col"><?php esc_html_e('Block name', 'hale-dash'); ?></th>
						<th scope="col" style="width:7em"><?php esc_html_e('Sites using', 'hale-dash'); ?></th>
						<th scope="col" style="width:12em"><?php esc_html_e('Cost saving per site (£)', 'hale-dash'); ?></th>
						<th scope="col" style="width:12em"><?php esc_html_e('Time saving per site (hours)', 'hale-dash'); ?></th>
						<th scope="col" style="width:16em"><?php esc_html_e('Note (shown as a footnote)', 'hale-dash'); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($names as $name) :
					$cost        = $saved['blocks'][$name]['cost'] ?? '';
					$time        = $saved['blocks'][$name]['time'] ?? '';
					$note        = $saved['blocks'][$name]['note'] ?? '';
					$title       = hale_dash_block_label($name, $titles);
					$used        = $counts[$name] ?? null;
					$block_sites = hale_dash_block_sites($name);
					$detail_id   = 'hd-sites-' . sanitize_title($name);
					?>
					<tr data-search="<?php echo esc_attr(strtolower($title . ' ' . $name . ' ' . $note . ' ' . implode(' ', $block_sites))); ?>">
						<td><?php echo esc_html($title); ?></td>
						<td><code><?php echo esc_html($name); ?></code></td>
						<td>
							<?php if ($block_sites) : ?>
								<button type="button" class="button-link hale-dash-sites-toggle"
									aria-expanded="false" aria-controls="<?php echo esc_attr($detail_id); ?>"
									aria-label="<?php echo esc_attr(sprintf(
										/* translators: 1: number of sites, 2: block label */
										_n('%1$s site uses %2$s — show list', '%1$s sites use %2$s — show list', (int) $used, 'hale-dash'),
										number_format((int) $used),
										$title
									)); ?>">
									<?php echo esc_html(number_format($used)); ?>
									<span class="hale-dash-sites-toggle__caret" aria-hidden="true">&#9656;</span>
								</button>
							<?php else : ?>
								<?php echo $used === null ? '&mdash;' : esc_html(number_format($used)); ?>
							<?php endif; ?>
						</td>
						<td>
							<input type="number" step="any" min="0" class="small-text"
								name="<?php echo esc_attr($opt); ?>[blocks][<?php echo esc_attr($name); ?>][cost]"
								value="<?php echo esc_attr((string) $cost); ?>">
						</td>
						<td>
							<input type="number" step="any" min="0" class="small-text"
								name="<?php echo esc_attr($opt); ?>[blocks][<?php echo esc_attr($name); ?>][time]"
								value="<?php echo esc_attr((string) $time); ?>">
						</td>
						<td>
							<input type="text" class="regular-text"
								name="<?php echo esc_attr($opt); ?>[blocks][<?php echo esc_attr($name); ?>][note]"
								value="<?php echo esc_attr($note); ?>"
								placeholder="<?php esc_attr_e('Why this saving? Optional.', 'hale-dash'); ?>"
								maxlength="200">
						</td>
					</tr>
					<?php if ($block_sites) : ?>
						<tr id="<?php echo esc_attr($detail_id); ?>" class="hale-dash-sites-detail" hidden>
							<td colspan="6">
								<p class="hale-dash-sites-detail__head"><strong><?php
									/* translators: 1: number of sites, 2: block label */
									printf(
										esc_html__('%1$s sites using %2$s', 'hale-dash'),
										esc_html(number_format(count($block_sites))),
										esc_html($title)
									);
								?></strong></p>
								<table class="widefat striped hale-dash-sites-detail__table">
									<thead>
										<tr>
											<th scope="col" style="width:7em"><?php esc_html_e('Site ID', 'hale-dash'); ?></th>
											<th scope="col"><?php esc_html_e('Name', 'hale-dash'); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ($block_sites as $bsid => $bsname) : ?>
											<tr>
												<td><?php echo esc_html((string) $bsid); ?></td>
												<td><?php echo esc_html($bsname); ?></td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</td>
						</tr>
					<?php endif; ?>
				<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e('Other block names', 'hale-dash'); ?></h2>
			<p class="description">
				<?php esc_html_e(
					'Add block names that are not listed above (one per line, e.g. my-plugin/feature) and save to get input rows for them.',
					'hale-dash'
				); ?>
			</p>
			<textarea rows="4" class="large-text code"
				name="<?php echo esc_attr($opt); ?>[extra_names]"><?php echo esc_textarea($saved['extra_names']); ?></textarea>

			<?php submit_button(); ?>
		</form>
	</div>

	<style>
	.hale-dash-sites-toggle { text-decoration: none; }
	.hale-dash-sites-toggle__caret { display: inline-block; font-size: 10px; transition: transform .15s; }
	.hale-dash-sites-toggle[aria-expanded="true"] .hale-dash-sites-toggle__caret { transform: rotate(90deg); }
	tr.hale-dash-sites-detail > td { background: #f6f7f7; padding: 10px 12px; }
	.hale-dash-sites-detail__head { margin: 0 0 6px; }
	.hale-dash-sites-detail__table { max-width: 460px; background: #fff; }
	</style>

	<script>
	(function () {
		var input = document.getElementById('hale-dash-block-search');
		var table = document.getElementById('hale-dash-block-table');
		if (!input || !table) {
			return;
		}
		var rows  = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-search]'));
		var count = document.getElementById('hale-dash-block-search-count');

		function apply() {
			var q = input.value.trim().toLowerCase();
			var shown = 0;
			rows.forEach(function (row) {
				var hay = row.getAttribute('data-search') || '';
				var match = q === '' || hay.indexOf(q) !== -1;
				row.hidden = !match;
				// Keep each block's detail row in step with its main row.
				var detail = row.nextElementSibling;
				if (detail && detail.classList.contains('hale-dash-sites-detail')) {
					if (!match) {
						detail.hidden = true;
						var btn = row.querySelector('.hale-dash-sites-toggle');
						if (btn) { btn.setAttribute('aria-expanded', 'false'); }
					}
				}
				if (match) {
					shown++;
				}
			});
			if (count) {
				count.textContent = q === '' ? '' : shown + ' of ' + rows.length + ' blocks';
			}
		}

		input.addEventListener('input', apply);
		input.addEventListener('search', apply);
	})();

	// Expand/collapse the per-block "sites using" detail row.
	(function () {
		var table = document.getElementById('hale-dash-block-table');
		if (!table) {
			return;
		}
		table.addEventListener('click', function (e) {
			var btn = e.target.closest('.hale-dash-sites-toggle');
			if (!btn) {
				return;
			}
			var detail = document.getElementById(btn.getAttribute('aria-controls'));
			if (!detail) {
				return;
			}
			var open = detail.hidden;
			detail.hidden = !open;
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		});
	})();

	// Filter the excluded-sites checkbox list.
	(function () {
		var input = document.getElementById('hale-dash-site-search');
		var list  = document.getElementById('hale-dash-site-list');
		if (!input || !list) {
			return;
		}
		var rows = Array.prototype.slice.call(list.querySelectorAll('.hale-dash-site-row'));

		function apply() {
			var q = input.value.trim().toLowerCase();
			rows.forEach(function (row) {
				var hay = row.getAttribute('data-search') || '';
				// The rows carry inline display:block, which would beat [hidden] —
				// toggle the inline value directly.
				row.style.display = (q === '' || hay.indexOf(q) !== -1) ? 'block' : 'none';
			});
		}

		input.addEventListener('input', apply);
		input.addEventListener('search', apply);
	})();
	</script>
	<?php
}
