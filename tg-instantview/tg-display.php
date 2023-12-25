<?php
/*
This is a fake template from teletype.in, triggers IV

Based on: https://gist.github.com/fishchev/ed2ca15d5ffd9594d41498a4bf9ba12e
*/
if (!defined("ABSPATH")) {
    exit;
}
$tg_options = get_option( 'tg_instantview_render' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
	<?php if (isset($tg_options['channel_name']) && strlen($tg_options['channel_name'])) { ?>
    <meta property="telegram:channel" content="<?php echo $tg_options['channel_name']; ?>">
	<?php } ?>
    <meta property="tg:site_verification" content="g7j8/rPFXfhyrq5q0QQV7EsYWv4=">
    <?php wp_head(); ?>
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
		<?php kanary_entry_footer(); ?>
	</footer><!-- .entry-footer -->
</article><!-- #post-## -->

</div>
</body>
