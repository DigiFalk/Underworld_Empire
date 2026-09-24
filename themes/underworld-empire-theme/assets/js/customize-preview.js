/* global wp */
/**
 * Customizer preview: instant site title / tagline, and keep the mobile panel
 * closed after the header is re-rendered.
 */
( function ( api ) {
	'use strict';
	[ [ 'blogname', '.uet-site-title a' ], [ 'blogdescription', '.uet-site-tagline' ] ].forEach( function ( pair ) {
		api( pair[ 0 ], function ( value ) {
			value.bind( function ( to ) {
				document.querySelectorAll( pair[ 1 ] ).forEach( function ( el ) {
					el.textContent = to;
				} );
			} );
		} );
	} );
	api.bind( 'preview-ready', function () {
		if ( api.selectiveRefresh ) {
			api.selectiveRefresh.bind( 'partial-content-rendered', function () {
				document.body.classList.remove( 'uet-no-scroll' );
			} );
		}
	} );
}( wp.customize ) );
