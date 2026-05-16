import { __ } from '@wordpress/i18n';
import { pickMedia } from '../lib/mediaFrame';
import styles from './shared.module.css';

export interface VideoData {
	url?: string;
	id?: number;
	autoplay?: boolean;
	loop?: boolean;
	muted?: boolean;
	controls?: boolean;
}

interface Props {
	data: VideoData;
	onChange: ( next: VideoData ) => void;
}

export function VideoEditor( { data, onChange }: Props ) {
	const handlePick = async () => {
		try {
			const picked = await pickMedia( {
				title: __( 'Select video', 'biolink-pro' ),
				buttonText: __( 'Use video', 'biolink-pro' ),
				multiple: false,
				type: 'video',
			} );
			if ( picked[ 0 ] ) {
				onChange( { ...data, id: picked[ 0 ].id, url: picked[ 0 ].url } );
			}
		} catch {
			// cancelled
		}
	};

	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Video URL', 'biolink-pro' ) }</span>
				<input
					type="url"
					className={ styles.input }
					value={ data.url ?? '' }
					onChange={ ( e ) => onChange( { ...data, url: e.target.value, id: 0 } ) }
					placeholder="https://example.com/clip.mp4"
				/>
			</label>
			<button type="button" className={ styles.btn } onClick={ handlePick }>
				{ __( 'Choose from media library', 'biolink-pro' ) }
			</button>
			<div className={ styles.row } style={ { marginTop: '12px' } }>
				<label className={ styles.checkbox }>
					<input
						type="checkbox"
						checked={ data.controls !== false }
						onChange={ ( e ) => onChange( { ...data, controls: e.target.checked } ) }
					/>
					{ __( 'Controls', 'biolink-pro' ) }
				</label>
				<label className={ styles.checkbox }>
					<input
						type="checkbox"
						checked={ !! data.autoplay }
						onChange={ ( e ) => onChange( { ...data, autoplay: e.target.checked } ) }
					/>
					{ __( 'Autoplay (muted)', 'biolink-pro' ) }
				</label>
				<label className={ styles.checkbox }>
					<input
						type="checkbox"
						checked={ !! data.loop }
						onChange={ ( e ) => onChange( { ...data, loop: e.target.checked } ) }
					/>
					{ __( 'Loop', 'biolink-pro' ) }
				</label>
				<label className={ styles.checkbox }>
					<input
						type="checkbox"
						checked={ !! data.muted }
						onChange={ ( e ) => onChange( { ...data, muted: e.target.checked } ) }
					/>
					{ __( 'Muted', 'biolink-pro' ) }
				</label>
			</div>
		</>
	);
}
