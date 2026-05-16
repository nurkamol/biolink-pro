import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { Link, Outlet, useLocation, useParams } from 'react-router-dom';
import { PagesApi, type BioBlock, type BioPage, type BioPageSettings } from '../../api/client';
import { LivePreview } from '../../components/builder/LivePreview';
import { QrDialog } from '../../components/builder/QrDialog';
import { IconCheck, IconCode, IconCopy, IconExternal, IconQr } from '../../components/ui/Icons';
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
							<span className={ styles.previewUrl }>{ formatPreviewHost( page.url ) }</span>
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

function formatPreviewHost( url: string ): string {
	try {
		const u = new URL( url, window.location.origin );
		return `${ u.host }${ u.pathname }`.replace( /\/$/, '' );
	} catch {
		return url;
	}
}
