<?php
/**
 * /careers (spec §13): vision, how it looks, hiring path, values, open positions.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => log_text( 'careers_title', 'בואו לעשות שינוי. בקריירה שלכם ובתכנון פני המדינה.', 'option' ),
	'lede'  => log_text( 'careers_lede', '', 'option' ),
] );
$path = log_rows( 'careers_path', [
	[ 'name' => 'קורות חיים', 'text' => '' ],
	[ 'name' => 'שיחת היכרות', 'text' => '' ],
	[ 'name' => 'משימת בית וראיון מקצועי', 'text' => '' ],
	[ 'name' => 'ברוכים הבאים', 'text' => '' ],
], 'option' );
$gallery = log_field( 'careers_gallery', 'option' );

$jobs = new WP_Query( [
	'post_type'      => 'log_job',
	'posts_per_page' => -1,
	'meta_key'       => 'is_open',
	'meta_value'     => '1',
	'orderby'        => 'date',
	'order'          => 'DESC',
] );
?>
<div class="container">
	<section class="section" data-section="culture">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_culture_title', 'איך זה נראה אצלנו', 'option' ) ); ?></h2>
		<?php if ( is_array( $gallery ) && $gallery ) : ?>
			<div class="gallery__grid">
				<?php foreach ( $gallery as $img ) : ?><figure><img src="<?php echo esc_url( $img['sizes']['log-card'] ?? $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy"></figure><?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="placeholder">גלריית "יום רגיל": הצוותים, המשרדים, האירועים והכלבים.</p>
		<?php endif; ?>
		<p class="dogs"><?php echo esc_html( log_text( 'careers_dogs', 'הכלבים מסתובבים חופשי במשרד.', 'option' ) ); ?></p>
	</section>

	<section class="section" data-section="path">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_path_title', 'מסלול הגיוס', 'option' ) ); ?></h2>
		<ol class="process">
			<?php foreach ( array_slice( $path, 0, 4 ) as $i => $step ) : ?>
				<li><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $step['name'] ?? '' ); ?></h3><?php if ( ! empty( $step['text'] ) ) : ?><p><?php echo esc_html( $step['text'] ); ?></p><?php endif; ?></li>
			<?php endforeach; ?>
		</ol>
	</section>

	<section class="section" data-section="jobs" id="jobs">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_jobs_title', 'הצטרפו אלינו', 'option' ) ); ?></h2>
		<?php if ( $jobs->have_posts() ) : ?>
			<ul class="jobs">
				<?php while ( $jobs->have_posts() ) : $jobs->the_post();
					$loc = [ 'tel-aviv' => 'תל אביב', 'jerusalem' => 'ירושלים', 'hybrid' => 'היברידי' ][ log_field( 'location', get_the_ID(), 'tel-aviv' ) ] ?? '';
					$scope = [ 'full' => 'משרה מלאה', 'part' => 'משרה חלקית', 'student' => 'סטודנט/ית' ][ log_field( 'scope', get_the_ID(), 'full' ) ] ?? ''; ?>
					<li class="jobs__item">
						<a href="<?php the_permalink(); ?>">
							<span class="jobs__title"><?php the_title(); ?></span>
							<span class="jobs__meta"><?php echo esc_html( implode( ' · ', array_filter( [ log_field( 'team', get_the_ID() ), $loc, $scope ] ) ) ); ?></span>
						</a>
					</li>
				<?php endwhile; wp_reset_postdata(); ?>
			</ul>
		<?php else : ?>
			<p><?php echo esc_html( log_text( 'careers_empty', 'אין כרגע משרות פתוחות. מוזמנים לשלוח קורות חיים כלליים.', 'option' ) ); ?></p>
		<?php endif; ?>
		<a class="btn" href="<?php echo esc_url( home_url( '/contact/?topic=jobs' ) ); ?>"><?php echo esc_html( log_text( 'careers_general', 'לא מצאתם את התפקיד שלכם? הגשת מועמדות כללית', 'option' ) ); ?></a>
	</section>
</div>
<?php get_footer();
