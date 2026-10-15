<?php
/**
 * /knowledge (spec §12): one lead item, then a list filtered by type.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => log_text( 'knowledge_title', 'מרכז ידע', 'option' ),
	'lede'  => log_text( 'knowledge_lede', 'ניירות עמדה, הודעות לעיתונות, מאחורי הקלעים.', 'option' ),
] );
?>
<div class="container">
	<?php $types = get_terms( [ 'taxonomy' => 'log_knowledge_type', 'hide_empty' => true ] );
	if ( $types && ! is_wp_error( $types ) ) : ?>
		<nav class="filters" aria-label="סינון לפי סוג">
			<a href="<?php echo esc_url( get_post_type_archive_link( 'log_knowledge' ) ); ?>" class="<?php echo is_tax() ? '' : 'is-active'; ?>">הכל</a>
			<?php foreach ( $types as $t ) : ?><a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a><?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<?php if ( have_posts() ) : $i = 0; ?>
		<div class="knowledge">
			<?php while ( have_posts() ) : the_post(); $i++;
				$type = get_the_terms( get_the_ID(), 'log_knowledge_type' ); ?>
				<article class="card card--knowledge<?php echo $i === 1 && ! is_paged() ? ' card--lead' : ''; ?>">
					<a class="card__link" href="<?php the_permalink(); ?>">
						<?php if ( has_post_thumbnail() ) : ?><figure class="card__media"><?php the_post_thumbnail( 'log-card', [ 'loading' => $i === 1 ? 'eager' : 'lazy' ] ); ?></figure><?php endif; ?>
						<div class="card__body">
							<p class="card__meta"><?php if ( $type && ! is_wp_error( $type ) ) { echo esc_html( $type[0]->name ) . ' · '; } echo esc_html( get_the_date() ); ?></p>
							<h2 class="card__title"><?php the_title(); ?></h2>
							<p class="card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
						</div>
					</a>
				</article>
			<?php endwhile; ?>
		</div>
		<?php the_posts_pagination( [ 'prev_text' => 'הקודם', 'next_text' => 'הבא' ] ); ?>
	<?php else : ?>
		<p class="placeholder">עדיין אין פריטי ידע.</p>
	<?php endif; ?>
</div>
<?php get_footer();
