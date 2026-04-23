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
			var isDark = theme === 'dark';
			btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
			var label = btn.querySelector('.hd-theme-toggle__label');
			if (label) label.textContent = isDark ? 'Switch to light mode' : 'Switch to dark mode';
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
		btn.addEventListener('click', function () {
			var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
			try { localStorage.setItem(STORAGE_KEY, next); } catch (e) {}
			applyTheme(next);
		});
	});
})();
