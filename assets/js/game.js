/**
 * Underworld Empire – live countdowns and mobile menu. No dependencies.
 */
( function () {
	'use strict';

	var root = document.querySelector( '.dfmg' );
	if ( ! root ) {
		return;
	}

	// Difference between server and browser clock, so timers are exact.
	var serverNow = parseInt( root.getAttribute( 'data-now' ), 10 ) || Math.floor( Date.now() / 1000 );
	var offset = serverNow - Math.floor( Date.now() / 1000 );

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
		var nodes = root.querySelectorAll( '.dfmg-countdown' );
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

	var toggle = root.querySelector( '.dfmg-menu-toggle' );
	var nav = root.querySelector( '.dfmg-nav' );
	function setMenu( open ) {
		nav.classList.toggle( 'is-open', open );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	}
	if ( toggle && nav ) {
		toggle.addEventListener( 'click', function () {
			setMenu( ! nav.classList.contains( 'is-open' ) );
		} );
		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && nav.classList.contains( 'is-open' ) ) {
				setMenu( false );
				toggle.focus();
			}
		} );
	}

	// Small screens show tables as cards: give every cell the label of its column.
	root.querySelectorAll( '.dfmg-table' ).forEach( function ( table ) {
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

	// Prevent accidental double submits.
	root.addEventListener( 'submit', function ( e ) {
		var form = e.target;
		if ( form.getAttribute( 'data-sent' ) ) {
			e.preventDefault();
			return;
		}
		form.setAttribute( 'data-sent', '1' );
	} );
} )();
