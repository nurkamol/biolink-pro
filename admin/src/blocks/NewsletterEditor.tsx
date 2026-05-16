import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface NewsletterData {
	heading?: string;
	description?: string;
	placeholder?: string;
	button_text?: string;
	success_message?: string;
}

interface Props {
	data: NewsletterData;
	onChange: ( next: NewsletterData ) => void;
}

export function NewsletterEditor( { data, onChange }: Props ) {
	const set = ( k: keyof NewsletterData, v: string ) => onChange( { ...data, [ k ]: v } );
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
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Placeholder', 'biolink-pro' ) }</span>
					<input
						type="text"
						className={ styles.input }
						value={ data.placeholder ?? '' }
						onChange={ ( e ) => set( 'placeholder', e.target.value ) }
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
			</div>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Success message', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.success_message ?? '' }
					onChange={ ( e ) => set( 'success_message', e.target.value ) }
				/>
			</label>
		</>
	);
}
