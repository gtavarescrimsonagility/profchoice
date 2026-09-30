=== Professional's Choice Core ===
Contributors: crimsonagility
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks for Professional's Choice: newsletter form and carousel.

== Description ==

Blocks (namespace `profchoice/`):

* **Newsletter Form** (`profchoice/newsletter-form`) with **Newsletter Email** and **Newsletter Message** (success/error) children. Submits through the Interactivity API to `POST /wp-json/profchoicecore/v1/subscribe`, and works without JavaScript through `admin-post.php`. Each email becomes a `subscriber` user (existing accounts keep their role) and a private "Subscribers" entry, exportable as CSV from the Subscribers screen. Protected by a honeypot field and a per-IP rate limit; it sends no email.
* **Carousel** (`profchoice/carousel`) with **Carousel Slide**, **Carousel Arrows** and **Carousel Dots** children. Fade or slide transitions (Embla), keyboard navigation, inactive slides made inert, and reduced-motion support. The arrows are two Button blocks, so they take every button style including the Hover/Focus/Active states; the Active/Inactive control settings style both arrows while enabled and disabled (e.g. at the ends when the carousel does not loop). The dots have Default and Active settings. In both blocks the second state inherits whatever it leaves empty from the first, so only the differences are set. Both can overlay the slides (positioned with `--pc-carousel-arrows-*` / `--pc-carousel-dots-*`) or sit in the flow.

Hooks:

* `profchoicecore_newsletter_subscribed( $email, $user_id, $post_id, $source )` after a subscription.

== Development ==

Sources live in `src/` (TypeScript + SCSS). The built `build/` folder is not committed:

    npm ci
    npm run build   # or: npm start

The GitHub Action builds, lints, type-checks and tests every change, and publishes `profchoicecore.<version>.zip` as a release when the version is new. Bump the version in `profchoicecore.php` (header and `PROFCHOICECORE_VERSION`), `package.json` and this file's Stable tag together.

== Credits ==

The carousel is adapted from rt-carousel by rtCamp (https://github.com/rtCamp/rt-carousel), GPL-2.0-or-later, and uses Embla Carousel (MIT). The CSV helpers are adapted from the axellcore plugin.

== Changelog ==

= 0.5.0 =
* State panels follow the WordPress 7.1 block panels: boxed color items inside each state panel (no grouped Color panel), stacked Width/Height that can be toggled from the panel menu, the core Border control (slider, unlink sides) and Radius control (slider, presets, unlink corners).
* Resetting an item clears it so it inherits again; Reset all restores the block defaults.

= 0.4.0 =
* Carousel Dots: the Inactive dot / Active dot panels are now Default / Active, and the active dot inherits every value it leaves empty (shown as placeholders). Variables renamed to --pc-carousel-dot-{default|active}-*.
* Carousel Arrows: a disabled arrow inherits the Active control values it does not override.
* Width, height, border and radius share rows at half width; the border control has no slider.

= 0.3.0 =
* Native style controls for the dots and arrows: a Color panel with the states grouped in tabs (Default/Active background, arrow icon), and per-state panels with size, border (color, style and width together), radius and opacity.
* Carousel Arrows: Active control and Inactive control settings for the enabled and disabled arrows.
* Dot variables renamed to --pc-carousel-dot-{inactive|active}-*.

= 0.2.0 =
* Carousel Arrows and Carousel Dots blocks: arrows styled as Button blocks (with hover/focus/active states), dots with Inactive/Active style settings.
* Server-side rendering of the carousel and newsletter state (correct inert, is-active and hidden attributes before JavaScript loads).

= 0.1.0 =
* Newsletter form and carousel blocks.
