<?php
/*
This is a fake template from teletype.in, triggers IV

Based on: https://gist.github.com/fishchev/ed2ca15d5ffd9594d41498a4bf9ba12e
*/
if (!defined("ABSPATH")) {
    exit;
}
// Get Plugin settings array
$tgiv_options = tgiv_options();
// Check for default values

// Get default WP meta tags, going to be displayed for this post
$tgiv_wp_meta = tgiv_extract_meta();

// Prepare list of meta tags, rendered for preview
$tgiv_meta = array();
// Required to render:
$tgiv_meta['tg:site_verification'] = 'g7j8/rPFXfhyrq5q0QQV7EsYWv4=';
// Channel name?
if ($tgiv_options['tgiv_channel_name']) {
	$tgiv_meta['telegram:channel'] = $tgiv_options['tgiv_channel_name'];
}

// Published date: article:published_time
if ($tgiv_options['tgiv_display_date']) {
	$tgiv_meta['article:published_time'] = get_the_date('c');
} else {
	// Date disabled, display none
	$tgiv_meta['article:published_time'] = '';
}
// Author: article:author
if ($tgiv_options['tgiv_display_author']) {
	$tgiv_meta['article:author'] = get_the_author();
} else {
	// Author disabled, display none
	$tgiv_meta['article:author'] = '';
}
// Image: og:image
if (isset($tgiv_wp_meta['og:image'])) {
	$tgiv_meta['og:image'] = $tgiv_wp_meta['og:image'];
} else {
	// If not set, get from WP
	$tgiv_meta['og:image'] = get_the_post_thumbnail_url();
}
// Site name: og:site_name
$tgiv_meta['og:site_name'] = get_bloginfo('name');
// Title: og:title
$tgiv_meta['og:title'] = get_the_title();
// Short text: og:description
if (isset($tgiv_wp_meta['og:description'])) {
	$tgiv_meta['og:description'] = $tgiv_wp_meta['og:description'];
} else {
	// If not set, fill with excert
	$tgiv_meta['og:description'] = get_the_excerpt();
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
	<?php foreach ($tgiv_meta as $tgiv_meta_key => $tgiv_meta_value) { ?>
    <meta property="<?php echo esc_html($tgiv_meta_key); ?>" content="<?php echo esc_html($tgiv_meta_value); ?>" />
	<?php } ?>
</head>

<body <?php body_class(); ?>>

<div id="content" class="site-content article">

<article class="article__content">
	<div class="entry-content">
		<div class="single-entry-thumb">
			<?php the_post_thumbnail(); ?>
		</div>
		<?php the_content(); ?>
	</div><!-- .entry-content -->

	<footer class="entry-footer">
	<?php
		if (get_the_category_list()) {
			?>
				<div class="cat-links">
					<?php the_category(', '); ?>
				</div>
			<?php
		}
		if (get_the_tag_list()) {
			?>
				<div class="tags-links">
					<?php the_tags('', ', '); ?>
				</div>
			<?php
		}
	?>

	</footer><!-- .entry-footer -->
</article><!-- #post-## -->

</div>
</body>
