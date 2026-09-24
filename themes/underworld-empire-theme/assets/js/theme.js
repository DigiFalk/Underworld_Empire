/**
 * Underworld Empire theme: mobile menu, sticky header state, scroll to top.
 */
( function () {
	'use strict';

	var header = document.querySelector( '.uet-header' );
	var toggle = document.querySelector( '.uet-menu-toggle' );

	if ( header && toggle ) {
		toggle.addEventListener( 'click', function () {
			var open = header.classList.toggle( 'is-menu-open' );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && header.classList.contains( 'is-menu-open' ) ) {
				header.classList.remove( 'is-menu-open' );
				toggle.setAttribute( 'aria-expanded', 'false' );
				toggle.focus();
			}
		} );
	}

	var top = document.querySelector( '.uet-scroll-top' );
	var ticking = false;
	function onScroll() {
		var y = window.pageYOffset || document.documentElement.scrollTop;
		if ( header ) {
			header.classList.toggle( 'is-scrolled', y > 10 );
		}
		if ( top ) {
			top.classList.toggle( 'is-visible', y > 400 );
		}
		ticking = false;
	}
	window.addEventListener( 'scroll', function () {
		if ( ! ticking ) {
			window.requestAnimationFrame( onScroll );
			ticking = true;
		}
	}, { passive: true } );
	onScroll();

	if ( top ) {
		top.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			window.scrollTo( { top: 0, behavior: 'smooth' } );
			var skip = document.getElementById( 'primary' );
			if ( skip ) {
				skip.setAttribute( 'tabindex', '-1' );
				skip.focus( { preventScroll: true } );
			}
		} );
	}
}() );
