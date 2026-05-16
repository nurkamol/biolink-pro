import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface CountdownData {
	label?: string;
	target?: string;
	expired_message?: string;
}

interface Props {
	data: CountdownData;
	onChange: ( next: CountdownData ) => void;
}

function toLocalInputValue( iso: string ): string {
	if ( ! iso ) return '';
	try {
		const d = new Date( iso );
		if ( Number.isNaN( d.getTime() ) ) return '';
		const pad = ( n: number ) => String( n ).padStart( 2, '0' );
		return `${ d.getFullYear() }-${ pad( d.getMonth() + 1 ) }-${ pad( d.getDate() ) }T${ pad(
			d.getHours()
		) }:${ pad( d.getMinutes() ) }`;
	} catch {
		return '';
	}
}

export function CountdownEditor( { data, onChange }: Props ) {
	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Label (optional)', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.label ?? '' }
					onChange={ ( e ) => onChange( { ...data, label: e.target.value } ) }
					placeholder={ __( 'Launching in', 'biolink-pro' ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Target date & time', 'biolink-pro' ) }</span>
				<input
					type="datetime-local"
					className={ styles.input }
					value={ toLocalInputValue( data.target ?? '' ) }
					onChange={ ( e ) => onChange( { ...data, target: e.target.value } ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Expired message', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.expired_message ?? '' }
					onChange={ ( e ) => onChange( { ...data, expired_message: e.target.value } ) }
				/>
			</label>
		</>
	);
}
