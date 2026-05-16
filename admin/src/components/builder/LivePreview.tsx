import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from 'react';
import styles from './LivePreview.module.css';

interface Props {
	url: string;
	/**
	 * Bumps whenever something the iframe should reflect changes — usually a
	 * monotonic save timestamp from the parent. Reload is debounced to avoid
	 * spamming the iframe while the user is typing.
	 */
	refreshKey: number;
}

export function LivePreview( { url, refreshKey }: Props ) {
	const iframeRef = useRef< HTMLIFrameElement | null >( null );
	const [ loading, setLoading ] = useState( true );
	const debounceRef = useRef< number | null >( null );

	useEffect( () => {
		if ( debounceRef.current ) {
			window.clearTimeout( debounceRef.current );
		}
		debounceRef.current = window.setTimeout( () => {
			if ( iframeRef.current ) {
				setLoading( true );
				// Add a cache-buster query param to force the reload past any
				// browser HTTP cache or service worker.
				const u = new URL( url, window.location.origin );
				u.searchParams.set( '_blpreview', String( refreshKey ) );
				iframeRef.current.src = u.toString();
			}
		}, 350 );
		return () => {
			if ( debounceRef.current ) window.clearTimeout( debounceRef.current );
		};
	}, [ refreshKey, url ] );

	return (
		<div className={ styles.preview }>
			<div className={ styles.header }>
				<span className={ styles.headerDot } />
				<span className={ styles.headerDot } />
				<span className={ styles.headerDot } />
				<span className={ styles.headerLabel }>{ __( 'Live preview', 'biolink-pro' ) }</span>
				<a
					className={ styles.openExternal }
					href={ url }
					target="_blank"
					rel="noreferrer"
					title={ __( 'Open in new tab', 'biolink-pro' ) }
				>
					↗
				</a>
			</div>
			<div className={ styles.phone }>
				{ loading && <div className={ styles.spinner }>{ __( 'Loading…', 'biolink-pro' ) }</div> }
				<iframe
					ref={ iframeRef }
					title={ __( 'Bio page preview', 'biolink-pro' ) }
					className={ styles.iframe }
					src={ url }
					onLoad={ () => setLoading( false ) }
				/>
			</div>
		</div>
	);
}
