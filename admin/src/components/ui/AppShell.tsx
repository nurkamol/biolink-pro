import { __ } from '@wordpress/i18n';
import { useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import { Link, NavLink, useLocation, useMatch } from 'react-router-dom';
import { PagesApi, type BioPage } from '../../api/client';
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
						icon={ <Icon name="grid" /> }
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
									<Icon name="grid" />
								</span>
								{ __( 'All pages', 'biolink-pro' ) }
							</NavLink>
						) }
					</NavGroup>

					<NavLink
						to={ navPageId ? `/pages/${ navPageId }/earn` : '/earn' }
						className={ navLinkClass }
					>
						<span className={ styles.navIcon }>
							<Icon name="coin" />
						</span>
						{ __( 'Earn', 'biolink-pro' ) }
						<span className={ styles.navBadge }>{ __( 'Soon', 'biolink-pro' ) }</span>
					</NavLink>

					<NavLink
						to={ navPageId ? `/pages/${ navPageId }/audience` : '/audience' }
						className={ navLinkClass }
					>
						<span className={ styles.navIcon }>
							<Icon name="users" />
						</span>
						{ __( 'Audience', 'biolink-pro' ) }
						<span className={ styles.navBadge }>{ __( 'Soon', 'biolink-pro' ) }</span>
					</NavLink>

					<NavLink
						to={ navPageId ? `/pages/${ navPageId }/insights` : '/analytics' }
						className={ navLinkClass }
					>
						<span className={ styles.navIcon }>
							<Icon name="bars" />
						</span>
						{ __( 'Insights', 'biolink-pro' ) }
					</NavLink>

					<div className={ styles.navSectionLabel }>{ __( 'Tools', 'biolink-pro' ) }</div>
					<NavLink to="/tools/social-planner" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<Icon name="calendar" />
						</span>
						{ __( 'Social planner', 'biolink-pro' ) }
						<span className={ styles.navBadge }>{ __( 'Soon', 'biolink-pro' ) }</span>
					</NavLink>
					<NavLink to="/tools/auto-reply" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<Icon name="chat" />
						</span>
						{ __( 'IG auto-reply', 'biolink-pro' ) }
						<span className={ styles.navBadge }>{ __( 'Soon', 'biolink-pro' ) }</span>
					</NavLink>
					<NavLink to="/tools/shortener" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<Icon name="link" />
						</span>
						{ __( 'Link shortener', 'biolink-pro' ) }
						<span className={ styles.navBadge }>{ __( 'Soon', 'biolink-pro' ) }</span>
					</NavLink>
					<NavLink to="/tools/post-ideas" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<Icon name="spark" />
						</span>
						{ __( 'Post ideas', 'biolink-pro' ) }
						<span className={ styles.navBadge }>{ __( 'Soon', 'biolink-pro' ) }</span>
					</NavLink>

					<div className={ styles.navSectionLabel }>{ __( 'Account', 'biolink-pro' ) }</div>
					<NavLink to="/changelog" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<Icon name="sparkle" />
						</span>
						{ __( "What's New", 'biolink-pro' ) }
					</NavLink>
					<NavLink to="/settings" className={ navLinkClass }>
						<span className={ styles.navIcon }>
							<Icon name="cog" />
						</span>
						{ __( 'Settings', 'biolink-pro' ) }
					</NavLink>
				</nav>

				<SetupChecklist />
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
					▾
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
				<span className={ styles.pageSelectorCaret }>▾</span>
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

function SetupChecklist() {
	// Placeholder — real wiring lands in v2.1 with the onboarding rework.
	const pct = 50;
	return (
		<div className={ styles.setupChecklist }>
			<div className={ styles.setupRing } style={ { '--pct': pct } as React.CSSProperties }>
				<span>{ pct }%</span>
			</div>
			<div className={ styles.setupTitle }>{ __( 'Your setup checklist', 'biolink-pro' ) }</div>
			<p className={ styles.setupSub }>{ __( '3 of 6 complete', 'biolink-pro' ) }</p>
			<Link to="/" className={ styles.setupBtn } style={ { textAlign: 'center', textDecoration: 'none', color: '#fff' } as React.CSSProperties }>
				{ __( 'Finish setup', 'biolink-pro' ) }
			</Link>
		</div>
	);
}

function Icon( { name }: { name: string } ) {
	// Tiny inline SVGs — keep bundle small, no icon library dep.
	const common = { width: 18, height: 18, viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', strokeWidth: 1.8, strokeLinecap: 'round' as const, strokeLinejoin: 'round' as const };
	switch ( name ) {
		case 'grid':
			return (
				<svg { ...common }>
					<rect x="3" y="3" width="7" height="7" rx="1.5" />
					<rect x="14" y="3" width="7" height="7" rx="1.5" />
					<rect x="3" y="14" width="7" height="7" rx="1.5" />
					<rect x="14" y="14" width="7" height="7" rx="1.5" />
				</svg>
			);
		case 'coin':
			return (
				<svg { ...common }>
					<circle cx="12" cy="12" r="9" />
					<path d="M9 12h6M12 9v6" />
				</svg>
			);
		case 'users':
			return (
				<svg { ...common }>
					<circle cx="9" cy="8" r="4" />
					<path d="M3 21c0-3.3 2.7-6 6-6s6 2.7 6 6" />
					<circle cx="17" cy="9" r="3" />
					<path d="M21 21c0-2.5-1.8-4.5-4-5" />
				</svg>
			);
		case 'bars':
			return (
				<svg { ...common }>
					<path d="M4 20V10M10 20V4M16 20v-7M22 20v-4" />
				</svg>
			);
		case 'calendar':
			return (
				<svg { ...common }>
					<rect x="3" y="5" width="18" height="16" rx="2" />
					<path d="M3 10h18M8 3v4M16 3v4" />
				</svg>
			);
		case 'chat':
			return (
				<svg { ...common }>
					<path d="M21 12c0 4-4 7-9 7-1.4 0-2.7-.2-3.9-.6L3 20l1.6-3.4C3.6 15.3 3 13.7 3 12c0-4 4-7 9-7s9 3 9 7z" />
				</svg>
			);
		case 'link':
			return (
				<svg { ...common }>
					<path d="M10 14a4 4 0 005.66 0l3-3a4 4 0 10-5.66-5.66l-1 1" />
					<path d="M14 10a4 4 0 00-5.66 0l-3 3a4 4 0 105.66 5.66l1-1" />
				</svg>
			);
		case 'spark':
			return (
				<svg { ...common }>
					<path d="M12 3l1.6 4.4L18 9l-4.4 1.6L12 15l-1.6-4.4L6 9l4.4-1.6L12 3z" />
				</svg>
			);
		case 'sparkle':
			return (
				<svg { ...common }>
					<path d="M12 3v4M12 17v4M3 12h4M17 12h4M5.6 5.6l2.8 2.8M15.6 15.6l2.8 2.8M5.6 18.4l2.8-2.8M15.6 8.4l2.8-2.8" />
				</svg>
			);
		case 'cog':
			return (
				<svg { ...common }>
					<circle cx="12" cy="12" r="3" />
					<path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1A1.7 1.7 0 009 19.4a1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1A1.7 1.7 0 004.6 9a1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.9.3H9a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.9-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.9V9a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z" />
				</svg>
			);
		default:
			return null;
	}
}
