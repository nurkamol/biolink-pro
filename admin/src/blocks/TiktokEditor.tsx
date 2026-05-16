import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface TiktokData {
	url?: string;
}

interface Props {
	data: TiktokData;
	onChange: ( next: TiktokData ) => void;
}

export function TiktokEditor( { data, onChange }: Props ) {
	return (
		<label className={ styles.field }>
			<span className={ styles.label }>{ __( 'TikTok URL', 'biolink-pro' ) }</span>
			<input
				type="url"
				className={ styles.input }
				value={ data.url ?? '' }
				onChange={ ( e ) => onChange( { ...data, url: e.target.value } ) }
				placeholder="https://www.tiktok.com/@user/video/…"
			/>
			<span className={ styles.hint }>
				{ __( 'Paste the full video URL.', 'biolink-pro' ) }
			</span>
		</label>
	);
}
