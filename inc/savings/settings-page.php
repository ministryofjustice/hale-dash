<?php

/**
 * Cost & time savings — the Settings > Cost & time savings admin screen.
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

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
 * Settings screen assets (block filter / drill-down / excluded-sites filter,
 * plus the small amount of screen-specific CSS).
 */
add_action('admin_enqueue_scripts', static function ($hook) {
	if ($hook !== 'settings_page_hale-dash-savings') {
		return;
	}
	wp_enqueue_style(
		'hale-dash-savings-admin',
		get_theme_file_uri() . '/dist/css/savings-admin.min.css',
		[],
		hale_dash_asset_version('/css/savings-admin.min.css')
	);
	wp_enqueue_script(
		'hale-dash-savings-admin',
		get_theme_file_uri() . '/dist/js/savings-admin.js',
		[],
		hale_dash_asset_version('/js/savings-admin.js'),
		true
	);
});

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
	<div class="wrap hale-dash-savings-admin">
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
		<form method="post" class="hd-rescan-form">
			<?php wp_nonce_field('hale_dash_rescan', 'hale_dash_rescan_nonce'); ?>
			<button type="submit" name="hale_dash_rescan" value="1" class="button button-secondary">
				<?php echo $scan ? esc_html__('Rescan network blocks', 'hale-dash') : esc_html__('Scan network blocks', 'hale-dash'); ?>
			</button>
		</form>

		<form action="options.php" method="post">
			<?php settings_fields('hale_dash_savings_group'); ?>

			<h2><?php esc_html_e('Report options', 'hale-dash'); ?></h2>
			<p>
				<label for="hale-dash-hours-per-day">
					<strong><?php esc_html_e('Hours in a working day', 'hale-dash'); ?></strong>
				</label><br>
				<input type="number" step="0.25" min="1" max="24" class="small-text"
					id="hale-dash-hours-per-day"
					name="<?php echo esc_attr($opt); ?>[hours_per_day]"
					value="<?php echo esc_attr(hale_dash_trim_decimal($saved['hours_per_day'])); ?>">
				<span class="description"><?php esc_html_e('Used to show total saved hours as "≈ N working days" on the report.', 'hale-dash'); ?></span>
			</p>

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
				<?php if ($excluded) : ?>
					<table class="widefat striped hd-excluded-table">
						<thead>
							<tr>
								<th scope="col"><?php esc_html_e("Site ID", "hale-dash"); ?></th>
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

				<details class="hale-dash-excluded-editor">
					<summary class="button">
						<span class="hd-label--closed"><?php esc_html_e('Edit excluded sites', 'hale-dash'); ?></span>
						<span class="hd-label--open"><?php esc_html_e('Done', 'hale-dash'); ?></span>
					</summary>
					<p>
						<input type="search" id="hale-dash-site-search" class="regular-text"
							placeholder="<?php esc_attr_e('Filter sites…', 'hale-dash'); ?>"
							autocomplete="off" onkeydown="return event.key !== 'Enter';">
					</p>
					<div id="hale-dash-site-list">
						<?php foreach ($all_sites as $site) :
							$sid = (int) $site->blog_id;
							$hay = strtolower($hale_dash_site_names[$sid] . ' ' . $site->path . ' #' . $sid);
							?>
							<label class="hale-dash-site-row" data-search="<?php echo esc_attr($hay); ?>">
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
				<span id="hale-dash-block-search-count" class="description" aria-live="polite"></span>
			</p>

			<table class="widefat striped" id="hale-dash-block-table">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e('Block', 'hale-dash'); ?></th>
						<th scope="col"><?php esc_html_e('Block name', 'hale-dash'); ?></th>
						<th scope="col"><?php esc_html_e("Sites using", "hale-dash"); ?></th>
						<th scope="col"><?php esc_html_e("Cost saving per site (£)", "hale-dash"); ?></th>
						<th scope="col"><?php esc_html_e("Time saving per site (hours)", "hale-dash"); ?></th>
						<th scope="col"><?php esc_html_e("Note (shown as a footnote)", "hale-dash"); ?></th>
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
											<th scope="col"><?php esc_html_e("Site ID", "hale-dash"); ?></th>
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
	<?php
}
