import { render } from '@wordpress/element';
import Dashboard from './components/Dashboard';
import Settings from './components/Settings';
import './index.css';

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('wp-event-manager-app');
	if (container) {
		const urlParams = new URLSearchParams(window.location.search);
		const page = urlParams.get('page');

		if (page === 'wp-event-manager-settings') {
			render(<Settings />, container);
		} else {
			render(<Dashboard />, container);
		}
	}
});
