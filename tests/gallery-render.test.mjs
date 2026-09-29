import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import test from 'node:test';

const template = new URL( '../woocommerce/single-product/product-image.php', import.meta.url ).pathname;

test( 'gallery slider keeps full-width slides inside the square viewport', () => {
	const theme = readFileSync( new URL( '../inc/class-cbv-theme.php', import.meta.url ), 'utf8' );
	const css = readFileSync( new URL( '../assets/css/main.css', import.meta.url ), 'utf8' );
	assert.match( theme, /add_theme_support\( 'wc-product-gallery-slider' \)/ );
	assert.match( css, /\.flex-viewport > \.woocommerce-product-gallery__wrapper\s*\{[^}]*flex: 0 0 auto/ );
	assert.match( css, /\.woocommerce div\.product \.woocommerce-product-gallery\s*\{[^}]*min-width: 0/ );
} );

function render( scenario ) {
	return execFileSync( 'php', [ new URL( 'fixtures/gallery-render.php', import.meta.url ).pathname, template, scenario ], { encoding: 'utf8' } );
}

test( 'ordered gallery media wins over featured image and thumbnails remain slides', () => {
	const html = render( 'image' );
	assert.match( html, /image-42/ );
	assert.doesNotMatch( html, /image-10/ );
	assert.match( html, /image-filter-42/ );
	assert.match( html, /image-filter-42<\/span>\s*<div>thumbnail-slide<\/div>\s*<\/div>/ );
	assert.match( html, /badge-hook/ );
} );

test( 'video uses media renderer and video filter without fallback thumbnails', () => {
	const html = render( 'video' );
	assert.match( html, /video-42/ );
	assert.match( html, /video-filter-42/ );
	assert.doesNotMatch( html, /image-filter|flex-control-thumbs/ );
} );

test( 'placeholder retains customer fallback image and thumbnail count', () => {
	const html = render( 'placeholder' );
	assert.match( html, /custom-fallback.png/ );
	assert.equal( ( html.match( /<li>/g ) || [] ).length, 2 );
} );

test( 'older WooCommerce without media API renders the featured image', () => {
	assert.match( render( 'legacy' ), /image-10/ );
} );

test( 'invalid product produces no markup', () => {
	assert.equal( render( 'invalid' ), '' );
} );
