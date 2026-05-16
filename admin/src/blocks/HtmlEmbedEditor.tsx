import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface HtmlEmbedData {
	html?: string;
}

interface Props {
	data: HtmlEmbedData;
	onChange: ( next: HtmlEmbedData ) => void;
}

export function HtmlEmbedEditor( { data, onChange }: Props ) {
	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'HTML', 'biolink-pro' ) }</span>
				<textarea
					className={ styles.textarea }
					value={ data.html ?? '' }
					onChange={ ( e ) => onChange( { ...data, html: e.target.value } ) }
					rows={ 8 }
					placeholder="<div>Custom embed code…</div>"
				/>
				<span className={ styles.hint }>
					{ __(
						'Sanitized via wp_kses_post unless the page author has unfiltered_html.',
						'biolink-pro'
					) }
				</span>
			</label>
		</>
	);
}
