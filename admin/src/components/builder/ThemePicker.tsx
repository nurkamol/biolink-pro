import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import { ThemesApi, type ThemePreset } from '../../api/client';
import styles from './SettingsPanel.module.css';

interface Props {
	value: string;
	onChange: ( slug: string ) => void;
}

export function ThemePicker( { value, onChange }: Props ) {
	const [ themes, setThemes ] = useState< ThemePreset[] >( [] );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		ThemesApi.list()
			.then( setThemes )
			.catch( () => setThemes( [] ) )
			.finally( () => setLoading( false ) );
	}, [] );

	return (
		<div className={ styles.section }>
			<h3 className={ styles.sectionTitle }>{ __( 'Theme', 'biolink-pro' ) }</h3>
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
		</div>
	);
}
