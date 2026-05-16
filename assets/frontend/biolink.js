/**
 * BioLink Pro — public bio page progressive enhancement.
 *
 * Tiny, framework-free. Handles:
 *   - YouTube facade → real iframe on click
 *   - Countdown blocks
 *   - TikTok embed.js lazy-load
 *   - Newsletter + contact form submissions
 */
( function () {
	'use strict';

	const REST = ( window.BIOLINK_PRO_PUBLIC && window.BIOLINK_PRO_PUBLIC.restBase ) || '/wp-json/biolink/v1/';

	function activateYouTube( facade ) {
		const wrapper = facade.parentElement;
		if ( ! wrapper ) return;
		const id = wrapper.getAttribute( 'data-yt-id' );
		if ( ! id ) return;
		const iframe = document.createElement( 'iframe' );
		iframe.setAttribute(
			'src',
			'https://www.youtube-nocookie.com/embed/' + id + '?autoplay=1&rel=0'
		);
		iframe.setAttribute( 'allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture' );
		iframe.setAttribute( 'allowfullscreen', '' );
		iframe.setAttribute( 'title', facade.getAttribute( 'aria-label' ) || 'YouTube video' );
		iframe.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );
		facade.replaceWith( iframe );
	}

	function initYouTube() {
		document.querySelectorAll( '.bio-block__yt-facade' ).forEach( ( facade ) => {
			facade.addEventListener(
				'click',
				( event ) => {
					event.preventDefault();
					activateYouTube( facade );
				},
				{ once: true }
			);
		} );
	}

	function pad( n ) {
		return String( n ).padStart( 2, '0' );
	}

	function updateCountdown( block ) {
		const targetIso = block.getAttribute( 'data-target' );
		if ( ! targetIso ) return false;
		const target = new Date( targetIso ).getTime();
		if ( Number.isNaN( target ) ) return false;
		const now = Date.now();
		let diff = Math.max( 0, target - now );

		if ( diff <= 0 ) {
			const msg = block.getAttribute( 'data-expired' ) || '';
			block.innerHTML = '<p class="bio-block__countdown-expired">' + escapeHtml( msg ) + '</p>';
			return false;
		}

		const days = Math.floor( diff / 86_400_000 );
		diff -= days * 86_400_000;
		const hours = Math.floor( diff / 3_600_000 );
		diff -= hours * 3_600_000;
		const minutes = Math.floor( diff / 60_000 );
		diff -= minutes * 60_000;
		const seconds = Math.floor( diff / 1000 );

		const setUnit = ( unit, value ) => {
			const el = block.querySelector( '[data-unit="' + unit + '"]' );
			if ( el ) el.textContent = unit === 'd' ? String( value ) : pad( value );
		};
		setUnit( 'd', days );
		setUnit( 'h', hours );
		setUnit( 'm', minutes );
		setUnit( 's', seconds );
		return true;
	}

	function initCountdowns() {
		const blocks = document.querySelectorAll( '.bio-block--countdown' );
		if ( blocks.length === 0 ) return;
		const tick = () => {
			let anyActive = false;
			blocks.forEach( ( block ) => {
				if ( updateCountdown( block ) ) anyActive = true;
			} );
			if ( anyActive ) {
				window.setTimeout( tick, 1000 );
			}
		};
		tick();
	}

	function initTikTok() {
		const hasTikTok = document.querySelector( '.tiktok-embed' );
		if ( ! hasTikTok ) return;
		if ( document.querySelector( 'script[src*="tiktok.com/embed.js"]' ) ) return;
		const s = document.createElement( 'script' );
		s.async = true;
		s.src = 'https://www.tiktok.com/embed.js';
		document.body.appendChild( s );
	}

	function escapeHtml( s ) {
		const div = document.createElement( 'div' );
		div.textContent = s;
		return div.innerHTML;
	}

	function setStatus( el, message, kind ) {
		if ( ! el ) return;
		el.textContent = message;
		el.className = 'bio-block__nl-status bio-block__form-status--' + ( kind || 'info' );
	}

	function handleFormSubmit( form, endpoint, statusSelector ) {
		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const statusEl = form.querySelector( statusSelector );
			const button = form.querySelector( 'button[type="submit"]' );
			const fd = new FormData( form );
			const payload = Object.fromEntries( fd.entries() );
			payload.page_id = Number( form.getAttribute( 'data-page' ) || '0' );
			payload.nonce = form.getAttribute( 'data-nonce' ) || '';
			if ( button ) button.disabled = true;
			setStatus( statusEl, 'Sending…', 'info' );
			try {
				const res = await fetch( REST + endpoint, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( payload ),
				} );
				const json = await res.json().catch( () => ( {} ) );
				if ( res.ok && json && json.ok ) {
					setStatus( statusEl, form.getAttribute( 'data-success' ) || 'Done.', 'ok' );
					form.reset();
				} else {
					setStatus(
						statusEl,
						( json && json.message ) || 'Something went wrong.',
						'error'
					);
				}
			} catch {
				setStatus( statusEl, 'Network error. Please try again.', 'error' );
			} finally {
				if ( button ) button.disabled = false;
			}
		} );
	}

	function initForms() {
		document
			.querySelectorAll( 'form[data-action="newsletter"]' )
			.forEach( ( form ) => handleFormSubmit( form, 'newsletter/subscribe', '.bio-block__nl-status' ) );
		document
			.querySelectorAll( 'form[data-action="contact"]' )
			.forEach( ( form ) => handleFormSubmit( form, 'contact/submit', '.bio-block__contact-status' ) );
	}

	function init() {
		initYouTube();
		initCountdowns();
		initTikTok();
		initForms();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
