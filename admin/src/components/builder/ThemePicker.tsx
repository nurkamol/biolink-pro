import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import { ThemesApi, type BioPageSettings, type ThemePreset } from '../../api/client';
import { AiSuggestButton } from '../ai/AiSuggestButton';
import styles from './SettingsPanel.module.css';

interface Props {
	value: string;
	settings: BioPageSettings;
	onChange: ( slug: string ) => void;
	onSettingsChange: ( next: BioPageSettings ) => void;
	pageSummary?: string;
}

export function ThemePicker( { value, settings, onChange, onSettingsChange, pageSummary = '' }: Props ) {
	const [ themes, setThemes ] = useState< ThemePreset[] >( [] );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		ThemesApi.list()
			.then( setThemes )
			.catch( () => setThemes( [] ) )
			.finally( () => setLoading( false ) );
	}, [] );

	const themeSlugs = themes.map( ( t ) => t.slug );

	// Parse "<slug>: <reason>" lines from the AI; fall back to whole line.
	const parseTheme = ( raw: string ): { label: string; value: string } => {
		const m = raw.match( /^\s*([a-z_]+)\s*[:\-]\s*(.+)$/i );
		if ( m && themeSlugs.includes( m[ 1 ].toLowerCase() ) ) {
			return { label: `${ m[ 1 ].toLowerCase() } — ${ m[ 2 ] }`, value: m[ 1 ].toLowerCase() };
		}
		for ( const slug of themeSlugs ) {
			if ( raw.toLowerCase().includes( slug ) ) {
				return { label: raw, value: slug };
			}
		}
		return { label: raw, value: themeSlugs[ 0 ] ?? 'mono' };
	};

	const currentPreset = themes.find( ( t ) => t.slug === value );

	const accent = settings.accent_color || currentPreset?.tokens.accent || '#000000';
	const accentText = settings.accent_text_color || currentPreset?.tokens.accentText || '#ffffff';
	const shape = settings.button_shape || currentPreset?.tokens.buttonShape || 'rounded';
	const style = settings.button_style || currentPreset?.tokens.buttonStyle || 'filled';

	const hasOverride =
		!! settings.accent_color ||
		!! settings.accent_text_color ||
		!! settings.button_shape ||
		!! settings.button_style;

	const resetOverrides = () => {
		onSettingsChange( {
			...settings,
			accent_color: '',
			accent_text_color: '',
			button_shape: '',
			button_style: '',
		} );
	};

	return (
		<div className={ styles.section }>
			<h3 className={ styles.sectionTitle }>{ __( 'Theme', 'biolink-pro' ) }</h3>
			<AiSuggestButton
				kind="theme"
				prompt={ pageSummary || __( 'Pick a theme for a personal bio page', 'biolink-pro' ) }
				onPick={ ( slug ) => onChange( slug ) }
				parseSuggestion={ parseTheme }
			/>
			{ loading ? (
				<p className={ styles.hint }>{ __( 'Loading themes…', 'biolink-pro' ) }</p>
			) : (
				<div className={ styles.themeGrid }>
					{ themes.map( ( t ) => (
						<button
							type="button"
							key={ t.slug }
							className={ `${ styles.themeTile } ${
								t.slug === value ? styles.themeTileSelected : ''
							}` }
							onClick={ () => onChange( t.slug ) }
							title={ t.description }
						>
							<span
								className={ styles.themeSwatch }
								style={ { background: t.swatch } }
							/>
							<span className={ styles.themeLabel }>{ t.label }</span>
						</button>
					) ) }
				</div>
			) }

			<div className={ styles.customizeBlock }>
				<h4 className={ styles.sectionTitle }>{ __( 'Customize', 'biolink-pro' ) }</h4>

				<div className={ styles.row }>
					<label className={ styles.field }>
						<span className={ styles.label }>{ __( 'Accent', 'biolink-pro' ) }</span>
						<input
							type="color"
							className={ styles.colorInput }
							value={ accent }
							onChange={ ( e ) =>
								onSettingsChange( { ...settings, accent_color: e.target.value } )
							}
						/>
					</label>
					<label className={ styles.field }>
						<span className={ styles.label }>{ __( 'On accent', 'biolink-pro' ) }</span>
						<input
							type="color"
							className={ styles.colorInput }
							value={ accentText }
							onChange={ ( e ) =>
								onSettingsChange( { ...settings, accent_text_color: e.target.value } )
							}
						/>
					</label>
				</div>

				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Button shape', 'biolink-pro' ) }</span>
					<div className={ styles.segmentedRow } style={ { gridTemplateColumns: '1fr 1fr 1fr' } }>
						{ ( [ 'pill', 'rounded', 'square' ] as const ).map( ( s ) => (
							<button
								type="button"
								key={ s }
								className={ `${ styles.segmented } ${ shape === s ? styles.segmentedActive : '' }` }
								onClick={ () => onSettingsChange( { ...settings, button_shape: s } ) }
							>
								{ s }
							</button>
						) ) }
					</div>
				</label>

				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Button style', 'biolink-pro' ) }</span>
					<div className={ styles.segmentedRow } style={ { gridTemplateColumns: '1fr 1fr 1fr' } }>
						{ ( [ 'filled', 'outline', 'glass' ] as const ).map( ( s ) => (
							<button
								type="button"
								key={ s }
								className={ `${ styles.segmented } ${ style === s ? styles.segmentedActive : '' }` }
								onClick={ () => onSettingsChange( { ...settings, button_style: s } ) }
							>
								{ s }
							</button>
						) ) }
					</div>
				</label>

				{ hasOverride && (
					<button type="button" className={ styles.resetButton } onClick={ resetOverrides }>
						{ __( 'Reset to theme defaults', 'biolink-pro' ) }
					</button>
				) }
			</div>
		</div>
	);
}
