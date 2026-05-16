import { __ } from '@wordpress/i18n';
import type { BioPageSettings } from '../../api/client';
import { pickMedia } from '../../lib/mediaFrame';
import styles from './SettingsPanel.module.css';

interface Props {
	settings: BioPageSettings;
	onChange: ( next: BioPageSettings ) => void;
}

const TYPES = [
	{ id: 'theme', label: __( 'Use theme default', 'biolink-pro' ) },
	{ id: 'color', label: __( 'Solid color', 'biolink-pro' ) },
	{ id: 'gradient', label: __( 'Gradient', 'biolink-pro' ) },
	{ id: 'image', label: __( 'Image', 'biolink-pro' ) },
] as const;

export function BackgroundEditor( { settings, onChange }: Props ) {
	const type = settings.bg_type ?? 'theme';

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
			}
		} catch {
			// cancelled
		}
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
					<button type="button" className={ styles.btn } onClick={ pickBgImage }>
						{ settings.bg_image_id
							? __( 'Change background image', 'biolink-pro' )
							: __( 'Select background image', 'biolink-pro' ) }
					</button>
					{ ( settings.bg_image_id ?? 0 ) > 0 && (
						<label className={ styles.field }>
							<span className={ styles.label }>
								{ __( 'Overlay', 'biolink-pro' ) }: { settings.bg_overlay ?? 0 }%
							</span>
							<input
								type="range"
								min={ 0 }
								max={ 80 }
								value={ settings.bg_overlay ?? 0 }
								onChange={ ( e ) =>
									onChange( { ...settings, bg_overlay: Number( e.target.value ) } )
								}
							/>
						</label>
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
