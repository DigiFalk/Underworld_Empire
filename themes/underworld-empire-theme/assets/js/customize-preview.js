/* global wp */
( function ( api ) {
	'use strict';
	api( 'blogname', function ( value ) {
		value.bind( function ( to ) {
			document.querySelectorAll( '.uet-site-title a' ).forEach( function ( el ) {
				el.textContent = to;
			} );
		} );
	} );
	api( 'blogdescription', function ( value ) {
		value.bind( function ( to ) {
			document.querySelectorAll( '.uet-site-tagline' ).forEach( function ( el ) {
				el.textContent = to;
			} );
		} );
	} );
}( wp.customize ) );
