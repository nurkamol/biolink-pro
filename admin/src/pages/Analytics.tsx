import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useMemo, useState } from 'react';
import {
	AnalyticsApi,
	PagesApi,
	type AnalyticsBucket,
	type AnalyticsLink,
	type AnalyticsPoint,
	type AnalyticsSummary,
	type BioPage,
} from '../api/client';
import styles from './Analytics.module.css';

const RANGES = [
	{ id: '7d', label: __( 'Last 7 days', 'biolink-pro' ), days: 7 },
	{ id: '30d', label: __( 'Last 30 days', 'biolink-pro' ), days: 30 },
	{ id: '90d', label: __( 'Last 90 days', 'biolink-pro' ), days: 90 },
	{ id: '365d', label: __( 'Last year', 'biolink-pro' ), days: 365 },
] as const;

function isoDate( d: Date ): string {
	return d.toISOString().slice( 0, 10 );
}

export function Analytics() {
	const [ pages, setPages ] = useState< BioPage[] >( [] );
	const [ pageId, setPageId ] = useState< number | null >( null );
	const [ rangeId, setRangeId ] = useState< ( typeof RANGES )[ number ][ 'id' ] >( '30d' );
	const [ summary, setSummary ] = useState< AnalyticsSummary | null >( null );
	const [ series, setSeries ] = useState< AnalyticsPoint[] >( [] );
	const [ links, setLinks ] = useState< AnalyticsLink[] >( [] );
	const [ devices, setDevices ] = useState< AnalyticsBucket[] >( [] );
	const [ geo, setGeo ] = useState< AnalyticsBucket[] >( [] );
	const [ referrers, setReferrers ] = useState< AnalyticsBucket[] >( [] );
	const [ loading, setLoading ] = useState( false );

	useEffect( () => {
		void PagesApi.list( { perPage: 100 } ).then( ( ps ) => {
			setPages( ps );
			if ( ps.length > 0 && pageId === null ) setPageId( ps[ 0 ].id );
		} );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	const range = useMemo( () => {
		const r = RANGES.find( ( x ) => x.id === rangeId ) ?? RANGES[ 1 ];
		const to = new Date();
		const from = new Date( Date.now() - r.days * 86_400_000 );
		return { from: isoDate( from ), to: isoDate( to ) };
	}, [ rangeId ] );

	const load = useCallback( async () => {
		if ( ! pageId ) return;
		setLoading( true );
		try {
			const [ s, t, l, d, g, r ] = await Promise.all( [
				AnalyticsApi.summary( pageId, range ),
				AnalyticsApi.timeseries( pageId, range ),
				AnalyticsApi.links( pageId, range ),
				AnalyticsApi.devices( pageId, range ),
				AnalyticsApi.geo( pageId, range ),
				AnalyticsApi.referrers( pageId, range ),
			] );
			setSummary( s );
			setSeries( t );
			setLinks( l );
			setDevices( d );
			setGeo( g );
			setReferrers( r );
		} finally {
			setLoading( false );
		}
	}, [ pageId, range ] );

	useEffect( () => {
		void load();
	}, [ load ] );

	const exportUrl = pageId ? AnalyticsApi.exportCsvUrl( pageId, range ) : '';

	return (
		<section className={ styles.root }>
			<header className={ styles.header }>
				<div>
					<h1>{ __( 'Analytics', 'biolink-pro' ) }</h1>
					<p className={ styles.subtitle }>
						{ __( 'Views, clicks, top links, devices, and referrers per page.', 'biolink-pro' ) }
					</p>
				</div>
				<div className={ styles.controls }>
					<select
						className={ styles.select }
						value={ pageId ?? '' }
						onChange={ ( e ) => setPageId( Number( e.target.value ) ) }
					>
						{ pages.map( ( p ) => (
							<option key={ p.id } value={ p.id }>
								{ p.title || `Page #${ p.id }` }
							</option>
						) ) }
					</select>
					<div className={ styles.rangeBar }>
						{ RANGES.map( ( r ) => (
							<button
								type="button"
								key={ r.id }
								className={ `${ styles.rangeBtn } ${
									rangeId === r.id ? styles.rangeBtnActive : ''
								}` }
								onClick={ () => setRangeId( r.id ) }
							>
								{ r.label }
							</button>
						) ) }
					</div>
					{ pageId && (
						<a className={ styles.exportBtn } href={ exportUrl } download>
							{ __( 'Export CSV', 'biolink-pro' ) }
						</a>
					) }
				</div>
			</header>

			{ ! pageId ? (
				<p className={ styles.empty }>
					{ __( 'Create a bio page first to see analytics.', 'biolink-pro' ) }
				</p>
			) : (
				<>
					<div className={ styles.cards }>
						<MetricCard
							label={ __( 'Views', 'biolink-pro' ) }
							value={ summary?.views ?? 0 }
							loading={ loading }
						/>
						<MetricCard
							label={ __( 'Clicks', 'biolink-pro' ) }
							value={ summary?.clicks ?? 0 }
							loading={ loading }
						/>
						<MetricCard
							label={ __( 'Unique visitors', 'biolink-pro' ) }
							value={ summary?.unique_visitors ?? 0 }
							loading={ loading }
						/>
						<MetricCard
							label={ __( 'Click-through rate', 'biolink-pro' ) }
							value={ summary ? `${ ( summary.ctr * 100 ).toFixed( 1 ) }%` : '—' }
							loading={ loading }
						/>
					</div>

					<div className={ styles.chartCard }>
						<h2>{ __( 'Activity', 'biolink-pro' ) }</h2>
						<SparkChart data={ series } />
					</div>

					<div className={ styles.twoCol }>
						<div className={ styles.tableCard }>
							<h2>{ __( 'Top links', 'biolink-pro' ) }</h2>
							{ links.length === 0 ? (
								<p className={ styles.empty }>{ __( 'No clicks yet.', 'biolink-pro' ) }</p>
							) : (
								<table className={ styles.table }>
									<thead>
										<tr>
											<th>{ __( 'Label', 'biolink-pro' ) }</th>
											<th>{ __( 'URL', 'biolink-pro' ) }</th>
											<th className={ styles.numCol }>
												{ __( 'Clicks', 'biolink-pro' ) }
											</th>
										</tr>
									</thead>
									<tbody>
										{ links.map( ( l ) => (
											<tr key={ l.link_id }>
												<td>{ l.label }</td>
												<td className={ styles.mono }>{ l.url }</td>
												<td className={ styles.numCol }>{ l.clicks }</td>
											</tr>
										) ) }
									</tbody>
								</table>
							) }
						</div>
						<BucketCard label={ __( 'Devices', 'biolink-pro' ) } items={ devices } />
					</div>
					<div className={ styles.twoCol }>
						<BucketCard label={ __( 'Countries', 'biolink-pro' ) } items={ geo } />
						<BucketCard label={ __( 'Referrers', 'biolink-pro' ) } items={ referrers } />
					</div>
				</>
			) }
		</section>
	);
}

function MetricCard( { label, value, loading }: { label: string; value: number | string; loading: boolean } ) {
	return (
		<div className={ styles.card }>
			<div className={ styles.cardLabel }>{ label }</div>
			<div className={ styles.cardValue }>{ loading ? '…' : value }</div>
		</div>
	);
}

function BucketCard( { label, items }: { label: string; items: AnalyticsBucket[] } ) {
	const total = items.reduce( ( s, x ) => s + x.count, 0 ) || 1;
	return (
		<div className={ styles.tableCard }>
			<h2>{ label }</h2>
			{ items.length === 0 ? (
				<p className={ styles.empty }>{ __( 'No data yet.', 'biolink-pro' ) }</p>
			) : (
				<ul className={ styles.bucketList }>
					{ items.map( ( b ) => (
						<li key={ b.bucket } className={ styles.bucketRow }>
							<span className={ styles.bucketName }>{ b.bucket }</span>
							<span className={ styles.bucketBar }>
								<span
									className={ styles.bucketBarFill }
									style={ { width: `${ ( b.count / total ) * 100 }%` } }
								/>
							</span>
							<span className={ styles.bucketCount }>{ b.count }</span>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}

function SparkChart( { data }: { data: AnalyticsPoint[] } ) {
	if ( data.length === 0 ) {
		return (
			<p className={ styles.empty }>{ __( 'No activity yet.', 'biolink-pro' ) }</p>
		);
	}
	const w = 720;
	const h = 220;
	const pad = { l: 40, r: 12, t: 12, b: 28 };
	const innerW = w - pad.l - pad.r;
	const innerH = h - pad.t - pad.b;
	const max = Math.max( 1, ...data.flatMap( ( d ) => [ d.views, d.clicks ] ) );
	const xStep = innerW / Math.max( 1, data.length - 1 );

	const path = ( accessor: 'views' | 'clicks' ): string => {
		return data
			.map( ( d, i ) => {
				const x = pad.l + i * xStep;
				const y = pad.t + innerH - ( d[ accessor ] / max ) * innerH;
				return `${ i === 0 ? 'M' : 'L' }${ x.toFixed( 1 ) },${ y.toFixed( 1 ) }`;
			} )
			.join( ' ' );
	};

	const labelEvery = Math.max( 1, Math.floor( data.length / 6 ) );

	return (
		<svg className={ styles.chart } viewBox={ `0 0 ${ w } ${ h }` } role="img" aria-label="Activity chart">
			<g>
				{ [ 0, 0.25, 0.5, 0.75, 1 ].map( ( t ) => {
					const y = pad.t + innerH - t * innerH;
					return (
						<g key={ t }>
							<line
								x1={ pad.l }
								x2={ pad.l + innerW }
								y1={ y }
								y2={ y }
								className={ styles.gridLine }
							/>
							<text x={ pad.l - 6 } y={ y + 4 } textAnchor="end" className={ styles.axisText }>
								{ Math.round( t * max ) }
							</text>
						</g>
					);
				} ) }
				{ data.map( ( d, i ) =>
					i % labelEvery === 0 ? (
						<text
							key={ d.date }
							x={ pad.l + i * xStep }
							y={ h - 8 }
							textAnchor="middle"
							className={ styles.axisText }
						>
							{ d.date.slice( 5 ) }
						</text>
					) : null
				) }
			</g>
			<path d={ path( 'views' ) } fill="none" stroke="#2271b1" strokeWidth="2" />
			<path d={ path( 'clicks' ) } fill="none" stroke="#7c3aed" strokeWidth="2" />
			<g className={ styles.chartLegend } transform={ `translate(${ pad.l + 8 } ${ pad.t + 16 })` }>
				<g transform="translate(0 0)">
					<rect width="10" height="10" fill="#2271b1" />
					<text x="14" y="9" className={ styles.legendText }>
						{ __( 'Views', 'biolink-pro' ) }
					</text>
				</g>
				<g transform="translate(70 0)">
					<rect width="10" height="10" fill="#7c3aed" />
					<text x="14" y="9" className={ styles.legendText }>
						{ __( 'Clicks', 'biolink-pro' ) }
					</text>
				</g>
			</g>
		</svg>
	);
}
