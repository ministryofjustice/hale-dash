(function () {
	var STORAGE_KEY = 'hd-theme';
	var root = document.documentElement;

	function currentTheme() {
		var stored = null;
		try { stored = localStorage.getItem(STORAGE_KEY); } catch (e) {}
		if (stored === 'dark' || stored === 'light') return stored;
		return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches
			? 'dark'
			: 'light';
	}

	function applyTheme(theme) {
		if (theme === 'dark') {
			root.setAttribute('data-theme', 'dark');
		} else {
			root.removeAttribute('data-theme');
		}
		var btn = document.querySelector('.hd-theme-toggle');
		if (btn) {
			btn.setAttribute('aria-checked', theme === 'dark' ? 'true' : 'false');
		}
	}

	applyTheme(currentTheme());

	if (window.matchMedia) {
		var mq = window.matchMedia('(prefers-color-scheme: dark)');
		var osListener = function (e) {
			var stored = null;
			try { stored = localStorage.getItem(STORAGE_KEY); } catch (err) {}
			if (stored !== 'dark' && stored !== 'light') {
				applyTheme(e.matches ? 'dark' : 'light');
			}
		};
		if (mq.addEventListener) mq.addEventListener('change', osListener);
		else if (mq.addListener) mq.addListener(osListener);
	}

	document.addEventListener('DOMContentLoaded', function () {
		applyTheme(currentTheme());
		var btn = document.querySelector('.hd-theme-toggle');
		if (!btn) return;

		var header = document.querySelector('.govuk-header__container');
		if (header) header.appendChild(btn);

		btn.addEventListener('click', function () {
			var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
			applyTheme(next);
		});
	});
})();
