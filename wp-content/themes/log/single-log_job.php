<?php
/**
 * Job page (spec §13). Closed jobs stay online and point to the open ones.
 */
get_header();
while ( have_posts() ) : the_post();
	$jid   = get_the_ID();
	$open = (bool) log_field( 'is_open', $jid );
	$loc  = [ 'tel-aviv' => 'תל אביב', 'jerusalem' => 'ירושלים (גבעת שאול)', 'hybrid' => 'היברידי' ][ log_field( 'location', $jid, 'tel-aviv' ) ] ?? '';
	$scope = [ 'full' => 'משרה מלאה', 'part' => 'משרה חלקית', 'student' => 'סטודנט/ית' ][ log_field( 'scope', $jid, 'full' ) ] ?? '';
	$side = array_filter( [
		'מיקום'         => $loc,
		'היקף'          => $scope,
		'צוות'          => log_field( 'team', $jid ),
		'גודל הצוות'    => log_field( 'team_size', $jid ),
		'כפיפות'        => log_field( 'reports_to', $jid ),
		'תחילת עבודה'   => log_field( 'start_date', $jid ),
	] );
	$blocks = [
		'על התפקיד'   => [ 'about', false ],
		'מה תעשו'     => [ 'what_you_do', true ],
		'עם מי תעבדו' => [ 'work_with', false ],
		'דרישות'      => [ 'requirements', true ],
		'יתרון'       => [ 'advantage', true ],
		'מה מחכה לכם' => [ 'whats_waiting', false ],
		'איך מתקבלים' => [ 'how_to_apply', false ],
	];
	?>
	<article class="job">
		<?php get_template_part( 'template-parts/page-header', null, [
			'eyebrow' => esc_html( implode( ' · ', array_filter( [ log_field( 'team', $jid ), $loc, $scope ] ) ) ),
			'title'   => get_the_title(),
		] ); ?>

		<?php if ( ! $open ) : ?>
			<div class="container"><p class="notice">המשרה הזו אוישה. <a href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) . '#jobs' ); ?>">למשרות הפתוחות</a></p></div>
		<?php endif; ?>

		<div class="container job__layout">
			<div class="job__body">
				<?php foreach ( $blocks as $heading => [ $field, $list ] ) :
					$text = log_field( $field, $jid );
					if ( ! $text ) { continue; } ?>
					<section class="story-block">
						<h2><?php echo esc_html( $heading ); ?></h2>
						<?php echo $list ? log_lines_to_list( $text ) : wpautop( esc_html( $text ) ); ?>
					</section>
				<?php endforeach; ?>
				<?php if ( get_the_content() ) : ?><div class="prose"><?php the_content(); ?></div><?php endif; ?>
			</div>

			<aside class="job__side">
				<dl class="facts">
					<?php foreach ( $side as $k => $v ) : ?><div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div><?php endforeach; ?>
				</dl>
				<?php if ( $open ) : ?>
					<a class="btn btn--primary" href="#apply">הגשת מועמדות</a>
				<?php endif; ?>
				<button type="button" class="btn btn--ghost" data-copy-link>העתקת קישור למשרה</button>
			</aside>
		</div>

		<?php if ( $open ) : ?>
			<section class="section section--apply container" id="apply">
				<h2 class="section__title">הגשת מועמדות: <?php the_title(); ?></h2>
				<?php get_template_part( 'template-parts/form-apply', null, [ 'job' => $jid ] ); ?>
			</section>
		<?php endif; ?>
	</article>
	<?php
endwhile;
get_footer();
