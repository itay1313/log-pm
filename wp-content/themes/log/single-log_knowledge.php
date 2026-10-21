<?php
/**
 * Knowledge item (spec v6 §13): type · title · date · image · excerpt · body · files · copy link · more of the same type.
 * A media mention can be short: outlet, date, excerpt and a link to the original piece.
 */
get_header();
while ( have_posts() ) : the_post();
	$kid      = get_the_ID();
	$type     = get_the_terms( $kid, 'log_knowledge_type' );
	$type     = $type && ! is_wp_error( $type ) ? $type[0] : null;
	$outlet   = log_field( 'outlet', $kid );
	$external = log_field( 'external_url', $kid );
	?>
	<article class="knowledge-item">
		<?php get_template_part( 'template-parts/page-header', null, [
			'meta'    => esc_html( implode( ' · ', array_filter( [ $type?->name, $outlet, get_the_date() ] ) ) ),
			'title'   => get_the_title(),
		] ); ?>
		<p class="container"><a class="back-link" href="<?php echo esc_url( get_post_type_archive_link( 'log_knowledge' ) ); ?>" data-back>למרכז הידע</a></p>
		<?php if ( has_excerpt() ) : ?><p class="container knowledge-item__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
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

		<div class="container">
			<?php if ( $external ) : ?><a class="btn" href="<?php echo esc_url( $external ); ?>" target="_blank" rel="noopener">לכתבה המקורית</a><?php endif; ?>
			<button type="button" class="btn btn--ghost" data-copy-link>העתקת קישור</button>
		</div>

		<?php
		$more = $type ? new WP_Query( [
			'post_type'      => 'log_knowledge',
			'posts_per_page' => 3,
			'post__not_in'   => [ $kid ],
			'no_found_rows'  => true,
			'tax_query'      => [ [ 'taxonomy' => 'log_knowledge_type', 'terms' => $type->term_id ] ],
		] ) : null;
		if ( $more && $more->have_posts() ) : ?>
			<section class="section section--more">
				<div class="container">
					<h2 class="section__title">פריטים נוספים</h2>
					<ul class="knowledge-list">
						<?php while ( $more->have_posts() ) : $more->the_post(); ?>
							<li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> <span class="meta"><?php echo esc_html( get_the_date() ); ?></span></li>
						<?php endwhile; wp_reset_postdata(); ?>
					</ul>
				</div>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;
get_footer();
