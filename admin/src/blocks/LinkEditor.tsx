import { __ } from '@wordpress/i18n';
import type { ChangeEvent } from 'react';
import styles from './shared.module.css';

export interface LinkData {
	label?: string;
	url?: string;
	icon?: string;
	utm?: string;
	featured?: boolean;
}

interface Props {
	data: LinkData;
	onChange: ( next: LinkData ) => void;
}

const ICON_OPTIONS = [
	'link',
	'globe',
	'mail',
	'phone',
	'shopping-bag',
	'play',
	'download',
	'arrow-right',
	'heart',
	'calendar',
	'star',
	'pin',
];

export function LinkEditor( { data, onChange }: Props ) {
	const set =
		< K extends keyof LinkData >( key: K ) =>
		( e: ChangeEvent< HTMLInputElement | HTMLSelectElement > ): void => {
			const value =
				e.target.type === 'checkbox'
					? ( e.target as HTMLInputElement ).checked
					: e.target.value;
			onChange( { ...data, [ key ]: value } );
		};

	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Label', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.label ?? '' }
					onChange={ set( 'label' ) }
					placeholder={ __( 'Visit my portfolio', 'biolink-pro' ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'URL', 'biolink-pro' ) }</span>
				<input
					type="url"
					className={ styles.input }
					value={ data.url ?? '' }
					onChange={ set( 'url' ) }
					placeholder="https://"
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Icon', 'biolink-pro' ) }</span>
				<select
					className={ styles.select }
					value={ data.icon ?? 'link' }
					onChange={ set( 'icon' ) }
				>
					{ ICON_OPTIONS.map( ( name ) => (
						<option key={ name } value={ name }>
							{ name }
						</option>
					) ) }
				</select>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'UTM (optional)', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.utm ?? '' }
					onChange={ set( 'utm' ) }
					placeholder="utm_source=biolink&utm_medium=button"
				/>
				<span className={ styles.hint }>
					{ __( 'Query string fragment; only utm_* keys are kept.', 'biolink-pro' ) }
				</span>
			</label>
			<label className={ styles.checkbox }>
				<input
					type="checkbox"
					checked={ !! data.featured }
					onChange={ set( 'featured' ) }
				/>
				{ __( 'Featured (highlighted style)', 'biolink-pro' ) }
			</label>
		</>
	);
}
