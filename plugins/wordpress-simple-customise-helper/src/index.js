import { render } from '@wordpress/element';
import SettingsApp from './components/SettingsApp';
import './index.scss';

document.addEventListener( 'DOMContentLoaded', () => {
    const htmlElement = document.getElementById( 'wsch-admin-app' );

    if ( htmlElement ) {
        render( <SettingsApp />, htmlElement );
    }
} );
