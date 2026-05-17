import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from 'react';
import { SettingsApi } from '../api/client';
import styles from './Settings.module.css';

interface General {
	analytics_retention_days?: number;
	show_credit?: boolean;
	allow_tracking?: boolean;
	ai_enabled?: boolean;
	page_slug?: string;
	[ k: string ]: unknown;
}

interface Integrations {
	openai_api_key?: string;
	openai_api_key_set?: boolean;
	stripe_secret?: string;
	stripe_secret_set?: boolean;
	[ k: string ]: unknown;
}

export function Settings() {
	const [ general, setGeneral ] = useState< General >( {} );
	const [ integrations, setIntegrations ] = useState< Integrations >( {} );
	const [ tab, setTab ] = useState< 'general' | 'integrations' >( 'general' );
	const [ saving, setSaving ] = useState( false );
	const [ message, setMessage ] = useState< string | null >( null );
	const [ loading, setLoading ] = useState( true );

	useEffect( () => {
		void SettingsApi.get()
			.then( ( r ) => {
				setGeneral( ( r.general as General ) ?? {} );
				setIntegrations( ( r.integrations as Integrations ) ?? {} );
			} )
			.finally( () => setLoading( false ) );
	}, [] );

	const save = async () => {
		setSaving( true );
		setMessage( null );
		try {
			await SettingsApi.update( { general, integrations } );
			setMessage( __( 'Saved.', 'biolink-pro' ) );
		} catch ( err ) {
			setMessage( err instanceof Error ? err.message : __( 'Save failed.', 'biolink-pro' ) );
		} finally {
			setSaving( false );
		}
	};

	if ( loading ) {
		return <p className={ styles.empty }>{ __( 'Loading settings…', 'biolink-pro' ) }</p>;
	}

	return (
		<section className={ styles.root }>
			<header className={ styles.header }>
				<h1>{ __( 'Settings', 'biolink-pro' ) }</h1>
				<button type="button" className={ styles.saveBtn } onClick={ save } disabled={ saving }>
					{ saving ? __( 'Saving…', 'biolink-pro' ) : __( 'Save', 'biolink-pro' ) }
				</button>
			</header>

			<div className={ styles.tabs }>
				<button
					type="button"
					className={ tab === 'general' ? styles.tabActive : styles.tab }
					onClick={ () => setTab( 'general' ) }
				>
					{ __( 'General', 'biolink-pro' ) }
				</button>
				<button
					type="button"
					className={ tab === 'integrations' ? styles.tabActive : styles.tab }
					onClick={ () => setTab( 'integrations' ) }
				>
					{ __( 'Integrations', 'biolink-pro' ) }
				</button>
			</div>

			{ message && <div className={ styles.flash }>{ message }</div> }

			{ tab === 'general' && (
				<div className={ styles.card }>
					<label className={ styles.field }>
						<span>{ __( 'Page URL prefix', 'biolink-pro' ) }</span>
						<input
							type="text"
							className={ styles.input }
							value={ general.page_slug ?? 'bio' }
							placeholder="bio"
							onChange={ ( e ) =>
								setGeneral( {
									...general,
									page_slug: e.target.value
										.toLowerCase()
										.replace( /[^a-z0-9-]/g, '' ),
								} )
							}
						/>
						<span style={ { fontSize: 12, color: 'var(--biolink-color-text-muted)', display: 'block', marginTop: 4 } }>
							{ sprintf(
								/* translators: 1: site URL prefix, 2: slug */
								__( 'Public bio pages will live at /%s/{page-slug}. Existing pages keep their links once permalinks refresh (we flush automatically on save).', 'biolink-pro' ),
								general.page_slug ?? 'bio'
							) }
						</span>
					</label>
					<label className={ styles.field }>
						<span>{ __( 'Analytics retention (days)', 'biolink-pro' ) }</span>
						<input
							type="number"
							className={ styles.input }
							value={ general.analytics_retention_days ?? 365 }
							min={ 7 }
							max={ 3650 }
							onChange={ ( e ) =>
								setGeneral( { ...general, analytics_retention_days: Number( e.target.value ) } )
							}
						/>
					</label>
					<label className={ styles.toggle }>
						<input
							type="checkbox"
							checked={ general.show_credit !== false }
							onChange={ ( e ) => setGeneral( { ...general, show_credit: e.target.checked } ) }
						/>
						<span>{ __( 'Show "Powered by BioLink Pro" credit on public pages', 'biolink-pro' ) }</span>
					</label>
					<label className={ styles.toggle }>
						<input
							type="checkbox"
							checked={ general.allow_tracking !== false }
							onChange={ ( e ) => setGeneral( { ...general, allow_tracking: e.target.checked } ) }
						/>
						<span>{ __( 'Enable click + view tracking', 'biolink-pro' ) }</span>
					</label>
					<label className={ styles.toggle }>
						<input
							type="checkbox"
							checked={ !! general.ai_enabled }
							onChange={ ( e ) => setGeneral( { ...general, ai_enabled: e.target.checked } ) }
						/>
						<span>{ __( 'Enable AI suggestions in the builder', 'biolink-pro' ) }</span>
					</label>
				</div>
			) }

			{ tab === 'integrations' && (
				<div className={ styles.card }>
					<h2 className={ styles.groupTitle }>{ __( 'AI', 'biolink-pro' ) }</h2>
					<SecretField
						label={ __( 'OpenAI API key', 'biolink-pro' ) }
						placeholder="sk-…"
						isSet={ !! integrations.openai_api_key_set }
						masked={ ( integrations.openai_api_key as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, openai_api_key: v } ) }
						hint={ __( 'Powers "✨ Suggest" buttons in the builder.', 'biolink-pro' ) }
					/>

					<h2 className={ styles.groupTitle }>{ __( 'Payments', 'biolink-pro' ) }</h2>
					<SecretField
						label={ __( 'Stripe secret key', 'biolink-pro' ) }
						placeholder="sk_live_…"
						isSet={ !! integrations.stripe_secret_set }
						masked={ ( integrations.stripe_secret as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, stripe_secret: v } ) }
						hint={ __( 'Powers the Donation block when its Provider is set to Stripe. Mode (test/live) auto-detected from sk_test_ / sk_live_ prefix.', 'biolink-pro' ) }
					/>
					<SecretField
						label={ __( 'Stripe webhook secret', 'biolink-pro' ) }
						placeholder="whsec_…"
						isSet={ !! ( integrations as Record< string, unknown > ).stripe_webhook_secret_set }
						masked={ ( ( integrations as Record< string, unknown > ).stripe_webhook_secret as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, stripe_webhook_secret: v } ) }
						hint={ __( 'Stripe → Developers → Webhooks. Point at /wp-json/biolink/v1/webhooks/stripe.', 'biolink-pro' ) }
					/>
					<SecretField
						label={ __( 'PayPal client ID', 'biolink-pro' ) }
						placeholder="AY..."
						isSet={ !! integrations.paypal_client_id_set }
						masked={ ( integrations.paypal_client_id as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, paypal_client_id: v } ) }
					/>
					<SecretField
						label={ __( 'PayPal secret', 'biolink-pro' ) }
						placeholder="EL..."
						isSet={ !! integrations.paypal_secret_set }
						masked={ ( integrations.paypal_secret as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, paypal_secret: v } ) }
					/>
					<label className={ styles.toggle }>
						<input
							type="checkbox"
							checked={ !! integrations.paypal_sandbox }
							onChange={ ( e ) =>
								setIntegrations( { ...integrations, paypal_sandbox: e.target.checked } )
							}
						/>
						<span>{ __( 'PayPal sandbox mode', 'biolink-pro' ) }</span>
					</label>

					<h2 className={ styles.groupTitle }>{ __( 'Email', 'biolink-pro' ) }</h2>
					<SecretField
						label={ __( 'Mailchimp API key', 'biolink-pro' ) }
						placeholder="abc...-us12"
						isSet={ !! integrations.mailchimp_api_key_set }
						masked={ ( integrations.mailchimp_api_key as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, mailchimp_api_key: v } ) }
					/>
					<label className={ styles.field }>
						<span>{ __( 'Mailchimp list ID', 'biolink-pro' ) }</span>
						<input
							type="text"
							className={ styles.input }
							placeholder="a1b2c3d4e5"
							value={ ( integrations.mailchimp_list_id as string ) || '' }
							onChange={ ( e ) =>
								setIntegrations( { ...integrations, mailchimp_list_id: e.target.value } )
							}
						/>
					</label>

					<SecretField
						label={ __( 'MailerLite API key', 'biolink-pro' ) }
						placeholder="eyJ..."
						isSet={ !! integrations.mailerlite_api_key_set }
						masked={ ( integrations.mailerlite_api_key as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, mailerlite_api_key: v } ) }
					/>
					<label className={ styles.field }>
						<span>{ __( 'MailerLite group ID (optional)', 'biolink-pro' ) }</span>
						<input
							type="text"
							className={ styles.input }
							placeholder="123456789"
							value={ ( integrations.mailerlite_group_id as string ) || '' }
							onChange={ ( e ) =>
								setIntegrations( { ...integrations, mailerlite_group_id: e.target.value } )
							}
						/>
					</label>

					<SecretField
						label={ __( 'Resend API key', 'biolink-pro' ) }
						placeholder="re_..."
						isSet={ !! integrations.resend_api_key_set }
						masked={ ( integrations.resend_api_key as string ) || '' }
						onChange={ ( v ) => setIntegrations( { ...integrations, resend_api_key: v } ) }
					/>
					<label className={ styles.field }>
						<span>{ __( 'Resend audience ID', 'biolink-pro' ) }</span>
						<input
							type="text"
							className={ styles.input }
							placeholder="aud-…"
							value={ ( integrations.resend_audience_id as string ) || '' }
							onChange={ ( e ) =>
								setIntegrations( { ...integrations, resend_audience_id: e.target.value } )
							}
						/>
					</label>
				</div>
			) }
		</section>
	);
}

interface SecretFieldProps {
	label: string;
	placeholder: string;
	isSet: boolean;
	masked: string;
	onChange: ( v: string ) => void;
	hint?: string;
}

function SecretField( { label, placeholder, isSet, masked, onChange, hint }: SecretFieldProps ) {
	const [ editing, setEditing ] = useState( false );
	return (
		<label className={ styles.field }>
			<span>{ label }</span>
			{ isSet && ! editing ? (
				<div className={ styles.secretRow }>
					<code className={ styles.masked }>{ masked || '•••••••• (stored)' }</code>
					<button type="button" className={ styles.linkBtn } onClick={ () => setEditing( true ) }>
						{ __( 'Replace', 'biolink-pro' ) }
					</button>
					<button
						type="button"
						className={ `${ styles.linkBtn } ${ styles.linkBtnDanger }` }
						onClick={ () => onChange( '' ) }
					>
						{ __( 'Remove', 'biolink-pro' ) }
					</button>
				</div>
			) : (
				<input
					type="password"
					className={ styles.input }
					placeholder={ placeholder }
					autoComplete="off"
					onChange={ ( e ) => onChange( e.target.value ) }
				/>
			) }
			{ hint && <small className={ styles.hint }>{ hint }</small> }
		</label>
	);
}
