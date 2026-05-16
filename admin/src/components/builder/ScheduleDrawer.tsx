import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useMemo } from 'react';
import type { BioBlock } from '../../api/client';
import { findBlockMeta } from '../../blocks';
import { IconClose } from '../ui/Icons';
import styles from './ScheduleDrawer.module.css';

interface Props {
	open: boolean;
	blocks: BioBlock[];
	onClose: () => void;
}

type Entry = {
	uuid: string;
	type: string;
	label: string;
	startAt: string;
	endAt: string;
	status: 'upcoming' | 'active' | 'expired' | 'open';
};

export function ScheduleDrawer( { open, blocks, onClose }: Props ) {
	const entries = useMemo< Entry[] >( () => {
		const now = Date.now();
		const rows: Entry[] = [];
		for ( const b of blocks ) {
			const d = b.data as Record< string, unknown >;
			const startAt = ( d._start_at as string ) || '';
			const endAt = ( d._end_at as string ) || '';
			if ( ! startAt && ! endAt ) continue;
			const start = startAt ? new Date( startAt ).getTime() : 0;
			const end = endAt ? new Date( endAt ).getTime() : 0;
			let status: Entry[ 'status' ] = 'open';
			if ( start && now < start ) status = 'upcoming';
			else if ( end && now > end ) status = 'expired';
			else status = 'active';
			rows.push( {
				uuid: b.uuid,
				type: b.type,
				label: labelFor( b ),
				startAt,
				endAt,
				status,
			} );
		}
		// Sort by start (upcoming first), then by end.
		return rows.sort( ( a, b ) => {
			const aKey = a.startAt || a.endAt;
			const bKey = b.startAt || b.endAt;
			return aKey.localeCompare( bKey );
		} );
	}, [ blocks ] );

	useEffect( () => {
		if ( ! open ) return;
		const onKey = ( e: KeyboardEvent ) => {
			if ( e.key === 'Escape' ) onClose();
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ open, onClose ] );

	if ( ! open ) return null;

	return (
		<div className={ styles.overlay } onClick={ onClose } role="presentation">
			<aside
				className={ styles.drawer }
				role="dialog"
				aria-modal="true"
				aria-labelledby="biolink-schedule-title"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<header className={ styles.header }>
					<h2 id="biolink-schedule-title" className={ styles.title }>
						{ __( 'Scheduled blocks', 'biolink-pro' ) }
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

				{ entries.length === 0 ? (
					<div className={ styles.empty }>
						<p>
							{ __(
								'No blocks have a schedule set. Use the clock chip on a link row to set a visible-from / visible-until window.',
								'biolink-pro'
							) }
						</p>
					</div>
				) : (
					<ul className={ styles.list }>
						{ entries.map( ( e ) => (
							<li key={ e.uuid } className={ styles.row }>
								<div className={ styles.rowHead }>
									<span className={ `${ styles.statusDot } ${ styles[ `dot_${ e.status }` ] }` } />
									<span className={ styles.rowLabel }>{ e.label }</span>
									<span className={ styles.rowType }>{ e.type }</span>
								</div>
								<div className={ styles.rowMeta }>
									{ e.startAt && (
										<span>
											{ __( 'From', 'biolink-pro' ) }{ ' ' }
											<strong>{ fmt( e.startAt ) }</strong>
										</span>
									) }
									{ e.endAt && (
										<span>
											{ __( 'Until', 'biolink-pro' ) }{ ' ' }
											<strong>{ fmt( e.endAt ) }</strong>
										</span>
									) }
									<span className={ styles.statusText }>{ describeStatus( e.status ) }</span>
								</div>
							</li>
						) ) }
					</ul>
				) }

				{ entries.length > 0 && (
					<footer className={ styles.footer }>
						{ sprintf(
							/* translators: %d: count */
							__( '%d scheduled block(s) on this page.', 'biolink-pro' ),
							entries.length
						) }
					</footer>
				) }
			</aside>
		</div>
	);
}

function labelFor( block: BioBlock ): string {
	const meta = findBlockMeta( block.type );
	const data = block.data as Record< string, unknown >;
	return (
		( data.label as string ) ||
		( data.heading as string ) ||
		( data.name as string ) ||
		( meta?.label ?? block.type )
	);
}

function describeStatus( s: Entry[ 'status' ] ): string {
	switch ( s ) {
		case 'upcoming':
			return __( '⏳ upcoming', 'biolink-pro' );
		case 'active':
			return __( '✅ active now', 'biolink-pro' );
		case 'expired':
			return __( '⌛ expired', 'biolink-pro' );
		default:
			return __( '👁 open-ended', 'biolink-pro' );
	}
}

function fmt( iso: string ): string {
	if ( ! iso ) return '';
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
