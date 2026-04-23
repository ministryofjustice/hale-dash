(function () {
	var nameInput = document.getElementById('hd-search-name');
	var idInput   = document.getElementById('hd-search-id');
	var countEl   = document.getElementById('hd-search-count');

	if (!nameInput || !idInput) return;

	var items = Array.from(document.querySelectorAll('.hale-dash-site-item'));

	function filter() {
		var nameQuery = nameInput.value.trim().toLowerCase();
		var idQuery   = idInput.value.trim();
		var visible   = 0;

		items.forEach(function (item) {
			var name   = item.getAttribute('data-site-name') || '';
			var siteId = item.getAttribute('data-site-id') || '';

			var nameMatch = !nameQuery || name.indexOf(nameQuery) !== -1;
			var idMatch   = !idQuery   || siteId === idQuery;

			if (nameMatch && idMatch) {
				item.style.display = '';
				visible++;
			} else {
				item.style.display = 'none';
			}
		});

		if (nameQuery || idQuery) {
			countEl.textContent = visible + ' of ' + items.length + ' sites shown';
		} else {
			countEl.textContent = '';
		}
	}

	nameInput.addEventListener('input', filter);
	idInput.addEventListener('input', filter);
})();
