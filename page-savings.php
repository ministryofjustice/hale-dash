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
 * TODO: scaffold. Block detection is a content scan of published post/page/
 * wp_block content (see inc/savings-settings.php). Confirm the method, the post
 * types and which sites count before relying on the figures.
 *
 * @package   Hale Dash
 * @copyright Ministry of Justice
 * @version   1.0
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

<script>
(function () {
	var wrap    = document.querySelector('.hale-dash-savings');
	var content = document.getElementById('hale-dash-savings-content');
	if (!wrap || !content) {
		return;
	}

	// Print — delegated so it keeps working after the content below is swapped.
	document.addEventListener('click', function (e) {
		if (e.target.closest('[data-hd-print]')) {
			window.print();
		}
	});

	// "Sites using" drill-down — delegated for the same reason.
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.hale-dash-savings__sites-toggle');
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

	// Recalculate — AJAX with a progress bar; falls back to a normal POST +
	// full reload if fetch/FormData aren't available or the request fails.
	document.addEventListener('submit', function (e) {
		var form = e.target.closest('[data-hd-recalculate]');
		if (!form || !window.fetch || !window.FormData) {
			return;
		}
		e.preventDefault();

		// Query fresh every time — a successful run replaces content's children
		// (including this element), so a reference cached at page-load would
		// go stale after the first recalculation.
		var progress = content.querySelector('#hale-dash-savings-progress');
		var ajaxUrl  = wrap.getAttribute('data-hd-ajax-url');
		var data     = new FormData(form);
		data.set('action', 'hale_dash_recalculate');

		content.setAttribute('aria-busy', 'true');
		if (progress) {
			progress.hidden = false;
		}

		// Belt and braces: never leave the bar spinning forever if the request
		// hangs (e.g. a slow proxy) — abort and fall back after 60s.
		var timedOut   = false;
		var controller = window.AbortController ? new AbortController() : null;
		var timeoutId  = controller && window.setTimeout(function () {
			timedOut = true;
			controller.abort();
		}, 60000);

		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data,
			signal: controller ? controller.signal : undefined
		})
			.then(function (res) { return res.json(); })
			.then(function (json) {
				window.clearTimeout(timeoutId);
				if (!json || !json.success || !json.data || typeof json.data.html !== 'string') {
					throw new Error('Unexpected response');
				}
				// The fresh markup already has its own progress bar, hidden —
				// no separate cleanup needed on this (now replaced) one.
				content.innerHTML = json.data.html;
				content.removeAttribute('aria-busy');
			})
			.catch(function () {
				// Something went wrong before we got usable JSON back — undo
				// the busy state here (nothing was swapped) and fall back to
				// the reliable path: a real submit, full reload. Skip that on
				// a timeout though, since the same slow work would just run
				// again — surface it instead.
				window.clearTimeout(timeoutId);
				content.removeAttribute('aria-busy');
				if (progress) {
					progress.hidden = true;
				}
				if (timedOut) {
					window.alert('Recalculating is taking longer than expected. Please try again shortly.');
					return;
				}
				form.submit();
			});
	});
})();
</script>

<?php
get_footer();
