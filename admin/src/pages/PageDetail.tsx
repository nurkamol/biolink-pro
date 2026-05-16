import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import { PagesApi, type BioBlock, type BioPage } from '../api/client';
import { PageBuilder } from '../components/builder/PageBuilder';
import styles from './PageDetail.module.css';

export function PageDetail() {
	const { id } = useParams< { id: string } >();
	const pageId = Number( id );
	const [ page, setPage ] = useState< BioPage | null >( null );
	const [ title, setTitle ] = useState( '' );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

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

	const handleSave = async ( event: React.FormEvent< HTMLFormElement > ) => {
		event.preventDefault();
		if ( ! page ) return;
		setSaving( true );
		setError( null );
		try {
			const updated = await PagesApi.update( page.id, { title } );
			setPage( updated );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to save page.', 'biolink-pro' ) );
		} finally {
			setSaving( false );
		}
	};

	const handlePublish = async () => {
		if ( ! page ) return;
		try {
			const updated = await PagesApi.publish( page.id );
			setPage( updated );
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
			<Link to="/pages" className={ styles.back }>
				{ __( '← Back to pages', 'biolink-pro' ) }
			</Link>

			<form onSubmit={ handleSave } className={ styles.form }>
				<label className={ styles.label }>
					{ __( 'Title', 'biolink-pro' ) }
					<input
						type="text"
						value={ title }
						onChange={ ( e ) => setTitle( e.target.value ) }
						className={ styles.input }
					/>
				</label>

				<div className={ styles.meta }>
					<span>
						{ __( 'Status', 'biolink-pro' ) }: <strong>{ page.status }</strong>
					</span>
					<span>
						{ __( 'Slug', 'biolink-pro' ) }: <code>{ page.slug }</code>
					</span>
					<span>
						{ __( 'Blocks', 'biolink-pro' ) }: { page.blocks.length }
					</span>
				</div>

				{ error && <div className={ styles.error }>{ error }</div> }

				<div className={ styles.actions }>
					<button type="submit" className={ styles.primary } disabled={ saving }>
						{ saving ? __( 'Saving…', 'biolink-pro' ) : __( 'Save', 'biolink-pro' ) }
					</button>
					{ page.status !== 'publish' && window.BIOLINK_PRO.caps.publishPages && (
						<button type="button" className={ styles.secondary } onClick={ handlePublish }>
							{ __( 'Publish now', 'biolink-pro' ) }
						</button>
					) }
					<a href={ page.url } target="_blank" rel="noreferrer" className={ styles.viewLink }>
						{ __( 'View public page ↗', 'biolink-pro' ) }
					</a>
				</div>
			</form>

			<PageBuilder
				pageId={ page.id }
				blocks={ page.blocks }
				onChange={ ( next: BioBlock[] ) => setPage( { ...page, blocks: next } ) }
			/>
		</section>
	);
}
