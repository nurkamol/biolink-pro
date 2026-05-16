import apiFetch from '@wordpress/api-fetch';

apiFetch.use( apiFetch.createNonceMiddleware( window.BIOLINK_PRO.restNonce ) );
apiFetch.use( apiFetch.createRootURLMiddleware( window.BIOLINK_PRO.restBase ) );

export interface BioBlock {
	uuid: string;
	type: string;
	data: Record< string, unknown >;
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
	settings: Record< string, unknown >;
	blocks: BioBlock[];
	seo: Record< string, unknown >;
}

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
		return request< BioPage[] >( `pages${ qs ? `?${ qs }` : '' }` );
	},

	get( id: number ): Promise< BioPage > {
		return request< BioPage >( `pages/${ id }` );
	},

	create( payload: CreatePagePayload ): Promise< BioPage > {
		return request< BioPage >( 'pages', { method: 'POST', data: payload } );
	},

	update( id: number, payload: UpdatePagePayload ): Promise< BioPage > {
		return request< BioPage >( `pages/${ id }`, { method: 'PATCH', data: payload } );
	},

	remove( id: number, force = false ): Promise< { deleted: boolean; id: number } > {
		const query = force ? '?force=1' : '';
		return request< { deleted: boolean; id: number } >( `pages/${ id }${ query }`, {
			method: 'DELETE',
		} );
	},

	duplicate( id: number ): Promise< BioPage > {
		return request< BioPage >( `pages/${ id }/duplicate`, { method: 'POST' } );
	},

	publish( id: number, when: string = 'now' ): Promise< BioPage > {
		return request< BioPage >( `pages/${ id }/publish`, {
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

export const ChangelogApi = {
	list( force = false ): Promise< Release[] > {
		return request< Release[] >( `changelog${ force ? '?force=1' : '' }` );
	},
	status( force = false ): Promise< UpdateStatus > {
		return request< UpdateStatus >( `changelog/update-status${ force ? '?force=1' : '' }` );
	},
};

export const BlocksApi = {
	types(): Promise< BioBlockType[] > {
		return request< BioBlockType[] >( 'blocks' );
	},

	append( pageId: number, type: string, data: Record< string, unknown > = {} ): Promise< BioBlock > {
		return request< BioBlock >( `pages/${ pageId }/blocks`, {
			method: 'POST',
			data: { type, data },
		} );
	},

	update( pageId: number, uuid: string, patch: Partial< BioBlock > ): Promise< BioBlock > {
		return request< BioBlock >( `pages/${ pageId }/blocks/${ uuid }`, {
			method: 'PATCH',
			data: patch,
		} );
	},

	remove( pageId: number, uuid: string ): Promise< { deleted: boolean; uuid: string } > {
		return request< { deleted: boolean; uuid: string } >(
			`pages/${ pageId }/blocks/${ uuid }`,
			{ method: 'DELETE' }
		);
	},

	reorder( pageId: number, order: string[] ): Promise< BioBlock[] > {
		return request< BioBlock[] >( `pages/${ pageId }/blocks/reorder`, {
			method: 'POST',
			data: { order },
		} );
	},
};
