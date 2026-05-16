import { __ } from '@wordpress/i18n';
import styles from './shared.module.css';

export interface FaqData {
	items?: Array< { question: string; answer: string } >;
}

interface Props {
	data: FaqData;
	onChange: ( next: FaqData ) => void;
}

export function FaqEditor( { data, onChange }: Props ) {
	const items = data.items ?? [];

	const update = ( idx: number, patch: Partial< { question: string; answer: string } > ) => {
		const next = items.slice();
		next[ idx ] = { ...next[ idx ], ...patch };
		onChange( { items: next } );
	};

	const add = () => onChange( { items: [ ...items, { question: '', answer: '' } ] } );

	const remove = ( idx: number ) => {
		const next = items.slice();
		next.splice( idx, 1 );
		onChange( { items: next } );
	};

	return (
		<>
			{ items.map( ( item, idx ) => (
				<div key={ idx } className={ styles.field } style={ { padding: '8px', background: 'var(--biolink-color-surface)', borderRadius: '6px' } }>
					<label className={ styles.field }>
						<span className={ styles.label }>{ __( 'Question', 'biolink-pro' ) }</span>
						<input
							type="text"
							className={ styles.input }
							value={ item.question }
							onChange={ ( e ) => update( idx, { question: e.target.value } ) }
						/>
					</label>
					<label className={ styles.field }>
						<span className={ styles.label }>{ __( 'Answer', 'biolink-pro' ) }</span>
						<textarea
							className={ styles.textarea }
							value={ item.answer }
							onChange={ ( e ) => update( idx, { answer: e.target.value } ) }
							rows={ 3 }
						/>
					</label>
					<button
						type="button"
						className={ `${ styles.btn } ${ styles.btnDanger }` }
						onClick={ () => remove( idx ) }
					>
						{ __( 'Remove', 'biolink-pro' ) }
					</button>
				</div>
			) ) }
			<button type="button" className={ styles.btn } onClick={ add }>
				{ __( '+ Add question', 'biolink-pro' ) }
			</button>
		</>
	);
}
