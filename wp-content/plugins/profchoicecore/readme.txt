=== Professional's Choice Core ===
Contributors: crimsonagility
Requires at least: 7.1
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks for Professional's Choice: newsletter form and carousel.

== Description ==

Blocks (namespace `profchoice/`):

* **Newsletter Form** (`profchoice/newsletter-form`) with **Newsletter Email** and **Newsletter Message** (success/error) children. Submits through the Interactivity API to `POST /wp-json/profchoicecore/v1/subscribe`, and works without JavaScript through `admin-post.php`. Each email becomes a `subscriber` user (existing accounts keep their role) and a private "Subscribers" entry, exportable as CSV from the Subscribers screen. Protected by a honeypot field and a per-IP rate limit; it sends no email.
* **Carousel** (`profchoice/carousel`) with **Carousel Slide** children. Fade or slide transitions (Embla), arrows, dots, keyboard navigation, inactive slides made inert, and reduced-motion support. Arrows and dots are themed with `--pc-carousel-*` custom properties.

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

= 0.1.0 =
* Newsletter form and carousel blocks.
