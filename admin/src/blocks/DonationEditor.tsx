import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface DonationData {
	heading?: string;
	description?: string;
	amounts?: number[];
	currency?: string;
	cta_label?: string;
	cta_url?: string;
	provider?: 'link' | 'stripe' | 'paypal' | 'stripe_and_paypal';
}

interface Props {
	data: DonationData;
	onChange: ( next: DonationData ) => void;
}

export function DonationEditor( { data, onChange }: Props ) {
	const amountsStr = ( data.amounts ?? [] ).join( ', ' );
	const provider = data.provider ?? 'link';

	return (
		<>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Provider', 'biolink-pro' ) }</span>
				<select
					className={ styles.select }
					value={ provider }
					onChange={ ( e ) =>
						onChange( { ...data, provider: e.target.value as DonationData[ 'provider' ] } )
					}
				>
					<option value="stripe">{ __( 'Stripe Checkout', 'biolink-pro' ) }</option>
					<option value="stripe_and_paypal">
						{ __( 'Stripe + PayPal (Stripe primary)', 'biolink-pro' ) }
					</option>
					<option value="paypal">{ __( 'PayPal only', 'biolink-pro' ) }</option>
					<option value="link">{ __( 'External link (Payment Link / PayPal.me)', 'biolink-pro' ) }</option>
				</select>
				<span className={ styles.hint }>
					{ provider === 'link'
						? __( 'Use a Stripe Payment Link or PayPal.me URL below.', 'biolink-pro' )
						: __( 'Configure the provider in Settings → Integrations first.', 'biolink-pro' ) }
				</span>
			</label>

			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Heading', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.heading ?? '' }
					onChange={ ( e ) => onChange( { ...data, heading: e.target.value } ) }
				/>
			</label>
			<label className={ styles.field }>
				<span className={ styles.label }>{ __( 'Description', 'biolink-pro' ) }</span>
				<input
					type="text"
					className={ styles.input }
					value={ data.description ?? '' }
					onChange={ ( e ) => onChange( { ...data, description: e.target.value } ) }
				/>
			</label>
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>
						{ __( 'Suggested amounts (comma-separated)', 'biolink-pro' ) }
					</span>
					<input
						type="text"
						className={ styles.input }
						value={ amountsStr }
						onChange={ ( e ) =>
							onChange( {
								...data,
								amounts: e.target.value
									.split( ',' )
									.map( ( s ) => Number( s.trim() ) )
									.filter( ( n ) => ! Number.isNaN( n ) && n > 0 ),
							} )
						}
						placeholder="5, 10, 25"
					/>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Currency', 'biolink-pro' ) }</span>
					<input
						type="text"
						className={ styles.input }
						value={ data.currency ?? 'USD' }
						onChange={ ( e ) => onChange( { ...data, currency: e.target.value } ) }
					/>
				</label>
			</div>
			<div className={ styles.row }>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Button text', 'biolink-pro' ) }</span>
					<input
						type="text"
						className={ styles.input }
						value={ data.cta_label ?? '' }
						onChange={ ( e ) => onChange( { ...data, cta_label: e.target.value } ) }
					/>
				</label>
				<label className={ styles.field }>
					<span className={ styles.label }>{ __( 'Payment URL', 'biolink-pro' ) }</span>
					<input
						type="url"
						className={ styles.input }
						value={ data.cta_url ?? '' }
						onChange={ ( e ) => onChange( { ...data, cta_url: e.target.value } ) }
						placeholder="https://buy.stripe.com/… or https://paypal.me/…"
					/>
				</label>
			</div>
		</>
	);
}
