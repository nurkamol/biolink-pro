import type { ComponentType } from 'react';
import { __ } from '@wordpress/i18n';
import { ButtonEditor, type ButtonData } from './ButtonEditor';
import { DividerEditor, type DividerData } from './DividerEditor';
import { ImageGalleryEditor, type ImageGalleryData } from './ImageGalleryEditor';
import { LinkEditor, type LinkData } from './LinkEditor';
import { RichTextEditor, type RichTextData } from './RichTextEditor';
import { SocialIconsEditor, type SocialIconsData } from './SocialIconsEditor';
import { VideoEditor, type VideoData } from './VideoEditor';
import { YouTubeEditor, type YouTubeData } from './YouTubeEditor';

export type BlockData =
	| LinkData
	| ButtonData
	| SocialIconsData
	| ImageGalleryData
	| RichTextData
	| DividerData
	| VideoData
	| YouTubeData
	| Record< string, unknown >;

export interface BlockMeta {
	slug: string;
	label: string;
	icon: string;
	defaultData: BlockData;
	Editor: ComponentType< { data: BlockData; onChange: ( next: BlockData ) => void } >;
	preview: ( data: BlockData ) => string;
}

/**
 * Use a thunked getter so the i18n calls run after WP's translation runtime is ready.
 */
export function getBlockCatalog(): BlockMeta[] {
	return [
		{
			slug: 'link',
			label: __( 'Link', 'biolink-pro' ),
			icon: '🔗',
			defaultData: { label: __( 'New link', 'biolink-pro' ), url: '', icon: 'link' },
			Editor: LinkEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => ( d as LinkData ).label ?? __( '(empty link)', 'biolink-pro' ),
		},
		{
			slug: 'button',
			label: __( 'Button', 'biolink-pro' ),
			icon: '🔘',
			defaultData: { label: __( 'Click me', 'biolink-pro' ), url: '', variant: 'primary', size: 'md' },
			Editor: ButtonEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => ( d as ButtonData ).label ?? __( '(empty button)', 'biolink-pro' ),
		},
		{
			slug: 'social_icons',
			label: __( 'Social Icons', 'biolink-pro' ),
			icon: '⭐',
			defaultData: { items: [] },
			Editor: SocialIconsEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => {
				const items = ( d as SocialIconsData ).items ?? [];
				return items.length === 0
					? __( '(no platforms)', 'biolink-pro' )
					: items.map( ( i ) => i.platform ).join( ' · ' );
			},
		},
		{
			slug: 'image_gallery',
			label: __( 'Image Gallery', 'biolink-pro' ),
			icon: '🖼',
			defaultData: { ids: [], layout: 'grid', size: 'medium' },
			Editor: ImageGalleryEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => {
				const count = ( ( d as ImageGalleryData ).ids ?? [] ).length;
				return count === 0
					? __( '(no images)', 'biolink-pro' )
					: `${ count } image(s)`;
			},
		},
		{
			slug: 'rich_text',
			label: __( 'Rich Text', 'biolink-pro' ),
			icon: '📝',
			defaultData: { markdown: '', align: 'left' },
			Editor: RichTextEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => {
				const md = ( d as RichTextData ).markdown ?? '';
				return md.split( '\n' )[ 0 ]?.slice( 0, 60 ) || __( '(empty)', 'biolink-pro' );
			},
		},
		{
			slug: 'divider',
			label: __( 'Divider', 'biolink-pro' ),
			icon: '➖',
			defaultData: { style: 'line', color: '#dcdcde', spacing: 'md' },
			Editor: DividerEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => ( d as DividerData ).style ?? 'line',
		},
		{
			slug: 'video',
			label: __( 'Video', 'biolink-pro' ),
			icon: '🎬',
			defaultData: { url: '', controls: true },
			Editor: VideoEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => ( d as VideoData ).url || __( '(no video)', 'biolink-pro' ),
		},
		{
			slug: 'youtube',
			label: __( 'YouTube', 'biolink-pro' ),
			icon: '▶️',
			defaultData: { url: '', title: '' },
			Editor: YouTubeEditor as ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >,
			preview: ( d ) => ( d as YouTubeData ).url || __( '(no video)', 'biolink-pro' ),
		},
	];
}

export function findBlockMeta( slug: string ): BlockMeta | undefined {
	return getBlockCatalog().find( ( b ) => b.slug === slug );
}
