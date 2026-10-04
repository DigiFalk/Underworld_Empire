/**
 * Underworld Empire – live countdowns, menus and small helpers. No dependencies.
 * Works for the game itself (.dfmg) and for game elements placed elsewhere (.dfmg-hud).
 */
( function () {
	'use strict';

	// Difference between server and browser clock, so timers are exact.
	var clock = document.querySelector( '.dfmg[data-now], [data-dfmg-now]' );
	var serverNow = clock ? parseInt( clock.getAttribute( 'data-now' ) || clock.getAttribute( 'data-dfmg-now' ), 10 ) : 0;
	var offset = serverNow ? serverNow - Math.floor( Date.now() / 1000 ) : 0;

	function pad( n ) {
		return n < 10 ? '0' + n : String( n );
	}

	function format( s ) {
		var d = Math.floor( s / 86400 ),
			h = Math.floor( ( s % 86400 ) / 3600 ),
			m = Math.floor( ( s % 3600 ) / 60 ),
			sec = s % 60,
			parts = [];
		if ( d ) {
			parts.push( d + 'd' );
		}
		if ( h || d ) {
			parts.push( h + 'h' );
		}
		if ( m || h || d ) {
			parts.push( pad( m ) + 'm' );
		}
		parts.push( pad( sec ) + 's' );
		return parts.join( ' ' );
	}

	function tick() {
		var now = Math.floor( Date.now() / 1000 ) + offset;
		var nodes = document.querySelectorAll( '.dfmg-countdown' );
		for ( var i = 0; i < nodes.length; i++ ) {
			var el = nodes[ i ];
			var left = parseInt( el.getAttribute( 'data-expires' ), 10 ) - now;
			if ( left <= 0 ) {
				el.textContent = el.getAttribute( 'data-done' ) || '';
				el.className = 'dfmg-ready';
			} else {
				el.textContent = format( left );
			}
		}
	}

	tick();
	window.setInterval( tick, 1000 );

	// Sidebar toggle of the game (mobile).
	function setMenu( toggle, open ) {
		var nav = document.getElementById( toggle.getAttribute( 'aria-controls' ) );
		if ( nav ) {
			nav.classList.toggle( 'is-open', open );
			toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
		}
	}
	document.addEventListener( 'click', function ( e ) {
		var toggle = e.target.closest ? e.target.closest( '.dfmg-menu-toggle' ) : null;
		if ( toggle ) {
			setMenu( toggle, 'true' !== toggle.getAttribute( 'aria-expanded' ) );
		}
		// Close open dropdown menus when clicking elsewhere.
		document.querySelectorAll( '.dfmg-hud-menu[open]' ).forEach( function ( menu ) {
			if ( ! menu.contains( e.target ) ) {
				menu.removeAttribute( 'open' );
			}
		} );
	} );
	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' !== e.key ) {
			return;
		}
		document.querySelectorAll( '.dfmg-menu-toggle[aria-expanded="true"]' ).forEach( function ( toggle ) {
			setMenu( toggle, false );
			toggle.focus();
		} );
		document.querySelectorAll( '.dfmg-hud-menu[open]' ).forEach( function ( menu ) {
			menu.removeAttribute( 'open' );
			menu.querySelector( 'summary' ).focus();
		} );
	} );

	// Keep an opened dropdown menu inside the screen.
	document.addEventListener( 'toggle', function ( e ) {
		var menu = e.target;
		if ( ! menu.classList || ! menu.classList.contains( 'dfmg-hud-menu' ) || ! menu.open ) {
			return;
		}
		var panel = menu.querySelector( '.dfmg-hud-menu__panel' );
		panel.style.left = '';
		panel.style.right = '';
		if ( window.innerWidth <= 720 ) {
			return;
		}
		var r = panel.getBoundingClientRect();
		var shift = 0;
		if ( r.right > window.innerWidth - 8 ) {
			shift = Math.max( 8 - r.left, window.innerWidth - 8 - r.right );
		} else if ( r.left < 8 ) {
			shift = 8 - r.left;
		}
		if ( shift ) {
			panel.style.left = ( panel.offsetLeft + shift ) + 'px';
			panel.style.right = 'auto';
		}
	}, true );

	// Small screens show tables as cards: give every cell the label of its column.
	function labelTables( scope ) {
		scope.querySelectorAll( '.dfmg-table' ).forEach( function ( table ) {
			var heads = table.querySelectorAll( 'thead th' );
			if ( ! heads.length ) {
				return;
			}
			table.querySelectorAll( 'tbody tr' ).forEach( function ( row ) {
				Array.prototype.forEach.call( row.children, function ( cell, i ) {
					if ( heads[ i ] && ! cell.hasAttribute( 'data-label' ) ) {
						cell.setAttribute( 'data-label', heads[ i ].textContent.trim() );
					}
				} );
			} );
		} );
	}
	labelTables( document );

	// Check the size of an avatar before uploading (too large files never reach the server).
	document.addEventListener( 'change', function ( e ) {
		var input = e.target;
		if ( ! input.matches || ! input.matches( 'input[type="file"][data-max-kb]' ) ) {
			return;
		}
		var max = parseInt( input.getAttribute( 'data-max-kb' ), 10 ) * 1024;
		var file = input.files && input.files[ 0 ];
		input.setCustomValidity( file && max && file.size > max ? ( input.closest( 'form' ).querySelector( '.dfmg-muted' ) || {} ).textContent || 'Too large' : '' );
		input.reportValidity();
	} );

	// Prevent accidental double submits.
	document.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		if ( ! form.closest || ! form.closest( '.dfmg, .dfmg-hud' ) ) {
			return;
		}
		if ( form.getAttribute( 'data-sent' ) ) {
			e.preventDefault();
			return;
		}
		form.setAttribute( 'data-sent', '1' );
	} );

	// Customizer live preview: the game is re-rendered after layout changes.
	document.addEventListener( 'DOMContentLoaded', function () {
		if ( window.wp && window.wp.customize && window.wp.customize.selectiveRefresh ) {
			window.wp.customize.selectiveRefresh.bind( 'partial-content-rendered', function () {
				labelTables( document );
				tick();
			} );
		}
	} );
} )();
