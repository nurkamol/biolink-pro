import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { PagesApi, PortabilityApi, type BioPage } from '../api/client';
import styles from './Pages.module.css';

export function Pages() {
	const [ pages, setPages ] = useState< BioPage[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ creating, setCreating ] = useState( false );
	const [ newTitle, setNewTitle ] = useState( '' );
	const [ selected, setSelected ] = useState< Set< number > >( new Set() );
	const [ busyBulk, setBusyBulk ] = useState( false );
	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState< 'any' | 'publish' | 'draft' >( 'any' );
	const fileInputRef = useRef< HTMLInputElement | null >( null );
	const navigate = useNavigate();

	const reload = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			const result = await PagesApi.list( {
				perPage: 100,
				search: search.trim() || undefined,
				status: statusFilter === 'any' ? undefined : statusFilter,
			} );
			setPages( result );
			setSelected( new Set() );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to load pages.', 'biolink-pro' ) );
		} finally {
			setLoading( false );
		}
	}, [ search, statusFilter ] );

	useEffect( () => {
		const t = window.setTimeout( () => void reload(), 200 );
		return () => window.clearTimeout( t );
	}, [ reload ] );

	const handleCreate = async ( event: React.FormEvent< HTMLFormElement > ) => {
		event.preventDefault();
		const title = newTitle.trim();
		if ( ! title ) return;
		setCreating( true );
		try {
			const page = await PagesApi.create( { title, status: 'draft' } );
			setNewTitle( '' );
			navigate( `/pages/${ page.id }` );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to create page.', 'biolink-pro' ) );
		} finally {
			setCreating( false );
		}
	};

	const handleDelete = async ( id: number ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( __( 'Move this page to trash?', 'biolink-pro' ) ) ) return;
		try {
			await PagesApi.remove( id );
			setPages( ( prev ) => prev.filter( ( p ) => p.id !== id ) );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to delete page.', 'biolink-pro' ) );
		}
	};

	const toggle = ( id: number ) => {
		setSelected( ( prev ) => {
			const next = new Set( prev );
			if ( next.has( id ) ) next.delete( id );
			else next.add( id );
			return next;
		} );
	};

	const toggleAll = () => {
		setSelected( ( prev ) => {
			if ( prev.size === pages.length ) return new Set();
			return new Set( pages.map( ( p ) => p.id ) );
		} );
	};

	const bulkDelete = async () => {
		if ( selected.size === 0 ) return;
		// eslint-disable-next-line no-alert
		if (
			! window.confirm(
				sprintf(
					/* translators: %d: count */
					__( 'Trash %d selected page(s)?', 'biolink-pro' ),
					selected.size
				)
			)
		)
			return;
		setBusyBulk( true );
		try {
			await Promise.all( Array.from( selected ).map( ( id ) => PagesApi.remove( id ) ) );
			setPages( ( prev ) => prev.filter( ( p ) => ! selected.has( p.id ) ) );
			setSelected( new Set() );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Bulk delete failed.', 'biolink-pro' ) );
		} finally {
			setBusyBulk( false );
		}
	};

	const bulkDuplicate = async () => {
		if ( selected.size === 0 ) return;
		setBusyBulk( true );
		try {
			await Promise.all( Array.from( selected ).map( ( id ) => PagesApi.duplicate( id ) ) );
			await reload();
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Bulk duplicate failed.', 'biolink-pro' ) );
		} finally {
			setBusyBulk( false );
		}
	};

	const handleImport = async ( file: File ) => {
		try {
			const text = await file.text();
			const payload = JSON.parse( text );
			const page = await PortabilityApi.importJson( payload );
			await reload();
			navigate( `/pages/${ page.id }` );
		} catch ( err ) {
			setError(
				err instanceof Error
					? err.message
					: __( 'Import failed — make sure the file is a valid BioLink Pro JSON export.', 'biolink-pro' )
			);
		}
	};

	const allSelected = pages.length > 0 && selected.size === pages.length;
	const someSelected = selected.size > 0 && ! allSelected;

	return (
		<section className={ styles.root }>
			<header className={ styles.header }>
				<h1>{ __( 'Bio Pages', 'biolink-pro' ) }</h1>
				<div className={ styles.headerActions }>
					<button
						type="button"
						className={ styles.linkAction }
						onClick={ () => fileInputRef.current?.click() }
					>
						{ __( 'Import JSON', 'biolink-pro' ) }
					</button>
					<input
						ref={ fileInputRef }
						type="file"
						accept="application/json,.json"
						hidden
						onChange={ ( e ) => {
							const f = e.target.files?.[ 0 ];
							if ( f ) void handleImport( f );
							e.target.value = '';
						} }
					/>
				</div>
			</header>

			<form className={ styles.createForm } onSubmit={ handleCreate }>
				<input
					type="text"
					value={ newTitle }
					onChange={ ( e ) => setNewTitle( e.target.value ) }
					placeholder={ __( 'New bio page title…', 'biolink-pro' ) }
					className={ styles.input }
					disabled={ creating }
				/>
				<button type="submit" className={ styles.primaryButton } disabled={ creating || ! newTitle.trim() }>
					{ creating ? __( 'Creating…', 'biolink-pro' ) : __( 'Add page', 'biolink-pro' ) }
				</button>
			</form>

			<div className={ styles.filterBar }>
				<input
					type="search"
					placeholder={ __( 'Search by title…', 'biolink-pro' ) }
					value={ search }
					onChange={ ( e ) => setSearch( e.target.value ) }
					className={ styles.input }
				/>
				<select
					value={ statusFilter }
					onChange={ ( e ) =>
						setStatusFilter( e.target.value as 'any' | 'publish' | 'draft' )
					}
					className={ styles.input }
					aria-label={ __( 'Filter by status', 'biolink-pro' ) }
				>
					<option value="any">{ __( 'All statuses', 'biolink-pro' ) }</option>
					<option value="publish">{ __( 'Published', 'biolink-pro' ) }</option>
					<option value="draft">{ __( 'Draft', 'biolink-pro' ) }</option>
				</select>
				{ ( search || statusFilter !== 'any' ) && (
					<button
						type="button"
						className={ styles.linkAction }
						onClick={ () => {
							setSearch( '' );
							setStatusFilter( 'any' );
						} }
					>
						{ __( 'Clear filters', 'biolink-pro' ) }
					</button>
				) }
			</div>

			{ error && (
				<div className={ styles.error } role="alert">
					{ error }
				</div>
			) }

			{ selected.size > 0 && (
				<div className={ styles.bulkBar } role="region" aria-label={ __( 'Bulk actions', 'biolink-pro' ) }>
					<span className={ styles.bulkCount }>
						{ sprintf(
							/* translators: %d: count */
							__( '%d selected', 'biolink-pro' ),
							selected.size
						) }
					</span>
					<button
						type="button"
						className={ styles.linkAction }
						onClick={ bulkDuplicate }
						disabled={ busyBulk }
					>
						{ __( 'Duplicate', 'biolink-pro' ) }
					</button>
					<button
						type="button"
						className={ styles.dangerAction }
						onClick={ bulkDelete }
						disabled={ busyBulk }
					>
						{ __( 'Delete', 'biolink-pro' ) }
					</button>
					<button
						type="button"
						className={ styles.linkAction }
						onClick={ () => setSelected( new Set() ) }
					>
						{ __( 'Clear', 'biolink-pro' ) }
					</button>
				</div>
			) }

			{ loading ? (
				<p className={ styles.empty }>{ __( 'Loading pages…', 'biolink-pro' ) }</p>
			) : pages.length === 0 ? (
				<p className={ styles.empty }>
					{ __( 'No bio pages yet. Add your first one above.', 'biolink-pro' ) }
				</p>
			) : (
				<table className={ styles.table }>
					<thead>
						<tr>
							<th scope="col" className={ styles.checkCol }>
								<input
									type="checkbox"
									checked={ allSelected }
									ref={ ( el ) => {
										if ( el ) el.indeterminate = someSelected;
									} }
									onChange={ toggleAll }
									aria-label={ __( 'Select all pages', 'biolink-pro' ) }
								/>
							</th>
							<th scope="col">{ __( 'Title', 'biolink-pro' ) }</th>
							<th scope="col">{ __( 'Slug', 'biolink-pro' ) }</th>
							<th scope="col">{ __( 'Status', 'biolink-pro' ) }</th>
							<th scope="col">{ __( 'Blocks', 'biolink-pro' ) }</th>
							<th scope="col">{ __( 'Modified', 'biolink-pro' ) }</th>
							<th scope="col" className={ styles.actionsCol }>{ __( 'Actions', 'biolink-pro' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ pages.map( ( page ) => (
							<tr key={ page.id } className={ selected.has( page.id ) ? styles.rowSelected : '' }>
								<td className={ styles.checkCol }>
									<input
										type="checkbox"
										checked={ selected.has( page.id ) }
										onChange={ () => toggle( page.id ) }
										aria-label={ sprintf(
											/* translators: %s: page title */
											__( 'Select %s', 'biolink-pro' ),
											page.title
										) }
									/>
								</td>
								<td>
									<Link to={ `/pages/${ page.id }` } className={ styles.titleLink }>
										{ page.title || __( '(no title)', 'biolink-pro' ) }
									</Link>
								</td>
								<td className={ styles.mono }>{ page.slug }</td>
								<td>
									<span className={ `${ styles.badge } ${ styles[ `badge_${ page.status }` ] ?? '' }` }>
										{ page.status }
									</span>
								</td>
								<td>{ page.blocks.length }</td>
								<td>{ formatDate( page.modified ) }</td>
								<td className={ styles.actionsCol }>
									<a
										href={ page.url }
										target="_blank"
										rel="noreferrer"
										className={ styles.linkAction }
									>
										{ __( 'View', 'biolink-pro' ) }
									</a>
									<a
										href={ PortabilityApi.exportUrl( page.id ) }
										className={ styles.linkAction }
										title={ __( 'Download as JSON', 'biolink-pro' ) }
									>
										{ __( 'Export', 'biolink-pro' ) }
									</a>
									<button
										type="button"
										className={ styles.dangerAction }
										onClick={ () => handleDelete( page.id ) }
										aria-label={ sprintf(
											/* translators: %s: page title */
											__( 'Delete %s', 'biolink-pro' ),
											page.title
										) }
									>
										{ __( 'Delete', 'biolink-pro' ) }
									</button>
								</td>
							</tr>
						) ) }
					</tbody>
				</table>
			) }
		</section>
	);
}

function formatDate( iso: string ): string {
	if ( ! iso ) return '—';
	try {
		return new Date( iso ).toLocaleString();
	} catch {
		return iso;
	}
}
