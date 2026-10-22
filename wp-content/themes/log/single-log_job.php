<?php
/**
 * Job page (spec v6 §14): also made to be sent directly as a link.
 * Header: back to all jobs, team, title, tags, apply, copy link. Then: about the role, what you'll do,
 * who you'll work with, what matters (requirements and advantages), what awaits you, how hiring works,
 * job details (sticky beside the text on desktop), more open jobs.
 * Applying opens a dialog over the page. A closed job keeps its address: it says so and lists the open
 * jobs, with no form (noindex and sitemap rules in log-core).
 */
get_header();
while ( have_posts() ) : the_post();
	$jid   = get_the_ID();
	$open  = (bool) log_field( 'is_open', $jid );
	$loc   = [ 'tel-aviv' => 'תל אביב', 'jerusalem' => 'ירושלים (גבעת שאול)', 'hybrid' => 'היברידי' ][ log_field( 'location', $jid, 'tel-aviv' ) ] ?? '';
	$scope = [ 'full' => 'משרה מלאה', 'part' => 'משרה חלקית', 'student' => 'סטודנט/ית' ][ log_field( 'scope', $jid, 'full' ) ] ?? '';
	$team  = log_field( 'team', $jid );
	$jobs_url = get_post_type_archive_link( 'log_job' ) . '#jobs';
	$details = array_filter( [
		'מיקום'       => $loc,
		'היקף'        => $scope,
		'צוות'        => $team,
		'תחילת עבודה' => log_field( 'start_date', $jid ),
		'מספר משרה'   => log_field( 'job_number', $jid ),
	] );
	$path = log_rows( 'careers_path', [
		[ 'name' => 'קורות חיים', 'text' => '' ],
		[ 'name' => 'שיחת היכרות', 'text' => '' ],
		[ 'name' => 'משימת בית וראיון מקצועי', 'text' => '' ],
		[ 'name' => 'ברוכים הבאים', 'text' => '' ],
	], 'option' );
	$others = new WP_Query( [
		'post_type'      => 'log_job',
		'posts_per_page' => -1,
		'post__not_in'   => [ $jid ],
		'no_found_rows'  => true,
		'meta_query'     => [ [ 'key' => 'is_open', 'value' => '1' ] ],
	] );
	?>
	<article class="job" data-job="<?php echo (int) $jid; ?>">
		<header class="page-header job__header">
			<a class="back-link" href="<?php echo esc_url( $jobs_url ); ?>">לכל המשרות</a>
			<h1 class="page-header__title"><?php echo log_route_shield(); ?><?php the_title(); ?></h1>
			<?php if ( $team ) : ?><p class="page-header__meta"><?php echo esc_html( $team ); ?></p><?php endif; ?>
			<ul class="job__tags"><?php foreach ( array_filter( [ $loc, $scope ] ) as $tag ) : ?><li><?php echo esc_html( $tag ); ?></li><?php endforeach; ?></ul>
			<?php echo log_sheet_label( log_field( 'job_number', $jid ) ? 'משרה ' . log_field( 'job_number', $jid ) : '' ); ?>
			<div class="job__actions">
				<?php if ( $open ) : ?><button type="button" class="btn btn--primary" data-apply>הגשת מועמדות</button><?php endif; ?>
				<button type="button" class="btn btn--ghost" data-copy-link>העתקת קישור למשרה</button>
			</div>
		</header>

		<?php if ( ! $open ) : ?>
			<div class="container job__closed">
				<p class="notice">המשרה הזו כבר לא פתוחה.</p>
				<?php if ( $others->have_posts() ) : ?>
					<h2>המשרות הפתוחות</h2>
					<?php get_template_part( 'template-parts/jobs-list', null, [ 'query' => $others ] ); ?>
				<?php else : ?>
					<p><a href="<?php echo esc_url( $jobs_url ); ?>">לעמוד הקריירה</a></p>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="container job__layout">
				<div class="job__body">
					<?php
					$block = static function ( string $heading, string $html ): void {
						if ( trim( wp_strip_all_tags( $html ) ) === '' ) {
							return;
						}
						printf( '<section class="story-block"><h2>%s</h2>%s</section>', esc_html( $heading ), $html ); // $html is escaped by its builder
					};
					$block( 'על התפקיד', wpautop( esc_html( (string) log_field( 'about', $jid, '' ) ) ) );
					$block( 'מה תעשו', log_lines_to_list( log_field( 'what_you_do', $jid ) ) );
					$with = array_filter( [ 'צוות' => $team, 'גודל הצוות' => log_field( 'team_size', $jid ), 'כפיפות' => log_field( 'reports_to', $jid ) ] );
					$with_html = '';
					if ( $with ) {
						$with_html .= '<dl class="facts facts--inline">';
						foreach ( $with as $k => $v ) {
							$with_html .= '<div><dt>' . esc_html( $k ) . '</dt><dd>' . esc_html( $v ) . '</dd></div>';
						}
						$with_html .= '</dl>';
					}
					$with_html .= wpautop( esc_html( (string) log_field( 'work_with', $jid, '' ) ) );
					$block( 'עם מי תעבדו', $with_html );
					$req = log_lines_to_list( log_field( 'requirements', $jid ) );
					$adv = log_lines_to_list( log_field( 'advantage', $jid ) );
					$block( 'מה חשוב לנו', ( $req ? '<h3>דרישות</h3>' . $req : '' ) . ( $adv ? '<h3>יתרונות</h3>' . $adv : '' ) );
					$block( 'מה מחכה לכם', log_lines_to_list( log_field( 'whats_waiting', $jid ) ) );
					?>
					<?php if ( get_the_content() ) : ?><div class="prose"><?php the_content(); ?></div><?php endif; ?>
					<section class="story-block">
						<h2>איך מתקבלים</h2>
						<ol class="process process--compact">
							<?php foreach ( array_slice( $path, 0, 4 ) as $i => $step ) : ?>
								<li><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $step['name'] ?? '' ); ?></h3><?php if ( ! empty( $step['text'] ) ) : ?><p><?php echo esc_html( $step['text'] ); ?></p><?php endif; ?></li>
							<?php endforeach; ?>
						</ol>
					</section>
				</div>

				<aside class="job__side" aria-label="פרטי המשרה">
					<h2 class="job__side-title">פרטי המשרה</h2>
					<dl class="facts">
						<?php foreach ( $details as $k => $v ) : ?><div><dt><?php echo esc_html( $k ); ?></dt><dd><?php echo esc_html( $v ); ?></dd></div><?php endforeach; ?>
					</dl>
					<button type="button" class="btn btn--primary" data-apply>הגשת מועמדות</button>
				</aside>
			</div>

			<?php if ( $others->have_posts() ) : ?>
				<section class="section section--more">
					<div class="container">
						<h2 class="section__title">משרות נוספות</h2>
						<?php get_template_part( 'template-parts/jobs-list', null, [ 'query' => $others ] ); ?>
					</div>
				</section>
			<?php endif; ?>

			<dialog class="apply-dialog" id="apply" aria-labelledby="apply-title">
				<div class="apply-dialog__inner">
					<button type="button" class="apply-dialog__close" aria-label="סגירת החלון"><i class="icon icon--close" aria-hidden="true"></i></button>
					<h2 class="apply-dialog__title" id="apply-title">הגשת מועמדות: <?php the_title(); ?></h2>
					<?php get_template_part( 'template-parts/form-apply', null, [ 'job' => $jid ] ); ?>
				</div>
			</dialog>
		<?php endif; ?>
	</article>
	<?php
endwhile;
get_footer();
