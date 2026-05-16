import { __ } from '@wordpress/i18n';
import { Link } from 'react-router-dom';
import styles from './Dashboard.module.css';

export function Dashboard() {
	return (
		<section className={ styles.root }>
			<h1>{ __( 'Welcome to BioLink Pro', 'biolink-pro' ) }</h1>
			<p className={ styles.lede }>
				{ __(
					'Build mobile-first bio link pages right inside WordPress.',
					'biolink-pro'
				) }
			</p>

			<div className={ styles.cards }>
				<Link to="/pages" className={ styles.card }>
					<h2>{ __( 'Bio Pages', 'biolink-pro' ) }</h2>
					<p>{ __( 'Create, edit, and publish your bio pages.', 'biolink-pro' ) }</p>
				</Link>
				<Link to="/settings" className={ styles.card }>
					<h2>{ __( 'Settings', 'biolink-pro' ) }</h2>
					<p>{ __( 'Global plugin settings and integrations.', 'biolink-pro' ) }</p>
				</Link>
			</div>
		</section>
	);
}
