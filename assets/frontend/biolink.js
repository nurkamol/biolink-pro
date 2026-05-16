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

	function initYouTube( root ) {
		( root || document ).querySelectorAll( '.bio-block__yt-facade' ).forEach( ( facade ) => {
			if ( facade.dataset.bioInit ) return;
			facade.dataset.bioInit = '1';
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

	function initCountdowns( root ) {
		const blocks = ( root || document ).querySelectorAll( '.bio-block--countdown' );
		const fresh = [];
		blocks.forEach( ( b ) => {
			if ( b.dataset.bioInit ) return;
			b.dataset.bioInit = '1';
			fresh.push( b );
		} );
		if ( fresh.length === 0 ) return;
		const tick = () => {
			let anyActive = false;
			fresh.forEach( ( block ) => {
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

	function initForms( root ) {
		( root || document )
			.querySelectorAll( 'form[data-action="newsletter"]:not([data-bio-init])' )
			.forEach( ( form ) => {
				form.dataset.bioInit = '1';
				handleFormSubmit( form, 'newsletter/subscribe', '.bio-block__nl-status' );
			} );
		( root || document )
			.querySelectorAll( 'form[data-action="contact"]:not([data-bio-init])' )
			.forEach( ( form ) => {
				form.dataset.bioInit = '1';
				handleFormSubmit( form, 'contact/submit', '.bio-block__contact-status' );
			} );
	}

	function initCheckout( root ) {
		( root || document ).querySelectorAll( 'form[data-action="checkout"]:not([data-bio-init])' ).forEach( ( form ) => {
			form.dataset.bioInit = '1';
			form.addEventListener( 'submit', async ( event ) => {
				event.preventDefault();
				const button = form.querySelector( 'button[type="submit"]' );
				const status = form.querySelector( '.bio-block__donation-status' );
				const clickedAmount = event.submitter && event.submitter.value;
				const inputAmount = form.querySelector( 'input[name="amount"]' )?.value || '';
				const amount = Number( clickedAmount || inputAmount );
				if ( ! amount || amount <= 0 ) {
					setStatus( status, 'Enter a valid amount.', 'error' );
					return;
				}
				const endpoint = form.getAttribute( 'data-endpoint' );
				if ( ! endpoint ) return;
				if ( button ) button.disabled = true;
				setStatus( status, 'Redirecting…', 'info' );
				try {
					const res = await fetch( endpoint, {
						method: 'POST',
						headers: { 'Content-Type': 'application/json' },
						body: JSON.stringify( {
							amount,
							currency: form.getAttribute( 'data-currency' ) || 'USD',
							name: form.getAttribute( 'data-name' ) || 'Donation',
							description: form.getAttribute( 'data-name' ) || 'Donation',
							page_id: Number( form.getAttribute( 'data-page' ) || '0' ),
							block_uuid: form.getAttribute( 'data-block' ) || '',
						} ),
					} );
					const json = await res.json().catch( () => ( {} ) );
					if ( ! res.ok ) {
						setStatus( status, ( json && json.message ) || 'Checkout failed.', 'error' );
						if ( button ) button.disabled = false;
						return;
					}
					const url = json.url || json.approve_url;
					if ( url ) {
						window.location.href = url;
					} else {
						setStatus( status, 'No redirect URL returned.', 'error' );
						if ( button ) button.disabled = false;
					}
				} catch {
					setStatus( status, 'Network error.', 'error' );
					if ( button ) button.disabled = false;
				}
			} );
		} );
	}

	function fireViewBeacon() {
		const pageId = ( window.BIOLINK_PRO_PUBLIC && window.BIOLINK_PRO_PUBLIC.pageId ) || 0;
		if ( ! pageId ) return;
		try {
			const blob = new Blob(
				[ JSON.stringify( { page_id: pageId } ) ],
				{ type: 'application/json' }
			);
			if ( navigator.sendBeacon ) {
				navigator.sendBeacon( REST + 'track/view', blob );
			} else {
				fetch( REST + 'track/view', {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( { page_id: pageId } ),
					keepalive: true,
				} ).catch( () => {} );
			}
		} catch {
			// non-fatal
		}
	}

	function enhance( root ) {
		initYouTube( root );
		initCountdowns( root );
		initTikTok();
		initForms( root );
		initCheckout( root );
	}

	function openUnlockModal( anchor ) {
		const pageId = anchor.getAttribute( 'data-biolink-page' );
		const uuid = anchor.getAttribute( 'data-biolink-uuid' );
		const labelEl = anchor.querySelector( '.bio-block__label' );
		const label = labelEl ? labelEl.textContent : '';

		const overlay = document.createElement( 'div' );
		overlay.className = 'bio-unlock-overlay';
		overlay.innerHTML =
			'<div class="bio-unlock-modal" role="dialog" aria-modal="true" aria-labelledby="bio-unlock-title">' +
			'<button type="button" class="bio-unlock-close" aria-label="Close">&times;</button>' +
			'<div class="bio-unlock-icon" aria-hidden="true">🔒</div>' +
			'<h2 id="bio-unlock-title" class="bio-unlock-title">' + escapeHtml( label ) + '</h2>' +
			'<p class="bio-unlock-hint">Enter the passcode to view this content.</p>' +
			'<div class="bio-unlock-error" hidden></div>' +
			'<form class="bio-unlock-form">' +
			'<input type="password" name="passcode" placeholder="Passcode" autocomplete="off" required />' +
			'<button type="submit" class="bio-unlock-submit">Unlock</button>' +
			'</form>' +
			'</div>';
		document.body.appendChild( overlay );

		const input = overlay.querySelector( 'input[name="passcode"]' );
		const errorEl = overlay.querySelector( '.bio-unlock-error' );
		const form = overlay.querySelector( 'form' );
		const closeBtn = overlay.querySelector( '.bio-unlock-close' );
		const submit = overlay.querySelector( '.bio-unlock-submit' );

		setTimeout( () => input.focus(), 30 );

		const close = () => {
			overlay.remove();
			document.removeEventListener( 'keydown', onKey );
		};
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) close();
		};
		document.addEventListener( 'keydown', onKey );
		overlay.addEventListener( 'click', ( e ) => {
			if ( e.target === overlay ) close();
		} );
		closeBtn.addEventListener( 'click', close );

		form.addEventListener( 'submit', async ( e ) => {
			e.preventDefault();
			const passcode = input.value;
			if ( ! passcode ) return;
			submit.disabled = true;
			submit.textContent = 'Unlocking…';
			errorEl.hidden = true;
			try {
				const res = await fetch( REST + 'unlock/' + pageId + '/' + uuid, {
					method: 'POST',
					headers: { 'Content-Type': 'application/json' },
					body: JSON.stringify( { passcode } ),
					credentials: 'same-origin',
				} );
				const json = await res.json().catch( () => ( {} ) );
				if ( ! res.ok || ! json.ok ) {
					errorEl.textContent = ( json && json.message ) || 'Incorrect passcode.';
					errorEl.hidden = false;
					submit.disabled = false;
					submit.textContent = 'Unlock';
					input.select();
					return;
				}
				// Swap placeholder with the rendered block, then re-init enhancements.
				const wrap = document.createElement( 'div' );
				wrap.innerHTML = ( json.html || '' ).trim();
				const next = wrap.firstElementChild;
				if ( next && anchor.parentNode ) {
					anchor.replaceWith( next );
					enhance( next );
					next.dispatchEvent(
						new CustomEvent( 'biolink:unlocked', {
							bubbles: true,
							detail: { uuid, pageId },
						} )
					);
				}
				close();
			} catch ( err ) {
				errorEl.textContent = 'Network error. Try again.';
				errorEl.hidden = false;
				submit.disabled = false;
				submit.textContent = 'Unlock';
			}
		} );
	}

	function initUnlock() {
		// Event delegation so it works for elements swapped in later too.
		document.addEventListener( 'click', ( ev ) => {
			const anchor = ev.target.closest && ev.target.closest( '[data-biolink-unlock]' );
			if ( ! anchor ) return;
			ev.preventDefault();
			openUnlockModal( anchor );
		} );
	}

	function init() {
		enhance( document );
		initUnlock();
		fireViewBeacon();
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
