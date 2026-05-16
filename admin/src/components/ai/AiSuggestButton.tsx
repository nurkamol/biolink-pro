import { __ } from '@wordpress/i18n';
import { useState } from 'react';
import { AiApi } from '../../api/client';
import styles from './AiSuggestButton.module.css';

interface Props {
	kind: 'bio' | 'cta' | 'theme';
	prompt: string;
	onPick: ( value: string ) => void;
	/** Optional extra renderer for theme: lines look like "<slug>: <reason>". */
	parseSuggestion?: ( raw: string ) => { label: string; value: string };
}

export function AiSuggestButton( { kind, prompt, onPick, parseSuggestion }: Props ) {
	const [ busy, setBusy ] = useState( false );
	const [ options, setOptions ] = useState< Array< { label: string; value: string } > >( [] );
	const [ error, setError ] = useState< string | null >( null );

	if ( ! window.BIOLINK_PRO.caps.useAi ) {
		return null;
	}

	const suggest = async () => {
		setBusy( true );
		setError( null );
		try {
			const r =
				kind === 'bio'
					? await AiApi.bio( prompt )
					: kind === 'cta'
					? await AiApi.cta( prompt )
					: await AiApi.theme( prompt );
			const opts = r.suggestions.map( ( raw: string ) => {
				if ( parseSuggestion ) return parseSuggestion( raw );
				return { label: raw, value: raw };
			} );
			setOptions( opts );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'AI request failed.', 'biolink-pro' ) );
		} finally {
			setBusy( false );
		}
	};

	return (
		<div className={ styles.wrap }>
			<button
				type="button"
				className={ styles.btn }
				onClick={ suggest }
				disabled={ busy }
				aria-label={ __( 'Suggest with AI', 'biolink-pro' ) }
			>
				{ busy ? __( 'Thinking…', 'biolink-pro' ) : __( '✨ Suggest', 'biolink-pro' ) }
			</button>
			{ error && <p className={ styles.error }>{ error }</p> }
			{ options.length > 0 && (
				<ul className={ styles.options }>
					{ options.map( ( opt, i ) => (
						<li key={ i }>
							<button
								type="button"
								className={ styles.option }
								onClick={ () => {
									onPick( opt.value );
									setOptions( [] );
								} }
							>
								{ opt.label }
							</button>
						</li>
					) ) }
				</ul>
			) }
		</div>
	);
}

