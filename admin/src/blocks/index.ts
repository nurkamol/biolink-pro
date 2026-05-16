import type { ComponentType } from 'react';
import { __ } from '@wordpress/i18n';
import { ButtonEditor, type ButtonData } from './ButtonEditor';
import { ContactFormEditor, type ContactFormData } from './ContactFormEditor';
import { CountdownEditor, type CountdownData } from './CountdownEditor';
import { DividerEditor, type DividerData } from './DividerEditor';
import { DonationEditor, type DonationData } from './DonationEditor';
import { FaqEditor, type FaqData } from './FaqEditor';
import { HtmlEmbedEditor, type HtmlEmbedData } from './HtmlEmbedEditor';
import { ImageGalleryEditor, type ImageGalleryData } from './ImageGalleryEditor';
import { LinkEditor, type LinkData } from './LinkEditor';
import { MapEditor, type MapData } from './MapEditor';
import { NewsletterEditor, type NewsletterData } from './NewsletterEditor';
import { ProductCardEditor, type ProductCardData } from './ProductCardEditor';
import { RichTextEditor, type RichTextData } from './RichTextEditor';
import { SocialIconsEditor, type SocialIconsData } from './SocialIconsEditor';
import { SpotifyEditor, type SpotifyData } from './SpotifyEditor';
import { TiktokEditor, type TiktokData } from './TiktokEditor';
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
	| SpotifyData
	| TiktokData
	| FaqData
	| CountdownData
	| ProductCardData
	| HtmlEmbedData
	| MapData
	| NewsletterData
	| DonationData
	| ContactFormData
	| Record< string, unknown >;

export interface BlockMeta {
	slug: string;
	label: string;
	icon: string;
	defaultData: BlockData;
	Editor: ComponentType< { data: BlockData; onChange: ( next: BlockData ) => void } >;
	preview: ( data: BlockData ) => string;
	group: 'core' | 'embed' | 'monetize' | 'engage';
}

type AnyEditor = ComponentType< { data: BlockData; onChange: ( n: BlockData ) => void } >;

export function getBlockCatalog(): BlockMeta[] {
	return [
		// --- Core ---
		{
			slug: 'link',
			label: __( 'Link', 'biolink-pro' ),
			icon: '🔗',
			group: 'core',
			defaultData: { label: __( 'New link', 'biolink-pro' ), url: '', icon: 'link' },
			Editor: LinkEditor as AnyEditor,
			preview: ( d ) => ( d as LinkData ).label ?? __( '(empty link)', 'biolink-pro' ),
		},
		{
			slug: 'button',
			label: __( 'Button', 'biolink-pro' ),
			icon: '🔘',
			group: 'core',
			defaultData: { label: __( 'Click me', 'biolink-pro' ), url: '', variant: 'primary', size: 'md' },
			Editor: ButtonEditor as AnyEditor,
			preview: ( d ) => ( d as ButtonData ).label ?? __( '(empty button)', 'biolink-pro' ),
		},
		{
			slug: 'social_icons',
			label: __( 'Social Icons', 'biolink-pro' ),
			icon: '⭐',
			group: 'core',
			defaultData: { items: [] },
			Editor: SocialIconsEditor as AnyEditor,
			preview: ( d ) => {
				const items = ( d as SocialIconsData ).items ?? [];
				return items.length === 0
					? __( '(no platforms)', 'biolink-pro' )
					: items.map( ( i ) => i.platform ).join( ' · ' );
			},
		},
		{
			slug: 'rich_text',
			label: __( 'Rich Text', 'biolink-pro' ),
			icon: '📝',
			group: 'core',
			defaultData: { markdown: '', align: 'left' },
			Editor: RichTextEditor as AnyEditor,
			preview: ( d ) => {
				const md = ( d as RichTextData ).markdown ?? '';
				return md.split( '\n' )[ 0 ]?.slice( 0, 60 ) || __( '(empty)', 'biolink-pro' );
			},
		},
		{
			slug: 'divider',
			label: __( 'Divider', 'biolink-pro' ),
			icon: '➖',
			group: 'core',
			defaultData: { style: 'line', color: '#dcdcde', spacing: 'md' },
			Editor: DividerEditor as AnyEditor,
			preview: ( d ) => ( d as DividerData ).style ?? 'line',
		},

		// --- Embeds ---
		{
			slug: 'image_gallery',
			label: __( 'Image Gallery', 'biolink-pro' ),
			icon: '🖼',
			group: 'embed',
			defaultData: { ids: [], layout: 'grid', size: 'medium' },
			Editor: ImageGalleryEditor as AnyEditor,
			preview: ( d ) => {
				const count = ( ( d as ImageGalleryData ).ids ?? [] ).length;
				return count === 0 ? __( '(no images)', 'biolink-pro' ) : `${ count } image(s)`;
			},
		},
		{
			slug: 'video',
			label: __( 'Video', 'biolink-pro' ),
			icon: '🎬',
			group: 'embed',
			defaultData: { url: '', controls: true },
			Editor: VideoEditor as AnyEditor,
			preview: ( d ) => ( d as VideoData ).url || __( '(no video)', 'biolink-pro' ),
		},
		{
			slug: 'youtube',
			label: __( 'YouTube', 'biolink-pro' ),
			icon: '▶️',
			group: 'embed',
			defaultData: { url: '', title: '' },
			Editor: YouTubeEditor as AnyEditor,
			preview: ( d ) => ( d as YouTubeData ).url || __( '(no video)', 'biolink-pro' ),
		},
		{
			slug: 'spotify',
			label: __( 'Spotify', 'biolink-pro' ),
			icon: '🎵',
			group: 'embed',
			defaultData: { url: '', height: 'normal', theme: 'default' },
			Editor: SpotifyEditor as AnyEditor,
			preview: ( d ) => ( d as SpotifyData ).url || __( '(no track)', 'biolink-pro' ),
		},
		{
			slug: 'tiktok',
			label: __( 'TikTok', 'biolink-pro' ),
			icon: '🎶',
			group: 'embed',
			defaultData: { url: '' },
			Editor: TiktokEditor as AnyEditor,
			preview: ( d ) => ( d as TiktokData ).url || __( '(no video)', 'biolink-pro' ),
		},
		{
			slug: 'map',
			label: __( 'Map', 'biolink-pro' ),
			icon: '📍',
			group: 'embed',
			defaultData: { lat: '', lng: '', zoom: 14, label: '' },
			Editor: MapEditor as AnyEditor,
			preview: ( d ) => {
				const m = d as MapData;
				return m.lat && m.lng ? `${ m.lat }, ${ m.lng }` : __( '(no coordinates)', 'biolink-pro' );
			},
		},
		{
			slug: 'html_embed',
			label: __( 'HTML Embed', 'biolink-pro' ),
			icon: '⌨️',
			group: 'embed',
			defaultData: { html: '' },
			Editor: HtmlEmbedEditor as AnyEditor,
			preview: ( d ) => {
				const h = ( d as HtmlEmbedData ).html ?? '';
				return h ? `${ h.slice( 0, 40 ) }…` : __( '(empty)', 'biolink-pro' );
			},
		},

		// --- Engage ---
		{
			slug: 'faq',
			label: __( 'FAQ', 'biolink-pro' ),
			icon: '❓',
			group: 'engage',
			defaultData: { items: [] },
			Editor: FaqEditor as AnyEditor,
			preview: ( d ) => {
				const items = ( d as FaqData ).items ?? [];
				return items.length === 0
					? __( '(no questions)', 'biolink-pro' )
					: `${ items.length } question(s)`;
			},
		},
		{
			slug: 'countdown',
			label: __( 'Countdown', 'biolink-pro' ),
			icon: '⏰',
			group: 'engage',
			defaultData: {
				label: '',
				target: new Date( Date.now() + 7 * 24 * 60 * 60 * 1000 ).toISOString(),
				expired_message: __( "We're live!", 'biolink-pro' ),
			},
			Editor: CountdownEditor as AnyEditor,
			preview: ( d ) => ( d as CountdownData ).target ?? __( '(no target)', 'biolink-pro' ),
		},
		{
			slug: 'newsletter',
			label: __( 'Newsletter', 'biolink-pro' ),
			icon: '✉️',
			group: 'engage',
			defaultData: {
				heading: __( 'Subscribe', 'biolink-pro' ),
				placeholder: 'you@example.com',
				button_text: __( 'Subscribe', 'biolink-pro' ),
				success_message: __( 'Thanks! Check your inbox.', 'biolink-pro' ),
			},
			Editor: NewsletterEditor as AnyEditor,
			preview: ( d ) =>
				( d as NewsletterData ).heading || __( 'Subscribe', 'biolink-pro' ),
		},
		{
			slug: 'contact_form',
			label: __( 'Contact Form', 'biolink-pro' ),
			icon: '📨',
			group: 'engage',
			defaultData: {
				heading: __( 'Get in touch', 'biolink-pro' ),
				button_text: __( 'Send message', 'biolink-pro' ),
				success_message: __( "Thanks! I'll get back to you soon.", 'biolink-pro' ),
			},
			Editor: ContactFormEditor as AnyEditor,
			preview: ( d ) => ( d as ContactFormData ).heading || __( 'Contact', 'biolink-pro' ),
		},

		// --- Monetize ---
		{
			slug: 'product_card',
			label: __( 'Product Card', 'biolink-pro' ),
			icon: '🛍',
			group: 'monetize',
			defaultData: {
				name: '',
				cta_label: __( 'Buy now', 'biolink-pro' ),
				cta_url: '',
				provider: 'link',
				currency: 'USD',
			},
			Editor: ProductCardEditor as AnyEditor,
			preview: ( d ) => ( d as ProductCardData ).name || __( '(no product)', 'biolink-pro' ),
		},
		{
			slug: 'donation',
			label: __( 'Donation', 'biolink-pro' ),
			icon: '💛',
			group: 'monetize',
			defaultData: {
				heading: __( 'Support my work', 'biolink-pro' ),
				cta_label: __( 'Donate', 'biolink-pro' ),
				cta_url: '',
				currency: 'USD',
				amounts: [],
				provider: 'stripe',
			},
			Editor: DonationEditor as AnyEditor,
			preview: ( d ) =>
				( d as DonationData ).heading || __( 'Donation', 'biolink-pro' ),
		},
	];
}

export function findBlockMeta( slug: string ): BlockMeta | undefined {
	return getBlockCatalog().find( ( b ) => b.slug === slug );
}
