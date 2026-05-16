import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { AudienceApi, type AudienceSubmission } from '../api/client';
import styles from './Audience.module.css';

type Kind = 'all' | 'newsletter' | 'contact';

export function Audience() {
	const [ items, setItems ] = useState< AudienceSubmission[] >( [] );
	const [ total, setTotal ] = useState( 0 );
	const [ loading, setLoading ] = useState( true );
	const [ error, setError ] = useState< string | null >( null );
	const [ kind, setKind ] = useState< Kind >( 'all' );

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			const res = await AudienceApi.list( {
				perPage: 200,
				kind: kind === 'all' ? '' : kind,
			} );
			setItems( res.items );
			setTotal( res.total );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to load audience.', 'biolink-pro' ) );
		} finally {
			setLoading( false );
		}
	}, [ kind ] );

	useEffect( () => {
		void load();
	}, [ load ] );

	return (
		<section className={ styles.root }>
			<header className={ styles.header }>
				<div>
					<h1>{ __( 'Audience', 'biolink-pro' ) }</h1>
					<p className={ styles.subtitle }>
						{ __(
							'Newsletter subscribers and contact form submissions captured from your bio pages.',
							'biolink-pro'
						) }
					</p>
				</div>
				<div className={ styles.controls }>
					<div className={ styles.tabs }>
						{ (
							[
								[ 'all', __( 'All', 'biolink-pro' ) ],
								[ 'newsletter', __( 'Newsletter', 'biolink-pro' ) ],
								[ 'contact', __( 'Contact', 'biolink-pro' ) ],
							] as [ Kind, string ][]
						 ).map( ( [ id, label ] ) => (
							<button
								key={ id }
								type="button"
								className={ `${ styles.tab } ${ kind === id ? styles.tabActive : '' }` }
								onClick={ () => setKind( id ) }
							>
								{ label }
							</button>
						) ) }
					</div>
					<a
						className={ styles.exportBtn }
						href={ AudienceApi.exportUrl( kind === 'all' ? '' : kind ) }
					>
						{ __( 'Export CSV', 'biolink-pro' ) }
					</a>
				</div>
			</header>

			{ error && <div className={ styles.error }>{ error }</div> }

			{ loading ? (
				<p className={ styles.empty }>{ __( 'Loading…', 'biolink-pro' ) }</p>
			) : items.length === 0 ? (
				<div className={ styles.empty }>
					<p style={ { fontWeight: 600, color: 'var(--biolink-color-text)' } }>
						{ __( 'No submissions yet.', 'biolink-pro' ) }
					</p>
					<p>
						{ __(
							'Newsletter and contact form submissions captured from your bio pages will appear here.',
							'biolink-pro'
						) }
					</p>
				</div>
			) : (
				<>
					<p className={ styles.count }>
						{ sprintf(
							/* translators: %d: count */
							__( 'Showing %d submissions', 'biolink-pro' ),
							items.length
						) }
						{ total > items.length &&
							` (${ sprintf( /* translators: %d: total */ __( '%d total', 'biolink-pro' ), total ) })` }
					</p>
					<table className={ styles.table }>
						<thead>
							<tr>
								<th>{ __( 'When', 'biolink-pro' ) }</th>
								<th>{ __( 'Kind', 'biolink-pro' ) }</th>
								<th>{ __( 'Name', 'biolink-pro' ) }</th>
								<th>{ __( 'Email', 'biolink-pro' ) }</th>
								<th>{ __( 'Message', 'biolink-pro' ) }</th>
							</tr>
						</thead>
						<tbody>
							{ items.map( ( s ) => (
								<tr key={ s.id }>
									<td className={ styles.dateCell }>{ formatDate( s.created_at ) }</td>
									<td>
										<span className={ `${ styles.badge } ${ styles[ `kind_${ s.kind }` ] ?? '' }` }>
											{ s.kind }
										</span>
									</td>
									<td>{ s.name || '—' }</td>
									<td className={ styles.mono }>
										{ s.email ? <a href={ `mailto:${ s.email }` }>{ s.email }</a> : '—' }
									</td>
									<td className={ styles.message } title={ s.message }>
										{ s.message ? truncate( s.message, 100 ) : '—' }
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				</>
			) }
		</section>
	);
}

function formatDate( iso: string ): string {
	try {
		return new Date( iso ).toLocaleString( undefined, {
			month: 'short',
			day: 'numeric',
			year: 'numeric',
			hour: 'numeric',
			minute: '2-digit',
		} );
	} catch {
		return iso;
	}
}

function truncate( s: string, max: number ): string {
	return s.length <= max ? s : s.slice( 0, max - 1 ) + '…';
}
