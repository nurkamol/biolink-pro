import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface MapData {
	lat?: string;
	lng?: string;
	zoom?: number;
	label?: string;
}

interface Props {
	data: MapData;
	onChange: ( next: MapData ) => void;
}

export function MapEditor( { data, onChange }: Props ) {
	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Label (optional)', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.label ?? '' }
					onChange={ ( e ) => onChange( { ...data, label: e.target.value } ) }
					placeholder={ __( 'Find us at…', 'biolink-pro' ) }
				/>
			</label>
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Latitude', 'biolink-pro' ) }</span>
					<input
						type="text"
						className={ styles.input }
						value={ data.lat ?? '' }
						onChange={ ( e ) => onChange( { ...data, lat: e.target.value } ) }
						placeholder="40.7128"
					/>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Longitude', 'biolink-pro' ) }</span>
					<input
						type="text"
						className={ styles.input }
						value={ data.lng ?? '' }
						onChange={ ( e ) => onChange( { ...data, lng: e.target.value } ) }
						placeholder="-74.0060"
					/>
				</label>
			</div>
			<label className={ styles.field }>
				<span className={ styles.label }>
					{ __( 'Zoom', 'biolink-pro' ) }: { data.zoom ?? 14 }
				</span>
				<input
					type="range"
					min={ 1 }
					max={ 19 }
					value={ data.zoom ?? 14 }
					onChange={ ( e ) => onChange( { ...data, zoom: Number( e.target.value ) } ) }
				/>
			</label>
		</>
	);
}
