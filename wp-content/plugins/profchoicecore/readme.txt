=== Professional's Choice Core ===
Contributors: crimsonagility
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks and editor tools for Professional's Choice: newsletter form, carousel, video covers, icons, inline text formats, WooCommerce block bindings and variation swatches.

== Description ==

Blocks (namespace `profchoice/`):

* **Newsletter** (`profchoice/newsletter-form`) with **Newsletter Email** and **Newsletter Message** (success, pending confirmation, error) children. Submits through the Interactivity API to `POST /wp-json/profchoicecore/v1/subscribe`, and works without JavaScript through `admin-post.php`. Each email becomes a `subscriber` user (existing accounts keep their role); no email is sent. Optionally (Settings > Subscribers) new subscribers must confirm by email first: they get an Unconfirmed role and a confirmation link, and become Subscribers once they follow it. Subscribers, confirmed or not, cannot log in or reset a password. The Users > Subscribers screen lists their emails like a core list (search, sorting, pagination) and exports them as CSV. Protected by a honeypot field and a per-IP rate limit.
* **Carousel** (`profchoice/carousel`) with **Carousel Slide**, **Carousel Previous**, **Carousel Next** and **Carousel Indicators** children. Slide (default) or fade transitions (Embla), keyboard navigation, inactive slides made inert, and reduced-motion support. Previous and Next are separate blocks, each holding a Button block, so they take every button style including the Hover/Focus/Active states, and can be placed independently; their Active/Inactive control settings style them while enabled and disabled (e.g. at the ends when the carousel does not loop). The indicators have Default and Active settings. In these blocks the second state inherits whatever it leaves empty from the first, so only the differences are set. All of them can overlay the slides (arrows positioned with `--pc-carousel-arrows-*`; overlay indicators sit at the bottom, 15% in from each side and 1rem up by default, placed by the block's justification and margin) or sit in the flow.
* **Variation Swatches** (`profchoice/swatches`): one attribute of a variable product as color, image or button swatches, a radio group (arrow keys, roving focus) built with the Interactivity API. Values that no in-stock variation offers with the other selections are disabled. In the classic product page it replaces WooCommerce's variation dropdowns: the select stays in the form, hidden, so WooCommerce's variations script keeps working, and the swatches set it. Also available as the `[profchoice_swatches attribute="pa_color" product="123"]` shortcode.

WooCommerce swatches:

* Attribute types "Color / image" (`wc-visual`, WooCommerce's own type, made available on any theme) and "Button" (`button`, for labels such as sizes). Term colors and images are edited with WooCommerce's Color / image fields in Products > Attributes and stored in its term meta (`color`, `image`), so WooCommerce's block product page and Product Filters show them too; `product_attribute_color` / `product_attribute_image` are read as fallbacks.
* A product's Swatches tab (variable products) sets swatches or WooCommerce's dropdown per attribute, and a color or image per term for that product only (`_profchoice_swatches`).
* "Button" attributes get the term picker in the product's Attributes tab.
* A product's "Product page" tab: a badge (Pro Pick, New, Sale…) and an "As ridden by" card (rider, images, link), stored as `_profchoice_badge` and `_profchoice_rider` and exposed in the REST API.

Editor features:

* **WooCommerce bindings**: designed blocks (headings, buttons, images) can take a product category's or product's name, link and image, or the shop link, through the block bindings `profchoice/product-category`, `profchoice/product` (by ID or featured position) and `profchoice/shop`. Without WooCommerce or matching data, blocks keep their saved values.
* **Video covers**: a Cover with a video background gets a Video panel (Autoplay, Loop, Muted; Muted is locked on while Autoplay is on). A Button with the **Play** style inside it plays and pauses the video and fades out while it plays; focus then moves to the cover, so Space pauses it. The Play style only sets the shape (a circle); colors and the Hover/Focus/Active states come from the Button's own settings.
* **Icons**: a "Professional's Choice" icon collection (Play, Pause, Arrow Right, Arrow Long Right, Heart, Heart Filled, Check Circle, Info Circle, Alert Circle, Close) registered with the Icons API, so it shows in the Icon block and the inline icon picker. Add an icon with an SVG in `icons/` and a line in `icons/manifest.php`.
* **Inline icon** (rich text format): inserts any registered icon into text, with the same Icon library picker as the Icon block. It renders as an `<svg>` in the text color.
* **Visually hidden** (rich text format): hides the selected text on screen while keeping it for screen readers (e.g. an icon-only button's label). The editor shows it while the text is focused.

Size Guide (`block-size-guide` module):

* Products > Attributes > (attribute) > **Size guide**: a page for that attribute. The swatches of the attribute then show a "Size Guide" link (through the `profchoicecore_swatches_guide` filter) that opens the page in a modal; without JavaScript it links to the page. No page, no link.
* **Size Guide** block (`profchoice/size-guide`, Interactivity API store `profchoice/size-guide`): the link and its modal (native dialog: Esc, close button and backdrop close it, focus returns to the link), for an attribute or a chosen page. `[profchoice_size_guide attribute="pa_size"]` and `do_action( 'profchoicecore_size_guide', 'pa_size' )` render the same block; `profchoicecore_size_guide_page` filters the page.

Wishlist (`wishlist` module, Interactivity API store `profchoice/wishlist`):

* **Wishlist Button** (`profchoice/wishlist-button`): a heart, filled while the product is in a list (every heart of the same product updates together). It saves the product (and the chosen variation, on its page) to the visitor's default list. With Multiple lists on, a "Saved to" dialog then lets the visitor move it to another list or a new one; a filled heart opens the dialog to move or remove it.
* **Wishlist Link** (`profchoice/wishlist-link`): heart, count badge and label, for headers (`innerClasses` reuses a theme's header classes).
* **Wishlist** (`profchoice/wishlist`): the lists (tabs), rename, make default, delete, new list, and the saved products with add to cart, move and remove. On the Wishlist page and in My Account > Wishlists.
* Shortcodes `[profchoice_wishlist]`, `[profchoice_wishlist_button product="123"]` and `[profchoice_wishlist_link]` render the same blocks; `ProfChoiceCore\Wishlist\Wishlist::render_button()` / `render_link()` / `render_page()` for templates.
* Settings in WooCommerce > Settings > Wishlist: guests (saved with a browser cookie and kept after they log in) or login required, wishlist page, multiple lists and confirmation alerts (both off by default), guest list retention. Products list: a sortable Wishlists column (how many lists have each product).
* Lists are private `pc_wishlist` posts; REST API under `profchoicecore/v1/wishlist` and `/wishlists`.

Hooks:

* `profchoicecore_newsletter_subscribed( $email, $user_id )` after a subscription (after confirming, when email confirmation is on).
* `profchoicecore_newsletter_pending( $email, $user_id )` after a subscription that waits for confirmation.
* `profchoicecore_newsletter_confirmation_email` filters the confirmation email (to, subject, message, headers).
* `profchoicecore_wishlist_item_added( $product_id, $list_id, $user_id, $variation_id )`, `profchoicecore_wishlist_item_removed( $product_id, $list_id, $user_id )`, `profchoicecore_wishlist_list_created( $list_id, $owner )`, `profchoicecore_wishlist_list_deleted( $list_id, $owner )`.
* `profchoicecore_wishlist_button_attributes`, `profchoicecore_wishlist_page_id` and `profchoicecore_wishlist_guests_allowed` filters.
* `profchoicecore_modules` filters the modules to load.
* `profchoicecore_swatches_block_attributes( $attributes, $args, $product )` filters the swatches block of each attribute in the classic form. Themes can also pass block attributes (e.g. `className`, `sizeGuideUrl`, `showValue`) in the `profchoice_swatches` argument of `wc_dropdown_variation_attribute_options()`.

== Modules ==

Each feature is a self-contained folder in `modules/` (PHP in `includes/`, sources in `src/`, its own `build/`, assets, tests). The plugin loads every `modules/<name>/module.php`, skipping a module whose `Requires Plugins` header names an inactive plugin. Deleting a module's folder removes only that feature; the `profchoicecore_modules` filter turns one off without deleting it.

* `block-swatches`: Variation Swatches (WooCommerce).
* `block-size-guide`: Size Guide (WooCommerce).
* `product-page`: badge and "As ridden by" card (WooCommerce).
* `block-slider`: Carousel and its children.
* `block-lead-capture`: Newsletter, subscribers and CSV export.
* `block-bindings`: WooCommerce block bindings (WooCommerce).
* `block-cover-video`: video covers and the Play style.
* `icons`: icon collection and Inline icon format.
* `visually-hidden`: Visually hidden format.
* `wishlist`: Wishlist (WooCommerce).

== Development ==

Sources live in each module's `src/` (TypeScript + SCSS) and build into that module's `build/` folder, which is not committed (`bin/build.mjs` runs wp-scripts once per module; extra non-block entries go in the module's `entries.json`):

    npm ci
    npm run build   # or: npm start

The GitHub Action builds, lints, type-checks and tests every change, and publishes `profchoicecore.<version>.zip` as a release when the version is new. Bump the version in `profchoicecore.php` (header and `PROFCHOICECORE_VERSION`), `package.json` and this file's Stable tag together.

== Credits ==

The carousel is adapted from rt-carousel by rtCamp (https://github.com/rtCamp/rt-carousel), GPL-2.0-or-later, and uses Embla Carousel (MIT). The CSV helpers are adapted from the axellcore plugin.

== Changelog ==

= 0.2.0 =
Product page (PDP).

* Variation Swatches block (Interactivity API) with classic variations form integration and a `[profchoice_swatches]` shortcode.
* "Color / image" and "Button" attribute types on any theme, term picker for "Button" attributes, and a Swatches tab with per-product display and term overrides.
* "Product page" tab with the badge and the "As ridden by" card.
* Heart, Arrow Long Right, Check Circle, Info Circle, Alert Circle and Close icons.

= 0.1.0 =
First release, for the Professional's Choice Homepage.

* Newsletter block with Email and Message (success, pending, error) children: Interactivity API submit with a no-JavaScript fallback, subscribers stored as `subscriber` users who cannot log in, Users > Subscribers list with CSV export, optional email confirmation (Settings > Subscribers), honeypot and rate limit.
* Carousel block with Slide, Previous, Next and Indicators children: slide or fade transitions (Embla), loop and autoplay settings, keyboard navigation (also after clicking a slide), inert inactive slides, reduced-motion support, and server-rendered state. The toolbar picks the slide shown in the editor and adds slides and indicators; new slides start with a numbered gray Cover.
* Previous/Next hold a Button block (every button style and Hover/Focus/Active state) with an Inline icon and a Visually hidden label; Active/Inactive control settings (colors, size, border, radius, opacity) for the enabled and disabled states, an Overlay mode with a 15% default strip and Minimum width, and `--pc-carousel-arrows-top`/`-inset` variables.
* Indicators with Default/Active settings and an Overlay mode with default margins. Each indicator is a touch target of at least 24x24px (WCAG target size) with the visible bar or dot drawn inside it, Bootstrap-like by default (30x3px bars, 3px margins).
* Size presets for the indicator and arrow settings, overridable in a theme's theme.json.
* Video covers: Autoplay, Loop and Muted settings for core/cover videos, and a "Play" Button style that plays/pauses the video (focus moves to the cover; Space toggles).
* "Professional's Choice" icon collection (Play, Pause, Arrow Right) through the Icons API, and the Inline icon and Visually hidden rich text formats.
* WooCommerce block bindings: `profchoice/product-category`, `profchoice/product` (by ID or featured position) and `profchoice/shop`, shown in the editor too.
* "Professional's Choice" block category, first in the inserter.
