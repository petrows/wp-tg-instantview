<?php
/*
This is a fake template from teletype.in, triggers IV

Based on: https://gist.github.com/fishchev/ed2ca15d5ffd9594d41498a4bf9ba12e
*/
if (!defined("ABSPATH")) {
    exit;
}
$tg_options = get_option( 'tgiv_instantview_render' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
	<?php if (isset($tg_options['tgiv_channel_name']) && strlen($tg_options['tgiv_channel_name'])) { ?>
    <meta property="telegram:channel" content="<?php echo esc_html($tg_options['tgiv_channel_name']); ?>">
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
