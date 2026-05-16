import { __ } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { SettingsApi, TemplatesApi, type BioTemplate } from '../../api/client';
import styles from './OnboardingOverlay.module.css';

const STORAGE_KEY = 'biolink_pro_onboarding_dismissed';

export function OnboardingOverlay() {
	const [ dismissed, setDismissed ] = useState< boolean >( () => {
		return localStorage.getItem( STORAGE_KEY ) === '1';
	} );
	const [ templates, setTemplates ] = useState< BioTemplate[] >( [] );
	const [ busy, setBusy ] = useState( false );
	const navigate = useNavigate();

	useEffect( () => {
		if ( dismissed ) return;
		void TemplatesApi.list().then( setTemplates ).catch( () => setTemplates( [] ) );
	}, [ dismissed ] );

	const dismiss = ( permanent: boolean ) => {
		setDismissed( true );
		if ( permanent ) {
			localStorage.setItem( STORAGE_KEY, '1' );
			void SettingsApi.update( { general: { onboarding_complete: true } } ).catch( () => {} );
		}
	};

	const startFromScratch = () => dismiss( true );

	const useTemplate = async ( slug: string ) => {
		setBusy( true );
		try {
			const page = await TemplatesApi.apply( slug );
			dismiss( true );
			navigate( `/pages/${ page.id }` );
		} catch {
			setBusy( false );
		}
	};

	if ( dismissed ) {
		return null;
	}

	return (
		<div className={ styles.overlay } role="dialog" aria-labelledby="biolink-onboarding-title">
			<div className={ styles.modal }>
				<button
					type="button"
					className={ styles.close }
					onClick={ () => dismiss( false ) }
					aria-label={ __( 'Close', 'biolink-pro' ) }
				>
					×
				</button>
				<header className={ styles.header }>
					<h2 id="biolink-onboarding-title">{ __( 'Welcome to BioLink Pro', 'biolink-pro' ) }</h2>
					<p>
						{ __(
							"Pick a template to start with — or skip and build from scratch. You can switch themes any time.",
							'biolink-pro'
						) }
					</p>
				</header>
				<div className={ styles.grid }>
					{ templates.map( ( t ) => (
						<button
							type="button"
							key={ t.slug }
							className={ styles.card }
							onClick={ () => useTemplate( t.slug ) }
							disabled={ busy }
						>
							<span className={ styles.cardLabel }>{ t.label }</span>
							<span className={ styles.cardDesc }>{ t.description }</span>
						</button>
					) ) }
				</div>
				<footer className={ styles.footer }>
					<button type="button" className={ styles.skip } onClick={ startFromScratch }>
						{ __( 'Start from scratch', 'biolink-pro' ) }
					</button>
				</footer>
			</div>
		</div>
	);
}
