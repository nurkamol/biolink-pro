import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { Link, NavLink, useLocation, useMatch } from 'react-router-dom';
import { PagesApi, type BioPage } from '../../api/client';
import {
	IconBars,
	IconCaret,
	IconCog,
	IconGrid,
	IconSparkle,
} from './Icons';

function IconUsers() {
	return (
		<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
			<circle cx="9" cy="8" r="4" />
			<path d="M3 21c0-3.3 2.7-6 6-6s6 2.7 6 6" />
			<circle cx="17" cy="9" r="3" />
			<path d="M21 21c0-2.5-1.8-4.5-4-5" />
		</svg>
	);
}
import styles from './AppShell.module.css';

interface AppShellProps {
	children: ReactNode;
}

export function AppShell( { children }: AppShellProps ) {
	const [ pages, setPages ] = useState< BioPage[] >( [] );
	const location = useLocation();
	const pageMatch = useMatch( '/pages/:id/*' );
	const selectedId = pageMatch?.params.id ? Number( pageMatch.params.id ) : null;

	useEffect( () => {
		let cancelled = false;
		void ( async () => {
			try {
				const list = await PagesApi.list( { perPage: 50 } );
				if ( ! cancelled ) setPages( list );
			} catch {
				// non-fatal
			}
		} )();
		return () => {
			cancelled = true;
		};
	}, [ location.pathname ] );

	const selectedPage = useMemo(
		() => pages.find( ( p ) => p.id === selectedId ) ?? pages[ 0 ] ?? null,
		[ pages, selectedId ]
	);

	const navPageId = selectedPage?.id;

	return (
		<div className={ styles.shell }>
			<aside className={ styles.sidebar }>
				<div className={ styles.sidebarHeader }>
					<div className={ styles.brand }>
						<span className={ styles.brandMark }>BL</span>
						<span>BioLink Pro</span>
					</div>
					<PageSelector pages={ pages } selectedId={ selectedPage?.id ?? null } />
				</div>

				<nav className={ styles.nav } aria-label={ __( 'Primary', 'biolink-pro' ) }>
					<NavGroup
						label={ __( 'My BioLink', 'biolink-pro' ) }
						icon={ <IconGrid /> }
						defaultOpen
						containsActive={ Boolean( pageMatch ) }
					>
						{ navPageId ? (
							<>
								<NestedNavLink to={ `/pages/${ navPageId }/links` } label={ __( 'Links', 'biolink-pro' ) } />
								<NestedNavLink to={ `/pages/${ navPageId }/shop` } label={ __( 'Shop', 'biolink-pro' ) } />
								<NestedNavLink
									to={ `/pages/${ navPageId }/design` }
									label={ __( 'Design', 'biolink-pro' ) }
								/>
							</>
						) : (
							<NavLink to="/pages" className={ navLinkClass }>
								<span className={ styles.navIcon }>
									<IconGrid />
								</span>
								{ __( 'All pages', 'biolink-pro' ) }
							</NavLink>
						) }
					</NavGroup>

					<NavLink
						to={ navPageId ? `/pages/${ navPageId }/insights` : '/analytics' }
						className={ navLinkClass }
					>
						<span className={ styles.navIcon }>
							<IconBars />
						</span>
						{ __( 'Insights', 'biolink-pro' ) }
					</NavLink>

					<NavLink to="/audience" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<IconUsers />
						</span>
						{ __( 'Audience', 'biolink-pro' ) }
					</NavLink>

					<div className={ styles.navSectionLabel }>{ __( 'Account', 'biolink-pro' ) }</div>
					<NavLink to="/changelog" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<IconSparkle />
						</span>
						{ __( "What's New", 'biolink-pro' ) }
					</NavLink>
					<NavLink to="/settings" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<IconCog />
						</span>
						{ __( 'Settings', 'biolink-pro' ) }
					</NavLink>
				</nav>
			</aside>

			<main className={ styles.main }>{ children }</main>
		</div>
	);
}

function navLinkClass( { isActive }: { isActive: boolean } ): string {
	return isActive ? `${ styles.navLink } ${ styles.navLinkActive }` : styles.navLink;
}

function NestedNavLink( { to, label }: { to: string; label: string } ) {
	return (
		<NavLink
			to={ to }
			className={ ( { isActive } ) =>
				isActive
					? `${ styles.navLink } ${ styles.navLinkNested } ${ styles.navLinkActive }`
					: `${ styles.navLink } ${ styles.navLinkNested }`
			}
		>
			{ label }
		</NavLink>
	);
}

interface NavGroupProps {
	label: string;
	icon: ReactNode;
	defaultOpen?: boolean;
	containsActive?: boolean;
	children: ReactNode;
}

function NavGroup( { label, icon, defaultOpen = true, containsActive, children }: NavGroupProps ) {
	const [ open, setOpen ] = useState( defaultOpen || Boolean( containsActive ) );
	useEffect( () => {
		if ( containsActive ) setOpen( true );
	}, [ containsActive ] );
	return (
		<div className={ styles.navGroup }>
			<button
				type="button"
				className={ styles.navGroupHeader }
				onClick={ () => setOpen( ( v ) => ! v ) }
				aria-expanded={ open }
			>
				<span className={ styles.navIcon }>{ icon }</span>
				{ label }
				<span
					className={ `${ styles.navGroupChevron } ${ open ? styles.navGroupChevronOpen : '' }` }
				>
					<IconCaret direction={ open ? 'down' : 'right' } />
				</span>
			</button>
			{ open && <div className={ styles.navGroupBody }>{ children }</div> }
		</div>
	);
}

function PageSelector( { pages, selectedId }: { pages: BioPage[]; selectedId: number | null } ) {
	const [ open, setOpen ] = useState( false );
	const wrapRef = useRef< HTMLDivElement >( null );
	const selected = pages.find( ( p ) => p.id === selectedId ) ?? pages[ 0 ] ?? null;

	useEffect( () => {
		if ( ! open ) return;
		const onClick = ( e: MouseEvent ) => {
			if ( ! wrapRef.current?.contains( e.target as Node ) ) setOpen( false );
		};
		document.addEventListener( 'mousedown', onClick );
		return () => document.removeEventListener( 'mousedown', onClick );
	}, [ open ] );

	const initials = ( selected?.title ?? 'BL' ).slice( 0, 1 ).toUpperCase();

	return (
		<div ref={ wrapRef } style={ { position: 'relative' } }>
			<button type="button" className={ styles.pageSelector } onClick={ () => setOpen( ( v ) => ! v ) }>
				<span className={ styles.pageSelectorAvatar }>{ initials }</span>
				<span className={ styles.pageSelectorLabel }>
					{ selected ? selected.title || `Page #${ selected.id }` : __( 'Select a page', 'biolink-pro' ) }
				</span>
				<span className={ styles.pageSelectorCaret }>
					<IconCaret />
				</span>
			</button>
			{ open && (
				<div className={ styles.pageDropdown }>
					{ pages.map( ( p ) => (
						<Link
							key={ p.id }
							to={ `/pages/${ p.id }/links` }
							className={ `${ styles.pageDropdownItem } ${
								p.id === selectedId ? styles.active : ''
							}` }
							onClick={ () => setOpen( false ) }
						>
							{ p.title || `Page #${ p.id }` }
						</Link>
					) ) }
					{ pages.length > 0 && <div className={ styles.pageDropdownDivider } /> }
					<Link
						to="/pages"
						className={ styles.pageDropdownItem }
						onClick={ () => setOpen( false ) }
					>
						{ __( 'All pages', 'biolink-pro' ) }
					</Link>
				</div>
			) }
		</div>
	);
}
