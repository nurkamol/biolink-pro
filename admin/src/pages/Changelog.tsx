import { __, sprintf } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { ChangelogApi, type Release, type UpdateStatus } from '../api/client';
import styles from './Changelog.module.css';

export function Changelog() {
	const [ releases, setReleases ] = useState< Release[] >( [] );
	const [ status, setStatus ] = useState< UpdateStatus | null >( null );
	const [ loading, setLoading ] = useState( true );
	const [ refreshing, setRefreshing ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const load = useCallback( async ( force: boolean ) => {
		if ( force ) {
			setRefreshing( true );
		} else {
			setLoading( true );
		}
		setError( null );
		try {
			const [ rs, st ] = await Promise.all( [
				ChangelogApi.list( force ),
				ChangelogApi.status( force ),
			] );
			setReleases( rs );
			setStatus( st );
		} catch ( err ) {
			setError(
				err instanceof Error
					? err.message
					: __( 'Failed to fetch release data.', 'biolink-pro' )
			);
		} finally {
			setLoading( false );
			setRefreshing( false );
		}
	}, [] );

	useEffect( () => {
		void load( false );
	}, [ load ] );

	const updatesUrl = `${ window.BIOLINK_PRO.adminUrl.replace(
		/admin\.php.*/,
		''
	) }update-core.php`;

	return (
		<section className={ styles.root }>
			<header className={ styles.header }>
				<div>
					<h1>{ __( "What's New", 'biolink-pro' ) }</h1>
					<p className={ styles.subtitle }>
						{ sprintf(
							/* translators: %s: version string */
							__( 'Installed version: %s', 'biolink-pro' ),
							status?.current ?? window.BIOLINK_PRO.version
						) }
					</p>
				</div>
				<button
					type="button"
					className={ styles.refresh }
					onClick={ () => load( true ) }
					disabled={ refreshing }
				>
					{ refreshing
						? __( 'Refreshing…', 'biolink-pro' )
						: __( 'Check for updates', 'biolink-pro' ) }
				</button>
			</header>

			{ error && <div className={ styles.error }>{ error }</div> }

			{ status?.update_available && status.latest && (
				<div className={ styles.updateBanner }>
					<div>
						<strong>
							{ sprintf(
								/* translators: %s: version string */
								__( 'Update available: v%s', 'biolink-pro' ),
								status.latest
							) }
						</strong>
						<p className={ styles.updateBannerLede }>
							{ __(
								'Install it from the WordPress Updates screen, or download the zip directly from GitHub.',
								'biolink-pro'
							) }
						</p>
					</div>
					<div className={ styles.updateActions }>
						<a className={ styles.primaryAction } href={ updatesUrl }>
							{ __( 'Go to Updates', 'biolink-pro' ) }
						</a>
						{ status.download_url && (
							<a
								className={ styles.secondaryAction }
								href={ status.download_url }
								target="_blank"
								rel="noreferrer"
							>
								{ __( 'Download zip', 'biolink-pro' ) }
							</a>
						) }
					</div>
				</div>
			) }

			{ loading ? (
				<p className={ styles.empty }>{ __( 'Loading release history…', 'biolink-pro' ) }</p>
			) : releases.length === 0 ? (
				<p className={ styles.empty }>
					{ __(
						'No releases published yet. Once you tag and push a release on GitHub, it will appear here.',
						'biolink-pro'
					) }
				</p>
			) : (
				<ul className={ styles.list }>
					{ releases.map( ( release ) => (
						<li key={ release.tag } className={ styles.item }>
							<header className={ styles.itemHeader }>
								<h2 className={ styles.version }>
									{ release.name || release.tag }
									{ release.is_current && (
										<span className={ styles.currentBadge }>
											{ __( 'Installed', 'biolink-pro' ) }
										</span>
									) }
									{ release.is_newer && (
										<span className={ styles.newerBadge }>
											{ __( 'New', 'biolink-pro' ) }
										</span>
									) }
								</h2>
								<span className={ styles.date }>{ formatDate( release.date ) }</span>
							</header>
							<div
								className={ styles.body }
								// eslint-disable-next-line react/no-danger
								dangerouslySetInnerHTML={ { __html: release.body_html } }
							/>
							<footer className={ styles.itemFooter }>
								<a href={ release.html_url } target="_blank" rel="noreferrer">
									{ __( 'View on GitHub ↗', 'biolink-pro' ) }
								</a>
								{ release.download_url && (
									<a href={ release.download_url } target="_blank" rel="noreferrer">
										{ __( 'Download zip ↗', 'biolink-pro' ) }
									</a>
								) }
							</footer>
						</li>
					) ) }
				</ul>
			) }
		</section>
	);
}

function formatDate( iso: string ): string {
	if ( ! iso ) return '—';
	try {
		return new Date( iso ).toLocaleDateString( undefined, {
			year: 'numeric',
			month: 'short',
			day: 'numeric',
		} );
	} catch {
		return iso;
	}
}
