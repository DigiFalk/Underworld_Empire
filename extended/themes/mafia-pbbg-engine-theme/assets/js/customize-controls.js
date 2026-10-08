/* global wp, jQuery, mpetCustomizer */
/**
 * Mafia PBBG Engine theme – Customizer controls:
 *  - colour palettes,
 *  - desktop / tablet / mobile values,
 *  - drag & drop header and footer builder.
 */
( function ( api, $ ) {
	'use strict';

	var cfg = mpetCustomizer;
	var t = cfg.i18n;

	/* Palettes ------------------------------------------------------------------ */

	var colorKeys = [ 'base', 'surface', 'surface_2', 'border', 'text', 'muted', 'heading', 'accent', 'link_hover', 'button_bg', 'button_text' ];
	var applyingPalette = false;

	// A palette select fills its colour settings; changing a colour switches it to "custom".
	function bindPalette( paletteId, prefix ) {
		api( paletteId, function ( palette ) {
			palette.bind( function ( value ) {
				if ( ! cfg.palettes[ value ] ) {
					return;
				}
				applyingPalette = true;
				colorKeys.forEach( function ( key ) {
					api( prefix + key, function ( color ) {
						color.set( cfg.palettes[ value ][ key ] );
					} );
				} );
				applyingPalette = false;
			} );
		} );
		colorKeys.forEach( function ( key ) {
			api( prefix + key, function ( color ) {
				color.bind( function () {
					if ( ! applyingPalette && 'custom' !== api( paletteId ).get() ) {
						api( paletteId ).set( 'custom' );
					}
				} );
			} );
		} );
	}

	function bindPalettes() {
		bindPalette( 'mpet_palette', 'mpet_color_' );
		bindPalette( 'mpet_light_palette', 'mpet_light_color_' );
	}

	/* Responsive values --------------------------------------------------------- */

	function showDevice( device ) {
		$( '.mpet-responsive' ).each( function () {
			var $c = $( this );
			$c.find( '.mpet-device-btn' ).removeClass( 'is-active' ).filter( '[data-device="' + device + '"]' ).addClass( 'is-active' );
			$c.find( '.mpet-responsive__field' ).hide().filter( '[data-device="' + device + '"]' ).show();
		} );
		$( '.mpet-builder-panel' ).each( function () {
			var tab = 'desktop' === device ? 'desktop' : 'mobile';
			$( this ).find( '.mpet-builder-tab[data-device="' + tab + '"]' ).trigger( 'mpet-select' );
		} );
	}

	function bindResponsive() {
		$( document ).on( 'click', '.mpet-device-btn', function () {
			api.previewedDevice.set( $( this ).data( 'device' ) );
		} );
		$( document ).on( 'input', '.mpet-responsive .mpet-range', function () {
			$( this ).siblings( '.mpet-number' ).val( this.value ).trigger( 'change' );
		} );
		$( document ).on( 'input change', '.mpet-responsive .mpet-number', function () {
			$( this ).siblings( '.mpet-range' ).val( this.value );
		} );
		api.previewedDevice.bind( showDevice );
		showDevice( api.previewedDevice.get() || 'desktop' );
	}

	/* Builder -------------------------------------------------------------------- */

	function chip( id, labels ) {
		var $li = $( '<li class="mpet-chip" tabindex="0"></li>' ).attr( 'data-el', id );
		$( '<span class="mpet-chip__label"></span>' ).text( labels[ id ] || id ).appendTo( $li );
		$( '<button type="button" class="mpet-chip__edit dashicons dashicons-admin-generic"></button>' ).attr( { title: t.settings, 'aria-label': t.settings } ).appendTo( $li );
		$( '<button type="button" class="mpet-chip__x" aria-label="' + t.remove + '">&times;</button>' ).appendTo( $li );
		return $li;
	}

	function zone( key, label, items, labels ) {
		var $z = $( '<div class="mpet-bzone-wrap"></div>' );
		$( '<span class="mpet-bzone-label"></span>' ).text( label ).appendTo( $z );
		var $ul = $( '<ul class="mpet-bzone"></ul>' ).attr( 'data-zone', key ).appendTo( $z );
		( items || [] ).forEach( function ( id ) {
			chip( id, labels ).appendTo( $ul );
		} );
		var $add = $( '<select class="mpet-bzone-add"></select>' ).attr( 'aria-label', t.add + ': ' + label );
		$z.append( $add );
		return $z;
	}

	function Builder( control ) {
		var type = control.params.type === 'mpet_builder' ? control.container.find( '.mpet-builder' ).data( 'builder' ) : 'header';
		var conf = cfg[ type ];
		var labels = conf.elements;
		var $panel = $( '<div class="mpet-builder-panel" hidden></div>' ).addClass( 'mpet-builder-panel--' + type );
		var data;

		try {
			data = JSON.parse( control.setting.get() || '{}' );
		} catch ( e ) {
			data = {};
		}

		function save() {
			var out = {};
			if ( 'header' === type ) {
				[ 'desktop', 'mobile' ].forEach( function ( device ) {
					out[ device ] = {};
					Object.keys( conf.rows ).forEach( function ( row ) {
						out[ device ][ row ] = {};
						Object.keys( conf.zones ).forEach( function ( z ) {
							out[ device ][ row ][ z ] = collect( $panel.find( '.mpet-bdevice[data-device="' + device + '"] .mpet-bzone[data-zone="' + row + '.' + z + '"]' ) );
						} );
					} );
				} );
				out.mobile.popup = collect( $panel.find( '.mpet-bzone[data-zone="popup"]' ) );
			} else {
				Object.keys( conf.rows ).forEach( function ( row ) {
					var cols = parseInt( $panel.find( '.mpet-cols-select[data-row="' + row + '"]' ).val(), 10 ) || 0;
					out[ row ] = { cols: cols };
					[ 'c1', 'c2', 'c3', 'c4' ].forEach( function ( c ) {
						out[ row ][ c ] = collect( $panel.find( '.mpet-bzone[data-zone="' + row + '.' + c + '"]' ) );
					} );
				} );
			}
			control.setting.set( JSON.stringify( out ) );
			refreshPools();
		}

		function collect( $ul ) {
			return $ul.children( '.mpet-chip' ).map( function () {
				return $( this ).data( 'el' );
			} ).get();
		}

		// Every element can be used once per device (header) or once in the footer.
		function refreshPools() {
			$panel.find( '.mpet-bdevice' ).each( function () {
				var $dev = $( this );
				var used = $dev.find( '.mpet-bzone:not(.mpet-bzone--pool) .mpet-chip' ).map( function () {
					return $( this ).data( 'el' );
				} ).get();
				var free = Object.keys( labels ).filter( function ( id ) {
					return used.indexOf( id ) < 0;
				} );
				var $pool = $dev.find( '.mpet-bzone--pool' ).empty();
				free.forEach( function ( id ) {
					chip( id, labels ).appendTo( $pool );
				} );
				$dev.find( '.mpet-bzone-add' ).each( function () {
					var $s = $( this ).empty().append( $( '<option value="">+</option>' ) );
					free.forEach( function ( id ) {
						$s.append( $( '<option></option>' ).val( id ).text( labels[ id ] ) );
					} );
					$s.prop( 'disabled', ! free.length );
				} );
				$dev.find( '.mpet-bzone-wrap' ).each( function () {
					$( this ).toggleClass( 'is-empty', ! $( this ).find( '.mpet-chip' ).length );
				} );
			} );
		}

		function buildHeaderDevice( device ) {
			var $dev = $( '<div class="mpet-bdevice"></div>' ).attr( 'data-device', device );
			Object.keys( conf.rows ).forEach( function ( row ) {
				var $row = $( '<div class="mpet-brow"></div>' ).appendTo( $dev );
				$( '<div class="mpet-brow__label"></div>' ).text( conf.rows[ row ] ).appendTo( $row );
				var $zones = $( '<div class="mpet-brow__zones mpet-brow__zones--3"></div>' ).appendTo( $row );
				Object.keys( conf.zones ).forEach( function ( z ) {
					var items = data[ device ] && data[ device ][ row ] ? data[ device ][ row ][ z ] : [];
					zone( row + '.' + z, conf.zones[ z ], items, labels ).appendTo( $zones );
				} );
			} );
			if ( 'mobile' === device ) {
				var $row = $( '<div class="mpet-brow mpet-brow--popup"></div>' ).appendTo( $dev );
				$( '<div class="mpet-brow__label"></div>' ).text( t.popup ).appendTo( $row );
				var $zones = $( '<div class="mpet-brow__zones"></div>' ).appendTo( $row );
				zone( 'popup', t.popup, data.mobile ? data.mobile.popup : [], labels ).appendTo( $zones );
			}
			$( '<div class="mpet-bpool"></div>' ).append( $( '<span class="mpet-bzone-label"></span>' ).text( t.available ) ).append( '<ul class="mpet-bzone mpet-bzone--pool"></ul>' ).appendTo( $dev );
			return $dev;
		}

		function buildFooter() {
			var $dev = $( '<div class="mpet-bdevice"></div>' ).attr( 'data-device', 'footer' );
			Object.keys( conf.rows ).forEach( function ( row ) {
				var rowData = data[ row ] || { cols: 0 };
				var $row = $( '<div class="mpet-brow"></div>' ).appendTo( $dev );
				var $label = $( '<div class="mpet-brow__label"></div>' ).text( conf.rows[ row ] ).appendTo( $row );
				var $sel = $( '<select class="mpet-cols-select"></select>' ).attr( { 'data-row': row, 'aria-label': conf.rows[ row ] + ' – ' + t.columns } );
				for ( var i = 0; i <= 4; i++ ) {
					$sel.append( $( '<option></option>' ).val( i ).text( i ? i + ' ' + t.columns.toLowerCase() : t.hidden ) );
				}
				$sel.val( rowData.cols || 0 ).appendTo( $label );
				var $zones = $( '<div class="mpet-brow__zones"></div>' ).appendTo( $row );
				[ 'c1', 'c2', 'c3', 'c4' ].forEach( function ( c, idx ) {
					zone( row + '.' + c, t.column + ' ' + ( idx + 1 ), rowData[ c ] || [], labels ).appendTo( $zones );
				} );
				layoutFooterRow( $row, rowData.cols || 0 );
			} );
			$( '<div class="mpet-bpool"></div>' ).append( $( '<span class="mpet-bzone-label"></span>' ).text( t.available ) ).append( '<ul class="mpet-bzone mpet-bzone--pool"></ul>' ).appendTo( $dev );
			return $dev;
		}

		function layoutFooterRow( $row, cols ) {
			var $wraps = $row.find( '.mpet-bzone-wrap' );
			$wraps.each( function ( i ) {
				$( this ).toggle( i < cols );
			} );
			$row.find( '.mpet-brow__zones' ).attr( 'data-cols', cols );
			$row.toggleClass( 'is-hidden-row', ! cols );
		}

		// Build the UI.
		var $bar = $( '<div class="mpet-builder-bar"></div>' ).appendTo( $panel );
		if ( 'header' === type ) {
			[ 'desktop', 'mobile' ].forEach( function ( device ) {
				$( '<button type="button" class="mpet-builder-tab"></button>' ).attr( 'data-device', device ).text( t[ device ] ).appendTo( $bar );
			} );
			$panel.append( buildHeaderDevice( 'desktop' ) ).append( buildHeaderDevice( 'mobile' ) );
		} else {
			$panel.append( buildFooter() );
		}
		$( '<button type="button" class="mpet-builder-close dashicons dashicons-arrow-down-alt2"></button>' ).attr( 'aria-label', 'Hide' ).appendTo( $bar );

		$panel.on( 'click mpet-select', '.mpet-builder-tab', function () {
			var device = $( this ).data( 'device' );
			$panel.find( '.mpet-builder-tab' ).removeClass( 'is-active' ).filter( this ).addClass( 'is-active' );
			$panel.find( '.mpet-bdevice' ).hide().filter( '[data-device="' + device + '"]' ).show();
		} );
		$panel.on( 'click', '.mpet-builder-tab', function () {
			api.previewedDevice.set( 'desktop' === $( this ).data( 'device' ) ? 'desktop' : 'mobile' );
		} );
		$panel.find( '.mpet-builder-tab' ).first().trigger( 'mpet-select' );

		$panel.on( 'click', '.mpet-builder-close', function () {
			$panel.toggleClass( 'is-minimized' );
		} );

		// Remove an element (back to the pool).
		$panel.on( 'click', '.mpet-chip__x', function () {
			$( this ).closest( '.mpet-chip' ).remove();
			save();
		} );
		// Open the settings of an element.
		$panel.on( 'click', '.mpet-chip__edit', function () {
			var el = $( this ).closest( '.mpet-chip' ).data( 'el' );
			var key = ( 'footer' === type && 'html' === el ) ? 'footer-html' : el;
			var target = cfg.sections[ key ];
			if ( target && api.section( target ) ) {
				api.section( target ).focus();
			}
		} );
		// Keyboard / touch friendly: add through the select.
		$panel.on( 'change', '.mpet-bzone-add', function () {
			var id = $( this ).val();
			if ( id ) {
				chip( id, labels ).appendTo( $( this ).siblings( '.mpet-bzone' ) );
				save();
			}
		} );
		$panel.on( 'change', '.mpet-cols-select', function () {
			layoutFooterRow( $( this ).closest( '.mpet-brow' ), parseInt( this.value, 10 ) || 0 );
			save();
		} );

		$panel.find( '.mpet-bdevice' ).each( function () {
			var $zones = $( this ).find( '.mpet-bzone' );
			$zones.sortable( {
				connectWith: $zones,
				items: '> .mpet-chip',
				placeholder: 'mpet-chip mpet-chip--placeholder',
				tolerance: 'pointer',
				cancel: 'button',
				stop: function () {
					save();
				}
			} );
		} );
		refreshPools();

		// Show at the bottom of the preview while the builder section is open.
		$panel.appendTo( 'body' );
		api.section( control.section(), function ( section ) {
			section.expanded.bind( function ( open ) {
				$panel.prop( 'hidden', ! open );
				$( 'body' ).toggleClass( 'mpet-builder-open', open );
			} );
		} );
	}

	/* Boot ----------------------------------------------------------------------- */

	api.controlConstructor.mpet_builder = api.Control.extend( {
		ready: function () {
			new Builder( this ); // eslint-disable-line no-new
		}
	} );

	api.bind( 'ready', function () {
		bindPalettes();
		bindResponsive();
		if ( api.state && api.state.has( 'paneVisible' ) ) {
			api.state( 'paneVisible' ).bind( function ( visible ) {
				$( 'body' ).toggleClass( 'mpet-pane-hidden', ! visible );
			} );
		}
	} );
}( wp.customize, jQuery ) );
