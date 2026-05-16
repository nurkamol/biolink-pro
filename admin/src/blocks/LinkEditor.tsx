import { __ } from '@wordpress/i18n';
import { useState, type ChangeEvent } from 'react';
import { AnalyticsApi } from '../api/client';
import styles from './shared.module.css';

export interface LinkVariant {
	key: string;
	label: string;
	url: string;
	weight: number;
}

export interface LinkData {
	label?: string;
	url?: string;
	icon?: string;
	utm?: string;
	featured?: boolean;
	_variants?: LinkVariant[];
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

	const variants = data._variants ?? [];
	const hasVariants = variants.length > 0;

	const updateVariant = ( index: number, patch: Partial< LinkVariant > ) => {
		const next = variants.map( ( v, i ) => ( i === index ? { ...v, ...patch } : v ) );
		onChange( { ...data, _variants: next } );
	};

	const addVariant = () => {
		const nextKey = String.fromCharCode( 65 + variants.length ); // A, B, C…
		const fresh: LinkVariant = {
			key: nextKey,
			label: data.label ?? '',
			url: data.url ?? '',
			weight: 50,
		};
		onChange( { ...data, _variants: [ ...variants, fresh ] } );
	};

	const removeVariant = ( index: number ) => {
		const next = variants.filter( ( _, i ) => i !== index );
		onChange( { ...data, _variants: next.length > 0 ? next : undefined } );
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

			<VariantsSection
				variants={ variants }
				hasVariants={ hasVariants }
				onAdd={ addVariant }
				onUpdate={ updateVariant }
				onRemove={ removeVariant }
			/>
		</>
	);
}

interface VariantsSectionProps {
	variants: LinkVariant[];
	hasVariants: boolean;
	onAdd: () => void;
	onUpdate: ( index: number, patch: Partial< LinkVariant > ) => void;
	onRemove: ( index: number ) => void;
}

function VariantsSection( { variants, hasVariants, onAdd, onUpdate, onRemove }: VariantsSectionProps ) {
	const [ open, setOpen ] = useState( hasVariants );
	const totalWeight = variants.reduce( ( s, v ) => s + Math.max( 1, v.weight || 50 ), 0 );

	return (
		<details className={ styles.field } open={ open } onToggle={ ( e ) => setOpen( ( e.target as HTMLDetailsElement ).open ) }>
			<summary style={ { cursor: 'pointer', fontWeight: 600, fontSize: 13, padding: '6px 0' } }>
				{ hasVariants
					? `🧪 ${ __( 'A/B test', 'biolink-pro' ) } (${ variants.length } ${ __( 'variants', 'biolink-pro' ) })`
					: `🧪 ${ __( 'A/B test', 'biolink-pro' ) }` }
			</summary>
			<div style={ { paddingTop: 8 } }>
				<p className={ styles.hint } style={ { marginBottom: 8 } }>
					{ __(
						'Add 2+ variants; visitors are split deterministically by IP. Each click records which variant was active.',
						'biolink-pro'
					) }
				</p>
				{ variants.map( ( v, i ) => (
					<VariantRow
						key={ i }
						variant={ v }
						totalWeight={ totalWeight }
						onChange={ ( patch ) => onUpdate( i, patch ) }
						onRemove={ () => onRemove( i ) }
					/>
				) ) }
				<button type="button" className={ styles.btn } onClick={ onAdd } style={ { marginTop: 8 } }>
					+ { __( 'Add variant', 'biolink-pro' ) }
				</button>
			</div>
		</details>
	);
}

interface RowProps {
	variant: LinkVariant;
	totalWeight: number;
	onChange: ( patch: Partial< LinkVariant > ) => void;
	onRemove: () => void;
}

function VariantRow( { variant, totalWeight, onChange, onRemove }: RowProps ) {
	const pct = totalWeight > 0
		? Math.round( ( Math.max( 1, variant.weight || 50 ) / totalWeight ) * 100 )
		: 0;
	return (
		<div
			style={ {
				border: '1px solid var(--biolink-color-border)',
				borderRadius: 'var(--biolink-radius)',
				padding: 10,
				marginBottom: 8,
				display: 'flex',
				flexDirection: 'column',
				gap: 6,
			} }
		>
			<div style={ { display: 'flex', gap: 6, alignItems: 'center' } }>
				<input
					type="text"
					className={ styles.input }
					value={ variant.key }
					onChange={ ( e ) => onChange( { key: e.target.value.replace( /[^A-Za-z0-9_-]/g, '' ).slice( 0, 8 ) } ) }
					style={ { maxWidth: 70, textAlign: 'center', fontWeight: 700 } }
					title={ __( 'Variant key (A, B, etc.)', 'biolink-pro' ) }
				/>
				<input
					type="number"
					min={ 1 }
					max={ 100 }
					className={ styles.input }
					value={ variant.weight }
					onChange={ ( e ) => onChange( { weight: Number( e.target.value ) || 50 } ) }
					style={ { maxWidth: 70 } }
					title={ __( 'Weight (relative)', 'biolink-pro' ) }
				/>
				<span className={ styles.hint } style={ { minWidth: 38, textAlign: 'right' } }>
					{ pct }%
				</span>
				<button
					type="button"
					onClick={ onRemove }
					style={ { marginLeft: 'auto', background: 'none', border: 0, color: 'var(--biolink-color-danger)', cursor: 'pointer', fontSize: 14 } }
					aria-label={ __( 'Remove variant', 'biolink-pro' ) }
				>
					×
				</button>
			</div>
			<input
				type="text"
				className={ styles.input }
				value={ variant.label }
				onChange={ ( e ) => onChange( { label: e.target.value } ) }
				placeholder={ __( 'Variant label', 'biolink-pro' ) }
			/>
			<input
				type="url"
				className={ styles.input }
				value={ variant.url }
				onChange={ ( e ) => onChange( { url: e.target.value } ) }
				placeholder="https://"
			/>
		</div>
	);
}

// Re-export so consumers can read variant analytics if they want to surface
// per-variant click counts. v2.5 candidate: dedicated dashboard card.
export { AnalyticsApi };
