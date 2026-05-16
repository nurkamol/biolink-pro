import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import type { BioPageSettings } from '../../api/client';
import { pickMedia } from '../../lib/mediaFrame';
import styles from './SettingsPanel.module.css';

interface Props {
	settings: BioPageSettings;
	onChange: ( next: BioPageSettings ) => void;
}

export function PageHeaderEditor( { settings, onChange }: Props ) {
	const avatarId = settings.avatar_id ?? 0;
	const [ avatarUrl, setAvatarUrl ] = useState< string | null >( null );

	const loadAvatar = useCallback( async () => {
		if ( ! avatarId ) {
			setAvatarUrl( null );
			return;
		}
		try {
			const res = await fetch(
				`/wp-json/wp/v2/media/${ avatarId }?_fields=source_url,media_details`,
				{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
			);
			if ( ! res.ok ) return;
			const json = ( await res.json() ) as {
				source_url: string;
				media_details?: { sizes?: { thumbnail?: { source_url?: string } } };
			};
			setAvatarUrl( json.media_details?.sizes?.thumbnail?.source_url ?? json.source_url );
		} catch {
			// non-fatal
		}
	}, [ avatarId ] );

	useEffect( () => {
		void loadAvatar();
	}, [ loadAvatar ] );

	const pickAvatar = async () => {
		try {
			const picked = await pickMedia( {
				title: __( 'Select avatar', 'biolink-pro' ),
				buttonText: __( 'Use as avatar', 'biolink-pro' ),
				multiple: false,
				type: 'image',
			} );
			if ( picked[ 0 ] ) {
				onChange( { ...settings, avatar_id: picked[ 0 ].id } );
				setAvatarUrl( picked[ 0 ].url );
			}
		} catch {
			// cancelled
		}
	};

	const removeAvatar = () => {
		onChange( { ...settings, avatar_id: 0 } );
		setAvatarUrl( null );
	};

	return (
		<div className={ styles.section }>
			<h3 className={ styles.sectionTitle }>{ __( 'Header', 'biolink-pro' ) }</h3>

			<div className={ styles.avatarRow }>
				<div className={ styles.avatarPreview }>
					{ avatarUrl ? (
						<img src={ avatarUrl } alt="" />
					) : (
						<span className={ styles.avatarPlaceholder }>
							{ ( settings.handle || settings.headline || '?' ).slice( 0, 1 ).toUpperCase() }
						</span>
					) }
				</div>
				<div className={ styles.avatarActions }>
					<button type="button" className={ styles.btn } onClick={ pickAvatar }>
						{ avatarId
							? __( 'Change avatar', 'biolink-pro' )
							: __( 'Choose avatar', 'biolink-pro' ) }
					</button>
					{ avatarId !== 0 && (
						<button
							type="button"
							className={ `${ styles.btn } ${ styles.btnDanger }` }
							onClick={ removeAvatar }
						>
							{ __( 'Remove', 'biolink-pro' ) }
						</button>
					) }
				</div>
			</div>

			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Display name', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ settings.headline ?? '' }
					placeholder={ __( 'Defaults to page title', 'biolink-pro' ) }
					onChange={ ( e ) => onChange( { ...settings, headline: e.target.value } ) }
				/>
			</label>

			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Handle', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ settings.handle ?? '' }
					placeholder="username"
					onChange={ ( e ) => onChange( { ...settings, handle: e.target.value } ) }
				/>
				<span className={ styles.hint }>
					{ __( "The @ prefix is added automatically.", 'biolink-pro' ) }
				</span>
			</label>

			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Subtitle', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ settings.subheadline ?? '' }
					placeholder={ __( 'One short sentence about you', 'biolink-pro' ) }
					onChange={ ( e ) => onChange( { ...settings, subheadline: e.target.value } ) }
				/>
			</label>

			<label className={ styles.checkbox }>
				<input
					type="checkbox"
					checked={ !! settings.hide_name }
					onChange={ ( e ) => onChange( { ...settings, hide_name: e.target.checked } ) }
				/>
				{ __( 'Hide display name', 'biolink-pro' ) }
			</label>
		</div>
	);
}
