import { __ } from '@wordpress/i18n';
import type { ReactNode } from 'react';
import { NavLink } from 'react-router-dom';
import styles from './AppShell.module.css';

interface AppShellProps {
	children: ReactNode;
}

const navItems = [
	{ to: '/', label: __( 'Dashboard', 'biolink-pro' ), end: true },
	{ to: '/pages', label: __( 'Pages', 'biolink-pro' ), end: false },
	{ to: '/analytics', label: __( 'Analytics', 'biolink-pro' ), end: false },
	{ to: '/changelog', label: __( "What's New", 'biolink-pro' ), end: false },
	{ to: '/settings', label: __( 'Settings', 'biolink-pro' ), end: false },
];

export function AppShell( { children }: AppShellProps ) {
	return (
		<div className={ styles.shell }>
			<header className={ styles.topbar }>
				<div className={ styles.brand }>
					<span className={ styles.brandMark }>BL</span>
					<span>{ __( 'BioLink Pro', 'biolink-pro' ) }</span>
				</div>
				<nav className={ styles.nav } aria-label={ __( 'Primary', 'biolink-pro' ) }>
					{ navItems.map( ( item ) => (
						<NavLink
							key={ item.to }
							to={ item.to }
							end={ item.end }
							className={ ( { isActive } ) =>
								isActive ? `${ styles.navLink } ${ styles.navLinkActive }` : styles.navLink
							}
						>
							{ item.label }
						</NavLink>
					) ) }
				</nav>
			</header>
			<main className={ styles.main }>{ children }</main>
		</div>
	);
}
