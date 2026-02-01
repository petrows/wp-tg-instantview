<?php
/**
 * TG-InstantView WordPress Plugin
 *
 * Triggers Telegram InstantView for posts.
 *
 * @package   TG-InstantView
 * @author    Petro
 * @copyright Copyright (c) Petro
 * @license   GPLv3
 *
 * @wordpress-plugin
 * Plugin Name: TG-InstantView
 * Plugin URI: https://github.com/petrows/wp-tg-instantview
 * Description: Triggers Telegram InstantView for posts
 * Version: 1.7
 * Author URI: https://petro.ws/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
Define options and defaults:
Displayed Telegram channel name
*/
add_option( 'tgiv_instantview_render', array() );

/* Load admin settings */
if ( is_admin() ) {
	include __DIR__ . '/tg-admin.php';
}

/**
 * Register extra query var
 *
 * @param array $vars All query vars.
 * @return array Modified query vars.
 */
function tgiv_query_vars_filter( $vars ) {
	$vars[] .= 'tg-instantview';
	return $vars;
}

add_filter( 'query_vars', 'tgiv_query_vars_filter' );

/**
 * Function to get and prepare default options for plugin
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

/**
 * Function to control displayed links on plugin list page
 *
 * @param array $actions Existing action links.
 * @return array Modified action links.
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

/**
 * Function to get prepared meta HTML tags from "normal" WP output.
 * We have to use ob_* functions to get HTML, as seems to be there is
 * no better way to get rendered tags from SEO plugins and etc.
 *
 * @return array Array of meta tags, key = property, value = content.
 */
function tgiv_extract_meta() {
	ob_start();
	wp_head();
	$html_output = ob_get_contents();
	ob_end_clean();
	$meta_out = array();
	/* Find all meta tags */
	if ( preg_match_all( '/<meta([^>]+)\/>/Uuims', $html_output, $out ) ) {
		$html = '';
		foreach ( $out[1] as $meta_contents ) {
			/* Extract HTML attributes */
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
				$meta_out[ $meta_name ] = htmlspecialchars_decode( $meta_value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
			}
		}
	}
	return $meta_out;
}

/**
 * Telegram InstantView does not expand built-in Gutenberg gallery,
 * with nested <figure> with other figures inside. More precise, it
 * can, but require submit your template, which likely will be never approved.
 *
 * To "fix" this, we are expanding it set of <figure> tags, it will
 * display them as a nice built-in images gallery.
 *
 * @param string $block_content Original block content.
 * @return string Modified block content.
 */
function tgiv_extract_gallery( $block_content ) {
	/* Find all images inside gallery */
	if ( preg_match_all( '/<img[^>]+\/>/', $block_content, $out ) ) {
		$html = '';
		foreach ( $out[0] as $v ) {
			$html .= '<figure class="wp-block-image size-large">' . $v . '</figure>';
		}
		return $html;
	}

	return $block_content;
}

/* Load replace function - just before header starts to be rendered */
add_action( 'template_redirect', 'tgiv_instanview', 1 );

/** Disable lazy-load for featured images.
 *
 * @param array   $attr Image attributes.
 * @param WP_Post $attachment Attachment object.
 * @return array Modified attributes.
 */
function tgiv_disable_lazy_load_featured_images( $attr, $attachment = null ) {
	( $attachment ); /* unused */
	if ( isset( $attr['data-src'] ) && $attr['data-src'] ) {
		$attr['src'] = $attr['data-src'];
		unset( $attr['data-src'] );
	}
	$attr['loading'] = 'eager';
	return $attr;
}

/**
 * Main plugin function: detects Telegram bot and provide fake template.
 */
function tgiv_instanview() {
	global $wp_query;

	/* Activate only on single post page */
	if ( 1 !== $wp_query->post_count ) {
		return;
	}

	$user_agent = '';
	if ( isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
		/*
			This expression triggers InputNotSanitized warnings, and this is false-positive.
			See: https://github.com/WordPress/WordPress-Coding-Standards/issues/2246
		*/
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$user_agent = wp_unslash( $_SERVER['HTTP_USER_AGENT'] );
	}

	if ( /* This is Telegram Bot coming? */
		'TelegramBot (like TwitterBot)' === $user_agent

		/* ... or just use GET var '?tg-instantview=1' for testing */
		|| '1' === $wp_query->get( 'tg-instantview' )
	) {
		/*
			Okay, we are activated!

		We have to mark our output as coming out as "Feed".
		This is important to ask other plugins, to be more "nice" with output,
		fixes issue with EWWW Image Optimizer (and probably others).
		*/
		$wp_query->is_feed = true;

		/* Disable Lazy-load */
		add_filter( 'wp_lazy_loading_enabled', '__return_false', 1024 );
		add_filter( 'do_rocket_lazyload', '__return_false', 1024 );
		add_filter( 'wp_get_attachment_image_attributes', 'tgiv_disable_lazy_load_featured_images', 1024 );

		/* Add filter to "fix" gallery, see function above */
		add_filter( 'render_block_core/gallery', 'tgiv_extract_gallery' );

		/* Dsiplay special template to trigger IV */
		include __DIR__ . '/tg-display.php';
		/* We are done, stop processing here */
		exit();
	}
}
