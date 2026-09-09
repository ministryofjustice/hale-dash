/**
 * Cost & Time Savings — settings screen behaviour.
 *
 * - Filter the block table by title / name / note / site
 * - Expand/collapse a block's "sites using" detail row
 * - Filter the excluded-sites checkbox list
 *
 * Enqueued from inc/savings-settings.php on settings_page_hale-dash-savings.
 */
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
