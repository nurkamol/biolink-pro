import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface SocialIconsData {
	items?: Array< { platform: string; url: string } >;
}

interface Props {
	data: SocialIconsData;
	onChange: ( next: SocialIconsData ) => void;
}

const PLATFORMS = [
	'instagram',
	'tiktok',
	'youtube',
	'twitter',
	'linkedin',
	'github',
	'twitch',
	'discord',
	'facebook',
	'threads',
	'spotify',
	'website',
	'email',
];

export function SocialIconsEditor( { data, onChange }: Props ) {
	const items = data.items ?? [];

	const updateItem = ( index: number, patch: Partial< { platform: string; url: string } > ) => {
		const next = items.slice();
		next[ index ] = { ...next[ index ], ...patch };
		onChange( { items: next } );
	};

	const addItem = () => {
		onChange( { items: [ ...items, { platform: 'instagram', url: '' } ] } );
	};

	const removeItem = ( index: number ) => {
		const next = items.slice();
		next.splice( index, 1 );
		onChange( { items: next } );
	};

	return (
		<>
			<div className={ styles.repeater }>
				{ items.map( ( item, index ) => (
					<div className={ styles.repeaterItem } key={ index }>
						<select
							className={ styles.select }
							value={ item.platform }
							onChange={ ( e ) => updateItem( index, { platform: e.target.value } ) }
						>
							{ PLATFORMS.map( ( p ) => (
								<option key={ p } value={ p }>
									{ p }
								</option>
							) ) }
						</select>
						<input
							type="url"
							className={ styles.input }
							value={ item.url }
							placeholder="https://"
							onChange={ ( e ) => updateItem( index, { url: e.target.value } ) }
						/>
						<button
							type="button"
							className={ `${ styles.btn } ${ styles.btnDanger }` }
							onClick={ () => removeItem( index ) }
							aria-label={ __( 'Remove', 'biolink-pro' ) }
						>
							×
						</button>
					</div>
				) ) }
			</div>
			<button type="button" className={ styles.btn } onClick={ addItem }>
				{ __( '+ Add social link', 'biolink-pro' ) }
			</button>
		</>
	);
}
