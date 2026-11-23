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
			'meta'  => implode( ' · ', array_filter( [ $type?->name, $outlet, get_the_date() ] ) ),
			'title' => get_the_title(),
			'back'  => [ get_post_type_archive_link( 'log_knowledge' ), 'למרכז הידע' ],
		] ); ?>
		<?php // The piece reads in one column from the start line; what it is, where it ran and what to take away stand beside it. ?>
		<div class="container article__layout">
			<div class="article__body">
				<?php if ( has_excerpt() ) : ?><p class="knowledge-item__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
				<?php if ( has_post_thumbnail() ) : ?><figure class="knowledge-item__media"><?php the_post_thumbnail( 'log-wide' ); ?></figure><?php endif; ?>
				<div class="prose"><?php the_content(); ?></div>
			</div>
			<aside class="article__side" aria-label="פרטי הפריט">
				<dl class="facts">
					<?php foreach ( array_filter( [ 'סוג' => $type?->name, 'פורסם ב' => $outlet, 'תאריך' => get_the_date() ] ) as $k => $v ) : ?>
						<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
				<?php if ( $files = log_field( 'files', $kid ) ) : ?>
					<h2 class="article__side-title">להורדה</h2>
					<ul class="downloads">
						<?php foreach ( $files as $f ) : if ( empty( $f['file'] ) ) { continue; } ?>
							<li><a href="<?php echo esc_url( $f['file']['url'] ); ?>" download><?php echo esc_html( $f['label'] ?: $f['file']['filename'] ); ?></a> <span><?php echo esc_html( size_format( $f['file']['filesize'] ?? 0 ) ); ?></span></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
				<div class="article__actions">
					<?php if ( $external ) : ?><a class="btn" href="<?php echo esc_url( $external ); ?>" target="_blank" rel="noopener">לכתבה המקורית</a><?php endif; ?>
					<button type="button" class="btn btn--ghost" data-copy-link>העתקת קישור</button>
				</div>
			</aside>
		</div>

		<?php
		// Not `$more`: templates run in global scope, and the_post() overwrites WordPress's own global of that name.
		$others = $type ? new WP_Query( [
			'post_type'      => 'log_knowledge',
			'posts_per_page' => 3,
			'post__not_in'   => [ $kid ],
			'no_found_rows'  => true,
			'tax_query'      => [ [ 'taxonomy' => 'log_knowledge_type', 'terms' => $type->term_id ] ],
		] ) : null;
		if ( $others && $others->have_posts() ) : ?>
			<section class="section section--more">
				<div class="container">
					<h2 class="section__title"><?php echo log_title( 'פריטים נוספים' ); ?></h2>
					<ul class="knowledge-list">
						<?php while ( $others->have_posts() ) : $others->the_post(); ?>
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
