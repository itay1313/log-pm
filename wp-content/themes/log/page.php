<?php
/**
 * Default page (used by control, about, contact, accessibility, privacy until each gets its own).
 */
get_header();
while ( have_posts() ) : the_post();
	get_template_part( 'template-parts/page-header' );
	?>
	<div class="container prose">
		<?php the_content(); ?>
	</div>
	<?php
endwhile;
get_footer();
