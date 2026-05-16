import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { pickMedia } from '../../lib/mediaFrame';
import styles from './SettingsPanel.module.css';

export interface BioPageSeo {
	custom_title?: string;
	custom_description?: string;
	og_image_id?: number;
	no_index?: boolean;
	twitter_site?: string;
	[ k: string ]: unknown;
}

interface Props {
	seo: BioPageSeo;
	onChange: ( next: BioPageSeo ) => void;
}

export function SeoEditor( { seo, onChange }: Props ) {
	const ogId = seo.og_image_id ?? 0;
	const [ ogThumb, setOgThumb ] = useState< string | null >( null );

	const loadThumb = useCallback( async () => {
		if ( ! ogId ) {
			setOgThumb( null );
			return;
		}
		try {
			const res = await fetch(
				`/wp-json/wp/v2/media/${ ogId }?_fields=source_url,media_details`,
				{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
			);
			if ( ! res.ok ) return;
			const json = ( await res.json() ) as {
				source_url: string;
				media_details?: { sizes?: { medium?: { source_url?: string } } };
			};
			setOgThumb( json.media_details?.sizes?.medium?.source_url ?? json.source_url );
		} catch {
			// non-fatal
		}
	}, [ ogId ] );

	useEffect( () => {
		void loadThumb();
	}, [ loadThumb ] );

	const pickOg = async () => {
		try {
			const picked = await pickMedia( {
				title: __( 'Select share image', 'biolink-pro' ),
				buttonText: __( 'Use as share image', 'biolink-pro' ),
				multiple: false,
				type: 'image',
			} );
			if ( picked[ 0 ] ) {
				onChange( { ...seo, og_image_id: picked[ 0 ].id } );
				setOgThumb( picked[ 0 ].url );
			}
		} catch {
			// cancelled
		}
	};

	const removeOg = () => {
		onChange( { ...seo, og_image_id: 0 } );
		setOgThumb( null );
	};

	return (
		<div className={ styles.section }>
			<h3 className={ styles.sectionTitle }>{ __( 'SEO', 'biolink-pro' ) }</h3>

			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Title override', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ seo.custom_title ?? '' }
					placeholder={ __( 'Defaults to the page headline', 'biolink-pro' ) }
					onChange={ ( e ) => onChange( { ...seo, custom_title: e.target.value } ) }
				/>
			</label>

			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Meta description', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ seo.custom_description ?? '' }
					placeholder={ __( 'Defaults to the subtitle', 'biolink-pro' ) }
					maxLength={ 200 }
					onChange={ ( e ) => onChange( { ...seo, custom_description: e.target.value } ) }
				/>
				<span className={ styles.hint }>
					{ ( seo.custom_description ?? '' ).length } / 200
				</span>
			</label>

			<div className={ styles.field }>
				<span className={ styles.label }>{ __( 'Share image (og:image)', 'biolink-pro' ) }</span>
				{ ogThumb && (
					<div className={ styles.bgThumbWrap }>
						<img src={ ogThumb } alt="" className={ styles.bgThumb } />
						<button
							type="button"
							className={ styles.bgThumbRemove }
							onClick={ removeOg }
							aria-label={ __( 'Remove share image', 'biolink-pro' ) }
						>
							×
						</button>
					</div>
				) }
				<button type="button" className={ styles.btn } onClick={ pickOg }>
					{ ogId
						? __( 'Change share image', 'biolink-pro' )
						: __( 'Select share image', 'biolink-pro' ) }
				</button>
				<span className={ styles.hint }>
					{ __( 'Falls back to the avatar if not set.', 'biolink-pro' ) }
				</span>
			</div>

			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Twitter @ handle', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ seo.twitter_site ?? '' }
					placeholder="@yourbrand"
					onChange={ ( e ) => onChange( { ...seo, twitter_site: e.target.value } ) }
				/>
			</label>

			<label className={ styles.checkbox }>
				<input
					type="checkbox"
					checked={ !! seo.no_index }
					onChange={ ( e ) => onChange( { ...seo, no_index: e.target.checked } ) }
				/>
				{ __( 'Hide from search engines (noindex,nofollow)', 'biolink-pro' ) }
			</label>
		</div>
	);
}
