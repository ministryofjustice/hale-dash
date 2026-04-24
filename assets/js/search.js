(function () {
	var nameInput = document.getElementById('hd-search-name');
	var idInput   = document.getElementById('hd-search-id');
	var slugInput = document.getElementById('hd-search-slug');
	var countEl   = document.getElementById('hd-search-count');

	if (!nameInput || !idInput || !slugInput || !countEl) return;

	var items = Array.from(document.querySelectorAll('.hale-dash-site-item'));

	function filter() {
		var nameQuery = nameInput.value.trim().toLowerCase();
		var idQuery   = idInput.value.trim();
		var slugQuery = slugInput.value.trim().toLowerCase();
		var visible   = 0;

		items.forEach(function (item) {
			var name   = item.getAttribute('data-site-name') || '';
			var siteId = item.getAttribute('data-site-id') || '';
			var slug   = item.getAttribute('data-site-slug') || '';

			var nameMatch = !nameQuery || name.indexOf(nameQuery) !== -1;
			var idMatch   = !idQuery   || siteId === idQuery;
			var slugMatch = !slugQuery || slug.indexOf(slugQuery) !== -1;

			if (nameMatch && idMatch && slugMatch) {
				item.style.display = '';
				visible++;
			} else {
				item.style.display = 'none';
			}
		});

		if (nameQuery || idQuery || slugQuery) {
			countEl.textContent = visible + ' of ' + items.length + ' sites shown';
		} else {
			countEl.textContent = '';
		}
	}

	nameInput.addEventListener('input', filter);
	idInput.addEventListener('input', filter);
	slugInput.addEventListener('input', filter);
})();
