import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PagesApi, type BioBlock, type BioPage, type BioPageSettings } from '../api/client';
import { BackgroundEditor } from '../components/builder/BackgroundEditor';
import { LivePreview } from '../components/builder/LivePreview';
import { PageBuilder } from '../components/builder/PageBuilder';
import { PageHeaderEditor } from '../components/builder/PageHeaderEditor';
import { ThemePicker } from '../components/builder/ThemePicker';
import styles from './PageDetail.module.css';

type SettingsTab = 'header' | 'theme' | 'background';

export function PageDetail() {
	const { id } = useParams< { id: string } >();
	const pageId = Number( id );
	const [ page, setPage ] = useState< BioPage | null >( null );
	const [ title, setTitle ] = useState( '' );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ saveTick, setSaveTick ] = useState( 0 );
	const [ savedAt, setSavedAt ] = useState< Date | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ tab, setTab ] = useState< SettingsTab >( 'header' );
	const settingsSaveTimer = useRef< number | null >( null );

	const reload = useCallback( async () => {
		if ( ! Number.isFinite( pageId ) ) return;
		setLoading( true );
		setError( null );
		try {
			const fetched = await PagesApi.get( pageId );
			setPage( fetched );
			setTitle( fetched.title );
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
				setSaveTick( ( t ) => t + 1 );
				setError( null );
			} catch ( err ) {
				setError( err instanceof Error ? err.message : __( 'Failed to save.', 'biolink-pro' ) );
			} finally {
				setSaving( false );
			}
		},
		[ page ]
	);

	const handleTitleSave = async ( event: React.FormEvent< HTMLFormElement > ) => {
		event.preventDefault();
		await persist( { title } );
	};

	const handleSettingsChange = ( next: BioPageSettings ) => {
		if ( ! page ) return;
		setPage( { ...page, settings: next } );
		if ( settingsSaveTimer.current ) window.clearTimeout( settingsSaveTimer.current );
		settingsSaveTimer.current = window.setTimeout( () => {
			void persist( { settings: next as Record< string, unknown > } );
		}, 500 );
	};

	const handleThemeChange = ( slug: string ) => {
		if ( ! page ) return;
		setPage( { ...page, theme: slug } );
		void persist( { theme: slug } );
	};

	const handleBlocksChange = ( next: BioBlock[] ) => {
		if ( ! page ) return;
		setPage( { ...page, blocks: next } );
		// BlocksApi handles its own persistence — bump the preview key after each.
		setSaveTick( ( t ) => t + 1 );
	};

	const handlePublish = async () => {
		if ( ! page ) return;
		try {
			const updated = await PagesApi.publish( page.id );
			setPage( updated );
			setSaveTick( ( t ) => t + 1 );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to publish page.', 'biolink-pro' ) );
		}
	};

	if ( loading ) {
		return <p className={ styles.empty }>{ __( 'Loading page…', 'biolink-pro' ) }</p>;
	}

	if ( ! page ) {
		return (
			<section>
				<p>{ error ?? __( 'Page not found.', 'biolink-pro' ) }</p>
				<Link to="/pages">{ __( '← Back to pages', 'biolink-pro' ) }</Link>
			</section>
		);
	}

	return (
		<section className={ styles.root }>
			<div className={ styles.topBar }>
				<Link to="/pages" className={ styles.back }>
					{ __( '← Pages', 'biolink-pro' ) }
				</Link>

				<form className={ styles.titleForm } onSubmit={ handleTitleSave }>
					<input
						type="text"
						value={ title }
						onChange={ ( e ) => setTitle( e.target.value ) }
						className={ styles.titleInput }
						placeholder={ __( 'Untitled bio page', 'biolink-pro' ) }
					/>
					<span className={ `${ styles.statusBadge } ${ styles[ `status_${ page.status }` ] ?? '' }` }>
						{ page.status }
					</span>
				</form>

				<div className={ styles.topActions }>
					<span className={ styles.saveIndicator }>
						{ saving
							? __( 'Saving…', 'biolink-pro' )
							: savedAt
							? sprintf(
									/* translators: %s: time */
									__( 'Saved %s', 'biolink-pro' ),
									savedAt.toLocaleTimeString()
							  )
							: '' }
					</span>
					<button
						type="button"
						className={ styles.btn }
						onClick={ () => void persist( { title } ) }
						disabled={ saving }
					>
						{ __( 'Save', 'biolink-pro' ) }
					</button>
					{ page.status !== 'publish' && window.BIOLINK_PRO.caps.publishPages && (
						<button type="button" className={ styles.btnPrimary } onClick={ handlePublish }>
							{ __( 'Publish', 'biolink-pro' ) }
						</button>
					) }
					<a href={ page.url } target="_blank" rel="noreferrer" className={ styles.viewLink }>
						{ __( 'View ↗', 'biolink-pro' ) }
					</a>
				</div>
			</div>

			{ error && <div className={ styles.error }>{ error }</div> }

			<div className={ styles.layout }>
				<div className={ styles.workspace }>
					<aside className={ styles.settingsRail }>
						<div className={ styles.tabBar } role="tablist">
							<button
								type="button"
								role="tab"
								aria-selected={ tab === 'header' }
								className={ `${ styles.tab } ${ tab === 'header' ? styles.tabActive : '' }` }
								onClick={ () => setTab( 'header' ) }
							>
								{ __( 'Header', 'biolink-pro' ) }
							</button>
							<button
								type="button"
								role="tab"
								aria-selected={ tab === 'theme' }
								className={ `${ styles.tab } ${ tab === 'theme' ? styles.tabActive : '' }` }
								onClick={ () => setTab( 'theme' ) }
							>
								{ __( 'Theme', 'biolink-pro' ) }
							</button>
							<button
								type="button"
								role="tab"
								aria-selected={ tab === 'background' }
								className={ `${ styles.tab } ${ tab === 'background' ? styles.tabActive : '' }` }
								onClick={ () => setTab( 'background' ) }
							>
								{ __( 'Background', 'biolink-pro' ) }
							</button>
						</div>
						<div className={ styles.settingsBody }>
							{ tab === 'header' && (
								<PageHeaderEditor
									settings={ page.settings }
									onChange={ handleSettingsChange }
								/>
							) }
							{ tab === 'theme' && (
								<ThemePicker value={ page.theme } onChange={ handleThemeChange } />
							) }
							{ tab === 'background' && (
								<BackgroundEditor
									settings={ page.settings }
									onChange={ handleSettingsChange }
								/>
							) }
						</div>
					</aside>

					<div className={ styles.builderColumn }>
						<PageBuilder
							pageId={ page.id }
							blocks={ page.blocks }
							onChange={ handleBlocksChange }
						/>
					</div>
				</div>

				<aside className={ styles.previewColumn }>
					<LivePreview url={ page.url } refreshKey={ saveTick } />
				</aside>
			</div>
		</section>
	);
}
