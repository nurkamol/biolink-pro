import { createRoot } from 'react-dom/client';
import { App } from './App';
import './styles/globals.css';

// Route the React app based on which BioLink Pro submenu was loaded, so wp-admin
// submenu deep-links (e.g. ?page=biolink-pro-changelog) land on the right view.
const SUBMENU_TO_HASH: Record< string, string > = {
	'biolink-pro-changelog': '#/changelog',
	'biolink-pro-settings': '#/settings',
	'biolink-pro': '#/',
};

const params = new URLSearchParams( window.location.search );
const submenu = params.get( 'page' );
if ( submenu && SUBMENU_TO_HASH[ submenu ] && ! window.location.hash ) {
	window.location.hash = SUBMENU_TO_HASH[ submenu ];
}

const mount = document.getElementById( 'biolink-pro-app' );

if ( mount ) {
	createRoot( mount ).render( <App /> );
}
