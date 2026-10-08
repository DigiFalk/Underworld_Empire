/**
 * Mafia PBBG Engine theme: mobile menu panel, submenus, sticky header, scroll to top.
 * Uses event delegation so it keeps working when the Customizer re-renders the header.
 */
( function () {
	'use strict';

	var doc = document;
	var body = doc.body;

	function popup() {
		return doc.getElementById( 'mpet-mobile-popup' );
	}

	function toggles() {
		return doc.querySelectorAll( '.mpet-menu-toggle' );
	}

	function addSubmenuToggles( root ) {
		root.querySelectorAll( '.menu-item-has-children' ).forEach( function ( li ) {
			if ( li.querySelector( ':scope > .mpet-subtoggle' ) ) {
				return;
			}
			var link = li.querySelector( ':scope > a' );
			var btn = doc.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'mpet-subtoggle';
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
		var offcanvas = panel.classList.contains( 'mpet-popup--offcanvas' );
		body.classList.toggle( 'mpet-no-scroll', open && offcanvas );
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

	/* Light / dark mode: the choice is stored in the browser (see mpet_color_mode_script()). */
	function currentMode() {
		return doc.documentElement.getAttribute( 'data-mpet-mode' ) || 'dark';
	}
	function syncModeButtons() {
		var mode = currentMode();
		doc.querySelectorAll( '.mpet-mode-toggle' ).forEach( function ( btn ) {
			btn.setAttribute( 'aria-pressed', 'light' === mode ? 'true' : 'false' );
			btn.setAttribute( 'title', btn.getAttribute( 'data-label-' + mode ) || '' );
		} );
	}
	function setMode( mode ) {
		var root = doc.documentElement;
		root.classList.add( 'mpet-mode-changing' );
		root.setAttribute( 'data-mpet-mode', mode );
		try {
			window.localStorage.setItem( 'mpet-mode', mode );
		} catch ( err ) {}
		syncModeButtons();
		window.setTimeout( function () {
			root.classList.remove( 'mpet-mode-changing' );
		}, 300 );
	}
	syncModeButtons();

	doc.addEventListener( 'click', function ( e ) {
		var modeBtn = e.target.closest( '.mpet-mode-toggle' );
		if ( modeBtn ) {
			e.preventDefault();
			setMode( 'light' === currentMode() ? 'dark' : 'light' );
			return;
		}
		var toggle = e.target.closest( '.mpet-menu-toggle' );
		if ( toggle ) {
			e.preventDefault();
			setOpen( ! isOpen() );
			return;
		}
		if ( e.target.closest( '[data-mpet-close]' ) ) {
			setOpen( false );
			return;
		}
		var sub = e.target.closest( '.mpet-subtoggle' );
		if ( sub ) {
			var li = sub.parentElement;
			var open = li.classList.toggle( 'is-open' );
			sub.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
			return;
		}
		// Close the panel after following a same-page link.
		if ( isOpen() && e.target.closest( '#mpet-mobile-popup a[href*="#"]' ) ) {
			setOpen( false );
		}
		// Close an open header search when clicking elsewhere.
		doc.querySelectorAll( '.mpet-header-search[open]' ).forEach( function ( d ) {
			if ( ! d.contains( e.target ) ) {
				d.removeAttribute( 'open' );
			}
		} );
		if ( e.target.closest( '.mpet-scroll-top' ) ) {
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
		doc.querySelectorAll( '.mpet-header-search[open]' ).forEach( function ( d ) {
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
		var header = doc.querySelector( '.mpet-header' );
		var top = doc.querySelector( '.mpet-scroll-top' );
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
