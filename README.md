# commercebuild Velocity

Performance-first WooCommerce boilerplate theme for commercebuild customer sites. Generic, modern, and built to stay fast on catalogs approaching one million products.

## Compatibility

- WordPress 7.1
- WooCommerce 11.1.0 (with image-only fallback for 11.0.1)
- PHP 7.4 or newer

The theme uses classic WooCommerce templates for product archives, products,
cart, checkout, and My Account so cfw_core's ERP hooks continue to run.

The classic product gallery supports WooCommerce 11.1's ordered image/video
media with WooCommerce's thumbnail slider while retaining Velocity's badge
and fallback-image hooks. PHP 7.4 is
the language compatibility minimum; production sites should use a
[supported PHP release](https://www.php.net/supported-versions.php).

## Principles

1. **Server-rendered and crawlable.** Archives render real products on page 1; no zeroed main queries, no JS-only pagination.
2. **One query layer.** Every product/term lookup goes through `CBV_Query_Engine`: ID-only selects, `no_found_rows` where pagination is not needed, bounded limits, no meta_query on hot paths.
3. **ElasticPress when available.** If ElasticPress is active, catalog, search, and block queries set `ep_integrate` and are served by Elasticsearch. MySQL fallbacks stay bounded. Disable per-site with the `cbv_use_elasticpress` filter.
4. **Object cache everywhere, transients nowhere.** `CBV_Cache` uses cfwCache when cfw_core is present, `wp_cache_*` otherwise, with group flush plus a generation-salt fallback for caches that cannot flush groups.
5. **Known big-catalog wins built in.** The `product_visibility` NOT IN anti-join is stripped when no products are hidden (cached hourly) — worth ~300ms/query on multi-million-row `term_relationships` tables.
6. **Lean assets.** One small stylesheet, one deferred vanilla JS file, system font stack, no jQuery, no icon fonts, no emoji polyfill.

## Color palette

Appearance > Customize > Color Palette. Eleven tokens (primary, secondary, accent, background, surface, text, muted, border, success, danger, primary contrast) output as `--cbv-*` CSS custom properties with live preview. The same tokens are exposed to Gutenberg as the editor palette, so block color choices track the Customizer.

## Blocks

All blocks are dynamic (server-rendered), cached through the query engine, and live under the "commercebuild" category. No build step; the editor UI is dependency-free ServerSideRender.

| Block | Purpose |
|---|---|
| `commercebuild/product-grid` | Products by category, curated IDs, featured, or on-sale; orderby incl. price/popularity via WooCommerce lookup tables |
| `commercebuild/related-products` | Related products for the current product, cached per product for 1 hour |
| `commercebuild/category-tiles` | Category tile grid with cached counts and one-pass term-meta priming |
| `commercebuild/hero-slider` | No-frills hero banner/slider: image + video slides, CTA, autoplay; LCP-optimized first slide, reduced-motion/offscreen/tab-hidden aware, JS loads only where used |
| `commercebuild/hot-deals` | Manual picks or algorithm (on sale, best selling, top rated, newest) via cached indexed reads on `wc_product_meta_lookup`, never postmeta joins |

## Block Areas (Gutenberg header/footer)

Appearance > Block Areas holds two documents, "Header Area" and "Footer Area", each edited with the full block editor and rendered inside the structural header/footer. The chrome (logo, search, cart, nav) stays lean PHP; the editable areas are for announcement bars, footer columns, promos.

## Shared card renderer

`cbv_product_card()` / `cbv_product_grid()` render every product card in the theme (blocks and WooCommerce loops via `woocommerce/content-product.php`). Toggles: price, SKU, add-to-cart. Filter `cbv_product_card_args` to e.g. hide prices for guests. Grids prime post/meta/term caches for the whole set before rendering.

## Roadmap (from the client-repo survey)

Highest-value components customers rebuild on every site, in rough priority order:

1. Category mega menu driven by `product_cat` terms
2. AJAX search bar (SKU-aware, ElasticPress-backed)
3. Faceted filter sidebar (category/attribute/price) with mobile off-canvas
4. Shop toolbar: grid/list toggle, result count, sort, per-page
5. Brand grid/carousel block
6. Tabbed featured-products block
7. Hero + promo tile blocks (with `fetchpriority="high"` on the LCP image)
8. Configurable product tabs (description/specs/accessories/branch inventory)
9. Warehouse/branch stock display block
10. My Account endpoint framework (invoices, credit memos, saved lists)

## Checkout and addresses (cfw_core)

Classic checkout (`woocommerce/checkout/form-checkout.php`, `form-shipping.php`, `form-billing.php`). cfw_core owns ship-to and billing policy; the theme must not invent a second address editor.

- **Guests / B2C:** normal Woo billing + shipping. Editable.
- **B2B:** hide the billing fieldset (`cbv_checkout_show_billing_fields()` / class `cbv-checkout-billing--account`). Account billing cannot be changed at checkout unless `cfw_b2b_enable_billing_address_management` is on. Fields stay in the form so Woo still receives `billing_*`.
- **Ship-to picker:** `shipping_address_select` + `shipping_address_code` must stay in `.woocommerce-shipping-fields`. Keep `#ship-to-different-address-checkbox` in the markup and checked, including after shipping method updates. The frontend guard preserves the selected ERP address, restores the checked state, and restores the non-pickup shipping wrapper because cfw JS clears the selection and address code when that control is missing or unchecked.
- Use `cfw_is_b2b()` / `cfw_is_b2c()` / `cfw_get_customer_code()`. Do not read `customer_code` user meta or reach into the DI container.

Binding rules for later work: `AGENTS.md` §7.

## Extensibility

The theme is hook-dense by policy (see `AGENTS.md` §1): `do_action()` at every structural boundary (header/nav/footer/card/block content) and `apply_filters()` on every query, ID list, rendered HTML fragment, card field, the color palette, cache TTLs, and layout numbers. Site plugins customize behavior without touching theme files. All hooks are prefixed `cbv_` and documented inline.

## Development

- WordPress Coding Standards (tabs, spaces in parens, `array()`, snake_case, `cbv_`/`CBV_` prefixes).
- All output escaped, all input sanitized.
- SOC 2: every change needs a GitHub issue, branch, PR, independent review, and deploy approval before merge.
