<?php
/*
Plugin Name: TG-InstantView
Plugin URI: https://github.com/petrows/wp-tg-instantview
Description: Triggers Telegram InstantView for posts
Version: 1.6
Author: Petro
Author URI: https://petro.ws/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define options and defaults:
// Displayed Telegram channel name
add_option( 'tgiv_instantview_render', array() );

// Load admin settings
if ( is_admin() ) {
	include __DIR__ . '/tg-admin.php';
}

// Register test query var
function tgiv_query_vars_filter( $vars ) {
	$vars[] .= 'tg-instantview';
	return $vars;
}

add_filter( 'query_vars', 'tgiv_query_vars_filter' );

/*
Function to get and prepare default options for plugin
*/
function tgiv_options() {
	$options = get_option( 'tgiv_instantview_render' );
	if ( ! isset( $options['tgiv_channel_name'] ) ) {
		$options['tgiv_channel_name'] = '';
	}
	if ( ! isset( $options['tgiv_display_date'] ) ) {
		$options['tgiv_display_date'] = true;
	} else {
		$options['tgiv_display_date'] = boolval( $options['tgiv_display_date'] );
	}
	if ( ! isset( $options['tgiv_display_author'] ) ) {
		$options['tgiv_display_author'] = true;
	} else {
		$options['tgiv_display_author'] = boolval( $options['tgiv_display_author'] );
	}
	return $options;
}

/*
Functon to control displayed links on plugin list page
*/
function tgiv_add_action_links( $actions ) {
	$settings_url = admin_url( 'options-general.php?page=tgiv-instantview-setting-admin' );

	$links_add = array(
		'<a href="' . $settings_url . '">' . __( 'Settings' ) . '</a>',
	);
	$actions   = array_merge( $links_add, $actions );
	return $actions;
}

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'tgiv_add_action_links' );

/*
Function to get prepared meta HTML tags from "normal" WP output.
We have to use ob_* functions to get HTML, as seems to be there is
no better way to get rendered tags from SEO plugins and etc.
*/
function tgiv_extract_meta() {
	ob_start();
	wp_head();
	$html_output = ob_get_contents();
	ob_end_clean();
	$meta_out = array();
	// Find all meta tags
	if ( preg_match_all( '/<meta([^>]+)\/>/Uuims', $html_output, $out ) ) {
		$html = '';
		foreach ( $out[1] as $meta_contents ) {
			// Extract HTML attributes
			$meta_name  = '';
			$meta_value = '';
			if ( preg_match( '/property="([^"]*)"/Uuims', $meta_contents, $meta_v ) ) {
				$meta_name = $meta_v[1];
			} elseif ( preg_match( '/property=\'([^\']*)\'/Uuims', $meta_contents, $meta_v ) ) {
				$meta_name = $meta_v[1];
			}
			if ( preg_match( '/content="([^"]*)"/Uuims', $meta_contents, $meta_v ) ) {
				$meta_value = $meta_v[1];
			} elseif ( preg_match( '/content=\'([^\']*)\'/Uuims', $meta_contents, $meta_v ) ) {
				$meta_value = $meta_v[1];
			}
			if ( $meta_name ) {
				$meta_out[ $meta_name ] = htmlspecialchars_decode( $meta_value );
			}
		}
	}
	return $meta_out;
}

/*
Telegram InstantView does not expand built-in Gutenberg gallery,
with nested <figure> with other figures inside. More precise, it
can, but require submit your template, which likely will be never approved.

To "fix" this, we are expanding it set of <figure> tags, it will
display them as a nice built-in images gallery.
*/
function tgiv_extract_gallery( $block_content ) {
	// Find all images
	if ( preg_match_all( '/<img[^>]+\/>/', $block_content, $out ) ) {
		$html = '';
		foreach ( $out[0] as $v ) {
			$html .= '<figure class="wp-block-image size-large">' . $v . '</figure>';
		}
		return $html;
	}

	return $block_content;
}

// Load replace function - just before header starts to be rendered
add_action( 'template_redirect', 'tgiv_instanview', 1 );

// Function to disable lazy load function
function tgiv_disable_lazy_load_featured_images( $attr, $attachment = null ) {
	if ( @$attr['data-src'] ) {
		$attr['src'] = $attr['data-src'];
		unset( $attr['data-src'] );
	}
	$attr['loading'] = 'eager';
	return $attr;
}

// Main plugin function: detects Telegram bot and provide fake template
function tgiv_instanview() {
	global $wp_query;

	// Activate only on single post page
	if ( 1 !== $wp_query->post_count ) {
		return;
	}

	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';

	if ( // This is Telegram Bot coming?
		'TelegramBot (like TwitterBot)' == $user_agent

		// ... or use '?tg-instantview=1' for testing
		|| '1' === $wp_query->get( 'tg-instantview' )
	) {
		// Okay, we are activated!

		// We have to mark our output as coming out as "Feed".
		// This is important to ask other plugins, to be more "nice" with output,
		// fixes issue with EWWW Image Optimizer (and probably others).
		$wp_query->is_feed = true;

		// Disable Lazy-load
		add_filter( 'wp_lazy_loading_enabled', '__return_false', 1024 );
		add_filter( 'do_rocket_lazyload', '__return_false', 1024 );
		add_filter( 'wp_get_attachment_image_attributes', 'tgiv_disable_lazy_load_featured_images', 1024 );

		// Add filter to "fix" gallery, see function above
		add_filter( 'render_block_core/gallery', 'tgiv_extract_gallery' );

		// Dsiplay special template to trigger IV
		include __DIR__ . '/tg-display.php';
		// We are done, stop processing here
		exit();
	}
}
