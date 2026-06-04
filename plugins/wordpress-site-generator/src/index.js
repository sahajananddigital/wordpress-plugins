import { render } from '@wordpress/element';
import App from './App';
import './style.css';

window.addEventListener( 'load', function() {
    const root = document.getElementById( 'ai-site-generator-root' );
    if ( root ) {
        render( <App />, root );
    }
} );
