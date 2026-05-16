import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { RevisionsApi, type PageRevision } from '../../api/client';
import { IconClose } from '../ui/Icons';
import styles from './ScheduleDrawer.module.css';

interface Props {
	open: boolean;
	pageId: number;
	onClose: () => void;
	onRestored: () => void;
}

export function RevisionsDrawer( { open, pageId, onClose, onRestored }: Props ) {
	const [ items, setItems ] = useState< PageRevision[] >( [] );
	const [ loading, setLoading ] = useState( false );
	const [ restoring, setRestoring ] = useState< number | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	const load = useCallback( async () => {
		setLoading( true );
		setError( null );
		try {
			setItems( await RevisionsApi.list( pageId ) );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to load history.', 'biolink-pro' ) );
		} finally {
			setLoading( false );
		}
	}, [ pageId ] );

	useEffect( () => {
		if ( open ) void load();
	}, [ open, load ] );

	useEffect( () => {
		if ( ! open ) return;
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) onClose();
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ open, onClose ] );

	const restore = async ( revId: number ) => {
		// eslint-disable-next-line no-alert
		if ( ! window.confirm( __( 'Restore this revision? Current state will be saved as a new revision first.', 'biolink-pro' ) ) ) {
			return;
		}
		setRestoring( revId );
		setError( null );
		try {
			await RevisionsApi.restore( pageId, revId );
			onRestored();
			onClose();
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Restore failed.', 'biolink-pro' ) );
		} finally {
			setRestoring( null );
		}
	};

	if ( ! open ) return null;

	return (
		<div className={ styles.overlay } onClick={ onClose } role="presentation">
			<aside
				className={ styles.drawer }
				role="dialog"
				aria-modal="true"
				aria-labelledby="biolink-history-title"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<header className={ styles.header }>
					<h2 id="biolink-history-title" className={ styles.title }>
						{ __( 'Version history', 'biolink-pro' ) }
					</h2>
					<button
						type="button"
						className={ styles.closeBtn }
						onClick={ onClose }
						aria-label={ __( 'Close', 'biolink-pro' ) }
					>
						<IconClose size={ 16 } />
					</button>
				</header>

				{ error && (
					<div
						style={ {
							margin: '12px 20px',
							padding: '8px 12px',
							background: '#fdecea',
							border: '1px solid #c41e3a',
							color: '#c41e3a',
							borderRadius: 8,
							fontSize: 13,
						} }
					>
						{ error }
					</div>
				) }

				{ loading ? (
					<div className={ styles.empty }>{ __( 'Loading…', 'biolink-pro' ) }</div>
				) : items.length === 0 ? (
					<div className={ styles.empty }>
						<p>{ __( 'No revisions yet. Every save creates one.', 'biolink-pro' ) }</p>
					</div>
				) : (
					<ul className={ styles.list }>
						{ items.map( ( r, i ) => (
							<li key={ r.id } className={ styles.row }>
								<div className={ styles.rowHead }>
									<span
										className={ `${ styles.statusDot } ${
											i === 0 ? styles.dot_active : styles.dot_open
										}` }
									/>
									<span className={ styles.rowLabel }>
										{ formatRelative( r.saved_at ) }
									</span>
									{ i === 0 && (
										<span className={ styles.rowType }>
											{ __( 'Current', 'biolink-pro' ) }
										</span>
									) }
								</div>
								<div className={ styles.rowMeta }>
									<span>
										{ __( 'by', 'biolink-pro' ) }{ ' ' }
										<strong>{ r.author }</strong>
									</span>
									<span>{ formatAbsolute( r.saved_at ) }</span>
									{ i > 0 && (
										<button
											type="button"
											onClick={ () => void restore( r.id ) }
											disabled={ restoring === r.id }
											style={ {
												marginLeft: 'auto',
												background: 'var(--biolink-color-text)',
												color: '#fff',
												border: 0,
												borderRadius: 999,
												padding: '4px 12px',
												fontSize: 12,
												fontWeight: 600,
												cursor: restoring === r.id ? 'progress' : 'pointer',
												opacity: restoring === r.id ? 0.7 : 1,
											} }
										>
											{ restoring === r.id
												? __( 'Restoring…', 'biolink-pro' )
												: __( 'Restore', 'biolink-pro' ) }
										</button>
									) }
								</div>
							</li>
						) ) }
					</ul>
				) }

				{ items.length > 0 && (
					<footer className={ styles.footer }>
						{ sprintf(
							/* translators: %d: count */
							__( 'Showing last %d revisions (oldest pruned automatically).', 'biolink-pro' ),
							items.length
						) }
					</footer>
				) }
			</aside>
		</div>
	);
}

function formatRelative( iso: string ): string {
	const ms = Date.now() - new Date( iso ).getTime();
	if ( ms < 60_000 ) return __( 'just now', 'biolink-pro' );
	const mins = Math.floor( ms / 60_000 );
	if ( mins < 60 ) return sprintf( /* translators: %d: minutes */ __( '%d min ago', 'biolink-pro' ), mins );
	const hrs = Math.floor( mins / 60 );
	if ( hrs < 24 ) return sprintf( /* translators: %d: hours */ __( '%d hr ago', 'biolink-pro' ), hrs );
	const days = Math.floor( hrs / 24 );
	return sprintf( /* translators: %d: days */ __( '%d days ago', 'biolink-pro' ), days );
}

function formatAbsolute( iso: string ): string {
	try {
		return new Date( iso ).toLocaleString( undefined, {
			month: 'short',
			day: 'numeric',
			hour: 'numeric',
			minute: '2-digit',
		} );
	} catch {
		return iso;
	}
}
