import assert from 'node:assert/strict';
import { existsSync, readFileSync } from 'node:fs';
import test from 'node:test';

const fileUrl = ( path ) => new URL( `../${ path }`, import.meta.url );
const read = ( path ) => readFileSync( fileUrl( path ), 'utf8' );
const exists = ( path ) => existsSync( fileUrl( path ) );

const css = read( 'assets/css/main.css' );
const mainScript = read( 'assets/js/main.js' );
const deferredScript = read( 'assets/js/deferred.js' );
const heroScript = read( 'blocks/hero-slider/view.js' );
const heroRender = read( 'blocks/hero-slider/render.php' );
const catalogRender = read( 'inc/class-cbv-catalog-elements.php' );
const customizer = read( 'inc/class-cbv-customizer.php' );
const deferred = read( 'inc/class-cbv-deferred.php' );
const theme = read( 'inc/class-cbv-theme.php' );
const blockAreas = read( 'inc/class-cbv-block-areas.php' );
const blockRegistration = read( 'inc/class-cbv-blocks.php' );
const design = read( 'inc/class-cbv-design.php' );
const icons = read( 'inc/class-cbv-icons.php' );
const archive = read( 'woocommerce/archive-product.php' );
const layoutControl = read( 'inc/class-cbv-layout-control.php' );
const layoutControlCss = read( 'assets/css/layout-control.css' );
const searchRender = read( 'blocks/header-search/render.php' );
const wooCommerce = read( 'inc/class-cbv-woocommerce.php' );
const chrome = read( 'inc/class-cbv-chrome.php' );
const templateTags = read( 'inc/template-tags.php' );
const dashboardHome = read( 'template-parts/home-dashboard.php' );
const header = read( 'header.php' );

function token( name ) {
	const root = css.match( /:root\s*{([\s\S]*?)}/ );
	const match = root && root[ 1 ].match( new RegExp( `--${ name }:\\s*(#[0-9a-f]{6})`, 'i' ) );

	assert.ok( match, `Missing --${ name } default token` );

	return match[ 1 ];
}

function luminance( hex ) {
	const channels = [ 1, 3, 5 ].map( ( offset ) => parseInt( hex.slice( offset, offset + 2 ), 16 ) / 255 );
	const linear = channels.map( ( channel ) => channel <= 0.04045 ? channel / 12.92 : ( ( channel + 0.055 ) / 1.055 ) ** 2.4 );

	return ( 0.2126 * linear[ 0 ] ) + ( 0.7152 * linear[ 1 ] ) + ( 0.0722 * linear[ 2 ] );
}

function contrast( foreground, background ) {
	const lighter = Math.max( luminance( foreground ), luminance( background ) );
	const darker = Math.min( luminance( foreground ), luminance( background ) );

	return ( lighter + 0.05 ) / ( darker + 0.05 );
}

test( 'WordPress 7.1 compatibility metadata and global styles stay aligned', () => {
	const stylesheet = read( 'style.css' );
	const functions = read( 'functions.php' );
	const readme = read( 'README.md' );
	const config = JSON.parse( read( 'theme.json' ) );

	assert.match( stylesheet, /^Requires at least:\s*7\.1$/m );
	assert.match( stylesheet, /^Tested up to:\s*7\.1$/m );
	assert.match( stylesheet, /^Requires PHP:\s*7\.4$/m );
	assert.match( readme, /WordPress 7\.1/ );
	assert.match( readme, /WooCommerce 11\.1\.0/ );
	assert.equal( config.$schema, 'https://schemas.wp.org/wp/7.1/theme.json' );
	assert.equal( config.version, 3 );
	assert.deepEqual( config.settings.viewport, { mobile: '30rem', tablet: '56.25rem' } );
	assert.ok( config.styles.blocks[ 'core/button' ][ '@mobile' ].spacing.padding );

	for ( const state of [ ':hover', ':focus', ':focus-visible', ':active' ] ) {
		assert.ok( config.styles.blocks[ 'core/button' ][ state ].color.background );
	}

	const cssVersion = stylesheet.match( /^Version:\s*([0-9.]+)$/m )[ 1 ];
	const phpVersion = functions.match( /define\(\s*'CBV_VERSION',\s*'([^']+)'\s*\)/ )[ 1 ];

	assert.equal( cssVersion, phpVersion );
} );

test( 'all dynamic blocks use the stable WordPress 7.1 schema', () => {
	const blocks = [
		'account-tab',
		'account-tabs',
		'category-tiles',
		'header-account',
		'header-cart',
		'header-nav',
		'header-search',
		'hero-slider',
		'hot-deals',
		'product-grid',
		'related-products',
	];

	for ( const block of blocks ) {
		const metadata = JSON.parse( read( `blocks/${ block }/block.json` ) );

		assert.equal( metadata.$schema, 'https://schemas.wp.org/wp/7.1/block.json', block );
		assert.equal( metadata.apiVersion, 3, block );
		assert.equal( metadata.editorScript, 'cbv-blocks-editor', block );
		assert.equal( metadata.render, 'file:./render.php', block );
	}
} );

test( 'interactive block scripts use the theme release for cache invalidation', () => {
	const scripts = {
		'account-tabs': 'cbv-account-tabs-view',
		'hero-slider': 'cbv-hero-slider-view',
	};

	for ( const [ block, handle ] of Object.entries( scripts ) ) {
		const metadata = JSON.parse( read( `blocks/${ block }/block.json` ) );

		assert.equal( metadata.viewScript, handle );
		assert.match( blockRegistration, new RegExp( `'${ handle }'\\s*=>\\s*'blocks/${ block }/view\\.js'` ) );
	}

	assert.match( blockRegistration, /foreach \( \$view_scripts as \$handle => \$path \)[\s\S]*?wp_register_script\([\s\S]*?CBV_VERSION,[\s\S]*?'strategy'\s*=>\s*'defer'/ );
} );

test( 'WooCommerce 11.1.0 overrides use recognized paths and current contracts', () => {
	const versions = {
		'archive-product.php': '8.6.0',
		'content-product.php': '9.4.0',
		'cart/cart.php': '11.0.0',
		'cart/cart-totals.php': '2.3.6',
		'checkout/form-billing.php': '3.6.0',
		'checkout/form-checkout.php': '9.4.0',
		'checkout/form-shipping.php': '3.6.0',
		'checkout/review-order.php': '11.0.0',
		'myaccount/dashboard.php': '4.4.0',
		'single-product/product-image.php': '11.1.0',
	};

	assert.equal( exists( 'woocommerce/myaccount/dashboard.php' ), true );
	assert.equal( exists( 'woocommerce/my-account/dashboard.php' ), false );

	for ( const [ path, version ] of Object.entries( versions ) ) {
		assert.match( read( `woocommerce/${ path }` ), new RegExp( `@version\\s+${ version.replaceAll( '.', '\\.' ) }` ), path );
	}

	const archiveTemplate = read( 'woocommerce/archive-product.php' );
	const catalogLayout = read( 'inc/class-cbv-catalog-layout.php' );
	const loopTemplate = read( 'woocommerce/content-product.php' );
	const cartTemplate = read( 'woocommerce/cart/cart.php' );
	const totalsTemplate = read( 'woocommerce/cart/cart-totals.php' );
	const dashboardTemplate = read( 'woocommerce/myaccount/dashboard.php' );
	const galleryTemplate = read( 'woocommerce/single-product/product-image.php' );

	assert.match( archiveTemplate, /woocommerce_shop_loop_header/ );
	assert.match( catalogLayout, /remove_action\( 'woocommerce_shop_loop_header', 'woocommerce_product_taxonomy_archive_header', 10 \)/ );
	assert.match( loopTemplate, /wc_product_class/ );
	assert.match( cartTemplate, /instanceof WC_Product/ );
	assert.match( cartTemplate, /woocommerce_after_cart_item_name/ );
	assert.match( cartTemplate, /wc_wp_theme_get_element_class_name/ );
	assert.match( cartTemplate, /role="button"/ );
	assert.match( totalsTemplate, /woocommerce_shipping_calculator/ );
	assert.match( dashboardTemplate, /woocommerce_before_my_account/ );
	assert.match( dashboardTemplate, /woocommerce_after_my_account/ );
	assert.match( galleryTemplate, /ProductType::VARIABLE/ );
} );

test( 'account sign-in notices recover without duplicating WooCommerce output', () => {
	assert.match( wooCommerce, /add_action\( 'woocommerce_before_customer_login_form', array\( \$this, 'restore_login_notices' \), 9 \)/ );
	const restore = wooCommerce.match( /public function restore_login_notices\(\) \{[\s\S]*?\n\t}/ );

	assert.ok( restore, 'The account notice recovery method must remain available.' );
	assert.match( restore[ 0 ], /false !== has_action\( 'woocommerce_before_customer_login_form', 'woocommerce_output_all_notices' \)/ );
	assert.match( restore[ 0 ], /! function_exists\( 'woocommerce_output_all_notices' \)/ );
	assert.match( restore[ 0 ], /woocommerce_output_all_notices\(\);/ );
	assert.match( css, /\.woocommerce-message,[\s\S]*?\.woocommerce-error \{[\s\S]*?position:\s*relative;[\s\S]*?padding:\s*14px 18px 14px 48px;/ );
} );

test( 'WooCommerce notices use theme-owned status icons', () => {
	assert.match( css, /\.woocommerce-message::before,[\s\S]*?\.woocommerce-error::before \{[\s\S]*?content:\s*"";[\s\S]*?mask:/ );
	assert.match( css, /\.woocommerce-info::before \{[\s\S]*?mask-image:\s*url\("data:image\/svg\+xml/ );
	assert.match( css, /\.woocommerce-message::before \{[\s\S]*?background-color:\s*var\(--cbv-success\);[\s\S]*?mask-image:/ );
	assert.match( css, /\.woocommerce-error::before \{[\s\S]*?background-color:\s*var\(--cbv-danger\);[\s\S]*?mask-image:/ );
} );

test( 'password visibility controls stay in-field and keyboard accessible', () => {
	assert.match( css, /\.woocommerce form \.password-input \{[\s\S]*?position:\s*relative;/ );
	assert.match( css, /\.woocommerce form \.password-input \.input-text \{[\s\S]*?padding-inline-end:\s*52px;/ );
	assert.match( css, /\.woocommerce form \.show-password-input \{[\s\S]*?position:\s*absolute;[\s\S]*?width:\s*44px;[\s\S]*?height:\s*44px;/ );
	assert.match( css, /\.woocommerce form \.show-password-input::before \{[\s\S]*?mask:/ );
	assert.match( css, /\.woocommerce form \.show-password-input:focus-visible \{[\s\S]*?outline:/ );
} );

test( 'embedded Tabulator tables use the theme surface behind sparse results', () => {
	assert.match( css, /\.cfw-tabulator-table\.tabulator \{[\s\S]*?background-color:\s*var\(--cbv-surface\);/ );
} );

test( 'dashboard promotional cards support an opt-in post-panel position', () => {
	assert.match( chrome, /'promo_order'\s*=>\s*'before_panels'/ );
	assert.match( templateTags, /function cbv_the_dashboard_promos\( \$dashboard \)/ );
	assert.match( templateTags, /do_action\( 'cbv_dashboard_after_promos', \$dashboard \)/ );
	assert.match( dashboardHome, /'after_panels' === \$cbv_dash\['promo_order'\]/ );
	assert.match( dashboardHome, /if \( ! \$cbv_promos_after_panels \)[\s\S]*?cbv_the_dashboard_promos\( \$cbv_dash \)/ );
	assert.match( dashboardHome, /if \( \$cbv_promos_after_panels \)[\s\S]*?cbv_the_dashboard_promos\( \$cbv_dash \)/ );
} );

test( 'navigation uses a dedicated stateful submenu control', () => {
	assert.match( theme, /cbv-nav__toggle/ );
	assert.match( mainScript, /\.cbv-nav__toggle/ );
	assert.match( mainScript, /aria-expanded/ );
	// Hover opening is gated on the EVENT's pointerType, not on the device's
	// primary pointer. A hybrid touchscreen laptop matches
	// (hover: hover) and (pointer: fine), so a media-query guard let a
	// touch-generated enter open the submenu and the synthesized click then
	// closed it, leaving the first tap closed.
	assert.match( mainScript, /event\.pointerType/ );
	assert.match( mainScript, /return 'mouse' === event\.pointerType;/ );
	assert.match( mainScript, /'onpointerenter' in item \? 'pointerenter' : 'mouseenter'/ );
	assert.match( mainScript, /const hover_submenus = ! menu\.closest\( '#cbv-mobile-nav' \);/ );
	assert.match( mainScript, /hover_enter, function \( event \) \{[\s\S]*?if \( ! hover_submenus \|\| ! hover_event_from_pointer\( event \) \) \{[\s\S]*?return;/ );
	// The media query remains only as the no-PointerEvent fallback.
	assert.doesNotMatch( mainScript, /function hover_event_from_pointer[\s\S]{0,200}?return window\.matchMedia/ );
	assert.doesNotMatch( mainScript, /menu-item-has-children > a/ );
} );

test( 'phone drawer navigation stays stacked and in flow', () => {
	assert.match( css, /\.cbv-mobile-nav \.cbv-nav__list \{[\s\S]*?display:\s*grid;/ );
	assert.match( css, /\.cbv-mobile-nav \.cbv-nav__list \.sub-menu \{[\s\S]*?position:\s*static;[\s\S]*?width:\s*100%;[\s\S]*?box-shadow:\s*none;/ );
	assert.match( css, /\.cbv-mobile-nav__cats \{[\s\S]*?display:\s*grid;[\s\S]*?list-style:\s*none;/ );
	assert.match( css, /@media \(max-width: 600px\) \{[\s\S]*?\.cbv-mobile-nav__cats \{[\s\S]*?grid-template-columns:\s*repeat\(2, minmax\(0, 1fr\)\);/ );
	assert.match( css, /\.cbv-mobile-nav__cats a \{[\s\S]*?min-height:\s*44px;/ );
} );

test( 'mobile filter drawer removes closed content from interaction', () => {
	assert.match( mainScript, /drawer\.inert/ );
	assert.match( mainScript, /aria-modal/ );
	assert.match( mainScript, /focusable_selector/ );
	assert.match( mainScript, /toggle\.focus/ );
	assert.match( css, /cbv-catalog__sidebar--drawer[\s\S]*?visibility:\s*hidden/ );
} );

test( 'carousel exposes motion and active-slide state', () => {
	assert.match( heroRender, /cbv-hero__motion-toggle/ );
	assert.match( heroRender, /aria-current=/ );
	assert.match( heroRender, /role="group"/ );
	assert.match( heroRender, /\binert\b/ );
	assert.doesNotMatch( heroRender, /\sautoplay(?:\s|>)/ );
	assert.match( heroRender, /preload="metadata"/ );
	assert.match( heroScript, /user_paused/ );
	assert.match( heroScript, /slide\.inert/ );
	assert.match( heroScript, /aria-current/ );
} );

test( 'semantic surface pairs have AA contrast by default', () => {
	const pairs = [
		[ 'cbv-secondary-contrast', 'cbv-secondary' ],
		[ 'cbv-footer-contrast', 'cbv-footer-bg' ],
		[ 'cbv-success-contrast', 'cbv-success' ],
		[ 'cbv-danger-contrast', 'cbv-danger' ],
		[ 'cbv-accent-contrast', 'cbv-accent' ],
	];

	pairs.forEach( ( [ foreground, background ] ) => {
		assert.ok( contrast( token( foreground ), token( background ) ) >= 4.5, `${ foreground } must contrast with ${ background }` );
	} );

	assert.match( customizer, /cbv_contrast_tokens/ );
	assert.match( css, /cbv-stock--instock[\s\S]*?var\(--cbv-success-contrast\)/ );
	assert.doesNotMatch( blockAreas, /var\(--cbv-primary-contrast\)/ );
	assert.match( design, /cbv-nav__toggle\{color:var\(--cbv-text\)/ );
} );

test( 'stateful and compact controls expose state and 44px targets', () => {
	assert.match( catalogRender, /aria-pressed=/ );
	assert.match( mainScript, /aria-pressed/ );
	assert.match( css, /cbv-view-btn[\s\S]*?min-width:\s*44px[\s\S]*?min-height:\s*44px/ );
	assert.match( css, /cbv-hero__dot[\s\S]*?width:\s*44px[\s\S]*?height:\s*44px/ );
} );

test( 'hero fallback media recovers responsive attachments when possible', () => {
	assert.match( heroRender, /attachment_url_to_postid/ );
	assert.match( heroRender, /decoding/ );
} );

test( 'deferred sections expose a recoverable error state', () => {
	assert.match( deferred, /cbv-deferred__error/ );
	assert.match( deferred, /cbv_deferred_error_html/ );
	assert.match( deferredScript, /cbv-deferred__retry/ );
	assert.match( deferredScript, /attempts\s*=\s*0/ );
} );

test( 'dynamic commerce typography stays optically stable', () => {
	assert.match( css, /html\s*{[\s\S]*?-webkit-font-smoothing:\s*antialiased/ );
	assert.match( css, /cbv-block__heading[\s\S]*?text-wrap:\s*balance/ );
	assert.match( css, /cbv-hero__text[\s\S]*?text-wrap:\s*pretty/ );
	assert.match( css, /cbv-card__price[\s\S]*?font-variant-numeric:\s*tabular-nums/ );
	assert.match( css, /cbv-header__cart-count[\s\S]*?font-variant-numeric:\s*tabular-nums/ );
} );

test( 'classic commerce header search is opt-in and child-theme extensible', () => {
	assert.match( header, /apply_filters\( 'cbv_commerce_header_search_enabled', false \)/ );
	assert.match( header, /cbv_commerce_header_search_attributes/ );
	assert.match( header, /render_block\([\s\S]*?'commercebuild\/header-search'/ );
	assert.match( header, /cbv_commerce_header_search_html/ );
	assert.match( header, /cbv_commerce_header_search_before/ );
	assert.match( header, /cbv_commerce_header_search_after/ );
	assert.match( searchRender, /name="post_type" value="product"/ );
} );

test( 'card price hydration cannot replace the sibling stock state', () => {
	assert.match( templateTags, /cbv-card__price product__price/ );
	assert.match( templateTags, /cbv-card__price-row[\s\S]*?cbv-card__price product__price[\s\S]*?cbv-stock/ );
} );

test( 'related and upsell cards normalize every stock state', () => {
	assert.match( css, /cbv-pe--related \.cbv-stock--instock[\s\S]*?cbv-pe--upsells \.cbv-stock--instock/ );
	assert.match( css, /cbv-pe--related \.cbv-stock--outofstock[\s\S]*?cbv-pe--upsells \.cbv-stock--outofstock/ );
	assert.match( css, /cbv-pe--related \.cbv-stock--onbackorder[\s\S]*?cbv-pe--upsells \.cbv-stock--onbackorder/ );
} );

test( 'motion feedback is contextual and reduced-motion safe', () => {
	assert.match( css, /scale\(0\.96\)/ );
	assert.match( css, /prefers-reduced-motion:[\s\S]*?cbv-button:active[\s\S]*?transform:\s*none/ );
	assert.match( design, /prefers-reduced-motion:reduce[\s\S]*?cbv-card:hover[\s\S]*?transform:none/ );
	assert.match( heroRender, /cbv-hero__motion-glyph--pause/ );
	assert.match( heroRender, /cbv-hero__motion-glyph--play/ );
	assert.doesNotMatch( heroScript, /icon_element/ );
	assert.match( mainScript, /set_overlay_visible/ );
	assert.match( mainScript, /overlay\.classList\.remove\( 'is-visible' \)[\s\S]*?window\.setTimeout/ );
	assert.match( css, /cbv-filters-overlay\.is-visible/ );
} );

test( 'compact controls use scalable icons and usable targets', () => {
	assert.match( icons, /'play'/ );
	assert.match( icons, /'pause'/ );
	assert.match( icons, /'grid'/ );
	assert.match( icons, /'list'/ );
	assert.match( icons, /stroke_width/ );
	assert.match( archive, /cbv_icon\([\s\S]*?'filter'/ );
	assert.match( archive, /cbv_the_icon\( 'close'/ );
	assert.doesNotMatch( archive, /&#9776;|&times;/ );
	assert.doesNotMatch( catalogRender, /&#9638;|&#9776;/ );
	assert.match( layoutControl, /dashicons-menu/ );
	assert.match( layoutControl, /dashicons-admin-generic/ );
	assert.match( layoutControlCss, /cbv-layout__handle[\s\S]*?width:\s*40px[\s\S]*?height:\s*40px/ );
	assert.match( layoutControlCss, /cbv-layout__settings-toggle[\s\S]*?width:\s*40px[\s\S]*?height:\s*40px/ );
	assert.match( searchRender, /'stroke_width'\s*=>\s*1\.5/ );
} );

test( 'commerce cart and checkout buttons use the palette', () => {
	assert.match( css, /wc-block-components-button\.contained/ );
	assert.match( css, /wc-block-components-checkout-place-order-button/ );
	assert.match( css, /wc-block-checkout__sidebar[\s\S]*?var\(--cbv-surface\)/ );
	assert.match( css, /body\.woocommerce-cart[\s\S]*?--wc-primary:\s*var\(--cbv-primary\)/ );
} );

test( 'product grid can render a 7-up home row', () => {
	const tags = read( 'inc/template-tags.php' );

	assert.match( tags, /min\(\s*12,/ );
	assert.match( tags, /cbv_product_grid_columns/ );
	assert.match( tags, /cbv_header_welcome_name/ );
	assert.match( tags, /cbv_the_brand\(\s*\$context/ );
	assert.match( tags, /function cbv_the_catalog_hero/ );
	assert.match( tags, /cbv_catalog_hero/ );
	assert.match( archive, /cbv_the_catalog_hero/ );
	assert.match( css, /cbv-catalog-hero__title/ );
	assert.match( read( 'template-parts/home-marketing.php' ), /cbv_bestseller_ids/ );
	assert.doesNotMatch( read( 'template-parts/home-marketing.php' ), /width="720" height="520"/ );
	assert.match( css, /cbv-card__media img[\s\S]*?object-fit:\s*cover/ );
	const page = read( 'page.php' );
	assert.match( page, /is_account_page/ );
} );

test( 'classic cart and checkout templates ship with the theme', () => {
	const woo = read( 'inc/class-cbv-woocommerce.php' );
	const cart = read( 'woocommerce/cart/cart.php' );
	const totals = read( 'woocommerce/cart/cart-totals.php' );
	const checkout = read( 'woocommerce/checkout/form-checkout.php' );
	const review = read( 'woocommerce/checkout/review-order.php' );

	assert.match( woo, /ensure_classic_cart_checkout/ );
	assert.match( woo, /_cbv_content_before_classic/ );
	assert.match( cart, /cbv-dash-cart__table/ );
	assert.match( cart, /data-cbv-qty/ );
	assert.match( cart, /class="product-name"/ );
	assert.match( cart, /class="product-price"/ );
	assert.match( cart, /class="product-quantity"/ );
	assert.match( cart, /class="product-subtotal"/ );
	assert.match( cart, /class="product-remove"/ );
	assert.match( totals, /Order Summary/ );
	assert.match( checkout, /cbv-checkout-steps/ );
	assert.match( checkout, /Review & Payments/ );
	assert.match( checkout, /cbv-checkout-steps__label/ );
	assert.match( review, /cbv-checkout-coupon/ );
	assert.match( review, /cbv-checkout-line/ );
	assert.match( review, /class="product-name" colspan="2"/ );
	assert.match( review, /cbv-checkout-line__total/ );
	assert.match( review, /View details/ );
	assert.match( read( 'woocommerce/checkout/form-checkout.php' ), /cbv-checkout-count/ );
	assert.match( css, /cbv-checkout-line__thumb/ );
	assert.match( css, /woocommerce-checkout-review-order-table \.cart_item img[\s\S]*?max-width:\s*48px/ );
	assert.match( css, /woocommerce-checkout-review-order-table thead[\s\S]*?display:\s*none/ );
	assert.match( checkout, /cbv_the_checkout_shipping_methods/ );
	assert.match( checkout, /woocommerce_checkout_payment/ );
	assert.match( css, /cbv-checkout-steps__num/ );
	assert.match( css, /woocommerce-billing-fields__field-wrapper[\s\S]*?grid-template-columns:\s*1fr 1fr/ );
	assert.match( css, /@media \(max-width:\s*360px\)[\s\S]*?woocommerce-billing-fields__field-wrapper,[\s\S]*?woocommerce-shipping-fields__field-wrapper[\s\S]*?grid-template-columns:\s*minmax\(0, 1fr\)/ );
	assert.match( css, /cbv-checkout-line[\s\S]*?grid-template-columns:\s*48px minmax\(0, 1fr\) auto/ );
	assert.match( css, /@media \(max-width:\s*360px\)[\s\S]*?cbv-checkout-line[\s\S]*?grid-template-columns:\s*40px minmax\(0, 1fr\)[\s\S]*?cbv-checkout-line__total[\s\S]*?grid-column:\s*2/ );
	assert.match( css, /woocommerce-cart \.cbv-dash-qty \.qty/ );
	assert.match( mainScript, /data-cbv-qty/ );
	assert.match( woo, /Proceed to Payment/ );
	assert.match( woo, /cbv_order_button_text/ );
	assert.match( woo, /ensure_company_field/ );
	assert.match( woo, /Restaurant \/ business name/ );
	assert.match( css, /woocommerce-checkout \.woocommerce-form-coupon-toggle[\s\S]*?display:\s*none/ );
	assert.match( css, /cbv-cta-band__checks li::before/ );
	assert.match( css, /cbv-footer--commerce \.custom-logo[\s\S]*?height:\s*44px/ );
	assert.match( css, /cbv-checkout-methods/ );
	assert.match( css, /cbv-checkout__main \.form-row\.place-order/ );
	assert.match( css, /cbv-checkout-steps li:first-child::after/ );
	assert.match( css, /cbv-checkout-steps__label[\s\S]*?white-space:\s*nowrap/ );
	assert.match( css, /cbv-checkout-coupon/ );
	assert.match( css, /woocommerce-shipping-fields/ );
	assert.match( css, /cbv-checkout__sidebar[\s\S]*?border:\s*1px solid var\(--cbv-border\)/ );
} );

test( 'dashboard cart stacks without document overflow on phones', () => {
	const dashboard = read( 'template-parts/home-dashboard.php' );

	// The cart goes single column from 1024px, not 900px. Between those the
	// table needs ~566px but half the page is ~421-482px, so a two-column
	// cart slid the table under the order summary and hid Total/Remove.
	assert.match( css, /@media \(max-width:\s*1024px\)[\s\S]*?\.cbv-dash-cart\s*\{[\s\S]*?grid-template-columns:\s*minmax\(0,\s*1fr\)/ );
	assert.match( css, /\.cbv-dash-cart__table-wrap\s*\{[\s\S]*?min-width:\s*0/ );

	// And it must not be put back into two columns anywhere in that range.
	const wideBlock = css.match( /@media \(max-width:\s*1024px\)\s*\{[\s\S]*?\n\}/ );
	assert.ok( wideBlock, '1024px breakpoint block exists' );
	assert.doesNotMatch( wideBlock[ 0 ], /\.cbv-dash-cart,[\s\S]*?grid-template-columns:\s*1fr 1fr/ );
	assert.match( css, /@media \(max-width:\s*600px\)[\s\S]*?\.cbv-dash-cart__table thead\s*\{[\s\S]*?display:\s*none/ );
	assert.match( css, /\.cbv-dash-cart__table td::before\s*\{[\s\S]*?content:\s*attr\(data-title\)/ );
	assert.match( dashboard, /data-title="<\?php esc_attr_e\( 'Pack'/ );
	assert.match( dashboard, /data-title="<\?php esc_attr_e\( 'Qty'/ );
} );

test( 'checkout uses a stacked shipping address form', () => {
	const shipping = read( 'woocommerce/checkout/form-shipping.php' );
	const billing = read( 'woocommerce/checkout/form-billing.php' );
	const page = read( 'page.php' );
	const woo = read( 'inc/class-cbv-woocommerce.php' );

	assert.match( shipping, /Shipping Address/ );
	assert.match( billing, /Billing Address/ );
	assert.match( page, /is_cart\(\) \|\| is_checkout\(\)/ );
	assert.match( woo, /address_field_layout/ );
	assert.match( woo, /cbv_checkout_order_notes/ );
	assert.match( woo, /relocate_checkout_payment/ );
	assert.match( woo, /checkout_methods_fragment/ );
	const tags = read( 'inc/template-tags.php' );
	const review = read( 'woocommerce/checkout/review-order.php' );
	assert.match( tags, /function cbv_the_checkout_shipping_methods/ );
	assert.match( review, /get_cart_shipping_total/ );
	assert.doesNotMatch( review, /wc_cart_totals_shipping_html/ );
} );

test( 'product page chrome has gallery badge, pack size, qty stepper, and tabs', () => {
	const elements = read( 'inc/class-cbv-product-elements.php' );
	const layout = read( 'inc/class-cbv-product-layout.php' );
	const image = read( 'woocommerce/single-product/product-image.php' );
	const tags = read( 'inc/template-tags.php' );
	const cart = read( 'woocommerce/cart/cart.php' );
	const accountDashboard = exists( 'woocommerce/myaccount/dashboard.php' ) ? read( 'woocommerce/myaccount/dashboard.php' ) : '';
	const homeDashboard = read( 'template-parts/home-dashboard.php' );

	assert.match( elements, /'pack_size'/ );
	assert.match( elements, /'trade_notice'/ );
	assert.match( elements, /'account_cta'/ );
	assert.match( elements, /'sku_row'/ );
	assert.match( elements, /if \( 'outofstock' === \$status && \$product->is_in_stock\(\) \) \{[\s\S]*?\$status = 'instock'/ );
	assert.match( elements, /if \( ! \$purchasable \) \{[\s\S]*?return;/ );
	assert.doesNotMatch( elements, /render_disabled_cart/ );
	assert.match( tags, /cbv_product_card_purchase_enabled/ );
	assert.match( tags, /is_type\( array\( 'external', 'grouped' \) \)/ );
	assert.match( tags, /'onbackorder' === \$stock_status/ );
	assert.match( homeDashboard, /'onbackorder' === \$cbv_product->get_stock_status\(\)/ );
	assert.match( homeDashboard, /\$cbv_product->is_on_backorder\( \(int\) \$cbv_item\['quantity'\] \)/ );
	assert.match( cart, /'onbackorder' === \$_product->get_stock_status\(\)/ );
	assert.match( cart, /\$_product->is_on_backorder\( \$cart_item\['quantity'\] \)/ );
	assert.match( accountDashboard, /cbv-account-dash/ );
	assert.match( accountDashboard, /cbv_account_dashboard_cards/ );
	assert.match( accountDashboard, /cbv_account_dashboard_sections/ );
	assert.match( accountDashboard, /cbv-account-card__notice/ );
	assert.match( css, /\.cbv-account-card/ );
	assert.match( css, /woocommerce-checkout form \.form-row label[\s\S]*?text-transform:\s*uppercase/ );
	assert.match( elements, /'actions'/ );
	assert.match( elements, /'cta_band'/ );
	assert.match( elements, /cbv_product_pack_sizes/ );
	assert.match( elements, /cbv_related_heading/ );
	assert.match( elements, /data-cbv-qty/ );
	assert.match( layout, /filter_tabs/ );
	assert.match( elements, /render_description_tab/ );
	assert.doesNotMatch( elements, /woocommerce_product_description_tab/ );
	assert.match( image, /cbv_product_gallery_before/ );
	assert.match( image, /cbv_product_fallback_image_html/ );
	assert.match( image, /cbv_product_fallback_thumb_count/ );
	assert.match( css, /woocommerce-product-gallery__wrapper[\s\S]*?aspect-ratio:\s*1 \/ 1/ );
	assert.match( css, /div\.images\.woocommerce-product-gallery[\s\S]*?height:\s*100% !important/ );
	assert.match( css, /div\.images\.woocommerce-product-gallery[\s\S]*?object-fit:\s*cover/ );
	assert.match( elements, /if \( \$count > 0 \) \{[\s\S]*?cbv-stars/ );
	assert.match( css, /flex-viewport[\s\S]*?height:\s*auto !important/ );
	assert.match( css, /cbv-dash-search[\s\S]*?max-width:\s*960px/ );
	assert.match( css, /order-total td[\s\S]*?font-size:\s*28px/ );
	assert.match( read( 'inc/class-cbv-woocommerce.php' ), /woocommerce_product_add_to_cart_text/ );
	assert.match( css, /woocommerce-Tabs-panel--description \.cfw-dynamic-price[\s\S]*?display:\s*none/ );
	assert.match( elements, /get_post_field\(\s*'post_content'/ );
	assert.match( tags, /cbv_product_fallback_image_html/ );
	assert.match( tags, /cbv_product_card_title/ );
	assert.match( elements, /cbv_product_title/ );
	assert.match( elements, /cbv_product_form_enabled/ );
	assert.match( read( 'functions.php' ), /define\(\s*'CBV_VERSION',\s*'[^']+'\s*\);/ );
	assert.match( tags, /function cbv_price_suffix/ );
	assert.match( tags, /function cbv_the_cta_band/ );
	assert.match( icons, /'compare'/ );
	assert.match( mainScript, /cbv-qty, \.cbv-dash-qty/ );
	assert.match( css, /cbv-pack-size__option[\s\S]*?min-height:\s*48px/ );
	assert.match( css, /cbv-qty__btn[\s\S]*?width:\s*44px[\s\S]*?height:\s*44px/ );
	assert.match( css, /cbv-gallery-badge/ );
	assert.match( css, /@media \(max-width: 600px\)[\s\S]*?\.cbv-breadcrumb \{[\s\S]*?white-space:\s*nowrap;[\s\S]*?text-overflow:\s*ellipsis;/ );
	assert.doesNotMatch( css, /woocommerce-tabs:has\(ul\.tabs li:only-child\) ul\.tabs[\s\S]*?display:\s*none/ );
} );

test( 'catalogue pagination stays together without shrinking touch targets', () => {
	assert.match( css, /nav\.woocommerce-pagination\s*\{[\s\S]*?display:\s*flex;[\s\S]*?justify-content:\s*center;[\s\S]*?overflow-x:\s*auto;[\s\S]*?overscroll-behavior-inline:\s*contain;/ );
	assert.match( css, /nav\.woocommerce-pagination ul\s*\{[\s\S]*?display:\s*inline-flex;[\s\S]*?flex-wrap:\s*nowrap;[\s\S]*?width:\s*max-content;/ );
	assert.match( css, /nav\.woocommerce-pagination ul li\s*\{[\s\S]*?flex:\s*0 0 auto;/ );
	assert.match( css, /nav\.woocommerce-pagination ul li a,[\s\S]*?min-width:\s*44px;[\s\S]*?min-height:\s*44px;/ );
	assert.match( css, /@media \(max-width:\s*480px\)[\s\S]*?nav\.woocommerce-pagination\s*\{[\s\S]*?width:\s*100vw;[\s\S]*?margin-inline:\s*calc\(50% - 50vw\);[\s\S]*?justify-content:\s*safe center;/ );
} );

test( 'logged-in dashboard renders orders, qty steppers, and photo tiles', () => {
	const tags = read( 'inc/template-tags.php' );
	const dash = read( 'template-parts/home-dashboard.php' );
	const chrome = read( 'inc/class-cbv-chrome.php' );

	assert.match( tags, /function cbv_the_home_category_tiles/ );
	assert.match( tags, /function cbv_the_dash_qty/ );
	assert.match( tags, /function_exists\( 'wp_create_nonce' \)/ );
	assert.match( tags, /name="woocommerce-cart-nonce"/ );
	assert.doesNotMatch( tags, /id="woocommerce-cart-nonce"/ );
	assert.doesNotMatch( tags, /wp_nonce_field\( 'woocommerce-cart', 'woocommerce-cart-nonce'/ );
	assert.match( dash, /cbv_the_dash_qty/ );
	assert.match( dash, /cbv_the_home_category_tiles/ );
	assert.match( dash, /Recent Orders/ );
	assert.match( dash, /cbv_product_title/ );
	assert.match( dash, /get_cart_shipping_total/ );
	assert.match( dash, /get_coupons/ );
	assert.match( dash, /get_fees/ );
	assert.match( dash, /get_tax_totals/ );
	assert.match( chrome, /cbv_header_welcome_name/ );
	assert.match( chrome, /cbv_dashboard_orders/ );
	assert.match( css, /cbv-dash-qty__btn[\s\S]*?width:\s*44px[\s\S]*?height:\s*44px/ );
	assert.match( css, /cbv-dash-summary__line[\s\S]*?justify-content:\s*space-between/ );
	assert.match( css, /cbv-home--dashboard \.cbv-dash-summary > \.cbv-button[\s\S]*?width:\s*100%/ );
} );

test( 'checkout keeps cfw_core shipping address fields in the posted form', () => {
	const shipping = read( 'woocommerce/checkout/form-shipping.php' );
	const checkout = read( 'woocommerce/checkout/form-checkout.php' );
	const billing = read( 'woocommerce/checkout/form-billing.php' );
	const woo = read( 'inc/class-cbv-woocommerce.php' );

	assert.match( shipping, /get_checkout_fields\(\s*'shipping'\s*\)/ );
	assert.match( shipping, /id="ship-to-different-address-checkbox"/ );
	assert.match( shipping, /name="ship_to_different_address"/ );
	assert.match( shipping, /checked="checked"/ );
	assert.match( checkout, /woocommerce_checkout_shipping/ );
	assert.match( billing, /Billing Address/ );
	assert.match( billing, /cbv_checkout_show_billing_fields/ );
	assert.match( billing, /cbv-checkout-billing--account/ );
	assert.match( woo, /force_ship_to_different_address/ );
	assert.match( woo, /cbv_force_ship_to_different_address/ );
	assert.match( mainScript, /preserve_shipping_address/ );
	assert.match( mainScript, /remember_shipping_address/ );
	assert.match( mainScript, /updated_checkout cfw_updated_checkout/ );
	assert.match( css, /cbv-checkout \.woocommerce-shipping-fields[\s\S]*?display:\s*block/ );
	assert.match( css, /cbv-checkout-billing--account[\s\S]*?display:\s*none/ );
	assert.doesNotMatch( css, /woocommerce-checkout \.woocommerce-shipping-fields\s*\{\s*display:\s*none/ );
	assert.match( read( 'inc/template-tags.php' ), /function cbv_checkout_show_billing_fields/ );
	assert.match( read( 'inc/template-tags.php' ), /cfw_is_b2b/ );
	assert.match( read( 'AGENTS.md' ), /cfw_is_b2b/ );
	assert.match( read( 'AGENTS.md' ), /shipping_address_select/ );
} );
