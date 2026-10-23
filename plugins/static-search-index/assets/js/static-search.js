/**
 * Client-side static search powered by local Fuse.js.
 *
 * Intercepts WordPress search forms, loads search-index.json,
 * and renders fuzzy matches without a PHP/MySQL backend.
 *
 * @package Sahajanand_Static_Search
 */
(function () {
	'use strict';

	var settings = window.StaticSearchSettings || {};
	var indexUrl = settings.indexUrl || '';
	var minChars = typeof settings.minChars === 'number' ? settings.minChars : 2;
	var maxResults = typeof settings.maxResults === 'number' ? settings.maxResults : 12;
	var i18n = settings.i18n || {};

	var indexCache = null;
	var indexPromise = null;
	var fuseInstance = null;
	var activeForm = null;
	var debounceTimer = null;

	var resultsEl = null;

	/**
	 * Fuse.js field weights: title > excerpt > content.
	 */
	var fuseOptions = {
		includeScore: true,
		shouldSort: true,
		threshold: 0.4,
		ignoreLocation: true,
		minMatchCharLength: 2,
		keys: [
			{ name: 'title', weight: 0.5 },
			{ name: 'excerpt', weight: 0.3 },
			{ name: 'content', weight: 0.2 }
		]
	};

	/**
	 * Boot after DOM is ready.
	 */
	function init() {
		if (typeof window.Fuse === 'undefined') {
			return;
		}

		resultsEl = document.getElementById('static-search-results');
		if (!resultsEl) {
			resultsEl = document.createElement('div');
			resultsEl.id = 'static-search-results';
			resultsEl.className = 'static-search-results';
			resultsEl.setAttribute('role', 'listbox');
			resultsEl.hidden = true;
			document.body.appendChild(resultsEl);
		}

		var forms = document.querySelectorAll('form[role="search"], form.search-form, form.wp-block-search');
		if (!forms.length) {
			return;
		}

		Array.prototype.forEach.call(forms, bindForm);

		document.addEventListener('click', function (event) {
			if (!resultsEl.hidden && !resultsEl.contains(event.target) && !isInsideSearchForm(event.target)) {
				hideResults();
			}
		});

		document.addEventListener('keydown', function (event) {
			if (event.key === 'Escape') {
				hideResults();
			}
		});

		window.addEventListener('resize', positionResults);
		window.addEventListener('scroll', positionResults, true);
	}

	/**
	 * Attach submit + input handlers to a search form.
	 *
	 * @param {HTMLFormElement} form
	 */
	function bindForm(form) {
		var input = form.querySelector('input[type="search"], input[name="s"], input.wp-block-search__input');
		if (!input) {
			return;
		}

		form.classList.add('static-search-enhanced');
		input.setAttribute('autocomplete', 'off');
		input.setAttribute('aria-autocomplete', 'list');
		input.setAttribute('aria-controls', 'static-search-results');

		form.addEventListener('submit', function (event) {
			event.preventDefault();
			activeForm = form;
			runSearch(input.value, true);
		});

		input.addEventListener('input', function () {
			activeForm = form;
			window.clearTimeout(debounceTimer);
			debounceTimer = window.setTimeout(function () {
				runSearch(input.value, false);
			}, 220);
		});

		input.addEventListener('focus', function () {
			activeForm = form;
			if (input.value.trim().length >= minChars && resultsEl.children.length) {
				showResults();
				positionResults();
			}
		});
	}

	/**
	 * @param {Element} el
	 * @return {boolean}
	 */
	function isInsideSearchForm(el) {
		return !!(el.closest && el.closest('form[role="search"], form.search-form, form.wp-block-search'));
	}

	/**
	 * Fetch and cache the JSON index once.
	 *
	 * @return {Promise<Array>}
	 */
	function loadIndex() {
		if (indexCache) {
			return Promise.resolve(indexCache);
		}

		if (indexPromise) {
			return indexPromise;
		}

		if (!indexUrl) {
			return Promise.reject(new Error('Missing index URL'));
		}

		indexPromise = fetch(indexUrl, { credentials: 'same-origin' })
			.then(function (response) {
				if (!response.ok) {
					throw new Error('HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (data) {
				indexCache = Array.isArray(data) ? data : (data.items || []);
				fuseInstance = new window.Fuse(indexCache, fuseOptions);
				return indexCache;
			})
			.catch(function (error) {
				indexPromise = null;
				throw error;
			});

		return indexPromise;
	}

	/**
	 * Execute fuzzy search and render results.
	 *
	 * @param {string}  query
	 * @param {boolean} fromSubmit
	 */
	function runSearch(query, fromSubmit) {
		var trimmed = (query || '').trim();

		if (trimmed.length < minChars) {
			hideResults();
			return;
		}

		showStatus(i18n.searching || 'Searching…');

		loadIndex()
			.then(function () {
				var matches = fuseInstance.search(trimmed, { limit: maxResults });
				renderResults(trimmed, matches);
				if (fromSubmit || matches.length) {
					showResults();
					positionResults();
				}
			})
			.catch(function () {
				showStatus(i18n.error || 'Search is temporarily unavailable.');
				showResults();
				positionResults();
			});
	}

	/**
	 * Render match list into #static-search-results.
	 *
	 * @param {string} query
	 * @param {Array}  matches
	 */
	function renderResults(query, matches) {
		resultsEl.innerHTML = '';

		var heading = document.createElement('div');
		heading.className = 'static-search-results__heading';
		heading.textContent = (i18n.resultsFor || 'Results for') + ' “' + query + '”';
		resultsEl.appendChild(heading);

		if (!matches.length) {
			var empty = document.createElement('p');
			empty.className = 'static-search-results__empty';
			empty.textContent = i18n.noResults || 'No results found.';
			resultsEl.appendChild(empty);
			return;
		}

		var list = document.createElement('ul');
		list.className = 'static-search-results__list';

		matches.forEach(function (match) {
			var item = match.item || {};
			var li = document.createElement('li');
			li.className = 'static-search-results__item';
			li.setAttribute('role', 'option');

			var link = document.createElement('a');
			link.className = 'static-search-results__link';
			link.href = item.permalink || '#';

			var title = document.createElement('span');
			title.className = 'static-search-results__title';
			title.textContent = item.title || '';

			var meta = document.createElement('span');
			meta.className = 'static-search-results__meta';
			meta.textContent = formatMeta(item);

			var excerpt = document.createElement('span');
			excerpt.className = 'static-search-results__excerpt';
			excerpt.textContent = truncate(item.excerpt || '', 140);

			link.appendChild(title);
			link.appendChild(meta);
			link.appendChild(excerpt);
			li.appendChild(link);
			list.appendChild(li);
		});

		resultsEl.appendChild(list);
	}

	/**
	 * @param {Object} item
	 * @return {string}
	 */
	function formatMeta(item) {
		var parts = [];
		if (item.type) {
			parts.push(item.type);
		}
		if (item.categories && item.categories.length) {
			parts.push(item.categories.join(', '));
		}
		return parts.join(' · ');
	}

	/**
	 * @param {string} text
	 * @param {number} max
	 * @return {string}
	 */
	function truncate(text, max) {
		if (!text || text.length <= max) {
			return text || '';
		}
		return text.slice(0, max - 1).replace(/\s+\S*$/, '') + '…';
	}

	/**
	 * @param {string} message
	 */
	function showStatus(message) {
		resultsEl.innerHTML = '';
		var status = document.createElement('p');
		status.className = 'static-search-results__status';
		status.textContent = message;
		resultsEl.appendChild(status);
	}

	function showResults() {
		resultsEl.hidden = false;
		resultsEl.classList.add('is-open');
	}

	function hideResults() {
		resultsEl.hidden = true;
		resultsEl.classList.remove('is-open');
	}

	/**
	 * Position the dropdown under the active search input.
	 */
	function positionResults() {
		if (resultsEl.hidden || !activeForm) {
			return;
		}

		var input = activeForm.querySelector('input[type="search"], input[name="s"], input.wp-block-search__input');
		if (!input) {
			return;
		}

		var rect = input.getBoundingClientRect();
		var scrollX = window.pageXOffset || document.documentElement.scrollLeft;
		var scrollY = window.pageYOffset || document.documentElement.scrollTop;
		var width = Math.max(rect.width, 280);

		resultsEl.style.position = 'absolute';
		resultsEl.style.left = Math.round(rect.left + scrollX) + 'px';
		resultsEl.style.top = Math.round(rect.bottom + scrollY + 6) + 'px';
		resultsEl.style.width = Math.round(width) + 'px';
		resultsEl.style.zIndex = '100000';
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
