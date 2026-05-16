import { __ } from '@wordpress/i18n';
import {
	DndContext,
	DragOverlay,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
	type DragEndEvent,
	type DragStartEvent,
} from '@dnd-kit/core';
import {
	SortableContext,
	arrayMove,
	sortableKeyboardCoordinates,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useCallback, useMemo, useState } from 'react';
import { BlocksApi, type BioBlock } from '../../api/client';
import { findBlockMeta, getBlockCatalog, type BlockData } from '../../blocks';
import styles from './PageBuilder.module.css';

interface Props {
	pageId: number;
	blocks: BioBlock[];
	onChange: ( next: BioBlock[] ) => void;
}

export function PageBuilder( { pageId, blocks, onChange }: Props ) {
	const [ selectedUuid, setSelectedUuid ] = useState< string | null >( null );
	const [ activeUuid, setActiveUuid ] = useState< string | null >( null );
	const [ showInserter, setShowInserter ] = useState( false );
	const [ saving, setSaving ] = useState< string | null >( null );
	const [ error, setError ] = useState< string | null >( null );

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 4 } } ),
		useSensor( KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates } )
	);

	const selected = useMemo(
		() => blocks.find( ( b ) => b.uuid === selectedUuid ) ?? null,
		[ blocks, selectedUuid ]
	);

	const handleAdd = useCallback(
		async ( slug: string ) => {
			const meta = findBlockMeta( slug );
			if ( ! meta ) return;
			setShowInserter( false );
			setError( null );
			try {
				const created = await BlocksApi.append( pageId, slug, meta.defaultData );
				onChange( [ ...blocks, created ] );
				setSelectedUuid( created.uuid );
			} catch ( err ) {
				setError( messageFor( err, __( 'Failed to add block.', 'biolink-pro' ) ) );
			}
		},
		[ blocks, onChange, pageId ]
	);

	const handleUpdate = useCallback(
		async ( uuid: string, nextData: BlockData ) => {
			// Optimistic update
			const before = blocks;
			const next = blocks.map( ( b ) =>
				b.uuid === uuid ? { ...b, data: nextData as Record< string, unknown > } : b
			);
			onChange( next );
			setSaving( uuid );
			try {
				await BlocksApi.update( pageId, uuid, { data: nextData as Record< string, unknown > } );
				setError( null );
			} catch ( err ) {
				onChange( before );
				setError( messageFor( err, __( 'Failed to save block.', 'biolink-pro' ) ) );
			} finally {
				setSaving( null );
			}
		},
		[ blocks, onChange, pageId ]
	);

	const handleDelete = useCallback(
		async ( uuid: string ) => {
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( __( 'Remove this block?', 'biolink-pro' ) ) ) return;
			const before = blocks;
			onChange( blocks.filter( ( b ) => b.uuid !== uuid ) );
			setSelectedUuid( null );
			try {
				await BlocksApi.remove( pageId, uuid );
			} catch ( err ) {
				onChange( before );
				setError( messageFor( err, __( 'Failed to delete block.', 'biolink-pro' ) ) );
			}
		},
		[ blocks, onChange, pageId ]
	);

	const handleDragStart = ( event: DragStartEvent ) => {
		setActiveUuid( String( event.active.id ) );
	};

	const handleDragEnd = async ( event: DragEndEvent ) => {
		setActiveUuid( null );
		const { active, over } = event;
		if ( ! over || active.id === over.id ) return;
		const oldIndex = blocks.findIndex( ( b ) => b.uuid === active.id );
		const newIndex = blocks.findIndex( ( b ) => b.uuid === over.id );
		if ( oldIndex < 0 || newIndex < 0 ) return;

		const before = blocks;
		const next = arrayMove( blocks, oldIndex, newIndex );
		onChange( next );
		try {
			await BlocksApi.reorder( pageId, next.map( ( b ) => b.uuid ) );
		} catch ( err ) {
			onChange( before );
			setError( messageFor( err, __( 'Failed to reorder blocks.', 'biolink-pro' ) ) );
		}
	};

	const activeBlock = activeUuid ? blocks.find( ( b ) => b.uuid === activeUuid ) : null;

	return (
		<div className={ styles.builder }>
			<div className={ styles.canvasColumn }>
				<header className={ styles.canvasHeader }>
					<h2>{ __( 'Blocks', 'biolink-pro' ) }</h2>
					<button
						type="button"
						className={ styles.addButton }
						onClick={ () => setShowInserter( ( v ) => ! v ) }
					>
						{ __( '+ Add block', 'biolink-pro' ) }
					</button>
				</header>

				{ error && <div className={ styles.error }>{ error }</div> }

				{ showInserter && (
					<div className={ styles.inserter }>
						{ getBlockCatalog().map( ( meta ) => (
							<button
								type="button"
								key={ meta.slug }
								className={ styles.inserterItem }
								onClick={ () => handleAdd( meta.slug ) }
							>
								<span className={ styles.inserterIcon }>{ meta.icon }</span>
								<span>{ meta.label }</span>
							</button>
						) ) }
					</div>
				) }

				<DndContext
					sensors={ sensors }
					onDragStart={ handleDragStart }
					onDragEnd={ handleDragEnd }
				>
					<SortableContext
						items={ blocks.map( ( b ) => b.uuid ) }
						strategy={ verticalListSortingStrategy }
					>
						{ blocks.length === 0 ? (
							<div className={ styles.emptyCanvas }>
								{ __( 'No blocks yet. Click "Add block" above to start.', 'biolink-pro' ) }
							</div>
						) : (
							<div className={ styles.canvas }>
								{ blocks.map( ( block ) => (
									<SortableBlockRow
										key={ block.uuid }
										block={ block }
										selected={ selectedUuid === block.uuid }
										saving={ saving === block.uuid }
										onSelect={ () => setSelectedUuid( block.uuid ) }
										onDelete={ () => handleDelete( block.uuid ) }
									/>
								) ) }
							</div>
						) }
					</SortableContext>
					<DragOverlay>
						{ activeBlock ? (
							<div className={ `${ styles.blockRow } ${ styles.blockRowDragging }` }>
								<BlockRowContent block={ activeBlock } />
							</div>
						) : null }
					</DragOverlay>
				</DndContext>
			</div>

			<aside className={ styles.inspector }>
				{ selected ? (
					<>
						<header className={ styles.inspectorHeader }>
							<h3>{ findBlockMeta( selected.type )?.label ?? selected.type }</h3>
							{ saving === selected.uuid && (
								<span className={ styles.savingLabel }>
									{ __( 'Saving…', 'biolink-pro' ) }
								</span>
							) }
						</header>
						<InspectorEditor block={ selected } onChange={ handleUpdate } />
					</>
				) : (
					<div className={ styles.inspectorEmpty }>
						{ __( 'Select a block to edit its content.', 'biolink-pro' ) }
					</div>
				) }
			</aside>
		</div>
	);
}

interface BlockRowProps {
	block: BioBlock;
	selected: boolean;
	saving: boolean;
	onSelect: () => void;
	onDelete: () => void;
}

function SortableBlockRow( { block, selected, saving, onSelect, onDelete }: BlockRowProps ) {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging } =
		useSortable( { id: block.uuid } );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.3 : 1,
	};

	const cls = [ styles.blockRow ];
	if ( selected ) cls.push( styles.blockRowSelected );

	return (
		<div ref={ setNodeRef } style={ style } className={ cls.join( ' ' ) }>
			<button
				type="button"
				className={ styles.dragHandle }
				aria-label={ __( 'Drag to reorder', 'biolink-pro' ) }
				{ ...attributes }
				{ ...listeners }
			>
				⋮⋮
			</button>
			<button type="button" className={ styles.blockBody } onClick={ onSelect }>
				<BlockRowContent block={ block } />
			</button>
			{ saving && <span className={ styles.savingDot } aria-hidden="true" /> }
			<button
				type="button"
				className={ styles.deleteButton }
				onClick={ onDelete }
				aria-label={ __( 'Delete block', 'biolink-pro' ) }
			>
				×
			</button>
		</div>
	);
}

function BlockRowContent( { block }: { block: BioBlock } ) {
	const meta = findBlockMeta( block.type );
	return (
		<>
			<span className={ styles.blockIcon }>{ meta?.icon ?? '◻' }</span>
			<span className={ styles.blockLabel }>
				<span className={ styles.blockType }>{ meta?.label ?? block.type }</span>
				<span className={ styles.blockPreview }>
					{ meta ? meta.preview( block.data as BlockData ) : '' }
				</span>
			</span>
		</>
	);
}

function InspectorEditor( {
	block,
	onChange,
}: {
	block: BioBlock;
	onChange: ( uuid: string, next: BlockData ) => void;
} ) {
	const meta = findBlockMeta( block.type );
	if ( ! meta ) {
		return (
			<div className={ styles.inspectorEmpty }>
				{ __( 'Unknown block type.', 'biolink-pro' ) }
			</div>
		);
	}
	const Editor = meta.Editor;
	return (
		<Editor
			data={ block.data as BlockData }
			onChange={ ( next ) => onChange( block.uuid, next ) }
		/>
	);
}

function messageFor( err: unknown, fallback: string ): string {
	if ( err instanceof Error ) return err.message;
	return fallback;
}
