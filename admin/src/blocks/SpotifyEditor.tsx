import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface SpotifyData {
	url?: string;
	height?: 'compact' | 'normal' | 'tall';
	theme?: 'default' | 'black';
}

interface Props {
	data: SpotifyData;
	onChange: ( next: SpotifyData ) => void;
}

export function SpotifyEditor( { data, onChange }: Props ) {
	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Spotify URL', 'biolink-pro' ) }</span>
				<input
					type="url"
					className={ styles.input }
					value={ data.url ?? '' }
					onChange={ ( e ) => onChange( { ...data, url: e.target.value } ) }
					placeholder="https://open.spotify.com/track/…"
				/>
			</label>
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Height', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.height ?? 'normal' }
						onChange={ ( e ) =>
							onChange( { ...data, height: e.target.value as SpotifyData[ 'height' ] } )
						}
					>
						<option value="compact">{ __( 'Compact', 'biolink-pro' ) }</option>
						<option value="normal">{ __( 'Normal', 'biolink-pro' ) }</option>
						<option value="tall">{ __( 'Tall', 'biolink-pro' ) }</option>
					</select>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Theme', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.theme ?? 'default' }
						onChange={ ( e ) =>
							onChange( { ...data, theme: e.target.value as SpotifyData[ 'theme' ] } )
						}
					>
						<option value="default">{ __( 'Default', 'biolink-pro' ) }</option>
						<option value="black">{ __( 'Black', 'biolink-pro' ) }</option>
					</select>
				</label>
			</div>
		</>
	);
}
