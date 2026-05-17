import { __ } from '@wordpress/i18n';
import type { BioPageSettings } from '../../api/client';
import styles from './SettingsPanel.module.css';

interface Props {
	settings: BioPageSettings;
	onChange: ( next: BioPageSettings ) => void;
}

const MODES = [
	{ id: '', label: __( 'Transparent (default)', 'biolink-pro' ) },
	{ id: 'solid', label: __( 'Solid card', 'biolink-pro' ) },
	{ id: 'glass', label: __( 'Frosted glass', 'biolink-pro' ) },
] as const;

export function ContentAreaEditor( { settings, onChange }: Props ) {
	const mode = ( settings.content_bg_type ?? '' ) as '' | 'solid' | 'glass';
	const hasCard = mode === 'solid' || mode === 'glass';

	return (
		<div className={ styles.section }>
			<h3 className={ styles.sectionTitle }>{ __( 'Content area', 'biolink-pro' ) }</h3>
			<p className={ styles.hint } style={ { marginBottom: 12 } }>
				{ __(
					'Controls the column where your blocks render. On desktop, the wallpaper fills the rest of the viewport behind it.',
					'biolink-pro'
				) }
			</p>

			<div className={ styles.segmentedRow }>
				{ MODES.map( ( m ) => (
					<button
						type="button"
						key={ m.id || 'transparent' }
						className={ `${ styles.segmented } ${ mode === m.id ? styles.segmentedActive : '' }` }
						onClick={ () =>
							onChange( {
								...settings,
								content_bg_type: m.id as BioPageSettings[ 'content_bg_type' ],
							} )
						}
					>
						{ m.label }
					</button>
				) ) }
			</div>

			{ hasCard && (
				<>
					<label className={ styles.field }>
						<span className={ styles.label }>{ __( 'Card color', 'biolink-pro' ) }</span>
						<input
							type="color"
							className={ styles.colorInput }
							value={ settings.content_bg_color || '#ffffff' }
							onChange={ ( e ) =>
								onChange( { ...settings, content_bg_color: e.target.value } )
							}
						/>
					</label>

					<label className={ styles.field }>
						<span className={ styles.label }>
							{ __( 'Opacity', 'biolink-pro' ) }: { settings.content_bg_opacity ?? 90 }%
						</span>
						<input
							type="range"
							min={ 10 }
							max={ 100 }
							value={ settings.content_bg_opacity ?? 90 }
							onChange={ ( e ) =>
								onChange( {
									...settings,
									content_bg_opacity: Number( e.target.value ),
								} )
							}
						/>
					</label>

					{ mode === 'glass' && (
						<label className={ styles.field }>
							<span className={ styles.label }>
								{ __( 'Backdrop blur', 'biolink-pro' ) }:{ ' ' }
								{ settings.content_blur ?? 12 }px
							</span>
							<input
								type="range"
								min={ 0 }
								max={ 30 }
								value={ settings.content_blur ?? 12 }
								onChange={ ( e ) =>
									onChange( { ...settings, content_blur: Number( e.target.value ) } )
								}
							/>
						</label>
					) }
				</>
			) }

			<label className={ styles.field }>
				<span className={ styles.label }>
					{ __( 'Corner radius', 'biolink-pro' ) }: { settings.content_radius ?? 22 }px
				</span>
				<input
					type="range"
					min={ 0 }
					max={ 48 }
					value={ settings.content_radius ?? 22 }
					onChange={ ( e ) =>
						onChange( { ...settings, content_radius: Number( e.target.value ) } )
					}
				/>
			</label>

			<label className={ styles.field }>
				<span className={ styles.label }>
					{ __( 'Max width', 'biolink-pro' ) }: { settings.content_max_width ?? 620 }px
				</span>
				<input
					type="range"
					min={ 380 }
					max={ 960 }
					step={ 20 }
					value={ settings.content_max_width ?? 620 }
					onChange={ ( e ) =>
						onChange( {
							...settings,
							content_max_width: Number( e.target.value ),
						} )
					}
				/>
			</label>
		</div>
	);
}
