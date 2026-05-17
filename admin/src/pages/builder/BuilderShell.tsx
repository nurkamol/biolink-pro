import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, Outlet, useLocation, useParams } from 'react-router-dom';
import { PagesApi, type BioBlock, type BioPage, type BioPageSettings } from '../../api/client';
import { LivePreview } from '../../components/builder/LivePreview';
import { QrDialog } from '../../components/builder/QrDialog';
import { RevisionsDrawer } from '../../components/builder/RevisionsDrawer';
import { ScheduleDrawer } from '../../components/builder/ScheduleDrawer';
import { IconCheck, IconClock, IconCode, IconCopy, IconExternal, IconHistory, IconPencil, IconQr } from '../../components/ui/Icons';
import { BuilderContext, type BuilderContextValue } from './BuilderContext';
import styles from './BuilderShell.module.css';

const PREVIEW_ROUTES = [ 'links', 'design', 'shop' ];

export function BuilderShell() {
	const { id } = useParams< { id: string } >();
	const location = useLocation();
	const pageId = Number( id );
	const [ page, setPage ] = useState< BioPage | null >( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ savedAt, setSavedAt ] = useState< Date | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ previewTick, setPreviewTick ] = useState( 0 );
	const [ qrOpen, setQrOpen ] = useState( false );
	const [ scheduleOpen, setScheduleOpen ] = useState( false );
	const [ historyOpen, setHistoryOpen ] = useState( false );
	const [ copiedShortcode, setCopiedShortcode ] = useState( false );

	const settingsTimer = useRef< number | null >( null );
	const seoTimer = useRef< number | null >( null );

	const subRoute = useMemo( () => {
		const tail = location.pathname.replace( /^\/+/, '' ).split( '/' ).pop() ?? '';
		return tail;
	}, [ location.pathname ] );

	const showPreview = PREVIEW_ROUTES.includes( subRoute );

	const reload = useCallback( async () => {
		if ( ! Number.isFinite( pageId ) ) return;
		setLoading( true );
		setError( null );
		try {
			const fetched = await PagesApi.get( pageId );
			setPage( fetched );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to load page.', 'biolink-pro' ) );
		} finally {
			setLoading( false );
		}
	}, [ pageId ] );

	useEffect( () => {
		void reload();
	}, [ reload ] );

	const persist = useCallback(
		async ( patch: Parameters< typeof PagesApi.update >[ 1 ] ) => {
			if ( ! page ) return;
			setSaving( true );
			try {
				const updated = await PagesApi.update( page.id, patch );
				setPage( updated );
				setSavedAt( new Date() );
				setPreviewTick( ( t ) => t + 1 );
				setError( null );
			} catch ( err ) {
				setError( err instanceof Error ? err.message : __( 'Failed to save.', 'biolink-pro' ) );
			} finally {
				setSaving( false );
			}
		},
		[ page ]
	);

	const setBlocks = useCallback(
		( blocks: BioBlock[] ) => {
			if ( ! page ) return;
			setPage( { ...page, blocks } );
			setPreviewTick( ( t ) => t + 1 );
		},
		[ page ]
	);

	const setSettings = useCallback(
		( settings: BioPageSettings ) => {
			if ( ! page ) return;
			setPage( { ...page, settings } );
			if ( settingsTimer.current ) window.clearTimeout( settingsTimer.current );
			settingsTimer.current = window.setTimeout( () => {
				void persist( { settings: settings as Record< string, unknown > } );
			}, 400 );
		},
		[ page, persist ]
	);

	const setTheme = useCallback(
		( slug: string ) => {
			if ( ! page ) return;
			setPage( { ...page, theme: slug } );
			void persist( { theme: slug } );
		},
		[ page, persist ]
	);

	const setSeo = useCallback(
		( seo: Record< string, unknown > ) => {
			if ( ! page ) return;
			setPage( { ...page, seo } );
			if ( seoTimer.current ) window.clearTimeout( seoTimer.current );
			seoTimer.current = window.setTimeout( () => {
				void persist( { seo } );
			}, 500 );
		},
		[ page, persist ]
	);

	const setTitle = useCallback(
		( title: string ) => {
			if ( ! page ) return;
			setPage( { ...page, title } );
			void persist( { title } );
		},
		[ page, persist ]
	);

	const setSlug = useCallback(
		async ( slug: string ) => {
			if ( ! page ) return;
			const cleaned = slug
				.toLowerCase()
				.trim()
				.replace( /[^a-z0-9-]/g, '-' )
				.replace( /-+/g, '-' )
				.replace( /^-|-$/g, '' );
			if ( ! cleaned || cleaned === page.slug ) return;
			await persist( { slug: cleaned } );
		},
		[ page, persist ]
	);

	const bumpPreview = useCallback( () => setPreviewTick( ( t ) => t + 1 ), [] );

	const handlePublish = async () => {
		if ( ! page ) return;
		try {
			const updated = await PagesApi.publish( page.id );
			setPage( updated );
			setPreviewTick( ( t ) => t + 1 );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to publish page.', 'biolink-pro' ) );
		}
	};

	if ( loading ) {
		return <div className={ styles.loadingOverlay }>{ __( 'Loading page…', 'biolink-pro' ) }</div>;
	}

	if ( ! page ) {
		return (
			<div className={ styles.loadingOverlay }>
				<p>{ error ?? __( 'Page not found.', 'biolink-pro' ) }</p>
				<Link to="/pages">{ __( '← Back to pages', 'biolink-pro' ) }</Link>
			</div>
		);
	}

	const ctxValue: BuilderContextValue = {
		page,
		setBlocks,
		setSettings,
		setTheme,
		setSeo,
		setTitle,
		setSlug,
		bumpPreview,
		saving,
		savedAt,
	};

	const title = labelForRoute( subRoute );

	return (
		<BuilderContext.Provider value={ ctxValue }>
			<div className={ showPreview ? styles.shell : styles.shellNoPreview }>
				<div className={ styles.topBar }>
					<div className={ styles.crumb }>{ title }</div>
					<span className={ `${ styles.statusPill } ${ styles[ page.status ] ?? '' }` }>
						{ formatStatus( page.status ) }
					</span>
					<span className={ styles.savedIndicator }>
						{ saving ? (
							__( 'Saving…', 'biolink-pro' )
						) : savedAt ? (
							<>
								<IconCheck />{ ' ' }
								{ sprintf(
									/* translators: %s: time */
									__( 'Saved %s', 'biolink-pro' ),
									savedAt.toLocaleTimeString()
								) }
							</>
						) : (
							''
						) }
					</span>
					{ page.status !== 'publish' && window.BIOLINK_PRO.caps.publishPages && (
						<button type="button" className={ styles.primaryBtn } onClick={ handlePublish }>
							{ __( 'Publish', 'biolink-pro' ) }
						</button>
					) }
					<button
						type="button"
						className={ `${ styles.iconBtn } ${ copiedShortcode ? styles.iconBtnSuccess : '' }` }
						onClick={ () => {
							const code = `[biolink id="${ page.id }"]`;
							void navigator.clipboard?.writeText( code );
							setCopiedShortcode( true );
							window.setTimeout( () => setCopiedShortcode( false ), 1800 );
						} }
						title={ copiedShortcode
							? __( 'Copied!', 'biolink-pro' )
							: sprintf(
									/* translators: %s: shortcode */
									__( 'Copy shortcode: %s', 'biolink-pro' ),
									`[biolink id="${ page.id }"]`
							  ) }
						aria-label={ __( 'Copy shortcode', 'biolink-pro' ) }
					>
						{ copiedShortcode ? <IconCheck size={ 16 } /> : <IconCode size={ 16 } /> }
					</button>
					<button
						type="button"
						className={ styles.iconBtn }
						onClick={ () => setHistoryOpen( true ) }
						title={ __( 'Version history', 'biolink-pro' ) }
						aria-label={ __( 'Version history', 'biolink-pro' ) }
					>
						<IconHistory size={ 16 } />
					</button>
					<button
						type="button"
						className={ styles.iconBtn }
						onClick={ () => setScheduleOpen( true ) }
						title={ __( 'Scheduled blocks', 'biolink-pro' ) }
						aria-label={ __( 'Scheduled blocks', 'biolink-pro' ) }
					>
						<IconClock size={ 16 } />
					</button>
					<button
						type="button"
						className={ styles.iconBtn }
						onClick={ () => setQrOpen( true ) }
						title={ __( 'QR code', 'biolink-pro' ) }
						aria-label={ __( 'QR code', 'biolink-pro' ) }
					>
						<IconQr size={ 16 } />
					</button>
					<a
						className={ styles.iconBtn }
						href={ page.url }
						target="_blank"
						rel="noreferrer"
						title={ __( 'View page', 'biolink-pro' ) }
						aria-label={ __( 'View page', 'biolink-pro' ) }
					>
						<IconExternal size={ 16 } />
					</a>
				</div>

				{ error && <div className={ styles.errorBanner }>{ error }</div> }

				<div className={ styles.content }>
					<Outlet />
				</div>

				{ showPreview && (
					<aside className={ styles.preview }>
						<div className={ styles.previewUrlBar }>
							<EditableUrlBar
								url={ page.url }
								slug={ page.slug }
								onSave={ setSlug }
							/>
							<button
								type="button"
								className={ styles.previewIconBtn }
								onClick={ () => {
									void navigator.clipboard?.writeText( page.url );
								} }
								title={ __( 'Copy URL', 'biolink-pro' ) }
								aria-label={ __( 'Copy URL', 'biolink-pro' ) }
							>
								<IconCopy />
							</button>
						</div>
						<div className={ styles.phoneWrap }>
							<LivePreview url={ page.url } refreshKey={ previewTick } />
						</div>
					</aside>
				) }
			</div>
			<QrDialog
				pageId={ page.id }
				pageUrl={ page.url }
				open={ qrOpen }
				onClose={ () => setQrOpen( false ) }
			/>
			<ScheduleDrawer
				open={ scheduleOpen }
				blocks={ page.blocks }
				onClose={ () => setScheduleOpen( false ) }
			/>
			<RevisionsDrawer
				open={ historyOpen }
				pageId={ page.id }
				onClose={ () => setHistoryOpen( false ) }
				onRestored={ () => void reload() }
			/>
		</BuilderContext.Provider>
	);
}

function labelForRoute( route: string ): string {
	switch ( route ) {
		case 'links':
			return __( 'Links', 'biolink-pro' );
		case 'design':
			return __( 'Design', 'biolink-pro' );
		case 'shop':
			return __( 'Shop', 'biolink-pro' );
		case 'insights':
			return __( 'Insights', 'biolink-pro' );
		case 'audience':
			return __( 'Audience', 'biolink-pro' );
		case 'earn':
			return __( 'Earn', 'biolink-pro' );
		default:
			return __( 'BioLink', 'biolink-pro' );
	}
}

function EditableUrlBar( {
	url,
	slug,
	onSave,
}: {
	url: string;
	slug: string;
	onSave: ( slug: string ) => Promise< void >;
} ) {
	const [ editing, setEditing ] = useState( false );
	const [ draft, setDraft ] = useState( slug );
	const inputRef = useRef< HTMLInputElement | null >( null );

	const prefix = ( () => {
		try {
			const u = new URL( url );
			const parts = u.pathname.replace( /\/$/, '' ).split( '/' );
			parts.pop();
			return `${ u.host }${ parts.join( '/' ) }/`;
		} catch {
			return '/';
		}
	} )();

	useEffect( () => {
		if ( ! editing ) setDraft( slug );
	}, [ slug, editing ] );

	useEffect( () => {
		if ( editing && inputRef.current ) {
			inputRef.current.focus();
			inputRef.current.select();
		}
	}, [ editing ] );

	const commit = async () => {
		const cleaned = draft
			.toLowerCase()
			.trim()
			.replace( /[^a-z0-9-]/g, '-' )
			.replace( /-+/g, '-' )
			.replace( /^-|-$/g, '' );
		if ( ! cleaned || cleaned === slug ) {
			setDraft( slug );
			setEditing( false );
			return;
		}
		await onSave( cleaned );
		setEditing( false );
	};

	if ( editing ) {
		return (
			<span className={ styles.previewUrl } style={ { display: 'flex', alignItems: 'center', gap: 0 } }>
				<span style={ { color: 'var(--biolink-color-text-muted)' } }>{ prefix }</span>
				<input
					ref={ inputRef }
					type="text"
					value={ draft }
					onChange={ ( e ) => setDraft( e.target.value ) }
					onBlur={ () => void commit() }
					onKeyDown={ ( e ) => {
						if ( e.key === 'Enter' ) void commit();
						if ( e.key === 'Escape' ) {
							setDraft( slug );
							setEditing( false );
						}
					} }
					style={ {
						border: 0,
						background: 'transparent',
						padding: '0 4px',
						font: 'inherit',
						fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace',
						color: 'var(--biolink-color-text)',
						width: `${ Math.max( draft.length, 4 ) + 1 }ch`,
						outline: 'none',
					} }
					aria-label={ __( 'Page slug', 'biolink-pro' ) }
				/>
			</span>
		);
	}

	return (
		<button
			type="button"
			onClick={ () => setEditing( true ) }
			className={ styles.previewUrl }
			style={ {
				background: 'none',
				border: 0,
				padding: 0,
				font: 'inherit',
				color: 'inherit',
				cursor: 'text',
				display: 'flex',
				alignItems: 'center',
				gap: 6,
				textAlign: 'left',
				width: '100%',
			} }
			title={ __( 'Click to edit URL slug', 'biolink-pro' ) }
		>
			<span>{ prefix }<strong style={ { fontWeight: 600 } }>{ slug }</strong></span>
			<IconPencil size={ 11 } className={ styles.previewUrlPencil } />
		</button>
	);
}

function formatStatus( status: string ): string {
	switch ( status ) {
		case 'publish':
			return __( 'Live', 'biolink-pro' );
		case 'draft':
			return __( 'Draft', 'biolink-pro' );
		case 'pending':
			return __( 'Pending', 'biolink-pro' );
		default:
			return status.charAt( 0 ).toUpperCase() + status.slice( 1 );
	}
}

