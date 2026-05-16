/**
 * Thin typed wrapper around `wp.media` so block editors can open the
 * WordPress media library without each one re-declaring the global.
 *
 * Requires `wp_enqueue_media()` to have run server-side; Admin\Assets
 * enqueues it on every plugin admin screen.
 */

interface WPMediaAttachment {
	id: number;
	url: string;
	mime: string;
	type: string;
	subtype: string;
	alt: string;
	title: string;
	caption: string;
	sizes?: Record< string, { url: string; width: number; height: number } >;
}

interface WPMediaFrame {
	on( event: 'select', cb: () => void ): WPMediaFrame;
	open(): void;
	state(): { get( field: 'selection' ): { toJSON(): WPMediaAttachment[] } };
}

interface WPMedia {
	( opts: {
		title?: string;
		button?: { text: string };
		multiple?: boolean;
		library?: { type?: string };
	} ): WPMediaFrame;
}

declare global {
	interface Window {
		wp?: {
			media?: WPMedia;
		};
	}
}

export type MediaAttachment = WPMediaAttachment;

export interface PickOptions {
	title?: string;
	buttonText?: string;
	multiple?: boolean;
	type?: 'image' | 'video' | 'audio';
}

/**
 * Open the media library and resolve with the picked attachment(s).
 *
 * Rejects if `wp.media` isn't available (e.g. on a non-admin screen).
 */
export function pickMedia( opts: PickOptions = {} ): Promise< MediaAttachment[] > {
	return new Promise( ( resolve, reject ) => {
		const media = window.wp?.media;
		if ( ! media ) {
			reject( new Error( 'WordPress media frame is unavailable.' ) );
			return;
		}
		const frame = media( {
			title: opts.title,
			button: opts.buttonText ? { text: opts.buttonText } : undefined,
			multiple: opts.multiple ?? false,
			library: opts.type ? { type: opts.type } : undefined,
		} );
		frame.on( 'select', () => {
			const selection = frame.state().get( 'selection' ).toJSON();
			resolve( selection );
		} );
		frame.open();
	} );
}
