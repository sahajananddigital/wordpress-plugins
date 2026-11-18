(function() {
	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;

	function Dashboard() {
		var [activeTab, setActiveTab] = useState('metrics');
		var [settings, setSettings] = useState({
			github_api_token: '',
			github_repo_owner: '',
			github_repo_name: '',
			github_webhook_secret: '',
			inbound_email_secret: '',
			ai_api_key: '',
			ai_provider: 'gemini'
		});
		var [metrics, setMetrics] = useState({
			total_tickets: 0,
			open_tickets: 0,
			resolved_tickets: 0,
			avg_resolution_seconds: 0
		});
		var [isLoading, setIsLoading] = useState(true);
		var [isSaving, setIsSaving] = useState(false);
		var [notice, setNotice] = useState(null);

		useEffect(function() {
			if (window.gitSupportEngineData && window.gitSupportEngineData.nonce) {
				wp.apiFetch.use(wp.apiFetch.createNonceMiddleware(window.gitSupportEngineData.nonce));
			}
			fetchData();
		}, []);

		function fetchData() {
			setIsLoading(true);
			Promise.all([
				wp.apiFetch({ path: '/gitsupport/v1/settings' }),
				wp.apiFetch({ path: '/gitsupport/v1/metrics' })
			]).then(function(results) {
				setSettings(results[0]);
				setMetrics(results[1]);
				setIsLoading(false);
			}).catch(function(err) {
				showNotice('error', err.message || 'Failed to fetch data.');
				setIsLoading(false);
			});
		}

		function handleSaveSettings(e) {
			e.preventDefault();
			setIsSaving(true);
			setNotice(null);
			wp.apiFetch({
				path: '/gitsupport/v1/settings',
				method: 'POST',
				data: settings
			}).then(function() {
				showNotice('success', 'Settings saved successfully.');
				return wp.apiFetch({ path: '/gitsupport/v1/metrics' });
			}).then(function(fetchedMetrics) {
				setMetrics(fetchedMetrics);
				setIsSaving(false);
			}).catch(function(err) {
				showNotice('error', err.message || 'Failed to save settings.');
				setIsSaving(false);
			});
		}

		function showNotice(type, message) {
			setNotice({ type: type, message: message });
			setTimeout(function() { setNotice(null); }, 5000);
		}

		function formatResolutionTime(seconds) {
			if (!seconds || seconds <= 0) return 'N/A';
			var mins = Math.floor(seconds / 60);
			var hrs = Math.floor(mins / 60);
			var days = Math.floor(hrs / 24);

			if (days > 0) {
				return days + 'd ' + (hrs % 24) + 'h';
			}
			if (hrs > 0) {
				return hrs + 'h ' + (mins % 60) + 'm';
			}
			if (mins > 0) {
				return mins + 'm ' + Math.round(seconds % 60) + 's';
			}
			return Math.round(seconds) + 's';
		}

		if (isLoading) {
			return el('div', { className: 'gitsupport-loading' },
				el(wp.components.Spinner, null),
				' Loading GitSupport Engine Dashboard...'
			);
		}

		var siteUrl = window.gitSupportEngineData ? window.gitSupportEngineData.siteUrl : '';
		var inboundWebhookUrl = siteUrl + '/wp-json/gitsupport/v1/inbound?secret=' + (settings.inbound_email_secret || 'YOUR_SECRET');
		var githubWebhookUrl = siteUrl + '/wp-json/gitsupport/v1/github-comment';

		var tabContent;
		if (activeTab === 'metrics') {
			tabContent = el('div', { className: 'gitsupport-metrics-tab' },
				el('div', { className: 'gitsupport-card-grid' },
					el('div', { className: 'gitsupport-card' },
						el('h3', null, 'Total Tickets'),
						el('div', { className: 'gitsupport-card-value' }, metrics.total_tickets)
					),
					el('div', { className: 'gitsupport-card' },
						el('h3', null, 'Open Tickets'),
						el('div', { className: 'gitsupport-card-value open' }, metrics.open_tickets)
					),
					el('div', { className: 'gitsupport-card' },
						el('h3', null, 'Resolved Tickets'),
						el('div', { className: 'gitsupport-card-value resolved' }, metrics.resolved_tickets)
					),
					el('div', { className: 'gitsupport-card' },
						el('h3', null, 'Avg Resolution Time'),
						el('div', { className: 'gitsupport-card-value' }, formatResolutionTime(metrics.avg_resolution_seconds))
					)
				),
				el(wp.components.Panel, { header: 'Webhook Configuration Reference' },
					el(wp.components.PanelBody, { title: 'Configure these URLs in your third-party integrations', initialOpen: true },
						el('div', { className: 'webhook-field' },
							el('strong', null, 'Inbound Email Webhook URL (Postmark / Mailgun):'),
							el('pre', null, inboundWebhookUrl),
							el('p', { className: 'description' }, 'Configure your inbound mail service to post JSON payloads to this address.')
						),
						el('div', { className: 'webhook-field' },
							el('strong', null, 'GitHub Webhook URL:'),
							el('pre', null, githubWebhookUrl),
							el('p', { className: 'description' }, 'Add this webhook in your GitHub Repository settings under Webhooks. Select event Issue comments and Issues. Payload format must be application/json. Ensure the Webhook Secret matches what you configure in Settings.')
						)
					)
				)
			);
		} else {
			tabContent = el('form', { onSubmit: handleSaveSettings, className: 'gitsupport-settings-form' },
				el(wp.components.Panel, { header: 'GitHub Integration' },
					el(wp.components.PanelBody, { title: 'GitHub Settings', initialOpen: true },
						el(wp.components.TextControl, {
							label: 'GitHub API Personal Access Token',
							value: settings.github_api_token,
							onChange: function(val) { setSettings(Object.assign({}, settings, { github_api_token: val })); },
							type: 'password',
							help: 'Needs repo scope to create issues and read comments.'
						}),
						el(wp.components.TextControl, {
							label: 'GitHub Repository Owner',
							value: settings.github_repo_owner,
							onChange: function(val) { setSettings(Object.assign({}, settings, { github_repo_owner: val })); },
							placeholder: 'e.g. facebook'
						}),
						el(wp.components.TextControl, {
							label: 'GitHub Repository Name',
							value: settings.github_repo_name,
							onChange: function(val) { setSettings(Object.assign({}, settings, { github_repo_name: val })); },
							placeholder: 'e.g. react'
						}),
						el(wp.components.TextControl, {
							label: 'GitHub Webhook Secret',
							value: settings.github_webhook_secret,
							onChange: function(val) { setSettings(Object.assign({}, settings, { github_webhook_secret: val })); },
							type: 'password',
							help: 'Used to verify HMAC signature on incoming GitHub webhooks.'
						})
					)
				),
				el(wp.components.Panel, { header: 'Inbound Mail & AI settings' },
					el(wp.components.PanelBody, { title: 'Inbound & AI Configuration', initialOpen: true },
						el(wp.components.TextControl, {
							label: 'Inbound Email Hook Secret',
							value: settings.inbound_email_secret,
							onChange: function(val) { setSettings(Object.assign({}, settings, { inbound_email_secret: val })); },
							help: 'Required token to authenticate incoming emails POST requests.'
						}),
						el(wp.components.SelectControl, {
							label: 'AI Provider',
							value: settings.ai_provider,
							options: [
								{ label: 'Gemini (Recommended)', value: 'gemini' },
								{ label: 'OpenAI', value: 'openai' }
							],
							onChange: function(val) { setSettings(Object.assign({}, settings, { ai_provider: val })); }
						}),
						el(wp.components.TextControl, {
							label: 'AI API Key',
							value: settings.ai_api_key,
							onChange: function(val) { setSettings(Object.assign({}, settings, { ai_api_key: val })); },
							type: 'password',
							help: 'API key for the selected AI provider.'
						})
					)
				),
				el(wp.components.Button, { isPrimary: true, type: 'submit', isBusy: isSaving },
					isSaving ? 'Saving...' : 'Save Settings'
				)
			);
		}

		return el('div', { className: 'gitsupport-wrap' },
			el('h1', null, 'GitSupport Engine Dashboard'),
			notice ? el(wp.components.Notice, { status: notice.type, isDismissible: false }, notice.message) : null,
			el(wp.components.TabPanel, {
				className: 'gitsupport-tabs',
				activeClass: 'active-tab',
				onSelect: function(tabName) { setActiveTab(tabName); },
				tabs: [
					{ name: 'metrics', title: 'Overview & Metrics', className: 'tab-metrics' },
					{ name: 'settings', title: 'Settings', className: 'tab-settings' }
				]
			}, function() { return tabContent; })
		);
	}

	wp.domReady(function() {
		var rootEl = document.getElementById('gitsupport-dashboard-root');
		if (rootEl) {
			wp.element.render(el(Dashboard), rootEl);
		}
	});
})();
