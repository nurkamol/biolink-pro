import { __ } from '@wordpress/i18n';
import type { ChangeEvent } from 'react';
import { AiSuggestButton } from '../components/ai/AiSuggestButton';
import styles from './shared.module.css';

export interface ButtonData {
	label?: string;
	url?: string;
	variant?: 'primary' | 'secondary' | 'ghost';
	size?: 'sm' | 'md' | 'lg';
	icon?: string;
}

interface Props {
	data: ButtonData;
	onChange: ( next: ButtonData ) => void;
}

export function ButtonEditor( { data, onChange }: Props ) {
	const set =
		< K extends keyof ButtonData >( key: K ) =>
		( e: ChangeEvent< HTMLInputElement | HTMLSelectElement > ) =>
			onChange( { ...data, [ key ]: e.target.value as ButtonData[ K ] } );

	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Label', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.label ?? '' }
					onChange={ set( 'label' ) }
				/>
				<AiSuggestButton
					kind="cta"
					prompt={ data.url ? `Link to ${ data.url }` : 'Generic CTA button' }
					onPick={ ( v ) => onChange( { ...data, label: v } ) }
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
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Variant', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.variant ?? 'primary' }
						onChange={ set( 'variant' ) }
					>
						<option value="primary">{ __( 'Primary', 'biolink-pro' ) }</option>
						<option value="secondary">{ __( 'Secondary', 'biolink-pro' ) }</option>
						<option value="ghost">{ __( 'Ghost', 'biolink-pro' ) }</option>
					</select>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Size', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.size ?? 'md' }
						onChange={ set( 'size' ) }
					>
						<option value="sm">{ __( 'Small', 'biolink-pro' ) }</option>
						<option value="md">{ __( 'Medium', 'biolink-pro' ) }</option>
						<option value="lg">{ __( 'Large', 'biolink-pro' ) }</option>
					</select>
				</label>
			</div>
		</>
	);
}
