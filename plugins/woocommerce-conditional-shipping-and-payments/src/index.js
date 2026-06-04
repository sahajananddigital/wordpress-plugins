import { render } from '@wordpress/element';
import App from './components/App';
import './index.scss';

const appRoot = document.getElementById( 'wc-csp-admin-app' );

if ( appRoot ) {
	render( <App />, appRoot );
}
