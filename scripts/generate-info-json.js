#!/usr/bin/env node

/**
 * generate-info-json.js
 *
 * Generates or updates the info.json manifest file in the docs/ folder for GitHub Pages
 * during GitHub Actions automated releases.
 *
 * Usage:
 *   node scripts/generate-info-json.js --dir=plugins/static-search-index --version=1.0.1 --tag=static-search-index/v1.0.1 --zip=static-search-index-v1.0.1.zip --repo=sahajananddigital/wordpress-plugins-monorepo --docs=docs
 */

const fs = require('fs');
const path = require('path');

// Parse CLI arguments
function parseArgs() {
	const args = {};
	for (const arg of process.argv.slice(2)) {
		if (arg.startsWith('--')) {
			const [key, ...rest] = arg.slice(2).split('=');
			args[key] = rest.join('=');
		}
	}
	return args;
}

const args = parseArgs();
const pluginDir = args.dir;

if (!pluginDir || !fs.existsSync(pluginDir)) {
	console.error(`❌ Error: Plugin directory not found: ${pluginDir}`);
	process.exit(1);
}

const dirName = path.basename(pluginDir);
const version = args.version || '1.0.0';
const repo = args.repo || process.env.GITHUB_REPOSITORY || 'sahajananddigital/wordpress-plugins';
const tag = args.tag || `${dirName}/v${version}`;
const zipName = args.zip || `${dirName}-v${version}.zip`;
const docsRoot = args.docs || 'docs';

/**
 * Find the main PHP file in plugin directory.
 */
function findMainPluginFile(dir) {
	const files = fs.readdirSync(dir);
	for (const file of files) {
		if (file.endsWith('.php')) {
			const fullPath = path.join(dir, file);
			const stat = fs.statSync(fullPath);
			if (stat.isFile()) {
				const content = fs.readFileSync(fullPath, 'utf8');
				if (content.includes('Plugin Name:')) {
					return { file, fullPath, content };
				}
			}
		}
	}
	return null;
}

/**
 * Extract header value from PHP file content.
 */
function getHeader(content, headerName, defaultValue = '') {
	const regex = new RegExp(`${headerName}:[\\t ]*([^\\r\\n]+)`, 'i');
	const match = content.match(regex);
	return match ? match[1].trim() : defaultValue;
}

const mainPlugin = findMainPluginFile(pluginDir);
if (!mainPlugin) {
	console.error(`❌ Error: No main plugin file with 'Plugin Name:' found in ${pluginDir}`);
	process.exit(1);
}

const phpContent = mainPlugin.content;
const pluginName = getHeader(phpContent, 'Plugin Name', dirName);
const pluginUri = getHeader(phpContent, 'Plugin URI', `https://github.com/${repo}`);
const description = getHeader(phpContent, 'Description', `WordPress plugin ${pluginName}`);
const author = getHeader(phpContent, 'Author', 'Sahajanand Digital');
const authorUri = getHeader(phpContent, 'Author URI', 'https://sahajananddigital.in');
const requiresWp = getHeader(phpContent, 'Requires at least', '5.8');
const testedWp = getHeader(phpContent, 'Tested up to', '6.7');
const requiresPhp = getHeader(phpContent, 'Requires PHP', '7.4');

// Release package download URL from GitHub Release
const downloadUrl = `https://github.com/${repo}/releases/download/${tag}/${zipName}`;

// Target destination in docs/ folder for GitHub Pages deployment
const docsPluginDir = path.join(docsRoot, dirName);
if (!fs.existsSync(docsPluginDir)) {
	fs.mkdirSync(docsPluginDir, { recursive: true });
}
const infoJsonPath = path.join(docsPluginDir, 'info.json');

// Check existing data from docs/ or legacy plugin dir
let existingData = {};
const legacyPluginInfo = path.join(pluginDir, 'info.json');
const sourceInfoPath = fs.existsSync(infoJsonPath) ? infoJsonPath : (fs.existsSync(legacyPluginInfo) ? legacyPluginInfo : null);

if (sourceInfoPath) {
	try {
		existingData = JSON.parse(fs.readFileSync(sourceInfoPath, 'utf8'));
	} catch (e) {
		console.warn(`⚠️ Warning: Could not parse existing info.json at ${sourceInfoPath}: ${e.message}`);
	}
}

// Format UTC date: YYYY-MM-DD HH:mm:ss
const now = new Date();
const pad = (n) => String(n).padStart(2, '0');
const lastUpdated = `${now.getUTCFullYear()}-${pad(now.getUTCMonth() + 1)}-${pad(now.getUTCDate())} ${pad(now.getUTCHours())}:${pad(now.getUTCMinutes())}:${pad(now.getUTCSeconds())}`;

// Construct author HTML link
const authorHtml = authorUri ? `<a href="${authorUri}">${author}</a>` : author;

// Construct sections
const sections = existingData.sections || {};
if (!sections.description) {
	sections.description = description;
}
if (!sections.installation) {
	sections.installation = `<ol><li>Upload the plugin files to the <code>/wp-content/plugins/${dirName}</code> directory, or install via GitHub Releases.</li><li>Activate the plugin through the 'Plugins' screen in WordPress.</li></ol>`;
}
if (!sections.changelog) {
	sections.changelog = `<h4>${version}</h4><ul><li>Automated release of version ${version}.</li></ul>`;
} else if (!sections.changelog.includes(`<h4>${version}</h4>`)) {
	sections.changelog = `<h4>${version}</h4><ul><li>Automated release of version ${version}.</li></ul>` + sections.changelog;
}

// Assemble final info.json payload
const infoData = {
	name: existingData.name || pluginName,
	slug: dirName,
	author: authorHtml,
	author_profile: authorUri || `https://github.com/${repo.split('/')[0]}`,
	homepage: pluginUri,
	version: version,
	download_url: downloadUrl,
	requires: existingData.requires || requiresWp,
	tested: existingData.tested || testedWp,
	requires_php: existingData.requires_php || requiresPhp,
	last_updated: lastUpdated,
	sections: sections,
};

if (existingData.banners) {
	infoData.banners = existingData.banners;
}
if (existingData.icons) {
	infoData.icons = existingData.icons;
}

// Write info.json in docs/<dirName>/info.json
fs.writeFileSync(infoJsonPath, JSON.stringify(infoData, null, 2) + '\n', 'utf8');
console.log(`✅ Successfully generated info.json in docs folder at ${infoJsonPath}`);
