import { __ } from '@wordpress/i18n';
import { useState, type ReactNode } from 'react';
import { BackgroundEditor } from '../../components/builder/BackgroundEditor';
import { ContentAreaEditor } from '../../components/builder/ContentAreaEditor';
import { PageHeaderEditor } from '../../components/builder/PageHeaderEditor';
import { ThemePicker } from '../../components/builder/ThemePicker';
import { useBuilder } from './BuilderContext';
import styles from './DesignPage.module.css';

type Section = 'theme' | 'header' | 'wallpaper' | 'content' | 'buttons' | 'text' | 'footer' | 'advanced';

export function DesignPage() {
	const { page, setSettings, setTheme } = useBuilder();
	const [ open, setOpen ] = useState< Section | null >( 'theme' );

	const toggle = ( s: Section ) => setOpen( ( v ) => ( v === s ? null : s ) );

	const settings = page.settings;
	const buttonStyle = settings.button_style || 'filled';
	const buttonShape = settings.button_shape || 'rounded';

	return (
		<div className={ styles.root }>
			<DesignCard
				id="theme"
				title={ __( 'Theme', 'biolink-pro' ) }
				summary={ page.theme || '—' }
				open={ open === 'theme' }
				onToggle={ () => toggle( 'theme' ) }
			>
				<ThemePicker
					value={ page.theme }
					settings={ settings }
					onChange={ setTheme }
					onSettingsChange={ setSettings }
					pageSummary={ [ settings.headline, settings.subheadline, page.title ]
						.filter( Boolean )
						.join( ' — ' ) }
				/>
			</DesignCard>

			<DesignCard
				id="header"
				title={ __( 'Header', 'biolink-pro' ) }
				summary={ settings.handle ? `@${ settings.handle.replace( /^@/, '' ) }` : __( 'Classic', 'biolink-pro' ) }
				open={ open === 'header' }
				onToggle={ () => toggle( 'header' ) }
			>
				<PageHeaderEditor settings={ settings } onChange={ setSettings } />
			</DesignCard>

			<DesignCard
				id="wallpaper"
				title={ __( 'Wallpaper', 'biolink-pro' ) }
				summary={ ( settings.bg_type ?? 'theme' ) as string }
				open={ open === 'wallpaper' }
				onToggle={ () => toggle( 'wallpaper' ) }
			>
				<BackgroundEditor settings={ settings } onChange={ setSettings } />
			</DesignCard>

			<DesignCard
				id="content"
				title={ __( 'Content area', 'biolink-pro' ) }
				summary={
					settings.content_bg_type
						? `${ settings.content_bg_type } · ${ settings.content_max_width ?? 620 }px`
						: __( 'Transparent', 'biolink-pro' )
				}
				open={ open === 'content' }
				onToggle={ () => toggle( 'content' ) }
			>
				<ContentAreaEditor settings={ settings } onChange={ setSettings } />
			</DesignCard>

			<DesignCard
				id="buttons"
				title={ __( 'Buttons', 'biolink-pro' ) }
				summary={ `${ buttonStyle } · ${ buttonShape }` }
				open={ open === 'buttons' }
				onToggle={ () => toggle( 'buttons' ) }
			>
				<div className={ styles.section }>
					<div className={ styles.row }>
						<div className={ styles.rowLabel }>{ __( 'Button style', 'biolink-pro' ) }</div>
						<div className={ styles.tiles }>
							{ ( [
								{ id: 'filled', label: 'Solid', cls: '' },
								{ id: 'glass', label: 'Glass', cls: styles.tilePreviewGlass },
								{ id: 'outline', label: 'Outline', cls: styles.tilePreviewOutline },
							] as const ).map( ( t ) => (
								<button
									key={ t.id }
									type="button"
									className={ `${ styles.tile } ${ buttonStyle === t.id ? styles.tileActive : '' }` }
									onClick={ () => setSettings( { ...settings, button_style: t.id } ) }
								>
									<span className={ `${ styles.tilePreview } ${ t.cls }` } />
									<span>{ t.label }</span>
								</button>
							) ) }
						</div>
					</div>

					<div className={ styles.row }>
						<div className={ styles.rowLabel }>{ __( 'Corner radius', 'biolink-pro' ) }</div>
						<div className={ styles.tiles }>
							{ ( [
								{ id: 'square', label: 'Square', radius: '2px' },
								{ id: 'rounded', label: 'Rounded', radius: '10px' },
								{ id: 'pill', label: 'Pill', radius: '999px' },
							] as const ).map( ( t ) => (
								<button
									key={ t.id }
									type="button"
									className={ `${ styles.tile } ${ buttonShape === t.id ? styles.tileActive : '' }` }
									onClick={ () => setSettings( { ...settings, button_shape: t.id } ) }
								>
									<span
										className={ styles.tilePreview }
										style={ { borderRadius: t.radius } }
									/>
									<span>{ t.label }</span>
								</button>
							) ) }
						</div>
					</div>

					<div className={ styles.row }>
						<div className={ styles.rowLabel }>{ __( 'Accent color', 'biolink-pro' ) }</div>
						<ColorField
							value={ ( settings.accent_color as string ) || '#1f1f1f' }
							onChange={ ( v ) => setSettings( { ...settings, accent_color: v } ) }
						/>
					</div>

					<div className={ styles.row }>
						<div className={ styles.rowLabel }>{ __( 'Button text color', 'biolink-pro' ) }</div>
						<ColorField
							value={ ( settings.accent_text_color as string ) || '#ffffff' }
							onChange={ ( v ) => setSettings( { ...settings, accent_text_color: v } ) }
						/>
					</div>
				</div>
			</DesignCard>

			<DesignCard
				id="text"
				title={ __( 'Text', 'biolink-pro' ) }
				summary={ __( 'System font', 'biolink-pro' ) }
				open={ open === 'text' }
				onToggle={ () => toggle( 'text' ) }
			>
				<div className={ styles.section }>
					<div className={ styles.row }>
						<div className={ styles.rowLabel }>{ __( 'Page font', 'biolink-pro' ) }</div>
						<div className={ styles.colorRow }>
							<span className={ styles.colorHex }>System default</span>
						</div>
					</div>
					<p className={ styles.disabledHint }>
						{ __(
							'Custom Google Fonts and per-block text colors land in v2.1.',
							'biolink-pro'
						) }
					</p>
				</div>
			</DesignCard>

			<DesignCard
				id="footer"
				title={ __( 'Footer', 'biolink-pro' ) }
				summary={ settings.hide_branding ? __( 'Hidden', 'biolink-pro' ) : __( 'Visible', 'biolink-pro' ) }
				open={ open === 'footer' }
				onToggle={ () => toggle( 'footer' ) }
			>
				<div className={ styles.toggleRow }>
					<div>
						<div className={ styles.toggleLabel }>
							{ __( 'Hide "Made with BioLink Pro" footer', 'biolink-pro' ) }
						</div>
						<div className={ styles.toggleSub }>
							{ __(
								'Configure globally in Settings → General; per-page override is on the roadmap.',
								'biolink-pro'
							) }
						</div>
					</div>
				</div>
			</DesignCard>

			<DesignCard
				id="advanced"
				title={ __( 'Advanced', 'biolink-pro' ) }
				summary={
					( settings.custom_css ?? '' ).length > 0
						? `${ ( settings.custom_css as string ).length } chars custom CSS`
						: __( 'No custom CSS', 'biolink-pro' )
				}
				open={ open === 'advanced' }
				onToggle={ () => toggle( 'advanced' ) }
			>
				<div className={ styles.section }>
					<label className={ styles.rowLabel } htmlFor="biolink-custom-css">
						{ __( 'Per-page custom CSS', 'biolink-pro' ) }
					</label>
					<textarea
						id="biolink-custom-css"
						className={ styles.cssArea }
						value={ ( settings.custom_css as string ) ?? '' }
						onChange={ ( e ) => setSettings( { ...settings, custom_css: e.target.value } ) }
						placeholder={ '/* Target .bio-page, .bio-block--link, .bio-header, etc. */\n.bio-block--link {\n  font-weight: 700;\n}' }
						spellCheck={ false }
						rows={ 12 }
					/>
					<p className={ styles.disabledHint }>
						{ __(
							'Applied only to this bio page. Target the .bio-page wrapper, .bio-block--{type} classes, .bio-header, etc. </style> sequences are stripped for safety.',
							'biolink-pro'
						) }
					</p>
				</div>
			</DesignCard>
		</div>
	);
}

interface CardProps {
	id: Section;
	title: string;
	summary: string;
	open: boolean;
	onToggle: () => void;
	children: ReactNode;
}

function DesignCard( { title, summary, open, onToggle, children }: CardProps ) {
	return (
		<section className={ styles.card }>
			<button
				type="button"
				className={ styles.cardHead }
				onClick={ onToggle }
				aria-expanded={ open }
			>
				<span className={ styles.cardTitle }>{ title }</span>
				<span className={ styles.cardSummary }>
					{ summary }
					<span className={ `${ styles.cardChev } ${ open ? styles.cardChevOpen : '' }` }>›</span>
				</span>
			</button>
			{ open && <div className={ styles.cardBody }>{ children }</div> }
		</section>
	);
}

function ColorField( { value, onChange }: { value: string; onChange: ( v: string ) => void } ) {
	return (
		<div className={ styles.colorRow }>
			<label className={ styles.colorSwatch } style={ { background: value } }>
				<input type="color" value={ value } onChange={ ( e ) => onChange( e.target.value ) } />
			</label>
			<input
				type="text"
				className={ styles.colorHex }
				value={ value.toUpperCase() }
				onChange={ ( e ) => onChange( e.target.value ) }
			/>
		</div>
	);
}
