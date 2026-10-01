=== Professional's Choice Core ===
Contributors: crimsonagility
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.11.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks and editor tools for Professional's Choice: newsletter form, carousel, video covers, icons and inline text formats.

== Description ==

Blocks (namespace `profchoice/`):

* **Newsletter Form** (`profchoice/newsletter-form`) with **Newsletter Email** and **Newsletter Message** (success/error) children. Submits through the Interactivity API to `POST /wp-json/profchoicecore/v1/subscribe`, and works without JavaScript through `admin-post.php`. Each email becomes a `subscriber` user (existing accounts keep their role) and a private "Subscribers" entry, exportable as CSV from the Subscribers screen. Protected by a honeypot field and a per-IP rate limit; it sends no email.
* **Carousel** (`profchoice/carousel`) with **Carousel Slide**, **Carousel Arrows** and **Carousel Dots** children. Fade or slide transitions (Embla), keyboard navigation, inactive slides made inert, and reduced-motion support. The arrows are two Button blocks, so they take every button style including the Hover/Focus/Active states; the Active/Inactive control settings style both arrows while enabled and disabled (e.g. at the ends when the carousel does not loop). The dots have Default and Active settings. In both blocks the second state inherits whatever it leaves empty from the first, so only the differences are set. Both can overlay the slides (arrows positioned with `--pc-carousel-arrows-*`; overlay dots are a full-width strip at the bottom, placed by the block's justification and padding) or sit in the flow.

Editor features:

* **Video covers**: a Cover with a video background gets a Video panel (Autoplay, Loop, Muted; Muted is locked on while Autoplay is on). A Button with the **Play** style inside it plays and pauses the video and fades out while it plays; focus then moves to the cover, so Space pauses it. The Play style only sets the shape (a circle); colors and the Hover/Focus/Active states come from the Button's own settings.
* **Icons**: a "Professional's Choice" icon collection (Play, Pause, Arrow Right) registered with the Icons API, so it shows in the Icon block and the inline icon picker. Add an icon with an SVG in `icons/` and a line in `icons/manifest.php`.
* **Inline icon** (rich text format): inserts any registered icon into text, with the same Icon library picker as the Icon block. It renders as an `<svg>` in the text color.
* **Visually hidden** (rich text format): hides the selected text on screen while keeping it for screen readers (e.g. an icon-only button's label). The editor shows it while the text is focused.

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

= 0.11.1 =
* "Professional's Choice" block category, first in the inserter, holding the plugin's blocks.

= 0.11.0 =
* Video playback moves into core/cover: Autoplay, Loop and Muted settings for video covers, and a "Play" Button style that plays/pauses the video (keyboard: focus moves to the cover, Space toggles). The Cover Video block is removed.
* Icon collection "Professional's Choice" (Play, Pause, Arrow Right) through the Icons API.
* Inline icon and Visually hidden rich text formats.

= 0.10.0 =
* Cover Video block: poster-first background video with a Play/Pause Button block that fades out while playing.

= 0.9.0 =
* The dot and arrow state settings store nothing by default: the defaults live in the stylesheet (the active dot's red, 22px width and 4px radius; the disabled arrow's 0.35 opacity) and the settings only hold overrides. Reset clears them.

= 0.8.0 =
* Overlay dots: a full-width strip at the bottom of the carousel (bottom: 0) with 1rem block padding by default. Place them with the block's justification and padding (e.g. a larger bottom padding lifts them); the --pc-carousel-dots-bottom/left/transform variables are gone.
* State color indicators overlap as in core's Elements > Link.

= 0.7.0 =
* Colors are grouped again in a Color panel, each color with a tab per state (Background: Default / Active on the dots; Background and Icon: Active / Inactive on the arrows), as core's Elements > Link (Default / Hover).

= 0.6.0 =
* Width and Height use core's DimensionControl, as the Button block's Width: a slider over size presets with a toggle to a custom value.
* Default size presets for the dots (6-32px) and arrows (32-64px), registered like core's button presets under settings.blocks and overridable in a theme's theme.json.

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
