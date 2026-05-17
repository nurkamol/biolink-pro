import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import type { BioPageSettings } from '../../api/client';
import { pickMedia } from '../../lib/mediaFrame';
import styles from './SettingsPanel.module.css';

interface Props {
	settings: BioPageSettings;
	onChange: ( next: BioPageSettings ) => void;
}

const TYPES = [
	{ id: 'theme', label: __( 'Theme default', 'biolink-pro' ) },
	{ id: 'color', label: __( 'Solid color', 'biolink-pro' ) },
	{ id: 'gradient', label: __( 'Gradient', 'biolink-pro' ) },
	{ id: 'image', label: __( 'Image', 'biolink-pro' ) },
] as const;

export function BackgroundEditor( { settings, onChange }: Props ) {
	const type = settings.bg_type ?? 'theme';
	const bgImageId = settings.bg_image_id ?? 0;
	const [ bgThumb, setBgThumb ] = useState< string | null >( null );

	const loadThumb = useCallback( async () => {
		if ( ! bgImageId ) {
			setBgThumb( null );
			return;
		}
		try {
			const res = await fetch(
				`/wp-json/wp/v2/media/${ bgImageId }?_fields=source_url,media_details`,
				{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
			);
			if ( ! res.ok ) return;
			const json = ( await res.json() ) as {
				source_url: string;
				media_details?: { sizes?: { medium?: { source_url?: string } } };
			};
			setBgThumb( json.media_details?.sizes?.medium?.source_url ?? json.source_url );
		} catch {
			// non-fatal
		}
	}, [ bgImageId ] );

	useEffect( () => {
		void loadThumb();
	}, [ loadThumb ] );

	const pickBgImage = async () => {
		try {
			const picked = await pickMedia( {
				title: __( 'Select background image', 'biolink-pro' ),
				buttonText: __( 'Use as background', 'biolink-pro' ),
				multiple: false,
				type: 'image',
			} );
			if ( picked[ 0 ] ) {
				onChange( { ...settings, bg_image_id: picked[ 0 ].id } );
				setBgThumb( picked[ 0 ].url );
			}
		} catch {
			// cancelled
		}
	};

	const removeBgImage = () => {
		onChange( { ...settings, bg_image_id: 0 } );
		setBgThumb( null );
	};

	return (
		<div className={ styles.section }>
			<h3 className={ styles.sectionTitle }>{ __( 'Background', 'biolink-pro' ) }</h3>

			<div className={ styles.segmentedRow }>
				{ TYPES.map( ( t ) => (
					<button
						type="button"
						key={ t.id }
						className={ `${ styles.segmented } ${ type === t.id ? styles.segmentedActive : '' }` }
						onClick={ () => onChange( { ...settings, bg_type: t.id } ) }
					>
						{ t.label }
					</button>
				) ) }
			</div>

			{ type === 'color' && (
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Color', 'biolink-pro' ) }</span>
					<input
						type="color"
						className={ styles.colorInput }
						value={ settings.bg_color || '#fafafa' }
						onChange={ ( e ) => onChange( { ...settings, bg_color: e.target.value } ) }
					/>
				</label>
			) }

			{ type === 'gradient' && (
				<>
					<div className={ styles.row }>
						<label className={ styles.field }>
							<span className={ styles.label }>{ __( 'From', 'biolink-pro' ) }</span>
							<input
								type="color"
								className={ styles.colorInput }
								value={ settings.bg_gradient_from || '#ff7e5f' }
								onChange={ ( e ) =>
									onChange( { ...settings, bg_gradient_from: e.target.value } )
								}
							/>
						</label>
						<label className={ styles.field }>
							<span className={ styles.label }>{ __( 'To', 'biolink-pro' ) }</span>
							<input
								type="color"
								className={ styles.colorInput }
								value={ settings.bg_gradient_to || '#feb47b' }
								onChange={ ( e ) =>
									onChange( { ...settings, bg_gradient_to: e.target.value } )
								}
							/>
						</label>
					</div>
					<label className={ styles.field }>
						<span className={ styles.label }>
							{ __( 'Angle', 'biolink-pro' ) }: { settings.bg_gradient_angle ?? 135 }°
						</span>
						<input
							type="range"
							min={ 0 }
							max={ 360 }
							value={ settings.bg_gradient_angle ?? 135 }
							onChange={ ( e ) =>
								onChange( { ...settings, bg_gradient_angle: Number( e.target.value ) } )
							}
						/>
					</label>
				</>
			) }

			{ type === 'image' && (
				<>
					{ bgThumb && (
						<div className={ styles.bgThumbWrap }>
							<img src={ bgThumb } alt="" className={ styles.bgThumb } />
							<button
								type="button"
								className={ styles.bgThumbRemove }
								onClick={ removeBgImage }
								aria-label={ __( 'Remove background image', 'biolink-pro' ) }
							>
								×
							</button>
						</div>
					) }
					<button type="button" className={ styles.btn } onClick={ pickBgImage }>
						{ bgImageId
							? __( 'Change image', 'biolink-pro' )
							: __( 'Select background image', 'biolink-pro' ) }
					</button>
					{ bgImageId > 0 && (
						<>
							<label className={ styles.field } style={ { marginTop: '12px' } }>
								<span className={ styles.label }>
									{ __( 'Position', 'biolink-pro' ) }
								</span>
								<select
									className={ styles.select }
									value={ settings.bg_position ?? 'cover-center' }
									onChange={ ( e ) =>
										onChange( {
											...settings,
											bg_position: e.target
												.value as NonNullable< BioPageSettings[ 'bg_position' ] >,
										} )
									}
								>
									<option value="cover-center">
										{ __( 'Cover (center)', 'biolink-pro' ) }
									</option>
									<option value="cover-top">
										{ __( 'Cover (top)', 'biolink-pro' ) }
									</option>
									<option value="cover-bottom">
										{ __( 'Cover (bottom)', 'biolink-pro' ) }
									</option>
									<option value="contain">
										{ __( 'Contain (fit, no crop)', 'biolink-pro' ) }
									</option>
									<option value="tile">{ __( 'Tile / repeat', 'biolink-pro' ) }</option>
								</select>
							</label>
							<label className={ styles.field }>
								<span className={ styles.label }>
									{ __( 'Overlay', 'biolink-pro' ) }: { settings.bg_overlay ?? 0 }%
								</span>
								<input
									type="range"
									min={ 0 }
									max={ 90 }
									value={ settings.bg_overlay ?? 0 }
									onChange={ ( e ) =>
										onChange( {
											...settings,
											bg_overlay: Number( e.target.value ),
										} )
									}
								/>
							</label>
							<label className={ styles.field }>
								<span className={ styles.label }>
									{ __( 'Blur', 'biolink-pro' ) }: { settings.bg_blur ?? 0 }px
								</span>
								<input
									type="range"
									min={ 0 }
									max={ 30 }
									value={ settings.bg_blur ?? 0 }
									onChange={ ( e ) =>
										onChange( { ...settings, bg_blur: Number( e.target.value ) } )
									}
								/>
								<span className={ styles.hint }>
									{ __(
										'Softens the image so the content stays readable. Set the content area background below for the strongest effect.',
										'biolink-pro'
									) }
								</span>
							</label>
						</>
					) }
				</>
			) }

			{ type === 'theme' && (
				<p className={ styles.hint }>
					{ __( 'The selected theme controls the background.', 'biolink-pro' ) }
				</p>
			) }
		</div>
	);
}
