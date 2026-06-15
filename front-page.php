<?php

/**
 * Template Name: Dashboard Page
 *
 * @package Hale Dash
 * @copyright Ministry of Justice
 * @version 2.0
 */

get_header();

echo festiveGreeting(time());
echo getBrithday();
$sites = get_sites();
?>

<div class="govuk-grid-column-full">
	<div class="govuk-grid-row hale-dash-layout">
		<aside class="govuk-grid-column-one-third hale-dash-metrics-col">
			<h2 class="govuk-heading-l">Platform metrics</h2>
			<?php include get_stylesheet_directory() . '/components/feature-metrics.php'; ?>
		</aside>

		<section class="govuk-grid-column-two-thirds hale-dash-sites-col">
			<h2 class="govuk-heading-l">Sites</h2>
			<div class="hale-dash-search">
				<div class="hale-dash-search__field hale-dash-search__field--name">
					<label class="govuk-label govuk-label--s" for="hd-search-name">Search by name, slug or URL</label>
					<input class="govuk-input" type="search" id="hd-search-name" placeholder="e.g. 'Law Commission' or 'lawcom'" autocomplete="off">
				</div>
				<div class="hale-dash-search__field">
					<label class="govuk-label govuk-label--s" for="hd-search-id">Search by ID</label>
					<input class="govuk-input hale-dash-search__id-input" type="search" id="hd-search-id" placeholder="e.g. 42" inputmode="numeric" autocomplete="off">
				</div>
				<p class="hale-dash-search__count govuk-body-s govuk-hint" id="hd-search-count" aria-live="polite"></p>
			</div>
			<?php include get_stylesheet_directory() . '/components/site-status-list.php'; ?>
		</section>
	</div>
</div>

<?php
get_footer();
