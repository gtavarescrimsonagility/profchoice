# commercebuild Velocity - Development Directives

These directives are binding for all work in this repo, human or AI-assisted.

## 1. Extensibility is mandatory (general directive from Bradly, 2026-07-31)

This theme is a boilerplate customized per customer site. Every feature must be overridable **without editing theme files**:

- **Every structural boundary gets a `do_action()`**: before/after header, nav, footer, main; start/end of header and footer rows; top/media/title/price/bottom of the product card; before/after every block's content (`cbv_block_{name}_before` / `_after`).
- **Every piece of data or output gets an `apply_filters()`**: query args and result ID lists (`cbv_product_query_args`, `cbv_product_ids`, `cbv_related_ids`, `cbv_category_tiles`, `cbv_hot_deal_ids`, `cbv_hero_slides`), rendered HTML (`cbv_product_card_html`, `cbv_product_grid_html`), card fields (`cbv_product_card_args`, `cbv_product_card_sku`, `cbv_product_card_uom`), the palette (`cbv_palette`), cache TTLs (`cbv_cache_ttl`), layout numbers (`cbv_loop_columns`, `cbv_products_per_page`), block area routing (`cbv_block_area_post_id`), ElasticPress routing (`cbv_use_elasticpress`).
- When you add a new template, block, renderer, or engine method: add the corresponding hooks **in the same commit**. A renderer with no hooks is an incomplete renderer.
- Hook naming: `cbv_` prefix, snake_case, named for the location or data, not the caller.
- Every hook gets a docblock with `@since` and param docs.

## 2. Performance rules

- All catalog lookups go through `CBV_Query_Engine`. No direct `WP_Query` for products elsewhere, no `meta_query` on hot paths, no unbounded queries; every list has a LIMIT cap.
- ElasticPress (`ep_integrate`) when active; indexed lookup-table SQL (`wc_product_meta_lookup`) for algorithmic sorts; never postmeta joins for sorting.
- All caching via `CBV_Cache` (cfwCache when present, object cache otherwise). Never transients.
- Server-render page 1 of every archive for guests and crawlers - crawlable, cacheable. Logged-in sessions (never page-cacheable) may use cfw_core's dynamic archive (skeleton shell + AJAX hydration) for faster TTFB; the theme gates it via `cfw_dynamic_archive_enabled` + `cbv_dynamic_archive_enabled` (decision 2026-08-01, issue #7).
- Block JS ships via `viewScript` so it loads only where the block is used. No jQuery.
- Prime post/meta/term caches for a whole result set before rendering loops.

## 3. Editor parity

The Gutenberg editing experience must mirror the frontend:

- Frontend styles reach the canvas iframe via `add_editor_style( 'assets/css/main.css' )` - `enqueue_block_editor_assets` alone does NOT reach the canvas.
- Customizer palette vars are injected into the canvas via `block_editor_settings_all` and defined on `:root` AND `body` (and the Customizer preview JS must set both).
- `theme.json` layout `contentSize` mirrors the frontend `.cbv-container` inner width (1280px minus 40px padding = 1240px) so block widths match; `alignfull` breaks out via `.cbv-content .alignfull`.
- All theme blocks are dynamic (server-rendered) with ServerSideRender previews - the editor shows exactly what the frontend renders. Keep it that way; no static-markup blocks (they create deprecation debt).

## 4. Zero-config activation (directive from Bradly, 2026-07-31)

A customer must get the finished experience by installing and activating the theme — no manual page building, no copying block markup, no support ticket.

- Anything that has to exist in the database goes in `CBV_Setup` and runs automatically on `after_switch_theme` (deferred to `admin_init` so plugin shortcodes can be feature-detected first).
- **When you add a feature that needs seeded content, add its setup step in the same commit and bump `CBV_Setup::SETUP_VERSION`.** If you set something up by hand while testing, that is a bug report against setup.
- Setup must be idempotent, re-runnable, and non-destructive: never overwrite customer content. Only replace content still matching a known default, back it up to post meta first (`_cbv_content_before_setup`), and skip anything already converted.
- Feature-detect optional dependencies (`shortcode_exists`, `function_exists`, `class_exists`) so a plain WooCommerce site never gets cfw_core-only content or literal shortcode text.
- Prefer code defaults over database writes: palette colors, layout numbers, and modes all default in code via `get_theme_mod( $key, $default )`, so a fresh site needs no seeded options at all.

## 5. Conventions

- WordPress Coding Standards, PHPCS `--standard=WordPress` clean on changed files. Tabs, `array()`, snake_case, spaces inside parens.
- Prefixes: functions `cbv_`, classes `CBV_`, hooks `cbv_`, CSS classes/vars `cbv-`/`--cbv-`, text domain `commercebuild-velocity`.
- All output escaped, all input sanitized, nonces + capability checks on any state change.
- Version bump (`style.css` + `CBV_VERSION`) in every deployable commit - assets cache-bust off it.
- SOC 2: GitHub issue before code, `feature/{issue}-slug` branch, commits reference `[#issue]`, PR + independent review + "Approved to deploy" comment before merge. No AI attribution in commits, ever.

## 6. Compatibility

- The theme must work with WooCommerce alone. cfw_core (cfwCache, `original_sku`, `uom`), ElasticPress, and any plugin integrations are opt-in enhancements behind `class_exists`/`function_exists`/`defined` guards.
- Classic product/archive templates (not blockified FSE templates) - cfw_core's ERP features hook classic WooCommerce actions. Do not migrate templates to FSE without an explicit decision.

## 7. cfw_core checkout and address management (do not invent a second system)

When cfw_core is present, **its address rules win**. Velocity skins checkout. It does not replace ship-to/billing policy. Keep this section current when checkout or address UI changes.

### Who can edit what

| Shopper | Billing | Shipping / ship-to |
|---|---|---|
| Guest / B2C (`cfw_is_b2c()`) | Normal Woo billing form. Editable. | Normal shipping fields. Flexible. |
| B2B (`cfw_is_b2b()`) | Account billing is ERP-owned. **Do not offer an edit form.** Hide the billing fieldset (`cbv_checkout_show_billing_fields()`) unless `cfw_b2b_enable_billing_address_management` is on. Keep the inputs in the POST (Woo still needs `billing_*`). cfw_core already marks those inputs readonly. | **Must** render cfw's picker: `select[name="shipping_address_select"]` / `#shipping_address_select_1` plus hidden `#shipping_address_code`. Never CSS-hide `.woocommerce-shipping-fields`. Never drop `ship_to_different_address` or `#ship-to-different-address-checkbox` (cfw JS reads the checkbox id; if it is missing or unchecked, it clears the address code). |

Group permissions (do not bypass): `cfw_disallow_ship_to_creation`, `cfw_disallow_ship_to_update`, `cfw_disallow_ship_to_sync`. "Add New Address" and ERP writeback are cfw's job.

Sites like Coburn and Aztec hide B2B billing entirely for the same reason: it cannot be edited at checkout.

### Helpers to use (not DI, not raw user meta)

From `cfw_core` `HelperFunctions/User.php`:

- `cfw_is_b2b( $user_id = null )`
- `cfw_is_b2c( $customer_code = null )`
- `cfw_get_customer_code( $user_id = 0 )`

Theme wrappers: `cbv_checkout_show_billing_fields()`, filter `cbv_checkout_show_billing_fields`.

### Theme checkout contract

- Classic shortcode checkout only (`[woocommerce_checkout]`). Do not switch to Checkout Blocks without an explicit decision; cfw ship-to is wired for classic fields.
- Loop `$checkout->get_checkout_fields( 'shipping' )` so cfw can inject fields. Do not hard-code a name/address form that omits those keys.
- Keep the runtime guard in `assets/js/main.js` that captures the selected ERP address before a shipping method change, rechecks `ship_to_different_address`, and restores the selection and shipping field wrapper after leaving pickup. cfw_core's pickup handler can otherwise uncheck the hidden control while leaving pickup, which clears the ERP address selection.
- Prefer `function_exists( 'cfw_is_b2b' )` guards so a plain WooCommerce site still checkouts.

When you change checkout templates, CSS, or address JS: update this section and the README checkout notes in the same commit.

## Code navigation

For structural questions, use the available CodeGraph index first when present;
otherwise a focused Graphify query can help when `graphify-out/graph.json` exists.
Verify conclusions against current source. If an index is stale, incomplete, or
unavailable, use targeted `rg` searches. Read broad graph reports only for broad
architecture work. Rebuild or update indexes only when explicitly requested.
