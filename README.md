# WordPress Telegram Instant View plugin — media controls fork

This public fork adds opt-in media processing to the original
[`petrows/wp-tg-instantview`](https://github.com/petrows/wp-tg-instantview)
plugin.

Wordpress plugin to trigger [Telegram Instant View](https://instantview.telegram.org/) for all Blog posts.

![Plugin banner](assets/banner-1544x500.png)

Principal: it detects Telegram bot and returns special template for
posts, to trigger Instant View.

* No template submitting required.
* No special links required.
* Compatable with any cross-posting systems.
* Keep media unchanged by default.
* Optionally remove article media or replace it with direct links.
* Preserve the Open Graph image used by Telegram link preview cards.
* Normalize Gutenberg pullquotes for Telegram Instant View compatibility.

Original Telegram InstantView templates registry appears to be dead,
my template was not reviewed and submitted in over 1 year. This plugin
solves this issue server-side.

Plugin was verified by WP team and published in Plugin Registry: https://wordpress.org/plugins/tg-instantview

Thanks a lot [@fishchev for template](https://gist.github.com/fishchev/ed2ca15d5ffd9594d41498a4bf9ba12e).

My Telegram channel with plugin active: https://t.me/petro_ws

## Installation

* Install plugin from [Wordpress plugin registry](https://wordpress.org/plugins/tg-instantview).
* Or, download package from Releases page and install it via Wordpress Plugin Manager.
* Or, manually: copy the `tg-instantview` into your `wp-content/plugins` directory.
* Check plugin configuration in "Settings" -> "TG InstantView settings", you may configure your channel ID (appears in IV header).

## Media controls

Open **Settings → TG InstantView**:

1. Enable **Process media?**.
2. Choose **Remove media** or **Replace media with links**.
3. Save changes and clear the WordPress, Cloudflare and Telegram preview caches.

The option processes article images, `picture`, `iframe`, `video`, `audio`,
`object` and `embed` elements. The Open Graph image remains available for the
Telegram preview card.

## Automated SSH deployment

See [DEPLOY.md](DEPLOY.md) for the one-command deployment and rollback-safe
server installer.

## Notes

* To test, how your posts appears for Telegram bot, just add `tg-instantview=1` parameter to post URL, [Post example](https://petro.ws/vancouver-skies/?tg-instantview=1).
* Telegram bot **caches** parsing result, and Instant View will be triggered for **new** URL's for bot. You may prune cache by adding some random parameters like `test=123`, Telegram bot will request again.
* Instant View works only on mobile cliens! Do not try to test it desktop clients.

## Screenshots

![How looks in Telegram](assets/screenshot-1.jpg) ![Instant View](assets/screenshot-2.jpg) ![Instant View](assets/screenshot-3.jpg)  ![Instant View](assets/screenshot-4.jpg)
