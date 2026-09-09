<?php

/**
 * Cost & time savings — the public report body.
 *
 * Everything that depends on $savings (heading through the print footer). Shared
 * by page-savings.php and the AJAX recalculate handler so the two can't drift.
 *
 * @package Hale Dash
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
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
