<?php
/*
Plugin Name: TG-InstantView
Plugin URI: https://github.com/petrows/wp-tg-instantview
Description: Triggers Telegram InstantView for posts
Version: 1.3
Author: Petro
Author URI: https://petro.ws/
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if (!defined("ABSPATH")) {
    exit;
}

add_option('tgiv_instanview_channel_name', '');

// Load admin settings
if (is_admin()) {
    require (dirname(__FILE__) . '/tg-admin.php');
}

// Register test query var
function tgiv_query_vars_filter($vars) {
    $vars[] .= 'tg-instantview';
    return $vars;
}

add_filter( 'query_vars', 'tgiv_query_vars_filter' );

/*
Telegram InstantView does not expand built-in Gutenberg gallery,
with nested <figure> with other figures inside. More precise, it
can, but require submit your template, which likely will be never approved.

To "fix" this, we are expanding it set of <figure> tags, it will
display them as a nice built-in images gallery.
*/
function tgiv_extract_gallery($block_content)
{
    // Find all images
    if (preg_match_all('/<img[^>]+\/>/', $block_content, $out)) {
        $html = '';
        foreach($out[0] as $v) {
            $html .= '<figure class="wp-block-image size-large">'.$v.'</figure>';
        }
        return $html;
    }

    return $block_content;
}

// Load replace function - just before header starts to be rendered
add_action('template_redirect', 'tgiv_instanview', 1);

// Main plugin function: detects Telegram bot and provide fake template
function tgiv_instanview() {
    global $wp_query;

    // Activate only on single post page
    if (1 !== $wp_query->post_count) {
        return;
    }

    if (
        // This is Telegram Bot coming?
        'TelegramBot (like TwitterBot)' == $_SERVER['HTTP_USER_AGENT']
        ||
        // ... or use '?tg-instantview=1' for testing
        '1' === $wp_query->get( 'tg-instantview' )
    ) {
        // Okay, we are activated!

        // Add filter to "fix" gallery, see function above
        add_filter('render_block_core/gallery', 'tgiv_extract_gallery');

        // Dsiplay special template to trigger IV
        require (dirname(__FILE__) . '/tg-display.php');
        // We are done, stop processing here
        exit();
    }
}
