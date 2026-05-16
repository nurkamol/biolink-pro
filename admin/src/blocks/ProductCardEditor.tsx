import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { pickMedia } from '../lib/mediaFrame';
import styles from './shared.module.css';

export interface ProductCardData {
	image_id?: number;
	name?: string;
	description?: string;
	price?: string;
	cta_label?: string;
	cta_url?: string;
}

interface Props {
	data: ProductCardData;
	onChange: ( next: ProductCardData ) => void;
}

export function ProductCardEditor( { data, onChange }: Props ) {
	const [ thumb, setThumb ] = useState< string | null >( null );
	const id = data.image_id ?? 0;

	const loadThumb = useCallback( async () => {
		if ( ! id ) {
			setThumb( null );
			return;
		}
		try {
			const res = await fetch(
				`/wp-json/wp/v2/media/${ id }?_fields=source_url,media_details`,
				{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
			);
			if ( ! res.ok ) return;
			const json = ( await res.json() ) as {
				source_url: string;
				media_details?: { sizes?: { thumbnail?: { source_url?: string } } };
			};
			setThumb( json.media_details?.sizes?.thumbnail?.source_url ?? json.source_url );
		} catch {
			// non-fatal
		}
	}, [ id ] );

	useEffect( () => {
		void loadThumb();
	}, [ loadThumb ] );

	const pick = async () => {
		try {
			const picked = await pickMedia( {
				title: __( 'Select product image', 'biolink-pro' ),
				buttonText: __( 'Use image', 'biolink-pro' ),
				multiple: false,
				type: 'image',
			} );
			if ( picked[ 0 ] ) {
				onChange( { ...data, image_id: picked[ 0 ].id } );
				setThumb( picked[ 0 ].url );
			}
		} catch {
			// cancelled
		}
	};

	return (
		<>
			<div style={ { display: 'flex', gap: '8px', alignItems: 'center', marginBottom: '8px' } }>
				{ thumb && (
					<img
						src={ thumb }
						alt=""
						style={ { width: 48, height: 48, borderRadius: 6, objectFit: 'cover' } }
					/>
				) }
				<button type="button" className={ styles.btn } onClick={ pick }>
					{ id ? __( 'Change image', 'biolink-pro' ) : __( 'Choose image', 'biolink-pro' ) }
				</button>
			</div>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Name', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.name ?? '' }
					onChange={ ( e ) => onChange( { ...data, name: e.target.value } ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Description (optional)', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.description ?? '' }
					onChange={ ( e ) => onChange( { ...data, description: e.target.value } ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Price', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.price ?? '' }
					onChange={ ( e ) => onChange( { ...data, price: e.target.value } ) }
					placeholder="$19"
				/>
			</label>
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Button text', 'biolink-pro' ) }</span>
					<input
						type="text"
						className={ styles.input }
						value={ data.cta_label ?? '' }
						onChange={ ( e ) => onChange( { ...data, cta_label: e.target.value } ) }
					/>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Button URL', 'biolink-pro' ) }</span>
					<input
						type="url"
						className={ styles.input }
						value={ data.cta_url ?? '' }
						onChange={ ( e ) => onChange( { ...data, cta_url: e.target.value } ) }
						placeholder="https://"
					/>
				</label>
			</div>
		</>
	);
}
