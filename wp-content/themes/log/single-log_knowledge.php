<?php
/**
 * Knowledge item (spec §12).
 */
get_header();
while ( have_posts() ) : the_post();
	$kid   = get_the_ID();
	$type = get_the_terms( $kid, 'log_knowledge_type' );
	$by   = ( $p = log_field( 'author_person', $kid ) ) ? get_the_title( $p ) : get_the_author();
	?>
	<article class="knowledge-item">
		<?php get_template_part( 'template-parts/page-header', null, [
			'eyebrow' => esc_html( implode( ' · ', array_filter( [ $type && ! is_wp_error( $type ) ? $type[0]->name : '', get_the_date(), $by ] ) ) ),
			'title'   => get_the_title(),
		] ); ?>
		<?php if ( has_post_thumbnail() ) : ?><figure class="knowledge-item__media container"><?php the_post_thumbnail( 'log-wide' ); ?></figure><?php endif; ?>
		<div class="container prose"><?php the_content(); ?></div>

		<?php if ( $files = log_field( 'files', $kid ) ) : ?>
			<div class="container">
				<h2>להורדה</h2>
				<ul class="downloads">
					<?php foreach ( $files as $f ) : if ( empty( $f['file'] ) ) { continue; } ?>
						<li><a href="<?php echo esc_url( $f['file']['url'] ); ?>" download><?php echo esc_html( $f['label'] ?: $f['file']['filename'] ); ?></a> <span><?php echo esc_html( size_format( $f['file']['filesize'] ?? 0 ) ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<?php if ( $proj = log_field( 'related_project', $kid ) ) : ?>
			<div class="container">
				<h2>הפרויקט שהפריט מדבר עליו</h2>
				<?php $q = new WP_Query( [ 'p' => $proj, 'post_type' => 'log_project' ] );
				while ( $q->have_posts() ) : $q->the_post(); get_template_part( 'template-parts/card-project' ); endwhile; wp_reset_postdata(); ?>
			</div>
		<?php endif; ?>

		<div class="container">
			<button type="button" class="btn btn--ghost" data-copy-link>העתקת קישור</button>
		</div>
	</article>
	<?php
endwhile;
get_footer();
