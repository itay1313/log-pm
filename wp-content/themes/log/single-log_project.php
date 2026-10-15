<?php
/**
 * Project page (spec §9). One template; blocks without content don't render.
 * Unapproved projects 404 for the public.
 */
if ( ! log_field( 'approved', get_the_ID() ) && ! current_user_can( 'edit_posts' ) ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( '404' );
	exit;
}
get_header();
while ( have_posts() ) : the_post();
	$pid    = get_the_ID();
	$stage = (int) log_field( 'stage', $pid, 1 );
	$story = [
		'האתגר'    => log_field( 'challenge', $pid ),
		'התהליך'   => log_field( 'process', $pid ),
		'הפתרון'   => log_field( 'solution', $pid ),
		'הצעד הבא' => $stage < 4 ? log_field( 'next_step', $pid ) : null,
	];
	$facts = array_filter( [
		'לקוח'          => ( $c = log_field( 'client', $pid ) ) ? get_the_title( $c ) : null,
		'מיקום'         => log_field( 'city', $pid ),
		'רשות'          => log_field( 'authority', $pid ),
		'מחוז'          => log_field( 'district', $pid ),
		'סוג תכנית'     => log_field( 'plan_type', $pid ),
		'מוסד תכנון'    => log_field( 'institution', $pid ),
		'יח"ד'          => log_number( log_field( 'units', $pid ) ),
		'מ"ר תעסוקה'    => log_number( log_field( 'sqm_employment', $pid ) ),
		'דונם'          => log_number( log_field( 'dunam', $pid ) ),
		'שנת תכנון'     => log_field( 'year', $pid ),
		'מספר תכנית'    => log_field( 'plan_number', $pid ),
	] );
	?>
	<article class="project">
		<header class="project__hero">
			<?php if ( $video = log_field( 'video', $pid ) ) : ?>
				<video class="project__media" src="<?php echo esc_url( $video['url'] ); ?>" muted loop playsinline preload="metadata" poster="<?php echo esc_url( get_the_post_thumbnail_url( $pid, 'log-wide' ) ); ?>" aria-hidden="true"></video>
			<?php elseif ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'log-wide', [ 'class' => 'project__media', 'fetchpriority' => 'high' ] ); ?>
			<?php endif; ?>
			<div class="project__hero-copy">
				<?php echo log_stage_ring( $stage ); ?>
				<p class="eyebrow"><?php echo log_project_meta_line( $pid ); ?></p>
				<h1 class="project__title"><?php the_title(); ?></h1>
			</div>
		</header>

		<div class="container project__layout">
			<div class="project__story">
				<?php foreach ( $story as $heading => $text ) : if ( ! $text ) { continue; } ?>
					<section class="story-block">
						<h2><?php echo esc_html( $heading ); ?></h2>
						<?php echo wpautop( esc_html( $text ) ); ?>
					</section>
				<?php endforeach; ?>

				<?php if ( $quote = log_field( 'quote', $pid ) ) : ?>
					<blockquote class="pull-quote"><p><?php echo esc_html( $quote ); ?></p>
						<?php if ( $by = log_field( 'quote_by', $pid ) ) : ?><cite><?php echo esc_html( $by ); ?></cite><?php endif; ?>
					</blockquote>
				<?php endif; ?>

				<?php if ( get_the_content() ) : ?><div class="prose"><?php the_content(); ?></div><?php endif; ?>

				<?php if ( $ms = log_field( 'milestones', $pid ) ) : ?>
					<section class="milestones">
						<h2>אבני דרך</h2>
						<ol class="timeline">
							<?php foreach ( $ms as $m ) : ?>
								<li class="<?php echo ! empty( $m['pending'] ) ? 'is-pending' : ''; ?>"><span class="timeline__year"><?php echo esc_html( $m['year'] ); ?></span> <?php echo esc_html( $m['event'] ); ?></li>
							<?php endforeach; ?>
						</ol>
					</section>
				<?php endif; ?>

				<?php if ( $gallery = log_field( 'gallery', $pid ) ) : ?>
					<section class="gallery">
						<h2>תמונות ותרשימים</h2>
						<div class="gallery__grid">
							<?php foreach ( $gallery as $img ) : ?>
								<figure><img src="<?php echo esc_url( $img['sizes']['log-card'] ?? $img['url'] ); ?>" data-full="<?php echo esc_url( $img['sizes']['log-wide'] ?? $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy"><?php if ( $img['caption'] ) : ?><figcaption><?php echo esc_html( $img['caption'] ); ?></figcaption><?php endif; ?></figure>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endif; ?>
			</div>

			<aside class="project__facts">
				<dl class="facts">
					<?php foreach ( $facts as $k => $v ) : ?>
						<div><dt><?php echo esc_html( $k ); ?></dt><dd><?php
							if ( $k === 'מספר תכנית' && ( $url = log_field( 'plan_url', $pid ) ) ) {
								printf( '<a href="%s" rel="noopener" target="_blank">%s</a>', esc_url( $url ), esc_html( $v ) );
							} else {
								echo esc_html( $v );
							} ?></dd></div>
					<?php endforeach; ?>
				</dl>
				<?php if ( $partners = log_field( 'partners', $pid ) ) : ?>
					<h3>שותפים</h3>
					<ul class="partners"><?php foreach ( $partners as $p ) : ?><li><?php echo esc_html( $p['name'] ); ?><?php if ( $p['role'] ) : ?> <span>· <?php echo esc_html( $p['role'] ); ?></span><?php endif; ?></li><?php endforeach; ?></ul>
				<?php endif; ?>
				<?php if ( log_field( 'lat', $pid ) && ! log_field( 'is_national', $pid ) ) : ?>
					<a class="minimap" data-project="<?php echo $pid; ?>" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) . '#view=map&p=' . $pid ); ?>" aria-label="מיקום הפרויקט על המפה. לחיצה פותחת את המפה הגדולה."><span class="minimap__cta">לראות במפה הגדולה</span></a>
				<?php endif; ?>
				<?php if ( log_field( 'is_control', $pid ) ) : ?>
					<a class="badge badge--control" href="<?php echo esc_url( home_url( '/control/' ) ); ?>">פרויקט בליווי חברת הבקרה</a>
				<?php endif; ?>
				<button type="button" class="btn btn--ghost" data-copy-link>העתקת קישור לפרויקט</button>
			</aside>
		</div>

		<?php
		$domain = log_first_term_slug( $pid, 'log_domain' );
		$related   = $domain ? log_approved_projects_query( [
			'posts_per_page' => 3,
			'post__not_in'   => [ $pid ],
			'tax_query'      => [ [ 'taxonomy' => 'log_domain', 'field' => 'slug', 'terms' => $domain ] ],
		] ) : null;
		if ( $related && $related->have_posts() ) : ?>
			<section class="section section--more">
				<div class="container">
					<h2 class="section__title">פרויקטים נוספים</h2>
					<div class="grid grid--projects">
						<?php while ( $related->have_posts() ) : $related->the_post(); get_template_part( 'template-parts/card-project' ); endwhile; wp_reset_postdata(); ?>
					</div>
				</div>
			</section>
		<?php endif; ?>

		<section class="section section--cta">
			<div class="container">
				<h2 class="section__title">רוצים פרויקט דומה?</h2>
				<a class="btn btn--primary" href="<?php echo esc_url( add_query_arg( 'project', $pid, home_url( '/contact/' ) ) ); ?>">בואו נדבר</a>
			</div>
		</section>
	</article>
	<?php
endwhile;
get_footer();
