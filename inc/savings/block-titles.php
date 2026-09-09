<?php

/**
 * Cost & time savings — resolving a block name to a human title.
 *
 * Sources, most authoritative first:
 *   1. the block registry on the dashboard site (WP_Block_Type_Registry)
 *   2. an on-disk harvest of block.json + register_block_type() /
 *      acf_register_block_type() / registerBlockType() calls in a small set of
 *      plugin/theme roots — catches blocks not active on the dashboard site
 *   3. a slug prettifier fallback ("moj/inline-menu" => "Inline Menu (moj)")
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
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
 * / acf_register_block_type() / registerBlockType() calls in plugin/theme code.
 * Catches blocks that aren't registered on the dashboard site (e.g. mojblocks/*).
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

	// Roots to walk — plugin/theme folder slugs, from config. Deliberately
	// narrow; see HALE_DASH_SAVINGS_TITLE_PLUGINS / _THEMES.
	$roots = [];
	if (defined('WP_PLUGIN_DIR')) {
		foreach (HALE_DASH_SAVINGS_TITLE_PLUGINS as $slug) {
			$roots[] = WP_PLUGIN_DIR . '/' . $slug;
		}
	}
	$theme_root = get_theme_root();
	foreach (HALE_DASH_SAVINGS_TITLE_THEMES as $slug) {
		$roots[] = $theme_root . '/' . $slug;
	}
	$roots = array_unique($roots);

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
 * Display label for a block name. Uses the registered/harvested title when there
 * is one; otherwise prettifies the slug — "moj/inline-menu" => "Inline Menu
 * (moj)".
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
