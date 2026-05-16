import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from 'react';
import { BlocksApi, type BioBlock } from '../../api/client';
import { ProductCardEditor, type ProductCardData } from '../../blocks/ProductCardEditor';
import { findBlockMeta } from '../../blocks';
import { IconClose, IconPlus, IconTrash } from '../../components/ui/Icons';
import { useBuilder } from './BuilderContext';
import styles from './ShopPage.module.css';

export function ShopPage() {
	const { page, setBlocks } = useBuilder();
	const [ selectedUuid, setSelectedUuid ] = useState< string | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ saving, setSaving ] = useState( false );

	const products = page.blocks.filter( ( b ) => b.type === 'product_card' );
	const selected = products.find( ( p ) => p.uuid === selectedUuid ) ?? null;

	const handleAdd = useCallback( async () => {
		setError( null );
		const meta = findBlockMeta( 'product_card' );
		if ( ! meta ) return;
		try {
			const created = await BlocksApi.append( page.id, 'product_card', meta.defaultData );
			setBlocks( [ ...page.blocks, created ] );
			setSelectedUuid( created.uuid );
		} catch ( err ) {
			setError( err instanceof Error ? err.message : __( 'Failed to add product.', 'biolink-pro' ) );
		}
	}, [ page.id, page.blocks, setBlocks ] );

	const handleUpdate = useCallback(
		async ( uuid: string, nextData: ProductCardData ) => {
			const before = page.blocks;
			const next = page.blocks.map( ( b ) =>
				b.uuid === uuid ? { ...b, data: nextData as unknown as Record< string, unknown > } : b
			);
			setBlocks( next );
			setSaving( true );
			try {
				await BlocksApi.update( page.id, uuid, {
					data: nextData as unknown as Record< string, unknown >,
				} );
			} catch ( err ) {
				setBlocks( before );
				setError( err instanceof Error ? err.message : __( 'Failed to save product.', 'biolink-pro' ) );
			} finally {
				setSaving( false );
			}
		},
		[ page.id, page.blocks, setBlocks ]
	);

	const handleToggle = useCallback(
		async ( uuid: string, active: boolean ) => {
			const block = page.blocks.find( ( b ) => b.uuid === uuid );
			if ( ! block ) return;
			const data = { ...( block.data as Record< string, unknown > ), _active: active };
			await handleUpdate( uuid, data as ProductCardData );
		},
		[ page.blocks, handleUpdate ]
	);

	const handleDelete = useCallback(
		async ( uuid: string ) => {
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( __( 'Remove this product?', 'biolink-pro' ) ) ) return;
			const before = page.blocks;
			setBlocks( page.blocks.filter( ( b ) => b.uuid !== uuid ) );
			if ( selectedUuid === uuid ) setSelectedUuid( null );
			try {
				await BlocksApi.remove( page.id, uuid );
			} catch ( err ) {
				setBlocks( before );
				setError( err instanceof Error ? err.message : __( 'Failed to delete product.', 'biolink-pro' ) );
			}
		},
		[ page.id, page.blocks, setBlocks, selectedUuid ]
	);

	return (
		<div className={ styles.root }>
			{ error && <div className={ styles.errorBanner }>{ error }</div> }

			<p className={ styles.intro }>
				{ __(
					'Products added here also appear on the Links tab. Reorder them from there.',
					'biolink-pro'
				) }
			</p>

			<button type="button" className={ styles.addBtn } onClick={ handleAdd }>
				<IconPlus /> { __( 'Add product', 'biolink-pro' ) }
			</button>

			{ selected && (
				<EditorPanel
					block={ selected }
					saving={ saving }
					onChange={ ( next ) => handleUpdate( selected.uuid, next ) }
					onClose={ () => setSelectedUuid( null ) }
				/>
			) }

			{ products.length === 0 ? (
				<div className={ styles.empty }>
					<p className={ styles.emptyTitle }>
						{ __( 'Your shop is empty', 'biolink-pro' ) }
					</p>
					<p>
						{ __(
							'Add a product card to start selling. Each product can route through Stripe Checkout, PayPal, or just link out.',
							'biolink-pro'
						) }
					</p>
				</div>
			) : (
				<div className={ styles.grid }>
					{ products.map( ( block ) => (
						<ProductTile
							key={ block.uuid }
							block={ block }
							selected={ selectedUuid === block.uuid }
							onSelect={ () => setSelectedUuid( block.uuid === selectedUuid ? null : block.uuid ) }
							onToggle={ ( active ) => handleToggle( block.uuid, active ) }
							onDelete={ () => handleDelete( block.uuid ) }
						/>
					) ) }
				</div>
			) }
		</div>
	);
}

interface TileProps {
	block: BioBlock;
	selected: boolean;
	onSelect: () => void;
	onToggle: ( active: boolean ) => void;
	onDelete: () => void;
}

function ProductTile( { block, selected, onSelect, onToggle, onDelete }: TileProps ) {
	const data = block.data as ProductCardData & { _active?: boolean };
	const active = data._active !== false;
	const [ thumb, setThumb ] = useState< string | null >( null );

	useEffect( () => {
		const id = data.image_id ?? 0;
		if ( ! id ) {
			setThumb( null );
			return;
		}
		void ( async () => {
			try {
				const res = await fetch(
					`/wp-json/wp/v2/media/${ id }?_fields=source_url,media_details`,
					{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
				);
				if ( ! res.ok ) return;
				const json = ( await res.json() ) as {
					source_url: string;
					media_details?: { sizes?: { medium?: { source_url?: string } } };
				};
				setThumb( json.media_details?.sizes?.medium?.source_url ?? json.source_url );
			} catch {
				// non-fatal
			}
		} )();
	}, [ data.image_id ] );

	const classes = [ styles.card ];
	if ( selected ) classes.push( styles.cardSelected );
	if ( ! active ) classes.push( styles.cardInactive );

	return (
		<div className={ classes.join( ' ' ) }>
			<button type="button" onClick={ onSelect } style={ { all: 'unset', cursor: 'pointer', display: 'block' } }>
				<div className={ styles.thumb }>
					{ thumb ? <img src={ thumb } alt="" /> : <span>🛍</span> }
				</div>
				<div className={ styles.cardBody }>
					<p className={ styles.name }>
						{ data.name || __( 'Untitled product', 'biolink-pro' ) }
					</p>
					<p className={ styles.price }>
						{ data.price || data.price_value
							? `${ data.price ?? `${ data.currency ?? 'USD' } ${ data.price_value }` }`
							: __( '(no price)', 'biolink-pro' ) }
					</p>
				</div>
			</button>
			<div className={ styles.cardFooter }>
				<label className={ styles.toggleSwitch } aria-label={ __( 'Toggle visible', 'biolink-pro' ) }>
					<input
						type="checkbox"
						checked={ active }
						onChange={ ( e ) => onToggle( e.target.checked ) }
					/>
					<span className={ styles.toggleSlider } />
				</label>
				<button type="button" className={ styles.editBtn } onClick={ onSelect }>
					{ selected ? __( 'Close', 'biolink-pro' ) : __( 'Edit', 'biolink-pro' ) }
				</button>
				<button
					type="button"
					className={ styles.delBtn }
					onClick={ onDelete }
					aria-label={ __( 'Delete product', 'biolink-pro' ) }
				>
					<IconTrash />
				</button>
			</div>
		</div>
	);
}

interface EditorPanelProps {
	block: BioBlock;
	saving: boolean;
	onChange: ( next: ProductCardData ) => void;
	onClose: () => void;
}

function EditorPanel( { block, saving, onChange, onClose }: EditorPanelProps ) {
	return (
		<div className={ styles.editorPanel }>
			<div className={ styles.editorHead }>
				<p className={ styles.editorTitle }>
					{ __( 'Editing product', 'biolink-pro' ) }
					{ saving && (
						<span style={ { marginLeft: 8, fontSize: 12, color: 'var(--biolink-color-text-muted)' } }>
							{ __( 'Saving…', 'biolink-pro' ) }
						</span>
					) }
				</p>
				<button type="button" className={ styles.delBtn } onClick={ onClose } aria-label={ __( 'Close', 'biolink-pro' ) }>
					<IconClose />
				</button>
			</div>
			<ProductCardEditor
				data={ block.data as ProductCardData }
				onChange={ onChange }
			/>
		</div>
	);
}
