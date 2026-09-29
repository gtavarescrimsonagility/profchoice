/**
 * Customizer layout organizer: drag to reorder, toggle to enable, and
 * per-element settings. The whole layout serializes to one JSON setting.
 */
( function ( $ ) {
	'use strict';

	function serialize( $root ) {
		const layout = {};

		$root.find( '.cbv-layout__area' ).each( function () {
			const area = $( this ).data( 'area' );
			const rows = [];

			$( this ).find( '.cbv-layout__item' ).each( function () {
				const $item = $( this );
				const options = {};

				$item.find( '.cbv-layout__option' ).each( function () {
					const $field = $( this );
					const key = $field.data( 'option' );

					if ( 'checkbox' === $field.data( 'type' ) ) {
						options[ key ] = $field.is( ':checked' );
					} else if ( 'number' === $field.data( 'type' ) ) {
						options[ key ] = parseInt( $field.val(), 10 ) || 0;
					} else {
						options[ key ] = $field.val();
					}
				} );

				rows.push( {
					id: $item.data( 'id' ),
					enabled: $item.find( '.cbv-layout__enabled' ).is( ':checked' ),
					options: options
				} );
			} );

			layout[ area ] = rows;
		} );

		const $input = $root.find( '.cbv-layout__value' );
		$input.val( JSON.stringify( layout ) ).trigger( 'change' );
	}

	$( document ).on( 'click', '.cbv-layout__settings-toggle', function ( event ) {
		event.preventDefault();
		const $settings = $( this ).closest( '.cbv-layout__item' ).find( '.cbv-layout__settings' );
		$settings.prop( 'hidden', ! $settings.prop( 'hidden' ) );
	} );

	function init() {
		$( '.cbv-layout' ).each( function () {
			const $root = $( this );

			if ( $root.data( 'cbvInit' ) ) {
				return;
			}

			$root.data( 'cbvInit', true );

			$root.find( '.cbv-layout__list' ).sortable( {
				handle: '.cbv-layout__handle',
				axis: 'y',
				update: function () {
					serialize( $root );
				}
			} );

			$root.on( 'change', '.cbv-layout__enabled, .cbv-layout__option', function () {
				serialize( $root );
			} );

			$root.on( 'keyup', 'input[type="text"].cbv-layout__option, textarea.cbv-layout__option', function () {
				serialize( $root );
			} );
		} );
	}

	$( window ).on( 'load', init );
	$( document ).on( 'widget-added expanded', init );

	if ( window.wp && window.wp.customize ) {
		window.wp.customize.bind( 'ready', init );
	}
} )( jQuery );
