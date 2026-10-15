<?php
/**
 * Home (spec §6). Section order is final; each section is a placeholder until design.
 * The hero map mounts into #hero-map and is loaded lazily by main.js — never on other pages.
 */
get_header();
$stats = log_site_stats();
?>
<section class="hero" id="hero" data-section="map">
	<div class="hero__map" id="hero-map" aria-hidden="true"></div>
	<div class="hero__copy">
		<h1 class="hero__title">מובילים את התכנון וההתחדשות העירונית בישראל</h1>
		<p class="hero__lede">תכנון עירוני בכל קנה מידה: מתכניות מפורטות ועד תכניות מתאר, כוללניות ואסטרטגיות.</p>
		<dl class="hero__stats">
			<div><dt>יח"ד</dt><dd data-count="<?php echo (int) $stats['units']; ?>"><?php echo log_number( $stats['units'] ); ?></dd></div>
			<div><dt>פרויקטים פעילים</dt><dd data-count="<?php echo (int) $stats['projects']; ?>"><?php echo log_number( $stats['projects'] ); ?></dd></div>
			<div><dt>רשויות</dt><dd data-count="<?php echo (int) $stats['authorities']; ?>"><?php echo log_number( $stats['authorities'] ); ?></dd></div>
		</dl>
		<a class="btn btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">כל הפרויקטים</a>
	</div>
</section>

<section class="section section--who" data-section="who">
	<div class="container">
		<h2 class="section__title">מתכננים. מחברים. מובילים.</h2>
		<p class="section__lede">לוג מתכננת, מלווה ומבקרת את התכנון העירוני בישראל, מהשכונה ועד המדינה כולה.</p>
	</div>
</section>

<section class="section section--control" data-section="control">
	<div class="container">
		<p class="eyebrow">זרוע הבקרה הלאומית</p>
		<h2 class="section__title">מעקב וניתוח ארצי · התרת חסמים · חיבור בין השטח לממשל</h2>
		<a class="btn" href="<?php echo esc_url( home_url( '/control/' ) ); ?>">לעמוד חברת הבקרה</a>
	</div>
</section>

<section class="section section--process" data-section="process">
	<div class="container">
		<h2 class="section__title">איך אנחנו עובדים</h2>
		<ol class="process">
			<li><?php echo log_stage_ring( 1, false ); ?><h3>איתור והיתכנות</h3></li>
			<li><?php echo log_stage_ring( 2, false ); ?><h3>תכנון וקידום סטטוטורי</h3></li>
			<li><?php echo log_stage_ring( 3, false ); ?><h3>ליווי ואישור</h3></li>
			<li><?php echo log_stage_ring( 4, false ); ?><h3>בקרה ומעקב</h3></li>
		</ol>
	</div>
</section>

<section class="section section--featured" data-section="featured">
	<div class="container">
		<h2 class="section__title">פרויקטים נבחרים</h2>
		<?php
		$featured = log_approved_projects_query( [
			'posts_per_page' => 6,
			'meta_query'     => [
				[ 'key' => 'approved', 'value' => '1' ],
				[ 'key' => 'featured', 'value' => '1' ],
			],
		] );
		if ( $featured->have_posts() ) : ?>
			<div class="mosaic">
				<?php while ( $featured->have_posts() ) : $featured->the_post();
					get_template_part( 'template-parts/card-project' );
				endwhile; wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<p class="placeholder">כאן תופיע מוזאיקה של עד 6 פרויקטים מסומנים כ"נבחרים".</p>
		<?php endif; ?>
	</div>
</section>

<section class="section section--numbers" data-section="numbers">
	<div class="container">
		<h2 class="section__title">במספרים</h2>
		<dl class="numbers">
			<div><dd><?php echo log_number( $stats['units'] ); ?></dd><dt>יח"ד בתכנון ובבקרה</dt></div>
			<div><dd><?php echo log_number( $stats['authorities'] ); ?></dd><dt>רשויות</dt></div>
			<div><dd><?php echo log_number( $stats['projects'] ); ?></dd><dt>פרויקטים פעילים</dt></div>
			<div><dd><?php echo log_number( $stats['team'] ); ?></dd><dt>אנשי צוות</dt></div>
		</dl>
	</div>
</section>

<section class="section section--clients" data-section="clients">
	<div class="container">
		<h2 class="section__title">לקוחות ושותפים</h2>
		<p class="placeholder">לוגואים בגוון אחד, מסוננים לפי סוג גוף. מוצגים רק לקוחות עם אישור שימוש.</p>
	</div>
</section>

<section class="section section--news" data-section="news">
	<div class="container">
		<h2 class="section__title">מה חדש</h2>
		<p class="placeholder">שמונה משבצות: מרכז ידע, אינסטגרם ולינקדאין, לפי תאריך.</p>
	</div>
</section>

<section class="section section--careers" data-section="careers">
	<div class="container">
		<h2 class="section__title">מאחורי כל קו אופק, יש תכנית שלמה.</h2>
		<?php $open = wp_count_posts( 'log_job' )->publish ?? 0; ?>
		<a class="btn btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) ); ?>">למשרות הפתוחות<?php if ( $open ) : ?> (<?php echo (int) $open; ?>)<?php endif; ?></a>
	</div>
</section>
<?php get_footer();
