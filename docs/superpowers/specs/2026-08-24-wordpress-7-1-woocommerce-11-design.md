# WordPress 7.1 and WooCommerce 11.0.1 compatibility design

**Issue:** [#106](https://github.com/commercebuildteam/commercebuild-velocity/issues/106)

**Status:** Approved direction, awaiting implementation plan

**Target platform:** WordPress 7.1 and WooCommerce 11.0.1

## Context

commercebuild Velocity currently declares WordPress 6.4 as its minimum and
WordPress 6.6 as its tested version. The theme is at version 0.12.44, uses a
version 2 `theme.json`, registers eleven server-rendered blocks with Block API
version 3, and maintains ten customized WooCommerce templates.

The compatibility update must be substantive rather than a header-only claim.
It will adopt stable WordPress 7.1 theme features, synchronize the theme's
classic WooCommerce integration with WooCommerce 11.0.1, and prove the supported
runtime in a disposable environment.

The current baseline is clean. Its 17 Node UI contract tests pass, and every PHP
file passes `php -l` under PHP 8.5.9.

## Goals

- Raise the WordPress minimum and tested version from 6.4/6.6 to 7.1.
- Make WooCommerce 11.0.1 the explicitly documented and verified commerce
  runtime.
- Adopt stable WordPress 7.1 `theme.json` responsive styles, configurable
  viewports, and supported interactive states.
- Keep all eleven dynamic blocks compatible with the always-iframed WordPress
  7.1 editor.
- Synchronize every WooCommerce template override with its 11.0.1 contract while
  preserving Velocity's intentional markup, hooks, classic checkout behavior,
  and cfw_core integration boundaries.
- Add repeatable checks that prevent compatibility metadata, template paths, and
  template version annotations from drifting.

## Non-goals

- Do not convert the theme or its WooCommerce templates to full-site editing.
- Do not migrate the classic cart or checkout to WooCommerce blocks.
- Do not adopt experimental WooCommerce administration features.
- Do not add direct product queries outside `CBV_Query_Engine`, replace
  `CBV_Cache`, or weaken optional cfw_core and ElasticPress guards.
- Do not rewrite the existing outline icon set merely to claim use of the new
  WordPress Icon API.
- Do not merge, release, promote, or deploy this work as part of implementation.

## Design decisions

### 1. Compatibility declarations

`style.css` will declare both `Requires at least: 7.1` and `Tested up to: 7.1`.
The PHP minimum remains 7.4 because neither target platform requires raising it
for this theme and an unrelated PHP requirement change would expand the rollout
risk.

The README will contain a concise compatibility section naming WordPress 7.1,
WooCommerce 11.0.1, and PHP 7.4. WooCommerce compatibility will also be
represented by accurate `@version` annotations in every override. Custom
WooCommerce header fields will not be invented in `style.css`; WordPress does not
define them as theme headers.

The final deployable change will bump both `style.css` and `CBV_VERSION` from
0.12.44 to 0.12.45 after all substantive edits and tests are complete.

### 2. WordPress 7.1 global styles

`theme.json` will move from format version 2 to version 3 and use the versioned
WordPress 7.1 schema rather than the moving `trunk` schema.

It will define named mobile and tablet viewports through `settings.viewport`.
The tablet breakpoint will align with Velocity's existing 900px catalog and
navigation transition (`56.25rem`); the mobile breakpoint will use WordPress's
`30rem` default.
Responsive values will be introduced only where they improve an existing theme
surface without duplicating the full CSS layout system. Initial use will focus
on typography and spacing for core content elements shown in the editor canvas.

Supported `:hover`, `:focus`, `:focus-visible`, and `:active` style states will
be declared for the Core Button block. The declarations will reuse Velocity
palette variables or equivalent registered color values so the Customizer,
frontend, and editor canvas remain visually consistent. Existing CSS remains
the source of truth for classic WooCommerce markup that is not governed by
global block styles.

This is an additive adoption of WordPress 7.1 global-style capabilities. It is
not a migration of Velocity's customer-facing layout controls into
`theme.json`.

### 3. Always-iframed editor and dynamic blocks

Every `block.json` will use the versioned WordPress 7.1 block schema and retain
`apiVersion: 3`. All blocks remain dynamic, server-rendered, and registered from
metadata. `viewScript` remains limited to blocks that need frontend behavior.

The shared editor script will be tested for iframe-safe behavior. It may read
WordPress editor globals and its localized configuration, but it must not query
or mutate the top-level editor document as though it were the content canvas.
Any future canvas DOM access would have to derive `ownerDocument` and
`defaultView` from the rendered element. The current script has no such canvas
DOM access, so no architecture change is expected.

The existing `add_editor_style()` and `block_editor_settings_all` paths remain in
place because they intentionally supply frontend CSS, palette variables, and
active design presets to the iframe canvas.

### 4. WordPress 7.1 SVG Icon API

Velocity's `CBV_Icons` registry uses outline SVGs with `stroke`, `circle`, and
`rect` elements. WordPress 7.1's public Icon API sanitizes a narrower, fill-based
SVG subset; registering the current registry directly would strip meaningful
geometry and visibly break icons.

The compatibility work will therefore retain `CBV_Icons` as the renderer for the
existing public theme surfaces. No duplicate fill-icon collection will be added
until a customer-facing consumer needs Core icon discovery. This decision is
recorded here because passing the current registry through the new API would be
a visible regression, not an upgrade.

### 5. WooCommerce 11.0.1 template contract

All ten customized templates will be compared with the official WooCommerce
11.0.1 originals. Each override will receive the upstream template version it
implements, even though its markup remains customized:

| Override | WooCommerce 11.0.1 template version |
| --- | ---: |
| `archive-product.php` | 8.6.0 |
| `content-product.php` | 9.4.0 |
| `cart/cart.php` | 11.0.0 |
| `cart/cart-totals.php` | 2.3.6 |
| `checkout/form-billing.php` | 3.6.0 |
| `checkout/form-checkout.php` | 9.4.0 |
| `checkout/form-shipping.php` | 3.6.0 |
| `checkout/review-order.php` | 11.0.0 |
| `myaccount/dashboard.php` | 4.4.0 |
| `single-product/product-image.php` | 10.5.0 |

The comparison will preserve upstream hook order and arguments unless an
intentional Velocity variation is documented in the template. In particular:

- cart and checkout item loops will use the 11.0.1 `WC_Product` validity and
  visibility contract;
- cart controls will incorporate WooCommerce's current element-class integration
  and accessibility semantics while retaining Velocity's classes and layout;
- review-order customization will keep product thumbnails while incorporating
  the 11.0.0 item-validation behavior;
- the single-product gallery will preserve responsive WooCommerce gallery
  markup and the 10.5.0 variable-product placeholder behavior;
- archive handling will preserve Velocity's server-rendered page-one catalog
  and its existing WooCommerce hook boundaries.

The current account dashboard override is incorrectly stored at
`woocommerce/my-account/dashboard.php`. WooCommerce loads
`woocommerce/myaccount/dashboard.php`; the implementation will move the file to
the recognized path and update tests accordingly. The old directory will not be
left as a second, dead copy.

Classic checkout remains required. cfw_core continues to own ship-to and billing
policy, and Velocity continues to render and submit the fields produced by
WooCommerce and cfw_core rather than creating a second address model.

### 6. WooCommerce 11 behavior outside template copies

WooCommerce 11.0 enables product object caching on new stores and changes the
queried object for the Shop page to the Shop page `WP_Post`. The current source
audit found no conflicting assumption: Velocity caches product ID lists rather
than mutable product objects, narrows category-only queried objects to terms,
does not query product shipping classes through public taxonomy variables, and
uses Core or WooCommerce responsive image functions for product media.

Implementation will preserve those contracts and verify that Velocity does not:

- store mutable product objects in theme caches or rely on object identity;
- assume the Shop queried object is a `WP_Post_Type`;
- use product-shipping-class taxonomy public query variables;
- discard responsive `srcset`/`sizes` output when rendering product media.

No compatibility shim will be added where the current source does not depend on
the changed behavior. The result should stay small and evidence-driven.

### 7. Extensibility and performance

Any new renderer, helper, or structural boundary introduced while synchronizing
templates must include the required `cbv_` actions and filters with docblocks in
the same implementation commit. Existing WooCommerce hooks must be retained.

The update will not introduce direct product `WP_Query` calls, unbounded product
lookups, postmeta sorting, transients, or eagerly loaded block scripts. Product
result sets continue through `CBV_Query_Engine`, and theme-owned caching continues
through `CBV_Cache`.

## Testing strategy

Implementation will be test-driven. Each compatibility behavior will first be
expressed as a failing contract, then implemented with the smallest supporting
change.

Static contracts will verify:

- WordPress 7.1 metadata and synchronized theme version constants;
- `theme.json` format version 3, stable 7.1 schema, viewports, responsive values,
  and supported button states;
- stable 7.1 schemas, API version 3, and dynamic rendering for all eleven blocks;
- the recognized `woocommerce/myaccount/dashboard.php` path and absence of the
  obsolete path;
- upstream `@version` annotations for every WooCommerce override;
- the WooCommerce 11 cart, checkout, gallery, and product-validity contracts;
- continued classic-checkout and cfw_core field-preservation contracts.

Repository checks will include the full Node contract suite, PHP syntax checks,
and WordPress Coding Standards on changed PHP files.

Runtime validation will use a disposable WordPress 7.1 environment with
WooCommerce 11.0.1. It will cover activation, the block editor canvas, header and
footer block areas, a product archive, a single product with responsive images,
cart, classic checkout, and My Account dashboard routing. Logs will be inspected
for PHP warnings, deprecations, and fatal errors. A WooCommerce-only run will
prove that cfw_core remains optional; guarded enhancements can be checked
separately if an authorized disposable cfw_core fixture is available.

## Risks and recovery

- **Template synchronization can regress customized commerce flows.** Mitigate
  with upstream-by-upstream comparison, focused contracts, and runtime cart,
  checkout, account, and gallery checks.
- **The account dashboard move changes which file WooCommerce actually loads.**
  Validate both WooCommerce-only and logged-in dashboard behavior before review.
- **Global-style changes can alter frontend presentation beyond the editor.**
  Keep declarations narrow and compare representative frontend and editor
  surfaces at the same viewport.
- **Responsive global styles may overlap existing media queries.** Align named
  viewports with established breakpoints and avoid moving complete layout
  ownership into `theme.json`.

Before merge, recovery is a normal code reversion because the design introduces
no database migration or external state change. After deployment, a code rollback
restores the prior theme version, but WordPress and WooCommerce runtime versions
would still need to meet that prior release's verified support matrix. Deployment
and rollback execution are outside this issue's implementation scope.

## Authoritative references

- [WordPress 7.1 release](https://wordpress.org/news/2026/08/mary-lou/)
- [WordPress 7.1 field guide](https://make.wordpress.org/core/2026/08/05/wordpress-7-1-field-guide/)
- [Responsive block styles and configurable viewports](https://make.wordpress.org/core/2026/08/05/responsive-block-styles-and-configurable-viewports-in-wordpress-7-1/)
- [Pseudo and custom style states](https://make.wordpress.org/core/2026/08/05/pseudo-and-custom-style-states-in-wordpress-7-1/)
- [WordPress 7.1 Icon API](https://make.wordpress.org/core/2026/07/24/registering-and-rendering-svg-icons-in-wordpress-7-1/)
- [WordPress 7.1 iframed editor changes](https://make.wordpress.org/core/2026/08/03/iframed-editor-changes-in-wordpress-7-1/)
- [WooCommerce 11.0 release](https://developer.woocommerce.com/2026/08/04/woocommerce-11-0/)
- [WooCommerce 11.0.1 release](https://developer.woocommerce.com/2026/08/10/woocommerce-11-0-1/)
- [WooCommerce template structure](https://developer.woocommerce.com/docs/theming/theme-development/template-structure/)
