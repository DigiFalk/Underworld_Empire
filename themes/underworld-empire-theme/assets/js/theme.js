/**
 * Underworld Empire theme: mobile menu panel, submenus, sticky header, scroll to top.
 * Uses event delegation so it keeps working when the Customizer re-renders the header.
 */
( function () {
	'use strict';

	var doc = document;
	var body = doc.body;

	function popup() {
		return doc.getElementById( 'uet-mobile-popup' );
	}

	function toggles() {
		return doc.querySelectorAll( '.uet-menu-toggle' );
	}

	function addSubmenuToggles( root ) {
		root.querySelectorAll( '.menu-item-has-children' ).forEach( function ( li ) {
			if ( li.querySelector( ':scope > .uet-subtoggle' ) ) {
				return;
			}
			var link = li.querySelector( ':scope > a' );
			var btn = doc.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'uet-subtoggle';
			btn.setAttribute( 'aria-expanded', 'false' );
			btn.setAttribute( 'aria-label', ( link ? link.textContent.trim() + ': ' : '' ) + 'submenu' );
			if ( link ) {
				link.insertAdjacentElement( 'afterend', btn );
			} else {
				li.insertBefore( btn, li.firstChild );
			}
		} );
	}

	function setOpen( open ) {
		var panel = popup();
		if ( ! panel ) {
			return;
		}
		panel.hidden = ! open;
		toggles().forEach( function ( t ) {
			t.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		} );
		var offcanvas = panel.classList.contains( 'uet-popup--offcanvas' );
		body.classList.toggle( 'uet-no-scroll', open && offcanvas );
		if ( open ) {
			addSubmenuToggles( panel );
			var first = panel.querySelector( 'a, button, input' );
			if ( first && offcanvas ) {
				first.focus();
			}
		}
	}

	function isOpen() {
		var panel = popup();
		return !! panel && ! panel.hidden;
	}

	doc.addEventListener( 'click', function ( e ) {
		var toggle = e.target.closest( '.uet-menu-toggle' );
		if ( toggle ) {
			e.preventDefault();
			setOpen( ! isOpen() );
			return;
		}
		if ( e.target.closest( '[data-uet-close]' ) ) {
			setOpen( false );
			return;
		}
		var sub = e.target.closest( '.uet-subtoggle' );
		if ( sub ) {
			var li = sub.parentElement;
			var open = li.classList.toggle( 'is-open' );
			sub.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			return;
		}
		// Close the panel after following a same-page link.
		if ( isOpen() && e.target.closest( '#uet-mobile-popup a[href*="#"]' ) ) {
			setOpen( false );
		}
		// Close an open header search when clicking elsewhere.
		doc.querySelectorAll( '.uet-header-search[open]' ).forEach( function ( d ) {
			if ( ! d.contains( e.target ) ) {
				d.removeAttribute( 'open' );
			}
		} );
		if ( e.target.closest( '.uet-scroll-top' ) ) {
			e.preventDefault();
			window.scrollTo( { top: 0, behavior: 'smooth' } );
		}
	} );

	doc.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) {
			return;
		}
		if ( isOpen() ) {
			setOpen( false );
			var t = toggles()[ 0 ];
			if ( t ) {
				t.focus();
			}
		}
		doc.querySelectorAll( '.uet-header-search[open]' ).forEach( function ( d ) {
			d.removeAttribute( 'open' );
		} );
	} );

	// Close the mobile panel when switching to the desktop header.
	window.addEventListener( 'resize', function () {
		var panel = popup();
		if ( panel && isOpen() && window.getComputedStyle( panel ).display === 'none' ) {
			setOpen( false );
		}
	} );

	var ticking = false;
	function onScroll() {
		var y = window.pageYOffset || doc.documentElement.scrollTop;
		var header = doc.querySelector( '.uet-header' );
		var top = doc.querySelector( '.uet-scroll-top' );
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
}() );
