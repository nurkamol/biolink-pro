import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface ContactFormData {
	heading?: string;
	description?: string;
	button_text?: string;
	success_message?: string;
}

interface Props {
	data: ContactFormData;
	onChange: ( next: ContactFormData ) => void;
}

export function ContactFormEditor( { data, onChange }: Props ) {
	const set = ( k: keyof ContactFormData, v: string ) => onChange( { ...data, [ k ]: v } );
	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Heading', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.heading ?? '' }
					onChange={ ( e ) => set( 'heading', e.target.value ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Description', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.description ?? '' }
					onChange={ ( e ) => set( 'description', e.target.value ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Button text', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.button_text ?? '' }
					onChange={ ( e ) => set( 'button_text', e.target.value ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Success message', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.success_message ?? '' }
					onChange={ ( e ) => set( 'success_message', e.target.value ) }
				/>
				<span className={ styles.hint }>
					{ __( 'Submissions are emailed to the site admin.', 'biolink-pro' ) }
				</span>
			</label>
		</>
	);
}
