<?php
/**
 * Open jobs as rows (spec v6 §14): title · team · location · scope; the whole row links to the job page.
 * $args['query']: a WP_Query of jobs.
 */
$q = $args['query'];
?>
<ul class="jobs">
	<?php while ( $q->have_posts() ) : $q->the_post();
		$loc   = [ 'tel-aviv' => 'תל אביב', 'jerusalem' => 'ירושלים', 'hybrid' => 'היברידי' ][ log_field( 'location', get_the_ID(), 'tel-aviv' ) ] ?? '';
		$scope = [ 'full' => 'משרה מלאה', 'part' => 'משרה חלקית', 'student' => 'סטודנט/ית' ][ log_field( 'scope', get_the_ID(), 'full' ) ] ?? ''; ?>
		<li class="jobs__item">
			<a href="<?php the_permalink(); ?>">
				<span class="jobs__title"><?php the_title(); ?></span>
				<span class="jobs__meta"><?php echo esc_html( implode( ' · ', array_filter( [ log_field( 'team', get_the_ID() ), $loc, $scope ] ) ) ); ?></span>
			</a>
		</li>
	<?php endwhile; wp_reset_postdata(); ?>
</ul>
