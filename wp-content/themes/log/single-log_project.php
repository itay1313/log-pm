<?php
/**
 * Project page (spec v6 §10). One template; blocks without content don't render, headings included.
 * Who gets here is decided in log-core (projects.php): only public projects with a full page.
 */
get_header();
while ( have_posts() ) : the_post();
	$pid   = get_the_ID();
	$stage = log_project_stage( $pid );
	$story = [
		'האתגר'                         => log_field( 'challenge', $pid ),
		'העבודה של לוג והניהול המקצועי' => log_field( 'process', $pid ),
		'ההתקדמות והתוצאות'             => log_field( 'solution', $pid ),
		'השלב הבא'                      => $stage['completed'] ? null : log_field( 'next_step', $pid ),
	];
	$years   = log_project_years( $pid );
	$numbers = array_filter( [
		'יחידות דיור'  => log_field( 'units', $pid ),
		'מ"ר'          => log_field( 'sqm_employment', $pid ),
		'דונם'         => log_field( 'dunam', $pid ),
		'שנים בתהליך'  => $years,
	] );
	$auth  = log_first_term( $pid, 'log_authority' );
	$facts = array_filter( [
		'לקוח'        => ( $c = log_field( 'client', $pid ) ) ? get_the_title( $c ) : null,
		'רשות'        => $auth?->name,
		'מיקום'       => log_field( 'is_national', $pid ) ? 'פריסה ארצית' : log_field( 'city', $pid ),
		'יח"ד'        => log_number( log_field( 'units', $pid ) ),
		'מ"ר'         => log_number( log_field( 'sqm_employment', $pid ) ),
		'שטח'         => log_number( log_field( 'dunam', $pid ), 'דונם' ),
		'מספר תכנית'  => log_field( 'plan_number', $pid ),
	] );
	?>
	<article class="project" data-project="<?php echo esc_attr( rawurldecode( get_post_field( 'post_name', $pid ) ) ); ?>">
		<header class="project__hero">
			<?php if ( $video = log_field( 'video', $pid ) ) : ?>
				<video class="project__media" src="<?php echo esc_url( $video['url'] ); ?>" muted loop playsinline preload="metadata" poster="<?php echo esc_url( get_the_post_thumbnail_url( $pid, 'log-wide' ) ); ?>" aria-hidden="true"></video>
				<button type="button" class="project__pause" aria-pressed="false">עצירת הסרטון</button>
			<?php elseif ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'log-wide', [ 'class' => 'project__media', 'fetchpriority' => 'high', 'sizes' => '100vw' ] ); ?>
			<?php else : // no photo yet: main.js draws the same building illustration the hero card shows ?>
				<div class="project__media project__art" data-id="<?php echo (int) $pid; ?>" data-domain="<?php echo esc_attr( (string) log_first_term_slug( $pid, 'log_domain' ) ); ?>" data-stage="<?php echo (int) ( $stage['completed'] ? 4 : $stage['number'] ); ?>" aria-hidden="true"></div>
			<?php endif; ?>
			<div class="project__hero-copy">
				<a class="back-link back-link--light" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>" data-back data-back-home="חזרה למפה">חזרה לפרויקטים</a>
				<h1 class="project__title"><?php the_title(); ?></h1>
				<p class="project__meta"><?php echo log_project_meta_line( $pid ); ?></p>
				<?php echo log_stage_row( $stage, 'project__stage' ); ?>
				<?php if ( $grid = log_project_grid_ref( $pid ) ) : ?>
					<p class="project__grid-ref">רשת ישראל<b><?php echo esc_html( $grid ); ?></b></p>
				<?php elseif ( log_field( 'is_national', $pid ) ) : ?>
					<p class="project__grid-ref">פריסה<b>ארצית</b></p>
				<?php endif; ?>
			</div>
		</header>

		<?php if ( $numbers ) : ?>
			<div class="container">
				<dl class="project__numbers">
					<?php foreach ( $numbers as $label => $n ) : ?>
						<div><dd><?php echo log_number( $n ); ?></dd><dt><?php echo esc_html( $label ); ?></dt></div>
					<?php endforeach; ?>
				</dl>
			</div>
		<?php endif; ?>

		<?php
		// A short story (under ~120 words, no milestones or gallery) would leave a column of air beside the
		// details: the sheet then sets the details as a wide legend under the story, with the minimap beside it.
		$story_words = str_word_count( wp_strip_all_tags( implode( ' ', array_filter( $story ) ) . ' ' . get_the_content() ), 0, 'אבגדהוזחטיכךלמםנןסעפףצץקרשת' );
		$short       = $story_words < 120 && ! log_field( 'milestones', $pid ) && ! log_field( 'gallery', $pid ) && ! log_field( 'quote', $pid );
		?>
		<div class="container project__layout<?php echo $short ? ' project__layout--short' : ''; ?>">
			<div class="project__story">
				<?php if ( $quote = log_field( 'quote', $pid ) ) : ?>
					<blockquote class="pull-quote"><p><?php echo esc_html( $quote ); ?></p>
						<?php if ( $by = log_field( 'quote_by', $pid ) ) : ?><cite><?php echo esc_html( $by ); ?></cite><?php endif; ?>
					</blockquote>
				<?php endif; ?>

				<?php foreach ( $story as $heading => $text ) : if ( ! $text ) { continue; } ?>
					<section class="story-block">
						<h2><?php echo esc_html( $heading ); ?></h2>
						<?php echo wpautop( esc_html( $text ) ); ?>
					</section>
				<?php endforeach; ?>

				<?php if ( get_the_content() ) : ?><div class="prose"><?php the_content(); ?></div><?php endif; ?>

				<?php if ( $ms = log_field( 'milestones', $pid ) ) : ?>
					<section class="milestones">
						<h2>אבני דרך</h2>
						<ol class="timeline">
							<?php foreach ( $ms as $m ) : ?>
								<li class="<?php echo ! empty( $m['pending'] ) ? 'is-pending' : ''; ?>"><span class="timeline__year"><?php echo esc_html( $m['year'] ); ?></span> <?php echo esc_html( $m['event'] ); ?><?php if ( ! empty( $m['pending'] ) ) : ?> <span class="timeline__later">בהמשך</span><?php endif; ?></li>
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
				<?php if ( log_field( 'is_national', $pid ) ) : ?>
					<p class="minimap minimap--national">פריסה ארצית</p>
				<?php elseif ( log_field( 'lat', $pid ) ) : ?>
					<a class="minimap" data-project="<?php echo $pid; ?>" href="<?php echo esc_url( log_project_map_url( $pid ) ); ?>" aria-label="מיקום הפרויקט על המפה. לחיצה פותחת את המפה הגדולה."><span class="minimap__cta">לראות במפה הגדולה</span></a>
				<?php endif; ?>
				<button type="button" class="btn btn--ghost" data-copy-link>העתקת קישור לפרויקט</button>
			</aside>
		</div>

		<?php
		// Up to three projects from the same domain that have a page of their own.
		$domain  = log_first_term_slug( $pid, 'log_domain' );
		$related = $domain ? log_approved_projects_query( [
			'post__not_in' => [ $pid ],
			'meta_query'   => [ [ 'key' => 'approved', 'value' => '1' ], [ 'key' => 'full_page', 'value' => '1' ] ],
			'tax_query'    => [ [ 'taxonomy' => 'log_domain', 'field' => 'slug', 'terms' => $domain ] ],
		] ) : null;
		if ( $related ) {
			$related->posts      = array_slice( array_values( array_filter( $related->posts, static fn( $p ) => log_project_has_page( $p->ID ) ) ), 0, 3 );
			$related->post_count = count( $related->posts );
		}
		if ( $related && $related->have_posts() ) : ?>
			<section class="section section--more">
				<div class="container">
					<h2 class="section__title">פרויקטים נוספים</h2>
					<div class="grid grid--projects">
						<?php while ( $related->have_posts() ) : $related->the_post(); get_template_part( 'template-parts/card-project' ); endwhile; wp_reset_postdata(); ?>
					</div>
					<a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">לכל הפרויקטים</a>
				</div>
			</section>
		<?php endif; ?>

		<section class="section section--cta">
			<div class="container">
				<h2 class="section__title">רוצים פרויקט דומה?</h2>
				<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/?topic=business&project=' . get_post_field( 'post_name', $pid ) ) ); ?>">בואו נדבר</a>
			</div>
		</section>
	</article>
	<?php
endwhile;
get_footer();
