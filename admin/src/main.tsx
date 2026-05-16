import { createRoot } from 'react-dom/client';
import { App } from './App';
import './styles/globals.css';

const mount = document.getElementById( 'biolink-pro-app' );

if ( mount ) {
	createRoot( mount ).render( <App /> );
}
