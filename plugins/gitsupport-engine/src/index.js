import { render, useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import domReady from '@wordpress/dom-ready';
import {
	Panel,
	PanelBody,
	PanelRow,
	TextControl,
	SelectControl,
	Button,
	Spinner,
	Placeholder,
	Notice,
	TabPanel
} from '@wordpress/components';

import './index.css';

const Dashboard = () => {
	const [activeTab, setActiveTab] = useState('metrics');
	const [settings, setSettings] = useState({
		github_api_token: '',
		github_repo_owner: '',
		github_repo_name: '',
		github_webhook_secret: '',
		inbound_email_secret: '',
		ai_api_key: '',
		ai_provider: 'gemini'
	});
	const [metrics, setMetrics] = useState({
		total_tickets: 0,
		open_tickets: 0,
		resolved_tickets: 0,
		avg_resolution_seconds: 0
	});
	const [isLoading, setIsLoading] = useState(true);
	const [isSaving, setIsSaving] = useState(false);
	const [notice, setNotice] = useState(null);

	// Setup apiFetch middleware to include nonce.
	useEffect(() => {
		if (window.gitSupportEngineData && window.gitSupportEngineData.nonce) {
			apiFetch.use(apiFetch.createNonceMiddleware(window.gitSupportEngineData.nonce));
		}

		fetchData();
	}, []);

	const fetchData = async () => {
		setIsLoading(true);
		try {
			const fetchedSettings = await apiFetch({ path: '/gitsupport/v1/settings' });
			const fetchedMetrics = await apiFetch({ path: '/gitsupport/v1/metrics' });
			setSettings(fetchedSettings);
			setMetrics(fetchedMetrics);
		} catch (err) {
			showNotice('error', err.message || 'Failed to fetch data.');
		} finally {
			setIsLoading(false);
		}
	};

	const handleSaveSettings = async (e) => {
		e.preventDefault();
		setIsSaving(true);
		setNotice(null);
		try {
			await apiFetch({
				path: '/gitsupport/v1/settings',
				method: 'POST',
				data: settings
			});
			showNotice('success', 'Settings saved successfully.');
			// Refresh metrics
			const fetchedMetrics = await apiFetch({ path: '/gitsupport/v1/metrics' });
			setMetrics(fetchedMetrics);
		} catch (err) {
			showNotice('error', err.message || 'Failed to save settings.');
		} finally {
			setIsSaving(false);
		}
	};

	const showNotice = (type, message) => {
		setNotice({ type, message });
		setTimeout(() => setNotice(null), 5000);
	};

	const formatResolutionTime = (seconds) => {
		if (!seconds || seconds <= 0) return 'N/A';
		const mins = Math.floor(seconds / 60);
		const hrs = Math.floor(mins / 60);
		const days = Math.floor(hrs / 24);

		if (days > 0) {
			return `${days}d ${hrs % 24}h`;
		}
		if (hrs > 0) {
			return `${hrs}h ${mins % 60}m`;
		}
		if (mins > 0) {
			return `${mins}m ${Math.round(seconds % 60)}s`;
		}
		return `${Math.round(seconds)}s`;
	};

	if (isLoading) {
		return (
			<div className="gitsupport-loading">
				<Spinner /> Loading GitSupport Engine Dashboard...
			</div>
		);
	}

	const siteUrl = window.gitSupportEngineData ? window.gitSupportEngineData.siteUrl : '';
	const inboundWebhookUrl = `${siteUrl}/wp-json/gitsupport/v1/inbound?secret=${settings.inbound_email_secret || 'YOUR_SECRET'}`;
	const githubWebhookUrl = `${siteUrl}/wp-json/gitsupport/v1/github-comment`;

	return (
		<div className="gitsupport-wrap">
			<h1>GitSupport Engine Dashboard</h1>
			{notice && (
				<Notice status={notice.type} isDismissible={false}>
					{notice.message}
				</Notice>
			)}

			<TabPanel
				className="gitsupport-tabs"
				activeClass="active-tab"
				onSelect={(tabName) => setActiveTab(tabName)}
				tabs={[
					{
						name: 'metrics',
						title: 'Overview & Metrics',
						className: 'tab-metrics'
					},
					{
						name: 'settings',
						title: 'Settings',
						className: 'tab-settings'
					}
				]}
			>
				{(tab) => (
					<div className="gitsupport-tab-content">
						{tab.name === 'metrics' && (
							<div className="gitsupport-metrics-tab">
								<div className="gitsupport-card-grid">
									<div className="gitsupport-card">
										<h3>Total Tickets</h3>
										<div className="gitsupport-card-value">{metrics.total_tickets}</div>
									</div>
									<div className="gitsupport-card">
										<h3>Open Tickets</h3>
										<div className="gitsupport-card-value open">{metrics.open_tickets}</div>
									</div>
									<div className="gitsupport-card">
										<h3>Resolved Tickets</h3>
										<div className="gitsupport-card-value resolved">{metrics.resolved_tickets}</div>
									</div>
									<div className="gitsupport-card">
										<h3>Avg Resolution Time</h3>
										<div className="gitsupport-card-value">{formatResolutionTime(metrics.avg_resolution_seconds)}</div>
									</div>
								</div>

								<Panel header="Webhook Configuration Reference">
									<PanelBody title="Configure these URLs in your third-party integrations" initialOpen={true}>
										<div className="webhook-field">
											<strong>Inbound Email Webhook URL (Postmark / Mailgun):</strong>
											<pre>{inboundWebhookUrl}</pre>
											<p className="description">
												Configure your inbound mail service to post JSON payloads to this address.
											</p>
										</div>
										<div className="webhook-field">
											<strong>GitHub Webhook URL:</strong>
											<pre>{githubWebhookUrl}</pre>
											<p className="description">
												Add this webhook in your GitHub Repository settings under Webhooks. Select event <code>Issue comments</code> and <code>Issues</code>. Payload format must be <code>application/json</code>. Ensure the Webhook Secret matches what you configure in Settings.
											</p>
										</div>
									</PanelBody>
								</Panel>
							</div>
						)}

						{tab.name === 'settings' && (
							<form onSubmit={handleSaveSettings} className="gitsupport-settings-form">
								<Panel header="GitHub Integration">
									<PanelBody title="GitHub Settings" initialOpen={true}>
										<TextControl
											label="GitHub API Personal Access Token"
											value={settings.github_api_token}
											onChange={(val) => setSettings({ ...settings, github_api_token: val })}
											type="password"
											help="Needs repo scope to create issues and read comments."
										/>
										<TextControl
											label="GitHub Repository Owner"
											value={settings.github_repo_owner}
											onChange={(val) => setSettings({ ...settings, github_repo_owner: val })}
											placeholder="e.g. facebook"
										/>
										<TextControl
											label="GitHub Repository Name"
											value={settings.github_repo_name}
											onChange={(val) => setSettings({ ...settings, github_repo_name: val })}
											placeholder="e.g. react"
										/>
										<TextControl
											label="GitHub Webhook Secret"
											value={settings.github_webhook_secret}
											onChange={(val) => setSettings({ ...settings, github_webhook_secret: val })}
											type="password"
											help="Used to verify HMAC signature on incoming GitHub webhooks."
										/>
									</PanelBody>
								</Panel>

								<Panel header="Inbound Mail & AI settings">
									<PanelBody title="Inbound & AI Configuration" initialOpen={true}>
										<TextControl
											label="Inbound Email Hook Secret"
											value={settings.inbound_email_secret}
											onChange={(val) => setSettings({ ...settings, inbound_email_secret: val })}
											help="Required token to authenticate incoming emails POST requests."
										/>
										<SelectControl
											label="AI Provider"
											value={settings.ai_provider}
											options={[
												{ label: 'Gemini (Recommended)', value: 'gemini' },
												{ label: 'OpenAI', value: 'openai' }
											]}
											onChange={(val) => setSettings({ ...settings, ai_provider: val })}
										/>
										<TextControl
											label="AI API Key"
											value={settings.ai_api_key}
											onChange={(val) => setSettings({ ...settings, ai_api_key: val })}
											type="password"
											help="API key for the selected AI provider."
										/>
									</PanelBody>
								</Panel>

								<Button isPrimary type="submit" isBusy={isSaving}>
									{isSaving ? 'Saving...' : 'Save Settings'}
								</Button>
							</form>
						)}
					</div>
				)}
			</TabPanel>
		</div>
	);
};

domReady(() => {
	const rootEl = document.getElementById('gitsupport-dashboard-root');
	if (rootEl) {
		render(<Dashboard />, rootEl);
	}
});
