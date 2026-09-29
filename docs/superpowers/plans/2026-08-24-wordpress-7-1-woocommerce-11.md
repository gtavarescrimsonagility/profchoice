# WordPress 7.1 and WooCommerce 11.0.1 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make commercebuild Velocity explicitly require and verifiably support WordPress 7.1 with WooCommerce 11.0.1 while adopting stable WordPress 7.1 theme capabilities and preserving the classic commerce architecture.

**Architecture:** Keep the existing classic theme, dynamic block, query-engine, and classic-checkout boundaries. Add executable compatibility contracts first, update metadata and global styles, synchronize all WooCommerce overrides with the 11.0.1 template contract, and prove activation plus representative rendering in a disposable WordPress Playground runtime.

**Tech Stack:** PHP 7.4-compatible WordPress theme code, WordPress 7.1 `theme.json` v3, Block API v3 metadata, WooCommerce 11.0.1 classic templates, Node's built-in test runner, PHPCS, and WordPress Playground.

**Spec:** `docs/superpowers/specs/2026-08-24-wordpress-7-1-woocommerce-11-design.md`

## Global Constraints

- WordPress minimum and tested version are both exactly 7.1.
- WooCommerce 11.0.1 is the explicitly documented and runtime-verified commerce target.
- PHP minimum remains 7.4.
- Keep classic PHP product, archive, cart, checkout, and account templates; do not migrate to FSE or Checkout blocks.
- Keep all eleven blocks dynamic, server-rendered, and on Block API version 3.
- Keep cfw_core and ElasticPress optional behind existing guards.
- Preserve every WooCommerce hook and every required `cbv_` extensibility hook touched by the change.
- Keep product queries in `CBV_Query_Engine` and theme caches in `CBV_Cache`.
- Make the version change from 0.12.44 to 0.12.45 the final planned content edit.
- Produce one atomic deployable implementation commit so the release version is bumped once.
- Do not merge, release, promote, or deploy; stop at an independently reviewable pull request.

---

### Task 1: Add compatibility contracts and implement the atomic update

**Files:**

- Modify: `tests/ui-contracts.test.mjs`
- Modify: `style.css`
- Modify: `functions.php`
- Modify: `README.md`
- Modify: `theme.json`
- Modify: `blocks/account-tab/block.json`
- Modify: `blocks/account-tabs/block.json`
- Modify: `blocks/category-tiles/block.json`
- Modify: `blocks/header-account/block.json`
- Modify: `blocks/header-cart/block.json`
- Modify: `blocks/header-nav/block.json`
- Modify: `blocks/header-search/block.json`
- Modify: `blocks/hero-slider/block.json`
- Modify: `blocks/hot-deals/block.json`
- Modify: `blocks/product-grid/block.json`
- Modify: `blocks/related-products/block.json`
- Modify: `woocommerce/archive-product.php`
- Modify: `woocommerce/content-product.php`
- Modify: `woocommerce/cart/cart.php`
- Modify: `woocommerce/cart/cart-totals.php`
- Modify: `woocommerce/checkout/form-billing.php`
- Modify: `woocommerce/checkout/form-checkout.php`
- Modify: `woocommerce/checkout/form-shipping.php`
- Modify: `woocommerce/checkout/review-order.php`
- Move: `woocommerce/my-account/dashboard.php` to `woocommerce/myaccount/dashboard.php`
- Modify: `woocommerce/single-product/product-image.php`

**Interfaces:**

- Consumes: WordPress theme headers, `theme.json` v3 responsive/state syntax, Block API v3 metadata, WooCommerce 11.0.1 template hooks and helper functions.
- Produces: a WordPress 7.1-only theme package at version 0.12.45 whose recognized WooCommerce overrides carry the official 11.0.1 template versions.

- [ ] **Step 1: Write failing compatibility contracts**

Extend the Node imports and add filesystem helpers:

```js
import { existsSync, readFileSync } from 'node:fs';

const fileUrl = ( path ) => new URL( `../${ path }`, import.meta.url );
const read = ( path ) => readFileSync( fileUrl( path ), 'utf8' );
const exists = ( path ) => existsSync( fileUrl( path ) );
```

Add a WordPress target and global-styles contract with literal expectations:

```js
test( 'WordPress 7.1 compatibility metadata and global styles stay aligned', () => {
	const stylesheet = read( 'style.css' );
	const functions = read( 'functions.php' );
	const readme = read( 'README.md' );
	const config = JSON.parse( read( 'theme.json' ) );

	assert.match( stylesheet, /^Requires at least:\s*7\.1$/m );
	assert.match( stylesheet, /^Tested up to:\s*7\.1$/m );
	assert.match( stylesheet, /^Requires PHP:\s*7\.4$/m );
	assert.match( readme, /WordPress 7\.1/ );
	assert.match( readme, /WooCommerce 11\.0\.1/ );
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
```

Add a versioned block-metadata contract:

```js
test( 'all dynamic blocks use the stable WordPress 7.1 schema', () => {
	const blocks = [
		'account-tab', 'account-tabs', 'category-tiles', 'header-account',
		'header-cart', 'header-nav', 'header-search', 'hero-slider',
		'hot-deals', 'product-grid', 'related-products',
	];

	for ( const block of blocks ) {
		const metadata = JSON.parse( read( `blocks/${ block }/block.json` ) );
		assert.equal( metadata.$schema, 'https://schemas.wp.org/wp/7.1/block.json', block );
		assert.equal( metadata.apiVersion, 3, block );
		assert.equal( metadata.editorScript, 'cbv-blocks-editor', block );
		assert.equal( metadata.render, 'file:./render.php', block );
	}
} );
```

Add a recognized-path and upstream-template contract:

```js
test( 'WooCommerce 11.0.1 overrides use recognized paths and current contracts', () => {
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
		'single-product/product-image.php': '10.5.0',
	};

	assert.equal( exists( 'woocommerce/myaccount/dashboard.php' ), true );
	assert.equal( exists( 'woocommerce/my-account/dashboard.php' ), false );

	for ( const [ path, version ] of Object.entries( versions ) ) {
		assert.match( read( `woocommerce/${ path }` ), new RegExp( `@version\\s+${ version.replaceAll( '.', '\\.' ) }` ), path );
	}

	const archiveTemplate = read( 'woocommerce/archive-product.php' );
	const loopTemplate = read( 'woocommerce/content-product.php' );
	const cartTemplate = read( 'woocommerce/cart/cart.php' );
	const totalsTemplate = read( 'woocommerce/cart/cart-totals.php' );
	const dashboardTemplate = read( 'woocommerce/myaccount/dashboard.php' );
	const galleryTemplate = read( 'woocommerce/single-product/product-image.php' );

	assert.match( archiveTemplate, /woocommerce_shop_loop_header/ );
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
```

Update the existing account-dashboard assertions to read
`woocommerce/myaccount/dashboard.php`.

- [ ] **Step 2: Run the contracts and verify the expected red state**

Run:

```bash
node --test tests/ui-contracts.test.mjs
```

Expected: the three new tests fail because metadata still names WordPress 6.4/6.6, schemas still use `trunk`, WooCommerce template versions are absent, and the dashboard file still uses `my-account`. The existing 17 tests must continue to pass.

- [ ] **Step 3: Adopt WordPress 7.1 global styles and block metadata**

Set `theme.json` to the stable schema and format, retain the current settings,
add the viewports, and add narrow Core Button states:

```json
{
	"$schema": "https://schemas.wp.org/wp/7.1/theme.json",
	"version": 3,
	"settings": {
		"viewport": {
			"mobile": "30rem",
			"tablet": "56.25rem"
		}
	},
	"styles": {
		"blocks": {
			"core/button": {
				":hover": {
					"color": {
						"background": "var(--cbv-secondary)",
						"text": "var(--cbv-secondary-contrast)"
					}
				},
				":focus": {
					"color": {
						"background": "var(--cbv-secondary)",
						"text": "var(--cbv-secondary-contrast)"
					}
				},
				":focus-visible": {
					"color": {
						"background": "var(--cbv-secondary)",
						"text": "var(--cbv-secondary-contrast)"
					}
				},
				":active": {
					"color": {
						"background": "var(--cbv-accent)",
						"text": "var(--cbv-accent-contrast)"
					}
				},
				"@mobile": {
					"spacing": {
						"padding": {
							"top": "0.6875rem",
							"right": "1rem",
							"bottom": "0.6875rem",
							"left": "1rem"
						}
					}
				}
			}
		}
	}
}
```

The omitted `settings.layout`, `settings.color`, `settings.spacing`, and
`settings.typography` sections above remain exactly as they are in the current
file. Change only `$schema` in each `block.json` from `trunk` to
`https://schemas.wp.org/wp/7.1/block.json`; retain every name, attribute,
support, script, and render field.

- [ ] **Step 4: Synchronize the WooCommerce override paths and headers**

Move the account template with `git mv` and add these exact upstream versions to
the template docblocks:

```text
archive-product.php                         @version 8.6.0
content-product.php                         @version 9.4.0
cart/cart.php                               @version 11.0.0
cart/cart-totals.php                        @version 2.3.6
checkout/form-billing.php                   @version 3.6.0
checkout/form-checkout.php                  @version 9.4.0
checkout/form-shipping.php                  @version 3.6.0
checkout/review-order.php                   @version 11.0.0
myaccount/dashboard.php                     @version 4.4.0
single-product/product-image.php            @version 10.5.0
```

Keep `@package Commercebuild_Velocity`; the version annotation identifies the
upstream WooCommerce template contract implemented by the override.

- [ ] **Step 5: Apply the WooCommerce 11.0.1 behavioral deltas**

After `woocommerce_before_main_content`, restore the archive header hook:

```php
/** This action is documented in WooCommerce templates. */
do_action( 'woocommerce_shop_loop_header' );
```

Render the loop item's classes through WooCommerce:

```php
<li <?php wc_product_class( 'cbv-loop-item', $product ); ?>>
```

In `cart/cart.php`, derive a literal `$visible` boolean and filtered
`$product_name`, require a real `WC_Product`, fire
`woocommerce_after_cart_item_name`, use the filtered name for the quantity
input, preserve backorder notices, add `role="button"` plus the product name to
the remove link, and append the current WooCommerce element class to both submit
buttons:

```php
$visible = apply_filters( 'woocommerce_cart_item_visible', true, $cart_item, $cart_item_key );

if ( ! $_product instanceof WC_Product || ! $_product->exists() || $cart_item['quantity'] <= 0 || ! $visible ) {
	continue;
}

$product_name = apply_filters( 'woocommerce_cart_item_name', $_product->get_name(), $cart_item, $cart_item_key );
$button_class = wc_wp_theme_get_element_class_name( 'button' );
```

Add the upstream shipping-calculator fallback to `cart/cart-totals.php`:

```php
<?php elseif ( WC()->cart->needs_shipping() && 'yes' === get_option( 'woocommerce_enable_shipping_calc' ) ) : ?>
	<tr class="shipping">
		<th><?php esc_html_e( 'Shipping', 'commercebuild-velocity' ); ?></th>
		<td data-title="<?php esc_attr_e( 'Shipping', 'commercebuild-velocity' ); ?>"><?php woocommerce_shipping_calculator(); ?></td>
	</tr>
```

Move `woocommerce_account_dashboard` to the end of the custom dashboard output
and follow it with the two deprecated compatibility hooks that WooCommerce
11.0.1 still fires:

```php
do_action( 'woocommerce_account_dashboard' );
do_action( 'woocommerce_before_my_account' );
do_action( 'woocommerce_after_my_account' );
```

In the product gallery, import `Automattic\WooCommerce\Enums\ProductType` and
use the 10.5.0 variable-product placeholder class condition when the filter does
not provide fallback markup:

```php
$wrapper_classname = $product->is_type( ProductType::VARIABLE ) && ! empty( $product->get_visible_children() ) && '' !== $product->get_price()
	? 'woocommerce-product-gallery__image woocommerce-product-gallery__image--placeholder'
	: 'woocommerce-product-gallery__image--placeholder';
```

Preserve Velocity's custom gallery filter, thumbnail strip, checkout layout,
shipping-address form, product-card renderer, and all current `cbv_` hooks.

- [ ] **Step 6: Document the support matrix**

Add this section near the top of `README.md`:

```markdown
## Compatibility

- WordPress 7.1
- WooCommerce 11.0.1
- PHP 7.4 or newer

The theme uses classic WooCommerce templates for product archives, products,
cart, checkout, and My Account so cfw_core's ERP hooks continue to run.
```

- [ ] **Step 7: Make the final planned content edit by bumping the theme version**

Set both declarations to 0.12.45:

```text
style.css: Version: 0.12.45
functions.php: define( 'CBV_VERSION', '0.12.45' );
```

At the same time, set the WordPress headers in `style.css` to:

```text
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
```

Do not make another content edit after this step unless verification exposes a
defect; if it does, fix the defect and repeat this version check last.

- [ ] **Step 8: Run focused and broad verification**

Run:

```bash
node --test tests/ui-contracts.test.mjs
find . -path './.git' -prune -o -name '*.php' -type f -print0 | xargs -0 -n1 php -l
phpcs --standard=WordPress --extensions=php woocommerce/archive-product.php woocommerce/content-product.php woocommerce/cart/cart.php woocommerce/cart/cart-totals.php woocommerce/checkout/form-billing.php woocommerce/checkout/form-checkout.php woocommerce/checkout/form-shipping.php woocommerce/checkout/review-order.php woocommerce/myaccount/dashboard.php woocommerce/single-product/product-image.php
git diff --check
```

Expected: 20 Node tests pass, every PHP file reports no syntax error, PHPCS
reports no errors in changed PHP files, and `git diff --check` exits zero.

- [ ] **Step 9: Inspect and commit the atomic implementation**

Inspect `git status`, the full diff, and the exact staged diff. Stage only the
listed files, then commit:

```bash
git commit -m "fix: support WordPress 7.1 and WooCommerce 11 [#106]"
```

Expected: one implementation commit containing the compatibility contracts,
production changes, README support matrix, template move, and synchronized
0.12.45 version declarations.

---

### Task 2: Verify the exact runtime in WordPress Playground

**Files:**

- Create temporarily: `/tmp/cbv-106-playground/blueprint.json`
- No repository files modified.

**Interfaces:**

- Consumes: the committed theme worktree, WordPress 7.1, WooCommerce 11.0.1, PHP 8.3, and Playground's ephemeral SQLite runtime.
- Produces: runtime evidence for activation, version identity, block registration, WooCommerce template location, dynamic block rendering, and representative frontend responses.

- [ ] **Step 1: Confirm the runtime prerequisites**

Run:

```bash
node --version
npx @wp-playground/cli@latest --version
```

Expected: Node is at least 20.18 and the Playground CLI resolves successfully.

- [ ] **Step 2: Create the task-owned ephemeral blueprint**

Create `/tmp/cbv-106-playground/blueprint.json` with debug logging,
WooCommerce 11.0.1 activation, and a PHP assertion step:

```json
{
	"$schema": "https://playground.wordpress.net/blueprint-schema.json",
	"login": true,
	"preferredVersions": {
		"php": "8.3",
		"wp": "7.1"
	},
	"steps": [
		{
			"step": "defineWpConfigConsts",
			"consts": {
				"WP_DEBUG": true,
				"WP_DEBUG_LOG": true,
				"WP_DEBUG_DISPLAY": true,
				"WP_DISABLE_FATAL_ERROR_HANDLER": true
			}
		},
		{
			"step": "installPlugin",
			"pluginData": {
				"resource": "url",
				"url": "https://downloads.wordpress.org/plugin/woocommerce.11.0.1.zip"
			},
			"options": {
				"activate": true
			}
		},
		{
			"step": "runPHP",
			"code": "<?php require_once '/wordpress/wp-load.php'; require_once ABSPATH . 'wp-admin/includes/image.php'; if ( '7.1' !== get_bloginfo( 'version' ) ) { throw new Exception( 'Wrong WordPress version' ); } if ( ! defined( 'WC_VERSION' ) || '11.0.1' !== WC_VERSION ) { throw new Exception( 'Wrong WooCommerce version' ); } if ( 'commercebuild Velocity' !== wp_get_theme()->get( 'Name' ) ) { throw new Exception( 'Velocity is not active' ); } $dashboard = wp_normalize_path( get_theme_file_path( 'woocommerce/myaccount/dashboard.php' ) ); if ( ! file_exists( $dashboard ) || $dashboard !== wp_normalize_path( wc_locate_template( 'myaccount/dashboard.php' ) ) ) { throw new Exception( 'WooCommerce does not locate the dashboard override' ); } $registry = WP_Block_Type_Registry::get_instance(); foreach ( array( 'commercebuild/product-grid', 'commercebuild/related-products', 'commercebuild/category-tiles', 'commercebuild/hero-slider', 'commercebuild/hot-deals', 'commercebuild/account-tabs', 'commercebuild/account-tab', 'commercebuild/header-search', 'commercebuild/header-cart', 'commercebuild/header-account', 'commercebuild/header-nav' ) as $name ) { $block = $registry->get_registered( $name ); if ( ! $block || 3 !== $block->api_version || ! is_callable( $block->render_callback ) ) { throw new Exception( 'Invalid dynamic block: ' . $name ); } } $upload = wp_upload_dir(); $image_path = $upload['path'] . '/cbv-runtime-product.png'; $image = imagecreatetruecolor( 1200, 1200 ); $background = imagecolorallocate( $image, 48, 85, 117 ); imagefill( $image, 0, 0, $background ); imagepng( $image, $image_path ); imagedestroy( $image ); $attachment_id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'Runtime Product', 'post_status' => 'inherit' ), $image_path ); wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $image_path ) ); $product = new WC_Product_Simple(); $product->set_name( 'Runtime Product' ); $product->set_slug( 'runtime-product' ); $product->set_status( 'publish' ); $product->set_catalog_visibility( 'visible' ); $product->set_regular_price( '10.00' ); $product->set_image_id( $attachment_id ); $product_id = $product->save(); $responsive_image = wp_get_attachment_image( $attachment_id, 'woocommerce_single' ); if ( ! str_contains( $responsive_image, 'srcset=' ) || ! str_contains( $responsive_image, 'sizes=' ) ) { throw new Exception( 'Product image is not responsive' ); } $markup = '<!-- wp:commercebuild/product-grid {\"includeIds\":\"' . $product_id . '\",\"limit\":1} /-->'; if ( ! str_contains( do_blocks( $markup ), 'cbv-grid' ) ) { throw new Exception( 'Dynamic product grid did not render' ); } echo 'CBV_RUNTIME_SMOKE_OK';"
		}
	]
}
```

- [ ] **Step 3: Start the disposable exact-version server**

Run from the theme root:

```bash
npx @wp-playground/cli@latest server --auto-mount --wp=7.1 --php=8.3 --blueprint=/tmp/cbv-106-playground/blueprint.json --port=9461 --verbosity=debug
```

Expected: Playground completes the blueprint, prints
`CBV_RUNTIME_SMOKE_OK`, activates Velocity without a fatal error, and listens on
port 9461.

- [ ] **Step 4: Exercise representative frontend routes**

While the server is running, request the home page plus WooCommerce-created
shop, product, cart, checkout, and My Account pages:

```bash
curl -sS -o /dev/null -w '%{http_code} %{url_effective}\n' http://127.0.0.1:9461/
curl -sS -o /dev/null -w '%{http_code} %{url_effective}\n' http://127.0.0.1:9461/shop/
curl -sS -o /dev/null -w '%{http_code} %{url_effective}\n' http://127.0.0.1:9461/product/runtime-product/
curl -sS -o /dev/null -w '%{http_code} %{url_effective}\n' http://127.0.0.1:9461/cart/
curl -sS -o /dev/null -w '%{http_code} %{url_effective}\n' http://127.0.0.1:9461/checkout/
curl -sS -o /dev/null -w '%{http_code} %{url_effective}\n' http://127.0.0.1:9461/my-account/
```

Expected: each route returns HTTP 200 or the expected logged-out redirect for My
Account; no request produces a 500 response.

- [ ] **Step 5: Verify the always-iframed editor surface**

Open `http://127.0.0.1:9461/wp-admin/post-new.php` in the in-app browser. Confirm
the editor canvas is an iframe, insert the Product Grid block, select the Runtime
Product, and confirm its ServerSideRender preview appears inside the canvas.
Inspect the browser console and confirm the theme's block editor code produces no
uncaught exception or cross-document access error.

- [ ] **Step 6: Inspect debug output and stop the runtime**

Inspect the Playground output for `PHP Fatal`, `PHP Warning`, `Deprecated`, and
`_doing_it_wrong`. Stop the exact Playground process after evidence is captured.
Remove only `/tmp/cbv-106-playground` after confirming it is the directory
created by this task.

---

### Task 3: Prepare the protected review handoff

**Files:**

- No additional repository files expected.

**Interfaces:**

- Consumes: the verified implementation commit and fresh remote branch state.
- Produces: a pushed feature branch and review-gated pull request linked to issue #106.

- [ ] **Step 1: Re-run the full completion gate**

Run the complete Node suite, PHP syntax checks, changed-file PHPCS, JSON parsing,
`git diff --check`, and `git status --short --branch` again. Expected: every
command exits zero and the worktree contains no unstaged or untracked task files.

- [ ] **Step 2: Revalidate the push target**

Fetch `origin`, confirm the current branch is
`feature/106-wordpress-7-1-woocommerce-11`, confirm `origin/master` has not moved
past the implementation base in a way that changes the diff, and inspect the
commits unique to the branch.

- [ ] **Step 3: Push the feature branch and open the pull request**

Push without force and create a pull request into the dynamically confirmed
default branch. Use this title:

```text
fix: support WordPress 7.1 and WooCommerce 11
```

The pull request body must summarize the WordPress metadata/global-style update,
the WooCommerce template synchronization and dashboard-path fix, validation
commands and runtime evidence, risks, and the fact that no deployment occurred.
Link issue #106. Do not merge, self-approve, dismiss review, or add an
"Approved to deploy" comment.

- [ ] **Step 4: Verify remote and pull-request state**

Confirm the pushed branch head equals the local head, the PR head/base SHAs are
correct, the PR remains open for independent review, and required checks have
started. Report any branch-protection or check blocker exactly.
