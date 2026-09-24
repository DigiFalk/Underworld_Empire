/* global wp, jQuery, dfmgLayout */
/**
 * Underworld Empire – drag & drop game layout in the Customizer.
 * Drag game elements between the zones of a small map of the game page;
 * the preview updates immediately (selective refresh).
 */
( function ( api, $ ) {
	'use strict';

	var cfg = dfmgLayout;
	var t = cfg.i18n;

	function chip( id ) {
		var $li = $( '<li class="dfmg-lchip" tabindex="0"></li>' ).attr( 'data-el', id );
		$( '<span class="dfmg-lchip__label"></span>' ).text( cfg.elements[ id ] || id ).appendTo( $li );
		$( '<button type="button" class="dfmg-lchip__x">&times;</button>' ).attr( 'aria-label', t.remove ).appendTo( $li );
		return $li;
	}

	function zone( key, extraClass ) {
		var $wrap = $( '<div class="dfmg-lzone"></div>' ).addClass( extraClass || '' ).attr( 'data-zone-wrap', key );
		$( '<span class="dfmg-lzone__label"></span>' ).text( cfg.zones[ key ] ).appendTo( $wrap );
		$( '<ul class="dfmg-lzone__list"></ul>' ).attr( 'data-zone', key ).appendTo( $wrap );
		$( '<select class="dfmg-lzone__add"></select>' ).attr( 'aria-label', t.add + ': ' + cfg.zones[ key ] ).appendTo( $wrap );
		return $wrap;
	}

	function Builder( control ) {
		var $panel = $( '<div class="dfmg-layout-panel" hidden></div>' );
		var data;

		function parse( value ) {
			try {
				return JSON.parse( value || '' ) || {};
			} catch ( e ) {
				return {};
			}
		}
		data = parse( control.setting.get() );
		if ( ! Object.keys( data ).length ) {
			data = cfg.defaults;
		}

		// Bar.
		var $bar = $( '<div class="dfmg-layout-bar"></div>' ).appendTo( $panel );
		$( '<strong></strong>' ).text( t.title ).appendTo( $bar );
		$( '<button type="button" class="button dfmg-layout-reset"></button>' ).text( t.reset ).appendTo( $bar );
		$( '<button type="button" class="dfmg-layout-min dashicons dashicons-arrow-down-alt2" aria-label="Hide"></button>' ).appendTo( $bar );

		// Map of the game page.
		var $map = $( '<div class="dfmg-layout-map"></div>' ).appendTo( $panel );
		var $header = $( '<div class="dfmg-lrow dfmg-lrow--header"></div>' ).appendTo( $map );
		$( '<span class="dfmg-lrow__label"></span>' ).text( t.header ).appendTo( $header );
		$( '<div class="dfmg-lrow__zones dfmg-lrow__zones--3"></div>' ).append( zone( 'header-left' ), zone( 'header-center' ), zone( 'header-right' ) ).appendTo( $header );

		var $body = $( '<div class="dfmg-lrow dfmg-lrow--body"></div>' ).appendTo( $map );
		$( '<span class="dfmg-lrow__label"></span>' ).text( t.content ).appendTo( $body );
		var $bodyZones = $( '<div class="dfmg-lrow__zones dfmg-lrow__zones--body"></div>' ).appendTo( $body );
		$bodyZones.append( zone( 'sidebar', 'dfmg-lzone--sidebar' ) );
		$( '<div class="dfmg-lmain"></div>' )
			.append( zone( 'top' ) )
			.append( $( '<div class="dfmg-lmain__content"></div>' ).text( t.page ) )
			.append( zone( 'bottom' ) )
			.appendTo( $bodyZones );

		var $footer = $( '<div class="dfmg-lrow dfmg-lrow--footer"></div>' ).appendTo( $map );
		$( '<span class="dfmg-lrow__label"></span>' ).text( t.footer ).appendTo( $footer );
		$( '<div class="dfmg-lrow__zones dfmg-lrow__zones--3"></div>' ).append( zone( 'footer-left' ), zone( 'footer-center' ), zone( 'footer-right' ) ).appendTo( $footer );

		var $pool = $( '<div class="dfmg-lpool"></div>' ).appendTo( $panel );
		$( '<span class="dfmg-lzone__label"></span>' ).text( t.available ).appendTo( $pool );
		$( '<ul class="dfmg-lzone__list dfmg-lzone__list--pool"></ul>' ).appendTo( $pool );

		function fill( layout ) {
			$panel.find( '.dfmg-lzone__list:not(.dfmg-lzone__list--pool)' ).each( function () {
				var $ul = $( this ).empty();
				( layout[ $ul.data( 'zone' ) ] || [] ).forEach( function ( id ) {
					if ( cfg.elements[ id ] ) {
						chip( id ).appendTo( $ul );
					}
				} );
			} );
			refresh();
		}

		function collect() {
			var out = {};
			Object.keys( cfg.zones ).forEach( function ( key ) {
				out[ key ] = $panel.find( '.dfmg-lzone__list[data-zone="' + key + '"] .dfmg-lchip' ).map( function () {
					return $( this ).data( 'el' );
				} ).get();
			} );
			return out;
		}

		// Each element can be placed once: the rest stays in the pool.
		function refresh() {
			var used = $panel.find( '.dfmg-lzone__list:not(.dfmg-lzone__list--pool) .dfmg-lchip' ).map( function () {
				return $( this ).data( 'el' );
			} ).get();
			var free = Object.keys( cfg.elements ).filter( function ( id ) {
				return used.indexOf( id ) < 0;
			} );
			var $list = $pool.find( '.dfmg-lzone__list--pool' ).empty();
			free.forEach( function ( id ) {
				chip( id ).appendTo( $list );
			} );
			$panel.find( '.dfmg-lzone__add' ).each( function () {
				var $s = $( this ).empty().append( '<option value="">+</option>' );
				free.forEach( function ( id ) {
					$s.append( $( '<option></option>' ).val( id ).text( cfg.elements[ id ] ) );
				} );
				$s.prop( 'disabled', ! free.length );
			} );
			$panel.find( '.dfmg-lzone' ).each( function () {
				$( this ).toggleClass( 'is-empty', ! $( this ).find( '.dfmg-lchip' ).length );
			} );
		}

		function save() {
			control.setting.set( JSON.stringify( collect() ) );
			refresh();
		}

		fill( data );

		var $lists = $panel.find( '.dfmg-lzone__list' );
		$lists.sortable( {
			connectWith: $lists,
			items: '> .dfmg-lchip',
			placeholder: 'dfmg-lchip dfmg-lchip--placeholder',
			tolerance: 'pointer',
			cancel: 'button',
			stop: save
		} );

		$panel.on( 'click', '.dfmg-lchip__x', function () {
			$( this ).closest( '.dfmg-lchip' ).remove();
			save();
		} );
		$panel.on( 'change', '.dfmg-lzone__add', function () {
			if ( this.value ) {
				chip( this.value ).appendTo( $( this ).siblings( '.dfmg-lzone__list' ) );
				save();
			}
		} );
		$panel.on( 'click', '.dfmg-layout-reset', function () {
			fill( cfg.defaults );
			save();
		} );
		$panel.on( 'click', '.dfmg-layout-min', function () {
			$panel.toggleClass( 'is-minimized' );
		} );

		$panel.appendTo( 'body' );
		api.section( control.section(), function ( section ) {
			section.expanded.bind( function ( open ) {
				$panel.prop( 'hidden', ! open );
				$( 'body' ).toggleClass( 'dfmg-layout-open', open );
				// Show the game in the preview while editing its layout.
				if ( open && cfg.gameUrl && api.previewer.previewUrl.get().split( '?' )[ 0 ] !== cfg.gameUrl.split( '?' )[ 0 ] ) {
					api.previewer.previewUrl.set( cfg.gameUrl );
				}
			} );
		} );
	}

	api.controlConstructor.dfmg_layout = api.Control.extend( {
		ready: function () {
			new Builder( this ); // eslint-disable-line no-new
		}
	} );

	api.bind( 'ready', function () {
		if ( api.state && api.state.has( 'paneVisible' ) ) {
			api.state( 'paneVisible' ).bind( function ( visible ) {
				$( 'body' ).toggleClass( 'dfmg-pane-hidden', ! visible );
			} );
		}
	} );
}( wp.customize, jQuery ) );
