import { __ } from '@wordpress/i18n';
import {
	DndContext,
	KeyboardSensor,
	PointerSensor,
	useSensor,
	useSensors,
	type DragEndEvent,
} from '@dnd-kit/core';
import {
	SortableContext,
	arrayMove,
	sortableKeyboardCoordinates,
	useSortable,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { useCallback, useEffect, useState } from 'react';
import { BlocksApi, type BioBlock } from '../../api/client';
import { findBlockMeta, type BlockData } from '../../blocks';
import { AddBlockModal } from '../../components/ui/AddBlockModal';
import { PageHeaderEditor } from '../../components/builder/PageHeaderEditor';
import { useBuilder } from './BuilderContext';
import styles from './LinksPage.module.css';

export function LinksPage() {
	const { page, setBlocks, setSettings } = useBuilder();
	const [ selectedUuid, setSelectedUuid ] = useState< string | null >( null );
	const [ saving, setSaving ] = useState< string | null >( null );
	const [ error, setError ] = useState< string | null >( null );
	const [ showAdd, setShowAdd ] = useState( false );
	const [ editHeader, setEditHeader ] = useState( false );
	const [ avatarUrl, setAvatarUrl ] = useState< string | null >( null );

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 4 } } ),
		useSensor( KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates } )
	);

	const avatarId = page.settings.avatar_id ?? 0;
	useEffect( () => {
		if ( ! avatarId ) {
			setAvatarUrl( null );
			return;
		}
		void ( async () => {
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
		} )();
	}, [ avatarId ] );

	const handleAdd = useCallback(
		async ( slug: string ) => {
			const meta = findBlockMeta( slug );
			if ( ! meta ) return;
			setShowAdd( false );
			setError( null );
			try {
				const created = await BlocksApi.append( page.id, slug, meta.defaultData );
				setBlocks( [ ...page.blocks, created ] );
				setSelectedUuid( created.uuid );
			} catch ( err ) {
				setError( msg( err, __( 'Failed to add block.', 'biolink-pro' ) ) );
			}
		},
		[ page.id, page.blocks, setBlocks ]
	);

	const handleUpdate = useCallback(
		async ( uuid: string, nextData: BlockData ) => {
			const before = page.blocks;
			const next = page.blocks.map( ( b ) =>
				b.uuid === uuid ? { ...b, data: nextData as Record< string, unknown > } : b
			);
			setBlocks( next );
			setSaving( uuid );
			try {
				await BlocksApi.update( page.id, uuid, { data: nextData as Record< string, unknown > } );
			} catch ( err ) {
				setBlocks( before );
				setError( msg( err, __( 'Failed to save block.', 'biolink-pro' ) ) );
			} finally {
				setSaving( null );
			}
		},
		[ page.id, page.blocks, setBlocks ]
	);

	const handleDelete = useCallback(
		async ( uuid: string ) => {
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( __( 'Remove this block?', 'biolink-pro' ) ) ) return;
			const before = page.blocks;
			setBlocks( page.blocks.filter( ( b ) => b.uuid !== uuid ) );
			setSelectedUuid( null );
			try {
				await BlocksApi.remove( page.id, uuid );
			} catch ( err ) {
				setBlocks( before );
				setError( msg( err, __( 'Failed to delete block.', 'biolink-pro' ) ) );
			}
		},
		[ page.id, page.blocks, setBlocks ]
	);

	const handleToggleActive = useCallback(
		async ( uuid: string, next: boolean ) => {
			const block = page.blocks.find( ( b ) => b.uuid === uuid );
			if ( ! block ) return;
			const newData = { ...( block.data as Record< string, unknown > ), _active: next };
			await handleUpdate( uuid, newData as BlockData );
		},
		[ page.blocks, handleUpdate ]
	);

	const handleDragEnd = async ( event: DragEndEvent ) => {
		const { active, over } = event;
		if ( ! over || active.id === over.id ) return;
		const oldIndex = page.blocks.findIndex( ( b ) => b.uuid === active.id );
		const newIndex = page.blocks.findIndex( ( b ) => b.uuid === over.id );
		if ( oldIndex < 0 || newIndex < 0 ) return;
		const before = page.blocks;
		const next = arrayMove( page.blocks, oldIndex, newIndex );
		setBlocks( next );
		try {
			await BlocksApi.reorder( page.id, next.map( ( b ) => b.uuid ) );
		} catch ( err ) {
			setBlocks( before );
			setError( msg( err, __( 'Failed to reorder blocks.', 'biolink-pro' ) ) );
		}
	};

	const handle = ( page.settings.handle ?? '' ).replace( /^@/, '' );

	return (
		<div className={ styles.root }>
			{ error && <div className={ styles.errorBanner }>{ error }</div> }

			<section className={ styles.profileCard }>
				<div className={ styles.avatar }>
					{ avatarUrl ? <img src={ avatarUrl } alt="" /> : <span>👤</span> }
				</div>
				<div className={ styles.profileBody }>
					<p className={ styles.handle }>
						{ page.settings.headline || page.title || handle || __( '(no name)', 'biolink-pro' ) }
					</p>
					<p className={ styles.bio }>
						{ page.settings.subheadline || __( 'Add a short bio…', 'biolink-pro' ) }
					</p>
					<div className={ styles.socialRow }>
						<button
							type="button"
							className={ styles.editPencil }
							onClick={ () => setEditHeader( ( v ) => ! v ) }
							aria-label={ __( 'Edit profile', 'biolink-pro' ) }
							title={ __( 'Edit profile', 'biolink-pro' ) }
						>
							✎
						</button>
					</div>
				</div>
			</section>

			{ editHeader && (
				<section className={ styles.headerEditor }>
					<PageHeaderEditor settings={ page.settings } onChange={ setSettings } />
				</section>
			) }

			<button type="button" className={ styles.addBtn } onClick={ () => setShowAdd( true ) }>
				<span style={ { fontSize: 18 } }>+</span> { __( 'Add', 'biolink-pro' ) }
			</button>

			<div className={ styles.secondaryRow }>
				<button type="button" className={ styles.secondaryBtn } disabled title={ __( 'Coming soon', 'biolink-pro' ) }>
					⊞ { __( 'Add collection', 'biolink-pro' ) }
				</button>
				<button type="button" className={ styles.secondaryBtn } disabled title={ __( 'Coming soon', 'biolink-pro' ) }>
					{ __( 'View archive', 'biolink-pro' ) } ›
				</button>
			</div>

			{ page.blocks.length === 0 ? (
				<div className={ styles.emptyHero }>
					<p>{ __( 'No blocks yet. Click Add to insert your first link.', 'biolink-pro' ) }</p>
				</div>
			) : (
				<DndContext sensors={ sensors } onDragEnd={ handleDragEnd }>
					<SortableContext
						items={ page.blocks.map( ( b ) => b.uuid ) }
						strategy={ verticalListSortingStrategy }
					>
						<div className={ styles.list }>
							{ page.blocks.map( ( block ) => (
								<LinkRow
									key={ block.uuid }
									block={ block }
									selected={ selectedUuid === block.uuid }
									saving={ saving === block.uuid }
									onSelect={ () =>
										setSelectedUuid( ( prev ) =>
											prev === block.uuid ? null : block.uuid
										)
									}
									onUpdate={ handleUpdate }
									onDelete={ handleDelete }
									onToggleActive={ handleToggleActive }
								/>
							) ) }
						</div>
					</SortableContext>
				</DndContext>
			) }

			<AddBlockModal open={ showAdd } onClose={ () => setShowAdd( false ) } onPick={ handleAdd } />
		</div>
	);
}

interface LinkRowProps {
	block: BioBlock;
	selected: boolean;
	saving: boolean;
	onSelect: () => void;
	onUpdate: ( uuid: string, next: BlockData ) => void;
	onDelete: ( uuid: string ) => void;
	onToggleActive: ( uuid: string, next: boolean ) => void;
}

function LinkRow( {
	block,
	selected,
	saving,
	onSelect,
	onUpdate,
	onDelete,
	onToggleActive,
}: LinkRowProps ) {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable( {
		id: block.uuid,
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.3 : 1,
	};

	const meta = findBlockMeta( block.type );
	const data = block.data as Record< string, unknown >;
	const active = data._active !== false;

	const classes = [ styles.row ];
	if ( selected ) classes.push( styles.rowSelected );
	if ( ! active ) classes.push( styles.rowInactive );

	return (
		<div ref={ setNodeRef } style={ style } className={ classes.join( ' ' ) }>
			<button
				type="button"
				className={ styles.dragHandle }
				aria-label={ __( 'Drag to reorder', 'biolink-pro' ) }
				{ ...attributes }
				{ ...listeners }
			>
				⋮⋮
			</button>

			<div className={ styles.rowTop }>
				<button type="button" className={ styles.rowLabel } onClick={ onSelect }>
					<div className={ styles.rowTitle }>
						<span>{ meta?.icon ?? '◻' }</span>
						<span>{ meta ? meta.preview( data as BlockData ) || meta.label : block.type }</span>
					</div>
					<div className={ styles.rowSub }>
						{ ( data.url as string ) || meta?.label || '' }
					</div>
				</button>

				<label className={ styles.toggleSwitch } aria-label={ __( 'Toggle visible', 'biolink-pro' ) }>
					<input
						type="checkbox"
						checked={ active }
						onChange={ ( e ) => onToggleActive( block.uuid, e.target.checked ) }
					/>
					<span className={ styles.toggleSlider } />
				</label>
			</div>

			<div className={ styles.actionBar }>
				<button type="button" className={ styles.actionBtn } title={ __( 'Thumbnail', 'biolink-pro' ) } disabled>
					◌
				</button>
				<button type="button" className={ styles.actionBtn } title={ __( 'Highlight', 'biolink-pro' ) } disabled>
					★
				</button>
				<button type="button" className={ styles.actionBtn } title={ __( 'Schedule', 'biolink-pro' ) } disabled>
					⏰
				</button>
				<button type="button" className={ styles.actionBtn } title={ __( 'Lock', 'biolink-pro' ) } disabled>
					🔒
				</button>
				<span className={ styles.actionChip }>
					{ saving ? __( 'Saving…', 'biolink-pro' ) : '' }
				</span>
				<button
					type="button"
					className={ `${ styles.actionBtn } ${ styles.deleteBtn }` }
					onClick={ () => onDelete( block.uuid ) }
					aria-label={ __( 'Delete', 'biolink-pro' ) }
				>
					🗑
				</button>
			</div>

			{ selected && meta && (
				<div className={ styles.inspector }>
					<meta.Editor
						data={ block.data as BlockData }
						onChange={ ( next ) => onUpdate( block.uuid, next ) }
					/>
				</div>
			) }
		</div>
	);
}

function msg( err: unknown, fallback: string ): string {
	return err instanceof Error ? err.message : fallback;
}
