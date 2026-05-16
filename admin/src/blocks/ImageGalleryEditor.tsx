import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { pickMedia, type MediaAttachment } from '../lib/mediaFrame';
import styles from './shared.module.css';

export interface ImageGalleryData {
	ids?: number[];
	layout?: 'grid' | 'list';
	size?: 'medium' | 'large' | 'full';
}

interface Props {
	data: ImageGalleryData;
	onChange: ( next: ImageGalleryData ) => void;
}

interface Thumb {
	id: number;
	url: string;
}

export function ImageGalleryEditor( { data, onChange }: Props ) {
	const ids = data.ids ?? [];
	const [ thumbs, setThumbs ] = useState< Thumb[] >( [] );

	const loadThumbs = useCallback( async () => {
		if ( ids.length === 0 ) {
			setThumbs( [] );
			return;
		}
		try {
			// @wordpress/api-fetch resolves /wp/v2/media without a custom rest base
			const res = await fetch(
				`/wp-json/wp/v2/media?include=${ ids.join( ',' ) }&per_page=${ ids.length }&_fields=id,source_url,media_details`,
				{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
			);
			if ( ! res.ok ) return;
			const json = ( await res.json() ) as Array< {
				id: number;
				source_url: string;
				media_details?: { sizes?: { thumbnail?: { source_url?: string } } };
			} >;
			const byId = new Map<number, Thumb>(
				json.map( ( m ) => [
					m.id,
					{
						id: m.id,
						url: m.media_details?.sizes?.thumbnail?.source_url ?? m.source_url,
					},
				] )
			);
			setThumbs( ids.map( ( id ) => byId.get( id ) ).filter( Boolean ) as Thumb[] );
		} catch {
			// non-fatal
		}
	}, [ ids.join( ',' ) ] ); // eslint-disable-line react-hooks/exhaustive-deps

	useEffect( () => {
		void loadThumbs();
	}, [ loadThumbs ] );

	const handlePick = async () => {
		try {
			const picked = await pickMedia( {
				title: __( 'Select images', 'biolink-pro' ),
				buttonText: __( 'Add to gallery', 'biolink-pro' ),
				multiple: true,
				type: 'image',
			} );
			const newIds = picked.map( ( a: MediaAttachment ) => a.id );
			onChange( { ...data, ids: [ ...ids, ...newIds.filter( ( id ) => ! ids.includes( id ) ) ] } );
		} catch {
			// user cancelled or media frame unavailable
		}
	};

	const removeId = ( id: number ) => {
		onChange( { ...data, ids: ids.filter( ( x ) => x !== id ) } );
	};

	return (
		<>
			{ thumbs.length > 0 && (
				<div className={ styles.mediaGrid }>
					{ thumbs.map( ( t ) => (
						<div className={ styles.mediaThumb } key={ t.id }>
							<img src={ t.url } alt="" />
							<button
								type="button"
								className={ styles.mediaRemove }
								onClick={ () => removeId( t.id ) }
								aria-label={ __( 'Remove image', 'biolink-pro' ) }
							>
								×
							</button>
						</div>
					) ) }
				</div>
			) }
			<button type="button" className={ styles.btn } onClick={ handlePick }>
				{ ids.length === 0
					? __( '+ Select images', 'biolink-pro' )
					: __( '+ Add more images', 'biolink-pro' ) }
			</button>
			<div className={ styles.row } style={ { marginTop: '12px' } }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Layout', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.layout ?? 'grid' }
						onChange={ ( e ) => onChange( { ...data, layout: e.target.value as 'grid' | 'list' } ) }
					>
						<option value="grid">{ __( 'Grid', 'biolink-pro' ) }</option>
						<option value="list">{ __( 'List', 'biolink-pro' ) }</option>
					</select>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Image size', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.size ?? 'medium' }
						onChange={ ( e ) =>
							onChange( { ...data, size: e.target.value as 'medium' | 'large' | 'full' } )
						}
					>
						<option value="medium">{ __( 'Medium', 'biolink-pro' ) }</option>
						<option value="large">{ __( 'Large', 'biolink-pro' ) }</option>
						<option value="full">{ __( 'Full', 'biolink-pro' ) }</option>
					</select>
				</label>
			</div>
		</>
	);
}
