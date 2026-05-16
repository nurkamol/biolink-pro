import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import styles from './QrDialog.module.css';

interface Props {
	pageId: number;
	pageUrl: string;
	open: boolean;
	onClose: () => void;
}

interface QrResponse {
	url: string;
	mime: string;
}

export function QrDialog( { pageId, pageUrl, open, onClose }: Props ) {
	const [ fg, setFg ] = useState( '#000000' );
	const [ bg, setBg ] = useState( '#FFFFFF' );
	const [ format, setFormat ] = useState< 'png' | 'svg' >( 'png' );
	const [ size, setSize ] = useState( 512 );
	const [ src, setSrc ] = useState< string | null >( null );
	const [ loading, setLoading ] = useState( false );
	const [ error, setError ] = useState< string | null >( null );

	const fetchQr = useCallback( async () => {
		if ( ! open ) return;
		setLoading( true );
		setError( null );
		try {
			const params = new URLSearchParams( {
				format,
				fg,
				bg,
				size: String( size ),
			} );
			const res = await fetch(
				`/wp-json/biolink/v1/pages/${ pageId }/qr?${ params.toString() }`,
				{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
			);
			if ( ! res.ok ) {
				const body = ( await res.json().catch( () => ( {} ) ) ) as { message?: string };
				throw new Error( body.message || `HTTP ${ res.status }` );
			}
			const json = ( await res.json() ) as QrResponse;
			// Cache-bust so changes to fg/bg always re-render.
			setSrc( json.url + `?_=${ Date.now() }` );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to fetch QR code.', 'biolink-pro' ) );
		} finally {
			setLoading( false );
		}
	}, [ open, pageId, format, fg, bg, size ] );

	useEffect( () => {
		void fetchQr();
	}, [ fetchQr ] );

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
		<div className={ styles.overlay } onClick={ onClose } role="dialog" aria-labelledby="biolink-qr-title">
			<div className={ styles.modal } onClick={ ( e ) => e.stopPropagation() }>
				<header className={ styles.header }>
					<h2 id="biolink-qr-title">{ __( 'QR code', 'biolink-pro' ) }</h2>
					<button type="button" className={ styles.close } onClick={ onClose } aria-label={ __( 'Close', 'biolink-pro' ) }>
						×
					</button>
				</header>

				<div className={ styles.body }>
					<div className={ styles.preview }>
						{ loading && <div className={ styles.spinner }>…</div> }
						{ error && <div className={ styles.error }>{ error }</div> }
						{ src && ! error && (
							<img src={ src } alt={ __( 'QR code preview', 'biolink-pro' ) } className={ styles.image } />
						) }
						<p className={ styles.targetUrl }>{ pageUrl }</p>
					</div>

					<div className={ styles.controls }>
						<div className={ styles.row }>
							<label>
								<span>{ __( 'Foreground', 'biolink-pro' ) }</span>
								<input
									type="color"
									value={ fg }
									onChange={ ( e ) => setFg( e.target.value ) }
								/>
							</label>
							<label>
								<span>{ __( 'Background', 'biolink-pro' ) }</span>
								<input
									type="color"
									value={ bg }
									onChange={ ( e ) => setBg( e.target.value ) }
								/>
							</label>
						</div>

						<label className={ styles.field }>
							<span>
								{ __( 'Size', 'biolink-pro' ) }: { size }px
							</span>
							<input
								type="range"
								min={ 256 }
								max={ 1536 }
								step={ 64 }
								value={ size }
								onChange={ ( e ) => setSize( Number( e.target.value ) ) }
							/>
						</label>

						<div className={ styles.row }>
							<label className={ styles.toggle }>
								<input
									type="radio"
									name="format"
									checked={ format === 'png' }
									onChange={ () => setFormat( 'png' ) }
								/>
								PNG
							</label>
							<label className={ styles.toggle }>
								<input
									type="radio"
									name="format"
									checked={ format === 'svg' }
									onChange={ () => setFormat( 'svg' ) }
								/>
								SVG
							</label>
						</div>

						{ src && (
							<a className={ styles.downloadBtn } href={ src.split( '?' )[ 0 ] } download>
								{ __( 'Download', 'biolink-pro' ) }
							</a>
						) }
					</div>
				</div>
			</div>
		</div>
	);
}
