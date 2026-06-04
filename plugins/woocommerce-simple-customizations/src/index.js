/**
 * External dependencies
 */
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SettingsApp from './components/SettingsApp';
import './index.scss';

const root = document.getElementById( 'wsc-settings-root' );

if ( root ) {
	render( <SettingsApp />, root );
}
