/* global wp, uetPalettes */
( function ( api ) {
	'use strict';

	var keys = [ 'base', 'surface', 'surface_2', 'border', 'text', 'muted', 'heading', 'accent', 'link_hover', 'button_bg', 'button_text' ];
	var applying = false;

	api.bind( 'ready', function () {
		// Palette chosen: copy its colours into the individual colour settings.
		api( 'uet_palette', function ( setting ) {
			setting.bind( function ( value ) {
				if ( ! uetPalettes[ value ] ) {
					return;
				}
				applying = true;
				keys.forEach( function ( key ) {
					api( 'uet_color_' + key, function ( color ) {
						color.set( uetPalettes[ value ][ key ] );
					} );
				} );
				applying = false;
			} );
		} );

		// A colour edited by hand: switch the palette to "custom".
		keys.forEach( function ( key ) {
			api( 'uet_color_' + key, function ( color ) {
				color.bind( function () {
					if ( ! applying && api( 'uet_palette' ).get() !== 'custom' ) {
						api( 'uet_palette' ).set( 'custom' );
					}
				} );
			} );
		} );
	} );
}( wp.customize ) );
