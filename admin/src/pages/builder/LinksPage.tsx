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
import { useCallback, useEffect, useRef, useState } from 'react';
import { BlocksApi, type BioBlock } from '../../api/client';
import { findBlockMeta, type BlockData } from '../../blocks';
import { AddBlockModal } from '../../components/ui/AddBlockModal';
import {
	IconClock,
	IconGrip,
	IconImage,
	IconLock,
	IconPencil,
	IconPlus,
	IconStar,
	IconTrash,
} from '../../components/ui/Icons';
import { PageHeaderEditor } from '../../components/builder/PageHeaderEditor';
import { pickMedia } from '../../lib/mediaFrame';
import { useBuilder } from './BuilderContext';
import styles from './LinksPage.module.css';

type BlockMetaData = Record< string, unknown > & {
	_active?: boolean;
	_highlight?: boolean;
	_thumbnail_id?: number;
	_start_at?: string;
	_end_at?: string;
	_passcode?: string;
	_passcode_hash?: string;
};

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

	const patchMeta = useCallback(
		async ( uuid: string, patch: Partial< BlockMetaData > ) => {
			const block = page.blocks.find( ( b ) => b.uuid === uuid );
			if ( ! block ) return;
			const merged = { ...( block.data as Record< string, unknown > ), ...patch };
			// Strip nulls so we don't store empty fields.
			Object.keys( patch ).forEach( ( k ) => {
				if ( ( patch as Record< string, unknown > )[ k ] === null ) delete merged[ k ];
			} );
			await handleUpdate( uuid, merged as BlockData );
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
					{ avatarUrl ? (
						<img src={ avatarUrl } alt="" />
					) : (
						<span>{ ( handle || page.title || 'B' ).slice( 0, 1 ).toUpperCase() }</span>
					) }
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
						>
							<IconPencil /> { __( 'Edit profile', 'biolink-pro' ) }
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
				<IconPlus /> { __( 'Add', 'biolink-pro' ) }
			</button>

			<div className={ styles.secondaryRow }>
				<button type="button" className={ styles.secondaryBtn } disabled title={ __( 'Coming soon', 'biolink-pro' ) }>
					{ __( '+ Add collection', 'biolink-pro' ) }
				</button>
				<button type="button" className={ styles.secondaryBtn } disabled title={ __( 'Coming soon', 'biolink-pro' ) }>
					{ __( 'View archive ›', 'biolink-pro' ) }
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
									onPatchMeta={ patchMeta }
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
	onPatchMeta: ( uuid: string, patch: Partial< BlockMetaData > ) => Promise< void >;
}

type PopoverKind = null | 'schedule' | 'lock';

function LinkRow( { block, selected, saving, onSelect, onUpdate, onDelete, onPatchMeta }: LinkRowProps ) {
	const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable( {
		id: block.uuid,
	} );

	const style = {
		transform: CSS.Transform.toString( transform ),
		transition,
		opacity: isDragging ? 0.3 : 1,
	};

	const meta = findBlockMeta( block.type );
	const data = block.data as BlockMetaData;
	const active = data._active !== false;
	const highlight = data._highlight === true;
	const thumbId = ( data._thumbnail_id as number ) || 0;
	const scheduled = Boolean( data._start_at || data._end_at );
	const locked = Boolean( data._passcode_hash );

	const [ popover, setPopover ] = useState< PopoverKind >( null );
	const [ thumbUrl, setThumbUrl ] = useState< string | null >( null );

	useEffect( () => {
		if ( ! thumbId ) {
			setThumbUrl( null );
			return;
		}
		void ( async () => {
			try {
				const res = await fetch(
					`/wp-json/wp/v2/media/${ thumbId }?_fields=source_url,media_details`,
					{ headers: { 'X-WP-Nonce': window.BIOLINK_PRO.restNonce } }
				);
				if ( ! res.ok ) return;
				const json = ( await res.json() ) as {
					source_url: string;
					media_details?: { sizes?: { thumbnail?: { source_url?: string } } };
				};
				setThumbUrl( json.media_details?.sizes?.thumbnail?.source_url ?? json.source_url );
			} catch {
				// non-fatal
			}
		} )();
	}, [ thumbId ] );

	const classes = [ styles.row ];
	if ( selected ) classes.push( styles.rowSelected );
	if ( ! active ) classes.push( styles.rowInactive );

	const handlePickThumb = async () => {
		try {
			const picked = await pickMedia( {
				title: __( 'Select thumbnail', 'biolink-pro' ),
				buttonText: __( 'Use image', 'biolink-pro' ),
				multiple: false,
				type: 'image',
			} );
			if ( picked[ 0 ] ) {
				await onPatchMeta( block.uuid, { _thumbnail_id: picked[ 0 ].id } );
				setThumbUrl( picked[ 0 ].url );
			}
		} catch {
			// cancelled
		}
	};

	const handleClearThumb = async () => {
		await onPatchMeta( block.uuid, { _thumbnail_id: null as unknown as number } );
		setThumbUrl( null );
	};

	const handleSaveSchedule = async ( startAt: string, endAt: string ) => {
		await onPatchMeta( block.uuid, {
			_start_at: startAt || ( null as unknown as string ),
			_end_at: endAt || ( null as unknown as string ),
		} );
		setPopover( null );
	};

	const handleSavePasscode = async ( passcode: string ) => {
		// Server detects _passcode and hashes it into _passcode_hash; empty
		// string clears any existing lock.
		await onPatchMeta( block.uuid, { _passcode: passcode } );
		setPopover( null );
	};

	return (
		<div ref={ setNodeRef } style={ style } className={ classes.join( ' ' ) }>
			<button
				type="button"
				className={ styles.dragHandle }
				aria-label={ __( 'Drag to reorder', 'biolink-pro' ) }
				{ ...attributes }
				{ ...listeners }
			>
				<IconGrip />
			</button>

			<div className={ styles.rowTop }>
				<button type="button" className={ styles.rowLabel } onClick={ onSelect }>
					<div className={ styles.rowTitle }>
						{ thumbUrl ? (
							<img src={ thumbUrl } alt="" className={ styles.thumbPreview } />
						) : (
							<span>{ meta?.icon ?? '◻' }</span>
						) }
						<span>{ meta ? meta.preview( data as BlockData ) || meta.label : block.type }</span>
						<span className={ styles.rowTypeBadge }>{ meta?.label ?? block.type }</span>
					</div>
					<div className={ styles.rowSub }>
						{ ( data.url as string ) || meta?.label || '' }
					</div>
				</button>

				<label className={ styles.toggleSwitch } aria-label={ __( 'Toggle visible', 'biolink-pro' ) }>
					<input
						type="checkbox"
						checked={ active }
						onChange={ ( e ) => onPatchMeta( block.uuid, { _active: e.target.checked } ) }
					/>
					<span className={ styles.toggleSlider } />
				</label>
			</div>

			<div className={ styles.actionBar }>
				<button
					type="button"
					className={ `${ styles.actionBtn } ${ thumbUrl ? styles.actionBtnActive : '' }` }
					onClick={ thumbUrl ? handleClearThumb : handlePickThumb }
					title={ thumbUrl ? __( 'Remove thumbnail', 'biolink-pro' ) : __( 'Add thumbnail', 'biolink-pro' ) }
					aria-label={ __( 'Thumbnail', 'biolink-pro' ) }
				>
					<IconImage />
				</button>
				<button
					type="button"
					className={ `${ styles.actionBtn } ${ highlight ? styles.actionBtnActive : '' }` }
					onClick={ () => onPatchMeta( block.uuid, { _highlight: ! highlight } ) }
					title={ highlight ? __( 'Remove highlight', 'biolink-pro' ) : __( 'Highlight this link', 'biolink-pro' ) }
					aria-label={ __( 'Highlight', 'biolink-pro' ) }
				>
					<IconStar filled={ highlight } />
				</button>
				<button
					type="button"
					className={ `${ styles.actionBtn } ${ scheduled ? styles.actionBtnActive : '' }` }
					onClick={ () => setPopover( ( v ) => ( v === 'schedule' ? null : 'schedule' ) ) }
					title={ scheduled ? __( 'Edit schedule', 'biolink-pro' ) : __( 'Schedule', 'biolink-pro' ) }
					aria-label={ __( 'Schedule', 'biolink-pro' ) }
				>
					<IconClock />
				</button>
				<button
					type="button"
					className={ `${ styles.actionBtn } ${ locked ? styles.actionBtnActive : '' }` }
					onClick={ () => setPopover( ( v ) => ( v === 'lock' ? null : 'lock' ) ) }
					title={ locked ? __( 'Edit passcode', 'biolink-pro' ) : __( 'Lock with passcode', 'biolink-pro' ) }
					aria-label={ __( 'Lock', 'biolink-pro' ) }
				>
					<IconLock />
				</button>
				<span className={ styles.actionChip }>
					{ saving ? __( 'Saving…', 'biolink-pro' ) : '' }
				</span>
				<button
					type="button"
					className={ `${ styles.actionBtn } ${ styles.deleteBtn }` }
					onClick={ () => onDelete( block.uuid ) }
					aria-label={ __( 'Delete', 'biolink-pro' ) }
					title={ __( 'Delete', 'biolink-pro' ) }
				>
					<IconTrash />
				</button>

				{ popover === 'schedule' && (
					<SchedulePopover
						startAt={ ( data._start_at as string ) || '' }
						endAt={ ( data._end_at as string ) || '' }
						onSave={ handleSaveSchedule }
						onClose={ () => setPopover( null ) }
					/>
				) }
				{ popover === 'lock' && (
					<LockPopover
						locked={ locked }
						onSave={ handleSavePasscode }
						onClose={ () => setPopover( null ) }
					/>
				) }
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

interface SchedulePopoverProps {
	startAt: string;
	endAt: string;
	onSave: ( startAt: string, endAt: string ) => Promise< void >;
	onClose: () => void;
}

function SchedulePopover( { startAt, endAt, onSave, onClose }: SchedulePopoverProps ) {
	const popRef = useRef< HTMLDivElement >( null );
	const [ s, setS ] = useState( toLocalInput( startAt ) );
	const [ e, setE ] = useState( toLocalInput( endAt ) );

	useEffect( () => {
		const onDoc = ( ev: MouseEvent ) => {
			if ( ! popRef.current?.contains( ev.target as Node ) ) onClose();
		};
		setTimeout( () => document.addEventListener( 'mousedown', onDoc ), 0 );
		return () => document.removeEventListener( 'mousedown', onDoc );
	}, [ onClose ] );

	const save = () => {
		void onSave( fromLocalInput( s ), fromLocalInput( e ) );
	};
	const clear = () => {
		setS( '' );
		setE( '' );
		void onSave( '', '' );
	};

	return (
		<div ref={ popRef } className={ styles.popover }>
			<h3 className={ styles.popoverTitle }>{ __( 'Schedule', 'biolink-pro' ) }</h3>
			<p className={ styles.popoverHint }>
				{ __( 'The block is hidden outside this window. Times are in your site timezone.', 'biolink-pro' ) }
			</p>
			<div className={ styles.popoverField }>
				<label>{ __( 'Visible from', 'biolink-pro' ) }</label>
				<input type="datetime-local" value={ s } onChange={ ( ev ) => setS( ev.target.value ) } />
			</div>
			<div className={ styles.popoverField }>
				<label>{ __( 'Visible until', 'biolink-pro' ) }</label>
				<input type="datetime-local" value={ e } onChange={ ( ev ) => setE( ev.target.value ) } />
			</div>
			<div className={ styles.popoverActions }>
				<button type="button" onClick={ clear }>
					{ __( 'Clear', 'biolink-pro' ) }
				</button>
				<button type="button" className={ styles.popoverPrimary } onClick={ save }>
					{ __( 'Save schedule', 'biolink-pro' ) }
				</button>
			</div>
		</div>
	);
}

interface LockPopoverProps {
	locked: boolean;
	onSave: ( passcode: string ) => Promise< void >;
	onClose: () => void;
}

function LockPopover( { locked, onSave, onClose }: LockPopoverProps ) {
	const popRef = useRef< HTMLDivElement >( null );
	const [ pass, setPass ] = useState( '' );

	useEffect( () => {
		const onDoc = ( ev: MouseEvent ) => {
			if ( ! popRef.current?.contains( ev.target as Node ) ) onClose();
		};
		setTimeout( () => document.addEventListener( 'mousedown', onDoc ), 0 );
		return () => document.removeEventListener( 'mousedown', onDoc );
	}, [ onClose ] );

	const save = () => {
		if ( pass.length < 1 ) return;
		void onSave( pass );
	};
	const clear = () => {
		void onSave( '' );
	};

	return (
		<div ref={ popRef } className={ styles.popover }>
			<h3 className={ styles.popoverTitle }>
				{ locked ? __( 'Change passcode', 'biolink-pro' ) : __( 'Lock with passcode', 'biolink-pro' ) }
			</h3>
			<p className={ styles.popoverHint }>
				{ locked
					? __( 'A passcode is currently set. Enter a new one to change it, or remove the lock.', 'biolink-pro' )
					: __( 'Visitors must enter this passcode before being redirected to the link.', 'biolink-pro' ) }
			</p>
			<div className={ styles.popoverField }>
				<label htmlFor="biolink-lock-pass">
					{ locked ? __( 'New passcode', 'biolink-pro' ) : __( 'Passcode', 'biolink-pro' ) }
				</label>
				<input
					id="biolink-lock-pass"
					type="password"
					autoComplete="new-password"
					value={ pass }
					onChange={ ( e ) => setPass( e.target.value ) }
					onKeyDown={ ( e ) => {
						if ( e.key === 'Enter' ) save();
					} }
					placeholder={ __( 'At least 4 characters', 'biolink-pro' ) }
				/>
			</div>
			<div className={ styles.popoverActions }>
				{ locked ? (
					<button type="button" onClick={ clear }>
						{ __( 'Remove lock', 'biolink-pro' ) }
					</button>
				) : (
					<button type="button" onClick={ onClose }>
						{ __( 'Cancel', 'biolink-pro' ) }
					</button>
				) }
				<button
					type="button"
					className={ styles.popoverPrimary }
					onClick={ save }
					disabled={ pass.length === 0 }
				>
					{ locked ? __( 'Update', 'biolink-pro' ) : __( 'Save passcode', 'biolink-pro' ) }
				</button>
			</div>
		</div>
	);
}

function toLocalInput( iso: string ): string {
	if ( ! iso ) return '';
	// Accept either YYYY-MM-DDTHH:MM or full ISO; strip to minute precision.
	const m = iso.match( /^(\d{4}-\d{2}-\d{2})[T ](\d{2}:\d{2})/ );
	return m ? `${ m[ 1 ] }T${ m[ 2 ] }` : '';
}

function fromLocalInput( v: string ): string {
	if ( ! v ) return '';
	// Append :00 seconds so PHP's strtotime is happy without a timezone suffix
	// (treated as site-local time).
	return v.length === 16 ? `${ v }:00` : v;
}

function msg( err: unknown, fallback: string ): string {
	return err instanceof Error ? err.message : fallback;
}
