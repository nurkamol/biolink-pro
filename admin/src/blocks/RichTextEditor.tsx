import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface RichTextData {
	markdown?: string;
	align?: 'left' | 'center' | 'right';
}

interface Props {
	data: RichTextData;
	onChange: ( next: RichTextData ) => void;
}

export function RichTextEditor( { data, onChange }: Props ) {
	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Markdown', 'biolink-pro' ) }</span>
				<textarea
					className={ styles.textarea }
					value={ data.markdown ?? '' }
					onChange={ ( e ) => onChange( { ...data, markdown: e.target.value } ) }
					rows={ 8 }
					placeholder={ __( '## Heading\n\nWrite a short bio…\n\n- item one\n- item two', 'biolink-pro' ) }
				/>
				<span className={ styles.hint }>
					{ __( 'Supports headings, lists, links, **bold**, *italic*, and `code`.', 'biolink-pro' ) }
				</span>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Alignment', 'biolink-pro' ) }</span>
				<select
					className={ styles.select }
					value={ data.align ?? 'left' }
					onChange={ ( e ) =>
						onChange( { ...data, align: e.target.value as 'left' | 'center' | 'right' } )
					}
				>
					<option value="left">{ __( 'Left', 'biolink-pro' ) }</option>
					<option value="center">{ __( 'Center', 'biolink-pro' ) }</option>
					<option value="right">{ __( 'Right', 'biolink-pro' ) }</option>
				</select>
			</label>
		</>
	);
}
