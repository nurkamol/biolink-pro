/**
 * BioLink Pro — public bio page progressive enhancement.
 *
 * Tiny, framework-free. Swaps the YouTube facade for a real iframe on first
 * interaction so the initial page load stays under ~30 KB compressed.
 */
( function () {
	'use strict';

	function activateYouTube( facade ) {
		var wrapper = facade.parentElement;
		if ( ! wrapper ) {
			return;
		}
		var id = wrapper.getAttribute( 'data-yt-id' );
		if ( ! id ) {
			return;
		}
		var iframe = document.createElement( 'iframe' );
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

	function init() {
		document.querySelectorAll( '.bio-block__yt-facade' ).forEach( function ( facade ) {
			facade.addEventListener(
				'click',
				function ( event ) {
					event.preventDefault();
					activateYouTube( facade );
				},
				{ once: true }
			);
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
