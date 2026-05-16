import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface YouTubeData {
	url?: string;
	title?: string;
}

interface Props {
	data: YouTubeData;
	onChange: ( next: YouTubeData ) => void;
}

export function YouTubeEditor( { data, onChange }: Props ) {
	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'YouTube URL or video ID', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.url ?? '' }
					onChange={ ( e ) => onChange( { ...data, url: e.target.value } ) }
					placeholder="https://www.youtube.com/watch?v=…"
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Title (accessibility)', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.title ?? '' }
					onChange={ ( e ) => onChange( { ...data, title: e.target.value } ) }
				/>
			</label>
		</>
	);
}
