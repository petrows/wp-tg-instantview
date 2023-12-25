=== Plugin Name ===
Contributors: petro64
Tags: telegram
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.0
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

This plugin triggers Telegram Instantvew for all posts, no special links required.

== Description ==

Plugin to trigger [Telegram Instant View](https://instantview.telegram.org/) for all Blog posts.

Principal: it detects Telegram bot and returns special template for posts, to trigger Instant View.

* No template submitting required.
* No special links required.

Original Telegram InstantView templates registry appears to be dead, my template was not reviewed and submitted in over 1 year. This plugin solves this issue server-side.

My telegram channel with plugin active: https://t.me/petro_ws

== Frequently Asked Questions ==

= Why not to use original Telegram Instant View templates registry? =

Telegram's InstantView looks abadoned, they do not accept new templates for years.

= Why not to use way to build URL via Telegram InstantView Bot? =

This works, but it will create ugly long URL. This plugin makes everything clear, like you have template in registry, you may post clear links to posts directly, your frends may do so and so on.

= I installed plgun, but i still dont see InstantView on my posts =

If you still dont see InstantView on URL's, check the following:

* Telegram bot **caches** parsing result, and Instant View will be triggered for **new** URL's for bot. You may prune cache by adding some random parameters like `test=123` and change it every time, Telegram bot will request again.
* Instant View works only on mobile cliens! Do not try to test it desktop clients.

= I want to test, how Telegram sees my post via InstantView =

To test, how your posts appears for Telegram bot, just add `iv=1` parameter to post URL, [Post example](https://petro.ws/vancouver-skies/?iv=1).

== Screenshots ==

1. InstantView in Telegram mobile channel
2. InstantView opened
2. InstantView opened (gallery)

== Changelog ==

= 1.0 =
* Initital version
