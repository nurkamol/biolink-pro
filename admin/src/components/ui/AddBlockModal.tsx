import { __ } from '@wordpress/i18n';
import { useMemo, useState } from 'react';
import { getBlockCatalog, type BlockMeta } from '../../blocks';
import { IconCaret, IconClose, IconSearch } from './Icons';
import styles from './AddBlockModal.module.css';

type Category = 'suggested' | 'commerce' | 'social' | 'media' | 'engage' | 'all';

interface Props {
	open: boolean;
	onClose: () => void;
	onPick: ( slug: string ) => void;
}

const CATEGORIES: { id: Category; label: string; icon: string }[] = [
	{ id: 'suggested', label: 'Suggested', icon: '💡' },
	{ id: 'commerce', label: 'Commerce', icon: '🏬' },
	{ id: 'social', label: 'Social', icon: '♡' },
	{ id: 'media', label: 'Media', icon: '▶' },
	{ id: 'engage', label: 'Engage', icon: '✉' },
	{ id: 'all', label: 'View all', icon: '⋯' },
];

function matchesCategory( meta: BlockMeta, cat: Category ): boolean {
	if ( cat === 'all' ) return true;
	if ( cat === 'suggested' ) {
		return [ 'link', 'social_icons', 'image_gallery', 'youtube', 'spotify', 'tiktok' ].includes(
			meta.slug
		);
	}
	if ( cat === 'commerce' ) return meta.group === 'monetize';
	if ( cat === 'social' ) return meta.slug === 'social_icons' || meta.slug === 'tiktok';
	if ( cat === 'media' ) return meta.group === 'embed';
	if ( cat === 'engage' ) return meta.group === 'engage';
	return false;
}

export function AddBlockModal( { open, onClose, onPick }: Props ) {
	const [ cat, setCat ] = useState< Category >( 'suggested' );
	const [ q, setQ ] = useState( '' );
	const catalog = useMemo( () => getBlockCatalog(), [] );

	if ( ! open ) return null;

	const filtered = catalog.filter( ( m ) => {
		if ( q.trim() ) {
			const needle = q.toLowerCase();
			return m.label.toLowerCase().includes( needle ) || m.slug.includes( needle );
		}
		return matchesCategory( m, cat );
	} );

	const heroes = [
		{ slug: 'link', label: 'Link', icon: '🔗' },
		{ slug: 'product_card', label: 'Product', icon: '🏷' },
		{ slug: 'contact_form', label: 'Form', icon: '💬' },
		{ slug: 'image_gallery', label: 'Gallery', icon: '◫' },
	];

	return (
		<div className={ styles.overlay } onClick={ onClose } role="presentation">
			<div
				className={ styles.modal }
				role="dialog"
				aria-modal="true"
				aria-label={ __( 'Add a block', 'biolink-pro' ) }
				onClick={ ( e ) => e.stopPropagation() }
			>
				<header className={ styles.header }>
					<h2 className={ styles.title }>{ __( 'Add', 'biolink-pro' ) }</h2>
					<button
						type="button"
						className={ styles.closeBtn }
						onClick={ onClose }
						aria-label={ __( 'Close', 'biolink-pro' ) }
					>
						<IconClose size={ 16 } />
					</button>
				</header>

				<div className={ styles.search }>
					<span className={ styles.searchIcon }><IconSearch /></span>
					<input
						type="search"
						className={ styles.searchInput }
						placeholder={ __( 'Paste or search a link', 'biolink-pro' ) }
						value={ q }
						onChange={ ( e ) => setQ( e.target.value ) }
					/>
				</div>

				<div className={ styles.body }>
					<nav className={ styles.rail } aria-label={ __( 'Categories', 'biolink-pro' ) }>
						{ CATEGORIES.map( ( c ) => (
							<button
								key={ c.id }
								type="button"
								className={ `${ styles.railItem } ${
									cat === c.id && ! q ? styles.railItemActive : ''
								}` }
								onClick={ () => {
									setCat( c.id );
									setQ( '' );
								} }
							>
								<span className={ styles.railIcon }>{ c.icon }</span>
								<span>{ c.label }</span>
							</button>
						) ) }
					</nav>

					<div className={ styles.panel }>
						{ ! q && cat === 'suggested' && (
							<>
								<div className={ styles.heroes }>
									{ heroes.map( ( h ) => (
										<button
											key={ h.slug }
											type="button"
											className={ styles.heroTile }
											onClick={ () => onPick( h.slug ) }
										>
											<span className={ styles.heroLabel }>{ h.label }</span>
											<span className={ styles.heroIcon }>{ h.icon }</span>
										</button>
									) ) }
								</div>
								<div className={ styles.sectionLabel }>
									{ __( 'Suggested', 'biolink-pro' ) }
								</div>
							</>
						) }
						<ul className={ styles.list }>
							{ filtered.map( ( m ) => (
								<li key={ m.slug }>
									<button
										type="button"
										className={ styles.listItem }
										onClick={ () => onPick( m.slug ) }
									>
										<span className={ styles.listIcon }>{ m.icon }</span>
										<span className={ styles.listText }>
											<span className={ styles.listLabel }>{ m.label }</span>
											<span className={ styles.listSub }>
												{ describeBlock( m.slug ) }
											</span>
										</span>
										<span className={ styles.listChev }><IconCaret direction="right" size={ 14 } /></span>
									</button>
								</li>
							) ) }
							{ filtered.length === 0 && (
								<li className={ styles.empty }>
									{ __( 'No matches.', 'biolink-pro' ) }
								</li>
							) }
						</ul>
					</div>
				</div>
			</div>
		</div>
	);
}

function describeBlock( slug: string ): string {
	const map: Record< string, string > = {
		link: __( 'A simple link with an optional icon.', 'biolink-pro' ),
		button: __( 'A styled call-to-action button.', 'biolink-pro' ),
		social_icons: __( 'Row of platform icons that link to your profiles.', 'biolink-pro' ),
		rich_text: __( 'Headings, paragraphs, lists.', 'biolink-pro' ),
		divider: __( 'A visual separator between sections.', 'biolink-pro' ),
		image_gallery: __( 'A grid of images from your media library.', 'biolink-pro' ),
		video: __( 'Self-hosted MP4 / WebM video.', 'biolink-pro' ),
		youtube: __( 'Embed a YouTube video.', 'biolink-pro' ),
		spotify: __( 'Embed a Spotify track, album, or playlist.', 'biolink-pro' ),
		tiktok: __( 'Embed a TikTok post.', 'biolink-pro' ),
		map: __( 'A pinned location on a map.', 'biolink-pro' ),
		html_embed: __( 'Custom HTML for advanced embeds.', 'biolink-pro' ),
		faq: __( 'Collapsible question-and-answer pairs.', 'biolink-pro' ),
		countdown: __( 'Countdown timer to a date.', 'biolink-pro' ),
		newsletter: __( 'Email capture form.', 'biolink-pro' ),
		contact_form: __( 'Name + email + message form.', 'biolink-pro' ),
		product_card: __( 'Product image, price and buy button.', 'biolink-pro' ),
		donation: __( 'Tip jar with suggested amounts.', 'biolink-pro' ),
	};
	return map[ slug ] ?? '';
}
