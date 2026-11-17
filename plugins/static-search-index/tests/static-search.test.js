/**
 * Unit Tests for Frontend Static Search Logic (static-search.js).
 *
 * Runs using Node's built-in test runner:
 *   node plugins/static-search-index/tests/static-search.test.js
 */

const test = require('node:test');
const assert = require('node:assert/strict');

/**
 * Recreates the resolvePermalink function from static-search.js for isolated unit testing.
 *
 * @param {string} permalink
 * @param {string} homeUrl
 * @param {string} currentOrigin
 * @returns {string}
 */
function resolvePermalink(permalink, homeUrl, currentOrigin) {
	if (!permalink) {
		return '#';
	}

	// If already an absolute URL (http:// or https://), return as-is.
	if (/^https?:\/\//i.test(permalink)) {
		return permalink;
	}

	let cleanPath = '/' + permalink.replace(/^\/+/, '');
	const trimmedHome = (homeUrl || '').trim();

	if (trimmedHome) {
		try {
			const homeParsed = new URL(trimmedHome, currentOrigin);
			const homePath = homeParsed.pathname.replace(/\/+$/, '');

			if (homePath) {
				if (homeParsed.origin === currentOrigin) {
					// Dynamic staging site (same origin and subfolder): preserve subfolder.
					if (cleanPath.indexOf(homePath + '/') !== 0 && cleanPath !== homePath) {
						return homePath + cleanPath;
					}
				} else {
					// Static export site (different origin): ensure staging subfolder is stripped.
					if (cleanPath.indexOf(homePath + '/') === 0) {
						cleanPath = cleanPath.substring(homePath.length);
						if (!cleanPath || cleanPath.charAt(0) !== '/') {
							cleanPath = '/' + cleanPath;
						}
					} else if (cleanPath === homePath) {
						cleanPath = '/';
					}
				}
			}
		} catch (e) {
			// Fallback to cleanPath.
		}
	}

	return cleanPath;
}

/**
 * Truncates text cleanly at word boundaries.
 */
function truncate(text, max) {
	if (!text || text.length <= max) {
		return text || '';
	}
	return text.slice(0, max - 1).replace(/\s+\S*$/, '') + '…';
}

/**
 * Formats metadata line (type and categories).
 */
function formatMeta(item) {
	const parts = [];
	if (item.type) {
		parts.push(item.type);
	}
	if (item.categories && item.categories.length) {
		parts.push(item.categories.join(', '));
	}
	return parts.join(' · ');
}

test('resolvePermalink returns # for empty or falsy inputs', () => {
	assert.equal(resolvePermalink('', 'https://example.com', 'https://example.com'), '#');
	assert.equal(resolvePermalink(null, 'https://example.com', 'https://example.com'), '#');
	assert.equal(resolvePermalink(undefined, 'https://example.com', 'https://example.com'), '#');
});

test('resolvePermalink leaves absolute URLs untouched', () => {
	assert.equal(
		resolvePermalink('https://external.org/article', 'https://example.com', 'https://example.com'),
		'https://external.org/article'
	);
	assert.equal(
		resolvePermalink('http://cdn.example.com/asset.pdf', 'https://example.com', 'https://example.com'),
		'http://cdn.example.com/asset.pdf'
	);
});

test('resolvePermalink on static export site strips staging subdirectory', () => {
	const homeUrl = 'https://projects.sahajananddigital.in/shajananddigital';
	const staticOrigin = 'https://shajananddigital.in';

	// When index path has the staging subfolder:
	const result = resolvePermalink('/shajananddigital/my-awesome-article/', homeUrl, staticOrigin);
	assert.equal(result, '/my-awesome-article/');

	// When index path is already relative root:
	const result2 = resolvePermalink('/my-awesome-article/', homeUrl, staticOrigin);
	assert.equal(result2, '/my-awesome-article/');

	// When root path is clicked:
	const resultRoot = resolvePermalink('/shajananddigital', homeUrl, staticOrigin);
	assert.equal(resultRoot, '/');
});

test('resolvePermalink on dynamic staging site preserves staging subdirectory for preview', () => {
	const homeUrl = 'https://projects.sahajananddigital.in/shajananddigital';
	const stagingOrigin = 'https://projects.sahajananddigital.in';

	// Previewing relative path on dynamic site:
	const result = resolvePermalink('/my-awesome-article/', homeUrl, stagingOrigin);
	assert.equal(result, '/shajananddigital/my-awesome-article/');

	// Already prefixed:
	const resultPrefixed = resolvePermalink('/shajananddigital/my-awesome-article/', homeUrl, stagingOrigin);
	assert.equal(resultPrefixed, '/shajananddigital/my-awesome-article/');
});

test('truncate trims long strings cleanly at word boundaries', () => {
	const sample = 'Sahajanand Digital provides exceptional WordPress and static site engineering solutions.';
	const truncated = truncate(sample, 40);
	assert.ok(truncated.length <= 40, 'Must be within max length');
	assert.ok(truncated.endsWith('…'), 'Must end with ellipsis');
	assert.ok(!truncated.includes('WordPress and static site engineering solutions.'), 'Excess text must be trimmed');
});

test('formatMeta formats post type and category tags cleanly', () => {
	const item = {
		type: 'post',
		categories: ['News', 'Engineering'],
	};
	assert.equal(formatMeta(item), 'post · News, Engineering');

	const pageItem = {
		type: 'page',
		categories: [],
	};
	assert.equal(formatMeta(pageItem), 'page');
});
