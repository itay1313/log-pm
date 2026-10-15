<?php
/**
 * Generic fallback. Every real route has its own template.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [ 'title' => get_the_archive_title() ?: get_bloginfo( 'name' ) ] );
?>
<div class="container">
	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
		<article <?php post_class(); ?>>
			<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<?php the_excerpt(); ?>
		</article>
	<?php endwhile; the_posts_pagination(); else : ?>
		<p>אין כאן עדיין תוכן.</p>
	<?php endif; ?>
</div>
<?php get_footer();
