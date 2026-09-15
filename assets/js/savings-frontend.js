/**
 * Cost & Time Savings — front-end page behaviour.
 *
 * - Print button (delegated, survives the content swap below)
 * - "Sites using" drill-down toggle (delegated)
 * - Recalculate: AJAX + progress bar, with a no-JS/failure fallback to a
 *   normal POST + full reload.
 *
 * Enqueued from inc/savings-settings.php on the Cost & Time Savings template.
 */
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
