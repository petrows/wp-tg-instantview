<?php
/**
 * Standalone smoke tests for media processing helpers.
 *
 * Run with: php tests/media-test.php
 */

define( 'ABSPATH', __DIR__ . '/' );

function is_ssl() {
	return true;
}

function home_url( $path = '' ) {
	return 'https://example.test' . $path;
}

function esc_url_raw( $url ) {
	return preg_match( '#^https?://#i', $url ) ? $url : '';
}

function esc_url( $url ) {
	return htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
}

function esc_attr( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function esc_html( $value ) {
	return htmlspecialchars( $value, ENT_QUOTES, 'UTF-8' );
}

function sanitize_html_class( $value ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', $value );
}

function get_the_post_thumbnail() {
	return '<img src="https://example.test/featured.jpg" alt="Featured">';
}

function get_the_post_thumbnail_url() {
	return 'https://example.test/featured.jpg';
}

function get_post_thumbnail_id() {
	return 10;
}

function get_post_meta() {
	return 'Featured alt';
}

require dirname( __DIR__ ) . '/tg-instantview/tg-media.php';

function tgiv_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

$html = <<<'HTML'
<p>Before</p>
<figure><a href="https://example.test/full.jpg"><img src="/image.jpg" alt="Map"></a><figcaption>Map caption</figcaption></figure>
<picture><source srcset="/wide.webp 2x"><img src="/small.jpg" alt="Picture"></picture>
<iframe src="https://video.example/embed/1" title="Interview"></iframe>
<video><source src="/movie.mp4"></video>
<audio src="/audio.mp3"></audio>
<object data="/document.pdf"><embed src="/document.pdf"></object>
<p>After</p>
HTML;

tgiv_test_assert( $html === tgiv_process_media_html( $html, 'keep' ), 'keep mode must not modify HTML' );

$removed = tgiv_process_media_html( $html, 'remove' );
tgiv_test_assert( false === preg_match( '/<(?:img|picture|iframe|video|audio|object|embed)\b/i', $removed ), 'remove mode must remove media tags' );
tgiv_test_assert( false !== strpos( $removed, 'Before' ) && false !== strpos( $removed, 'After' ), 'remove mode must preserve article text' );

$linked = tgiv_process_media_html( $html, 'link' );
tgiv_test_assert( false === preg_match( '/<(?:img|picture|iframe|video|audio|object|embed)\b/i', $linked ), 'link mode must remove original media tags' );
tgiv_test_assert( false !== strpos( $linked, 'https://example.test/image.jpg' ), 'link mode must make relative image URLs absolute' );
tgiv_test_assert( false !== strpos( $linked, 'https://video.example/embed/1' ), 'link mode must preserve iframe URL' );
tgiv_test_assert( false === strpos( $linked, '<a href="https://example.test/full.jpg"><a' ), 'link mode must not create nested anchors' );

tgiv_test_assert( '' === tgiv_get_featured_media_html( 'remove' ), 'remove mode must remove featured body image' );
tgiv_test_assert( false !== strpos( tgiv_get_featured_media_html( 'link' ), 'featured.jpg' ), 'link mode must link featured image' );
tgiv_test_assert( false !== strpos( tgiv_get_featured_media_html( 'keep' ), '<img' ), 'keep mode must render featured image' );

fwrite( STDOUT, "Media tests passed\n" );
