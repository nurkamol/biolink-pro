import apiFetch from '@wordpress/api-fetch';

// WP already installs a nonce middleware (from wpApiSettings) and a root URL
// middleware pointing at `/wp-json/` when wp-api-fetch is enqueued. We only
// need to ensure our nonce stays valid if the page outlives the cookie.
apiFetch.use( apiFetch.createNonceMiddleware( window.BIOLINK_PRO.restNonce ) );

const NS = '/biolink/v1';

export interface BioBlock {
	uuid: string;
	type: string;
	data: Record< string, unknown >;
}

export interface BioPageSettings {
	avatar_id?: number;
	handle?: string;
	headline?: string;
	subheadline?: string;
	hide_name?: boolean;
	bg_type?: 'theme' | 'color' | 'gradient' | 'image';
	bg_color?: string;
	bg_gradient_from?: string;
	bg_gradient_to?: string;
	bg_gradient_angle?: number;
	bg_image_id?: number;
	bg_overlay?: number;
	accent_color?: string;
	accent_text_color?: string;
	button_shape?: '' | 'pill' | 'rounded' | 'square';
	button_style?: '' | 'filled' | 'outline' | 'glass';
	[ key: string ]: unknown;
}

export interface BioPage {
	id: number;
	title: string;
	slug: string;
	status: string;
	author: number;
	created: string;
	modified: string;
	url: string;
	theme: string;
	settings: BioPageSettings;
	blocks: BioBlock[];
	seo: Record< string, unknown >;
}

export interface ThemePreset {
	slug: string;
	label: string;
	description: string;
	background: { type: 'color' | 'gradient' | 'image'; value: string };
	swatch: string;
	tokens: {
		text: string;
		muted: string;
		accent: string;
		accentText: string;
		surface: string;
		border: string;
		buttonShape: 'pill' | 'rounded' | 'square';
		buttonStyle: 'filled' | 'outline' | 'glass';
	};
}

export const ThemesApi = {
	list(): Promise< ThemePreset[] > {
		return request< ThemePreset[] >( `${ NS }/themes` );
	},
};

export interface AnalyticsSummary {
	views: number;
	clicks: number;
	unique_visitors: number;
	ctr: number;
}

export interface AnalyticsPoint {
	date: string;
	views: number;
	clicks: number;
}

export interface AnalyticsLink {
	link_id: number;
	label: string;
	url: string;
	clicks: number;
}

export interface AnalyticsBucket {
	bucket: string;
	count: number;
}

export interface AnalyticsRange {
	from?: string;
	to?: string;
}

function range( params: AnalyticsRange ): string {
	const q = new URLSearchParams();
	if ( params.from ) q.set( 'from', params.from );
	if ( params.to ) q.set( 'to', params.to );
	const qs = q.toString();
	return qs ? `?${ qs }` : '';
}

export const AnalyticsApi = {
	summary( id: number, r: AnalyticsRange = {} ): Promise< AnalyticsSummary > {
		return request< AnalyticsSummary >( `${ NS }/analytics/pages/${ id }/summary${ range( r ) }` );
	},
	timeseries( id: number, r: AnalyticsRange = {} ): Promise< AnalyticsPoint[] > {
		return request< AnalyticsPoint[] >( `${ NS }/analytics/pages/${ id }/timeseries${ range( r ) }` );
	},
	links( id: number, r: AnalyticsRange = {} ): Promise< AnalyticsLink[] > {
		return request< AnalyticsLink[] >( `${ NS }/analytics/pages/${ id }/links${ range( r ) }` );
	},
	devices( id: number, r: AnalyticsRange = {} ): Promise< AnalyticsBucket[] > {
		return request< AnalyticsBucket[] >( `${ NS }/analytics/pages/${ id }/devices${ range( r ) }` );
	},
	geo( id: number, r: AnalyticsRange = {} ): Promise< AnalyticsBucket[] > {
		return request< AnalyticsBucket[] >( `${ NS }/analytics/pages/${ id }/geo${ range( r ) }` );
	},
	referrers( id: number, r: AnalyticsRange = {} ): Promise< AnalyticsBucket[] > {
		return request< AnalyticsBucket[] >( `${ NS }/analytics/pages/${ id }/referrers${ range( r ) }` );
	},
	exportCsvUrl( id: number, r: AnalyticsRange = {} ): string {
		return `${ window.BIOLINK_PRO.restBase }analytics/pages/${ id }/export.csv${ range( r ) }${ range( r ) ? '&' : '?' }_wpnonce=${ encodeURIComponent( window.BIOLINK_PRO.restNonce ) }`;
	},
};

export interface BioTemplate {
	slug: string;
	label: string;
	description: string;
	preview: string;
	theme: string;
	settings: Record< string, unknown >;
	blocks: Partial< BioBlock >[];
}

export const TemplatesApi = {
	list(): Promise< BioTemplate[] > {
		return request< BioTemplate[] >( `${ NS }/templates` );
	},
	apply( slug: string ): Promise< BioPage > {
		return request< BioPage >( `${ NS }/templates/${ slug }/apply`, { method: 'POST' } );
	},
};

export const SettingsApi = {
	get(): Promise< { general: Record< string, unknown >; integrations: Record< string, unknown > } > {
		return request( `${ NS }/settings` );
	},
	update( payload: Record< string, unknown > ): Promise< { ok: boolean } > {
		return request( `${ NS }/settings`, { method: 'PATCH', data: payload } );
	},
};

export const AiApi = {
	bio( prompt: string ): Promise< { suggestions: string[] } > {
		return request( `${ NS }/ai/bio`, { method: 'POST', data: { prompt } } );
	},
	cta( prompt: string ): Promise< { suggestions: string[] } > {
		return request( `${ NS }/ai/cta`, { method: 'POST', data: { prompt } } );
	},
	theme( prompt: string ): Promise< { suggestions: string[] } > {
		return request( `${ NS }/ai/theme`, { method: 'POST', data: { prompt } } );
	},
};

export const PortabilityApi = {
	exportUrl( id: number ): string {
		return `${ window.BIOLINK_PRO.restBase }pages/${ id }/export?download=1&_wpnonce=${ encodeURIComponent(
			window.BIOLINK_PRO.restNonce
		) }`;
	},
	importJson( payload: unknown ): Promise< BioPage > {
		return request< BioPage >( `${ NS }/pages/import`, { method: 'POST', data: payload } );
	},
};

export interface BioBlockType {
	slug: string;
	label: string;
	icon: string;
	schema: Record< string, unknown >;
}

export interface PageListParams {
	page?: number;
	perPage?: number;
	status?: string;
	search?: string;
}

export interface CreatePagePayload {
	title: string;
	slug?: string;
	status?: BioPage[ 'status' ];
	theme?: string;
	settings?: Record< string, unknown >;
	blocks?: Partial< BioBlock >[];
}

export interface UpdatePagePayload extends Partial< CreatePagePayload > {}

async function request< T >( path: string, init: Parameters< typeof apiFetch >[ 0 ] = {} ): Promise< T > {
	return apiFetch< T >( { path, ...init } );
}

export const PagesApi = {
	list( params: PageListParams = {} ): Promise< BioPage[] > {
		const query = new URLSearchParams();
		if ( params.page ) query.set( 'page', String( params.page ) );
		if ( params.perPage ) query.set( 'per_page', String( params.perPage ) );
		if ( params.status ) query.set( 'status', params.status );
		if ( params.search ) query.set( 'search', params.search );
		const qs = query.toString();
		return request< BioPage[] >( `${ NS }/pages${ qs ? `?${ qs }` : '' }` );
	},

	get( id: number ): Promise< BioPage > {
		return request< BioPage >( `${ NS }/pages/${ id }` );
	},

	create( payload: CreatePagePayload ): Promise< BioPage > {
		return request< BioPage >( `${ NS }/pages`, { method: 'POST', data: payload } );
	},

	update( id: number, payload: UpdatePagePayload ): Promise< BioPage > {
		return request< BioPage >( `${ NS }/pages/${ id }`, { method: 'PATCH', data: payload } );
	},

	remove( id: number, force = false ): Promise< { deleted: boolean; id: number } > {
		const query = force ? '?force=1' : '';
		return request< { deleted: boolean; id: number } >( `${ NS }/pages/${ id }${ query }`, {
			method: 'DELETE',
		} );
	},

	duplicate( id: number ): Promise< BioPage > {
		return request< BioPage >( `${ NS }/pages/${ id }/duplicate`, { method: 'POST' } );
	},

	publish( id: number, when: string = 'now' ): Promise< BioPage > {
		return request< BioPage >( `${ NS }/pages/${ id }/publish`, {
			method: 'POST',
			data: { publish: when },
		} );
	},
};

export interface Release {
	tag: string;
	version: string;
	name: string;
	date: string;
	body_html: string;
	html_url: string;
	is_current: boolean;
	is_newer: boolean;
	download_url: string | null;
}

export interface UpdateStatus {
	current: string;
	latest: string | null;
	update_available: boolean;
	release_url: string | null;
	download_url: string | null;
	published_at: string | null;
}

export interface InstallUpdateResult {
	status: 'updated' | 'already_latest';
	current: string;
	latest?: string;
	previous?: string;
	message: string;
}

export const ChangelogApi = {
	list( force = false ): Promise< Release[] > {
		return request< Release[] >( `${ NS }/changelog${ force ? '?force=1' : '' }` );
	},
	status( force = false ): Promise< UpdateStatus > {
		return request< UpdateStatus >( `${ NS }/changelog/update-status${ force ? '?force=1' : '' }` );
	},
	installUpdate(): Promise< InstallUpdateResult > {
		return request< InstallUpdateResult >( `${ NS }/changelog/install-update`, { method: 'POST' } );
	},
};

export const BlocksApi = {
	types(): Promise< BioBlockType[] > {
		return request< BioBlockType[] >( `${ NS }/blocks` );
	},

	append( pageId: number, type: string, data: Record< string, unknown > = {} ): Promise< BioBlock > {
		return request< BioBlock >( `${ NS }/pages/${ pageId }/blocks`, {
			method: 'POST',
			data: { type, data },
		} );
	},

	update( pageId: number, uuid: string, patch: Partial< BioBlock > ): Promise< BioBlock > {
		return request< BioBlock >( `${ NS }/pages/${ pageId }/blocks/${ uuid }`, {
			method: 'PATCH',
			data: patch,
		} );
	},

	remove( pageId: number, uuid: string ): Promise< { deleted: boolean; uuid: string } > {
		return request< { deleted: boolean; uuid: string } >(
			`${ NS }/pages/${ pageId }/blocks/${ uuid }`,
			{ method: 'DELETE' }
		);
	},

	reorder( pageId: number, order: string[] ): Promise< BioBlock[] > {
		return request< BioBlock[] >( `${ NS }/pages/${ pageId }/blocks/reorder`, {
			method: 'POST',
			data: { order },
		} );
	},
};
