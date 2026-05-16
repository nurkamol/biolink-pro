import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface DividerData {
	style?: 'line' | 'dots' | 'space';
	color?: string;
	spacing?: 'sm' | 'md' | 'lg';
}

interface Props {
	data: DividerData;
	onChange: ( next: DividerData ) => void;
}

export function DividerEditor( { data, onChange }: Props ) {
	return (
		<>
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Style', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.style ?? 'line' }
						onChange={ ( e ) =>
							onChange( { ...data, style: e.target.value as DividerData[ 'style' ] } )
						}
					>
						<option value="line">{ __( 'Line', 'biolink-pro' ) }</option>
						<option value="dots">{ __( 'Dots', 'biolink-pro' ) }</option>
						<option value="space">{ __( 'Space', 'biolink-pro' ) }</option>
					</select>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Spacing', 'biolink-pro' ) }</span>
					<select
						className={ styles.select }
						value={ data.spacing ?? 'md' }
						onChange={ ( e ) =>
							onChange( { ...data, spacing: e.target.value as DividerData[ 'spacing' ] } )
						}
					>
						<option value="sm">{ __( 'Small', 'biolink-pro' ) }</option>
						<option value="md">{ __( 'Medium', 'biolink-pro' ) }</option>
						<option value="lg">{ __( 'Large', 'biolink-pro' ) }</option>
					</select>
				</label>
			</div>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Color', 'biolink-pro' ) }</span>
				<input
					type="color"
					className={ styles.input }
					value={ data.color ?? '#dcdcde' }
					onChange={ ( e ) => onChange( { ...data, color: e.target.value } ) }
				/>
			</label>
		</>
	);
}
