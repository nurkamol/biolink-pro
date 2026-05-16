import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { PagesApi, type BioPage } from '../api/client';
import styles from './Pages.module.css';

export function Pages() {
	const [ pages, setPages ] = useState< BioPage[] >( [] );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ creating, setCreating ] = useState( false );
	const [ newTitle, setNewTitle ] = useState( '' );
	const navigate = useNavigate();

	const reload = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			const result = await PagesApi.list( { perPage: 50 } );
			setPages( result );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to load pages.', 'biolink-pro' ) );
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		void reload();
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

	return (
		<section className={ styles.root }>
			<header className={ styles.header }>
				<h1>{ __( 'Bio Pages', 'biolink-pro' ) }</h1>
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

			{ error && <div className={ styles.error }>{ error }</div> }

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
							<th>{ __( 'Title', 'biolink-pro' ) }</th>
							<th>{ __( 'Slug', 'biolink-pro' ) }</th>
							<th>{ __( 'Status', 'biolink-pro' ) }</th>
							<th>{ __( 'Blocks', 'biolink-pro' ) }</th>
							<th>{ __( 'Modified', 'biolink-pro' ) }</th>
							<th className={ styles.actionsCol }>{ __( 'Actions', 'biolink-pro' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ pages.map( ( page ) => (
							<tr key={ page.id }>
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
									<a href={ page.url } target="_blank" rel="noreferrer" className={ styles.linkAction }>
										{ __( 'View', 'biolink-pro' ) }
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
