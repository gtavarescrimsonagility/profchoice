=== Professional's Choice Core ===
Contributors: crimsonagility
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.18.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks and editor tools for Professional's Choice: newsletter form, carousel, video covers, icons and inline text formats.

== Description ==

Blocks (namespace `profchoice/`):

* **Newsletter** (`profchoice/newsletter-form`) with **Newsletter Email** and **Newsletter Message** (success, pending confirmation, error) children. Submits through the Interactivity API to `POST /wp-json/profchoicecore/v1/subscribe`, and works without JavaScript through `admin-post.php`. Each email becomes a `subscriber` user (existing accounts keep their role); no email is sent. Optionally (Settings > Subscribers) new subscribers must confirm by email first: they get an Unconfirmed role and a confirmation link, and become Subscribers once they follow it. Subscribers, confirmed or not, cannot log in or reset a password. The Users > Subscribers screen lists their emails like a core list (search, sorting, pagination) and exports them as CSV. Protected by a honeypot field and a per-IP rate limit.
* **Carousel** (`profchoice/carousel`) with **Carousel Slide**, **Carousel Previous**, **Carousel Next** and **Carousel Indicators** children. Slide (default) or fade transitions (Embla), keyboard navigation, inactive slides made inert, and reduced-motion support. Previous and Next are separate blocks, each holding a Button block, so they take every button style including the Hover/Focus/Active states, and can be placed independently; their Active/Inactive control settings style them while enabled and disabled (e.g. at the ends when the carousel does not loop). The indicators have Default and Active settings. In these blocks the second state inherits whatever it leaves empty from the first, so only the differences are set. All of them can overlay the slides (arrows positioned with `--pc-carousel-arrows-*`; overlay indicators sit at the bottom, 15% in from each side and 1rem up by default, placed by the block's justification and margin) or sit in the flow.

Editor features:

* **Video covers**: a Cover with a video background gets a Video panel (Autoplay, Loop, Muted; Muted is locked on while Autoplay is on). A Button with the **Play** style inside it plays and pauses the video and fades out while it plays; focus then moves to the cover, so Space pauses it. The Play style only sets the shape (a circle); colors and the Hover/Focus/Active states come from the Button's own settings.
* **Icons**: a "Professional's Choice" icon collection (Play, Pause, Arrow Right) registered with the Icons API, so it shows in the Icon block and the inline icon picker. Add an icon with an SVG in `icons/` and a line in `icons/manifest.php`.
* **Inline icon** (rich text format): inserts any registered icon into text, with the same Icon library picker as the Icon block. It renders as an `<svg>` in the text color.
* **Visually hidden** (rich text format): hides the selected text on screen while keeping it for screen readers (e.g. an icon-only button's label). The editor shows it while the text is focused.

Hooks:

* `profchoicecore_newsletter_subscribed( $email, $user_id )` after a subscription (after confirming, when email confirmation is on).
* `profchoicecore_newsletter_pending( $email, $user_id )` after a subscription that waits for confirmation.
* `profchoicecore_newsletter_confirmation_email` filters the confirmation email (to, subject, message, headers).

== Development ==

Sources live in `src/` (TypeScript + SCSS). The built `build/` folder is not committed:

    npm ci
    npm run build   # or: npm start

The GitHub Action builds, lints, type-checks and tests every change, and publishes `profchoicecore.<version>.zip` as a release when the version is new. Bump the version in `profchoicecore.php` (header and `PROFCHOICECORE_VERSION`), `package.json` and this file's Stable tag together.

== Credits ==

The carousel is adapted from rt-carousel by rtCamp (https://github.com/rtCamp/rt-carousel), GPL-2.0-or-later, and uses Embla Carousel (MIT). The CSV helpers are adapted from the axellcore plugin.

== Changelog ==

= 0.18.0 =
* Carousel Dots is renamed **Carousel Indicators** (`profchoice/carousel-indicators`; classes `pc-carousel-indicators` and `pc-carousel-indicator`, variables `--pc-carousel-indicator-{default|active}-*`). `profchoice/carousel-dots` is removed: rename it in saved content.
* Overlaid indicators default to a 15% side margin and a 1rem bottom margin (instead of 1rem block padding); the block's margin setting overrides them.
* Carousel toolbar: "Add dots" is now "Add indicators".

= 0.17.2 =
* Carousel: clicking a slide (outside its links, buttons and fields) focuses the carousel, so the arrow keys, Home and End navigate it right away. The carousel itself shows no focus ring.

= 0.17.1 =
* Carousel: a new carousel comes with Carousel Dots again (slide, Previous, Next and Dots). "Add dots" stays in the toolbar for a carousel without them.

= 0.17.0 =
* Carousel: Slide is the default transition (listed first). Carousels saved without a transition now slide; set Transition to Fade to keep a fade.

= 0.16.3 =
* Carousel Previous/Next and Carousel Dots: Border, Radius and Opacity in the state panels are added from the panel menu (like Width and Height), and show up on their own once set.

= 0.16.2 =
* Carousel Previous/Next: Opacity in the Active control and Inactive control panels is a slider with a number field and Reset, starting at the stylesheet value (1 active, 0.35 inactive, or the Active value it inherits).

= 0.16.1 =
* Carousel Previous/Next: the default button look (round, 2rem icon, 0.5rem padding, lighter text on hover, icon centred) comes from the stylesheet instead of being saved in each Button block, which now only holds customizations.

= 0.16.0 =
* Carousel: a new carousel starts with a slide plus Previous and Next, no dots; "Add dots" in the carousel toolbar inserts them.
* Carousel Dots default look: plain 10px round dots, white at half opacity, the active one opaque. The previous look (wider red active dot, outlined dots) is now set with the Default/Active settings.

= 0.15.0 =
* Carousel Previous/Next default look: a round button (2rem chevron Inline icon, Visually hidden "Previous"/"Next" label, 1rem padding, lighter text on hover). Overlaid, each control is a full-height strip at its edge, 15% wide by default (new Min. width setting), with the button centred; `--pc-carousel-arrows-inset` now defaults to 0.
* Buttons from earlier versions (`pc-carousel__arrow` class) keep their CSS arrow and position (1rem in from the edge, or `--pc-carousel-arrows-inset`).
* The Active/Inactive control settings apply to any button in the control, not only `pc-carousel__arrow` ones.

= 0.14.1 =
* Carousel Dots: in the editor, the active dot follows the slide shown (picked in the carousel toolbar) instead of always being the first.

= 0.14.0 =
* Carousel Arrows is split into two blocks, **Carousel Previous** (`profchoice/carousel-control-prev`) and **Carousel Next** (`profchoice/carousel-control-next`), each with its own Overlay and Active/Inactive settings. Overlaid, Previous sits at the left edge and Next at the right (same `--pc-carousel-arrows-top`/`-inset` variables). `profchoice/carousel-controls` is removed: replace each one with the two new blocks.
* Carousel Slide placeholders cycle through four grays (#777/#555, #666/#444, #555/#333, #444/#222).

= 0.13.1 =
* Carousel: a new carousel, and each slide added to it, starts with a gray Cover labelled "Slide N".

= 0.13.0 =
* Carousel: the toolbar shows the current slide ("Slide 1") with a dropdown to switch slides, instead of one button per slide.
* Carousel Slide: new slides start with a Cover. In a carousel whose slides start with another block (e.g. Media & Text), "Add slide" adds an empty one of those instead, so it keeps its structure.

= 0.12.1 =
* The Newsletter Form block is now titled "Newsletter" (same `profchoice/newsletter-form` name, so existing content is unchanged).

= 0.12.0 =
* Newsletter: subscriptions only create a `subscriber` user (no more "Subscribers" posts or source tracking). Subscribers cannot log in or reset a password.
* Users > Subscribers: a read-only list of the subscribers' emails (search, sorting, pagination), with Export as the header action. The CSV only has the emails.
* Optional email confirmation (Settings > Subscribers): new subscribers get the Unconfirmed role and a confirmation link; they become Subscribers once they confirm.
* Newsletter Message: a "Pending confirmation" type, shown after subscribing while confirmation is required (forms without it show the success message).
* The `profchoicecore_newsletter_subscribed` action now passes `( $email, $user_id )`; new `profchoicecore_newsletter_pending` action and `profchoicecore_newsletter_confirmation_email` filter.

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
