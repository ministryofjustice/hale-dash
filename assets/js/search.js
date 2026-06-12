(function () {
	var searchInput = document.getElementById('hd-search-name');
	var idInput     = document.getElementById('hd-search-id');
	var countEl     = document.getElementById('hd-search-count');

	if (!searchInput || !idInput || !countEl) return;

	var items = Array.from(document.querySelectorAll('.hale-dash-site-item'));

	function filter() {
		var searchQuery = searchInput.value.trim().toLowerCase();
		var idQuery   = idInput.value.trim();
		var visible   = 0;

		items.forEach(function (item) {
			var name   = item.getAttribute('data-site-name') || '';
			var siteId = item.getAttribute('data-site-id') || '';
			var url    = item.getAttribute('data-site-url') || '';
			var slug   = item.getAttribute('data-site-slug') || '';

			var searchMatch = !searchQuery || name.indexOf(searchQuery) !== -1 || url.indexOf(searchQuery) !== -1 || slug.indexOf(searchQuery) !== -1;
			var idMatch   = !idQuery   || siteId === idQuery;

			if (searchMatch && idMatch) {
				item.style.display = '';
				visible++;
			} else {
				item.style.display = 'none';
			}
		});

		if (searchQuery || idQuery) {
			countEl.textContent = visible + ' of ' + items.length + ' sites shown';
		} else {
			countEl.textContent = '';
		}
	}

	searchInput.addEventListener('input', filter);
	idInput.addEventListener('input', filter);
})();
