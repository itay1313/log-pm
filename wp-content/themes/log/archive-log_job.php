<?php
/**
 * /careers (spec §13): vision, how it looks, hiring path, values, open positions.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => 'בואו לעשות שינוי. בקריירה שלכם ובתכנון פני המדינה.',
] );

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
		<h2 class="section__title">איך זה נראה אצלנו</h2>
		<p class="placeholder">גלריית "יום רגיל": הצוותים, המשרדים, האירועים והכלבים.</p>
	</section>

	<section class="section" data-section="path">
		<h2 class="section__title">מסלול הגיוס</h2>
		<ol class="process">
			<li><?php echo log_stage_ring( 1, false ); ?><h3>קורות חיים</h3></li>
			<li><?php echo log_stage_ring( 2, false ); ?><h3>שיחת היכרות</h3></li>
			<li><?php echo log_stage_ring( 3, false ); ?><h3>משימת בית וראיון מקצועי</h3></li>
			<li><?php echo log_stage_ring( 4, false ); ?><h3>ברוכים הבאים</h3></li>
		</ol>
	</section>

	<section class="section" data-section="jobs" id="jobs">
		<h2 class="section__title">הצטרפו אלינו</h2>
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
			<p>אין כרגע משרות פתוחות. מוזמנים לשלוח קורות חיים כלליים.</p>
		<?php endif; ?>
		<a class="btn" href="<?php echo esc_url( home_url( '/contact/?topic=jobs' ) ); ?>">לא מצאתם את התפקיד שלכם? הגשת מועמדות כללית</a>
	</section>
</div>
<?php get_footer();
