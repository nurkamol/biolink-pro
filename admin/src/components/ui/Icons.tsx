/**
 * Shared SVG icon set. Monochrome, 1.8 stroke, 24x24 viewBox.
 * Color inherits from `currentColor` so callers control via CSS color.
 */

interface IconProps {
	size?: number;
	className?: string;
	'aria-hidden'?: boolean;
}

function svgProps( size: number ) {
	return {
		width: size,
		height: size,
		viewBox: '0 0 24 24',
		fill: 'none',
		stroke: 'currentColor',
		strokeWidth: 1.8,
		strokeLinecap: 'round' as const,
		strokeLinejoin: 'round' as const,
	};
}

export function IconGrid( { size = 18, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<rect x="3" y="3" width="7" height="7" rx="1.5" />
			<rect x="14" y="3" width="7" height="7" rx="1.5" />
			<rect x="3" y="14" width="7" height="7" rx="1.5" />
			<rect x="14" y="14" width="7" height="7" rx="1.5" />
		</svg>
	);
}

export function IconBars( { size = 18, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M4 20V10M10 20V4M16 20v-7M22 20v-4" />
		</svg>
	);
}

export function IconSparkle( { size = 18, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M12 3l2 5 5 2-5 2-2 5-2-5-5-2 5-2z" />
		</svg>
	);
}

export function IconCog( { size = 18, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<circle cx="12" cy="12" r="3" />
			<path d="M19.4 15a1.7 1.7 0 00.3 1.9l.1.1a2 2 0 11-2.8 2.8l-.1-.1a1.7 1.7 0 00-1.9-.3 1.7 1.7 0 00-1 1.5V21a2 2 0 01-4 0v-.1A1.7 1.7 0 009 19.4a1.7 1.7 0 00-1.9.3l-.1.1a2 2 0 11-2.8-2.8l.1-.1a1.7 1.7 0 00.3-1.9 1.7 1.7 0 00-1.5-1H3a2 2 0 010-4h.1A1.7 1.7 0 004.6 9a1.7 1.7 0 00-.3-1.9l-.1-.1a2 2 0 112.8-2.8l.1.1a1.7 1.7 0 001.9.3H9a1.7 1.7 0 001-1.5V3a2 2 0 014 0v.1a1.7 1.7 0 001 1.5 1.7 1.7 0 001.9-.3l.1-.1a2 2 0 112.8 2.8l-.1.1a1.7 1.7 0 00-.3 1.9V9a1.7 1.7 0 001.5 1H21a2 2 0 010 4h-.1a1.7 1.7 0 00-1.5 1z" />
		</svg>
	);
}

export function IconImage( { size = 16, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<rect x="3" y="4" width="18" height="16" rx="2" />
			<circle cx="9" cy="10" r="1.5" />
			<path d="M21 16l-5-5-9 9" />
		</svg>
	);
}

export function IconStar( { size = 16, filled = false, className }: IconProps & { filled?: boolean } = {} ) {
	return (
		<svg
			{ ...svgProps( size ) }
			className={ className }
			fill={ filled ? 'currentColor' : 'none' }
			aria-hidden
		>
			<path d="M12 3l2.6 5.6 6.1.6-4.6 4.2 1.3 6.1L12 16.7 6.6 19.5l1.3-6.1L3.3 9.2l6.1-.6z" />
		</svg>
	);
}

export function IconClock( { size = 16, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<circle cx="12" cy="12" r="9" />
			<path d="M12 7v5l3 2" />
		</svg>
	);
}

export function IconLock( { size = 16, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<rect x="5" y="11" width="14" height="9" rx="2" />
			<path d="M8 11V8a4 4 0 018 0v3" />
		</svg>
	);
}

export function IconTrash( { size = 16, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M4 7h16M10 11v6M14 11v6M5 7l1 12a2 2 0 002 2h8a2 2 0 002-2l1-12M9 7V5a2 2 0 012-2h2a2 2 0 012 2v2" />
		</svg>
	);
}

export function IconGrip( { size = 14, className }: IconProps = {} ) {
	return (
		<svg width={ size } height={ size } viewBox="0 0 16 16" fill="currentColor" className={ className } aria-hidden>
			<circle cx="5" cy="3" r="1.3" />
			<circle cx="11" cy="3" r="1.3" />
			<circle cx="5" cy="8" r="1.3" />
			<circle cx="11" cy="8" r="1.3" />
			<circle cx="5" cy="13" r="1.3" />
			<circle cx="11" cy="13" r="1.3" />
		</svg>
	);
}

export function IconExternal( { size = 14, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M14 4h6v6M10 14L20 4M19 13v6a1 1 0 01-1 1H5a1 1 0 01-1-1V6a1 1 0 011-1h6" />
		</svg>
	);
}

export function IconCopy( { size = 14, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<rect x="9" y="9" width="11" height="11" rx="2" />
			<path d="M5 15V5a1 1 0 011-1h10" />
		</svg>
	);
}

export function IconCaret( { size = 12, direction = 'down', className }: IconProps & { direction?: 'down' | 'right' } = {} ) {
	const path = direction === 'right' ? 'M9 6l6 6-6 6' : 'M6 9l6 6 6-6';
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d={ path } />
		</svg>
	);
}

export function IconCheck( { size = 14, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M4 12l5 5L20 6" />
		</svg>
	);
}

export function IconPlus( { size = 18, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M12 5v14M5 12h14" />
		</svg>
	);
}

export function IconClose( { size = 18, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M6 6l12 12M18 6L6 18" />
		</svg>
	);
}

export function IconPencil( { size = 14, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<path d="M4 20h4l11-11-4-4L4 16v4z" />
			<path d="M14 5l5 5" />
		</svg>
	);
}

export function IconQr( { size = 16, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<rect x="3" y="3" width="7" height="7" rx="1" />
			<rect x="14" y="3" width="7" height="7" rx="1" />
			<rect x="3" y="14" width="7" height="7" rx="1" />
			<path d="M14 14h3v3M21 14v3M14 21h3M21 17v4" />
			<rect x="5.5" y="5.5" width="2" height="2" />
			<rect x="16.5" y="5.5" width="2" height="2" />
			<rect x="5.5" y="16.5" width="2" height="2" />
		</svg>
	);
}

export function IconSearch( { size = 16, className }: IconProps = {} ) {
	return (
		<svg { ...svgProps( size ) } className={ className } aria-hidden>
			<circle cx="11" cy="11" r="7" />
			<path d="M20 20l-4-4" />
		</svg>
	);
}
