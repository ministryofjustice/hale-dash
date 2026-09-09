<?php

/**
 * Template Name: Cost & Time Savings
 *
 * Presents the money and delivery time the platform saves, built up from the
 * WordPress blocks in use across the multisite. Each site that uses a block
 * counts once towards that block's configured saving.
 *
 * Assign this template to a Page (Page Attributes > Template). Set the per-block
 * figures under Settings > Cost & time savings.
 *
 * The logic lives in inc/savings/ — this template is just the outer shell around
 * hale_dash_render_savings_content(). TODO: scaffold; confirm the detection
 * method, which sites and which post types count before relying on the figures.
 *
 * @package   Hale Dash
 * @copyright Ministry of Justice
 * @version   1.1
 */

get_header();

hale_dash_maybe_refresh_block_usage();
$savings = hale_dash_calculate_savings();
?>

<div class="govuk-grid-column-full">
	<div class="hale-dash-savings" data-hd-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">

		<div class="hale-dash-savings__doc-head" aria-hidden="true">
			<span class="hale-dash-savings__doc-org">Ministry of Justice &middot; Hale Platform</span>
			<span class="hale-dash-savings__doc-date">Generated <?php echo esc_html(wp_date('j F Y')); ?></span>
		</div>

		<div id="hale-dash-savings-content">
			<?php hale_dash_render_savings_content($savings); ?>
		</div>

	</div>
</div>

<?php
get_footer();
