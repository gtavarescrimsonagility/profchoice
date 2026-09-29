/**
 * Live Customizer preview: update CSS custom properties without a reload.
 */
( function ( api ) {
	'use strict';

	if ( ! api || ! window.cbvPalette ) {
		return;
	}

	function setVar( property, value ) {
		// Set on both html and body: the stylesheet defines the variables
		// on body too (for the editor canvas), which would otherwise
		// override a value set only on the root element.
		document.documentElement.style.setProperty( property, value );

		if ( document.body ) {
			document.body.style.setProperty( property, value );
		}
	}

	// WCAG relative luminance; mirrors CBV_Customizer::relative_luminance().
	function luminance( hex ) {
		var match = /^#?([0-9a-f]{6})$/i.exec(
			hex.length === 4 ? '#' + hex[ 1 ] + hex[ 1 ] + hex[ 2 ] + hex[ 2 ] + hex[ 3 ] + hex[ 3 ] : hex
		);

		if ( ! match ) {
			return null;
		}

		var parts = [ 0, 2, 4 ].map( function ( offset ) {
			var channel = parseInt( match[ 1 ].substr( offset, 2 ), 16 ) / 255;
			return channel <= 0.04045 ? channel / 12.92 : Math.pow( ( channel + 0.055 ) / 1.055, 2.4 );
		} );

		return ( 0.2126 * parts[ 0 ] ) + ( 0.7152 * parts[ 1 ] ) + ( 0.0722 * parts[ 2 ] );
	}

	// Mirrors CBV_Customizer::contrast_color().
	function contrastColor( hex ) {
		var backgroundLuminance = luminance( hex );

		if ( null === backgroundLuminance ) {
			return '#ffffff';
		}

		var darkLuminance = luminance( '#111827' );
		var ratioWhite    = 1.05 / ( backgroundLuminance + 0.05 );
		var ratioDark     = ( Math.max( backgroundLuminance, darkLuminance ) + 0.05 ) / ( Math.min( backgroundLuminance, darkLuminance ) + 0.05 );

		if ( Math.max( ratioDark, ratioWhite ) < 4.5 ) {
			return '#000000';
		}

		return ratioDark >= ratioWhite ? '#111827' : '#ffffff';
	}

	function applyContrast() {
		var mode = api( 'cbv_primary_contrast_mode' ) ? api( 'cbv_primary_contrast_mode' )() : 'auto';

		if ( 'custom' === mode ) {
			var picked = api( 'cbv_color_primary_contrast' ) ? api( 'cbv_color_primary_contrast' )() : '';
			setVar( '--cbv-primary-contrast', picked || '#ffffff' );
			return;
		}

		var primary = api( 'cbv_color_primary' ) ? api( 'cbv_color_primary' )() : '';

		if ( primary ) {
			setVar( '--cbv-primary-contrast', contrastColor( primary ) );
		}
	}

	const derived_contrast = {
		secondary: 'secondary-contrast',
		footer_bg: 'footer-contrast',
		success: 'success-contrast',
		danger: 'danger-contrast',
		accent: 'accent-contrast'
	};

	function applyDerivedContrast( source, value ) {
		if ( derived_contrast[ source ] && value ) {
			setVar( '--cbv-' + derived_contrast[ source ], contrastColor( value ) );
		}
	}

	window.cbvPalette.slugs.forEach( function ( slug ) {
		api( 'cbv_color_' + slug, function ( setting ) {
			setting.bind( function ( value ) {
				if ( 'primary_contrast' === slug ) {
					applyContrast();
					return;
				}

				setVar( '--cbv-' + slug.replace( /_/g, '-' ), value );
				applyDerivedContrast( slug, value );

				// In auto mode the contrast color tracks the primary.
				if ( 'primary' === slug ) {
					applyContrast();
				}
			} );
		} );
	} );

	api( 'cbv_primary_contrast_mode', function ( setting ) {
		setting.bind( applyContrast );
	} );
} )( window.wp && window.wp.customize );
