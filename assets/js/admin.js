/**
 * Underworld Empire admin: filter and search on the Modules screen.
 */
( function () {
	'use strict';

	var toolbar = document.querySelector( '[data-dfmg-modules-toolbar]' );
	if ( ! toolbar ) {
		return;
	}
	var cards = Array.prototype.slice.call( document.querySelectorAll( '.dfmg-admin-module' ) );
	var search = toolbar.querySelector( '[data-dfmg-module-search]' );
	var empty = document.querySelector( '[data-dfmg-no-modules]' );
	var filter = 'all';

	function apply() {
		var q = ( search.value || '' ).trim().toLowerCase();
		var shown = 0;
		cards.forEach( function ( card ) {
			var ok = ( 'all' === filter || card.getAttribute( 'data-state' ) === filter ) &&
				( ! q || card.getAttribute( 'data-search' ).indexOf( q ) > -1 );
			card.hidden = ! ok;
			shown += ok ? 1 : 0;
		} );
		if ( empty ) {
			empty.hidden = shown > 0;
		}
	}

	toolbar.addEventListener( 'click', function ( e ) {
		var btn = e.target.closest( '[data-filter]' );
		if ( ! btn ) {
			return;
		}
		filter = btn.getAttribute( 'data-filter' );
		toolbar.querySelectorAll( '[data-filter]' ).forEach( function ( b ) {
			b.classList.toggle( 'is-active', b === btn );
			b.setAttribute( 'aria-pressed', b === btn ? 'true' : 'false' );
		} );
		apply();
	} );
	search.addEventListener( 'input', apply );
} )();
