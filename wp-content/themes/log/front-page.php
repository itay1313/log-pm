<?php
/**
 * Home (spec §6). Section order is final; every text is an ACF field with the spec copy as fallback.
 * The hero map mounts into #hero-map and is loaded lazily by main.js — never on other pages.
 */
get_header();
$stats = log_site_stats();
$process = log_rows( 'process', [
	[ 'name' => 'איתור והיתכנות', 'text' => 'איפה יש פוטנציאל, ומה צריך כדי לממש אותו.' ],
	[ 'name' => 'תכנון וקידום סטטוטורי', 'text' => 'מהרעיון ועד תכנית שאפשר להפקיד.' ],
	[ 'name' => 'ליווי ואישור', 'text' => 'מוסדות תכנון, התנגדויות, מתן תוקף.' ],
	[ 'name' => 'בקרה ומעקב', 'text' => 'דשבורדים בזמן אמת, GIS, SQL ומדדי נתונים.' ],
] );
?>
<section class="hero" id="hero" data-section="map">
	<div class="hero-stage" role="region" aria-label="מפת הפרויקטים של לוג בתלת-ממד. לחיצה על המפה מאפשרת להתקרב בגלילה ולשוטט; Esc סוגר כרטיס פתוח."></div>
	<div class="hero-labels" aria-hidden="true"></div>
	<div class="hero-pins"></div>
	<div class="hero-edges"></div>
	<div class="hero-card" role="dialog" aria-live="polite" hidden></div>

	<div class="hero__copy">
		<h1 class="hero__title"><?php echo esc_html( log_text( 'hero_title', 'מובילים את התכנון וההתחדשות העירונית בישראל' ) ); ?></h1>
		<p class="hero__lede"><?php echo esc_html( log_text( 'hero_lede', 'תכנון עירוני בכל קנה מידה: מתכניות מפורטות ועד תכניות מתאר, כוללניות ואסטרטגיות.' ) ); ?></p>
		<dl class="hero__stats">
			<div><dt>יח"ד</dt><dd data-count="<?php echo (int) $stats['units']; ?>"><?php echo log_number( $stats['units'] ); ?></dd></div>
			<div><dt>פרויקטים פעילים</dt><dd data-count="<?php echo (int) $stats['projects']; ?>"><?php echo log_number( $stats['projects'] ); ?></dd></div>
			<div><dt>רשויות</dt><dd data-count="<?php echo (int) $stats['authorities']; ?>"><?php echo log_number( $stats['authorities'] ); ?></dd></div>
		</dl>
		<a class="hero__fallback btn btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>"><?php echo esc_html( log_text( 'hero_cta', 'כל הפרויקטים' ) ); ?></a>
	</div>

	<div class="hero-bar">
		<button type="button" class="hero-compass" aria-label="חזרה למבט על כל הארץ"><svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="13"/><path d="M16 5 L19.5 16 L16 14.5 L12.5 16 Z"/><path d="M16 27 L12.5 16 L16 17.5 L19.5 16 Z" class="s"/></svg></button>
		<span class="hero-scale" aria-hidden="true"><i></i><span></span></span>
		<button type="button" class="hero-all" aria-expanded="false" aria-controls="hero-drawer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6l6-2 6 2 6-2v14l-6 2-6-2-6 2z"/><path d="M9 4v14M15 6v14"/></svg> כל הפרויקטים <span></span></button>
	</div>
	<div class="hero-drawer" id="hero-drawer" hidden></div>
	<p class="hero__hint">לחצו על המפה כדי לשוטט ולהתקרב</p>
</section>

<section class="section section--who" data-section="who">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'who_title', 'מתכננים. מחברים. מובילים.' ) ); ?></h2>
		<p class="section__lede"><?php echo esc_html( log_text( 'who_text', 'לוג מתכננת, מלווה ומבקרת את התכנון העירוני בישראל, מהשכונה ועד המדינה כולה.' ) ); ?></p>
	</div>
</section>

<section class="section section--control" data-section="control">
	<div class="container">
		<p class="eyebrow"><?php echo esc_html( log_text( 'control_eyebrow', 'זרוע הבקרה הלאומית' ) ); ?></p>
		<h2 class="section__title"><?php echo esc_html( log_text( 'control_title', 'מעקב וניתוח ארצי · התרת חסמים · חיבור בין השטח לממשל' ) ); ?></h2>
		<a class="btn" href="<?php echo esc_url( home_url( '/control/' ) ); ?>">לעמוד חברת הבקרה</a>
	</div>
</section>

<section class="section section--process" data-section="process">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'process_title', 'איך אנחנו עובדים' ) ); ?></h2>
		<ol class="process">
			<?php foreach ( array_slice( $process, 0, 4 ) as $i => $step ) : ?>
				<li><?php echo log_stage_ring( $i + 1, false ); ?><h3><?php echo esc_html( $step['name'] ); ?></h3><?php if ( ! empty( $step['text'] ) ) : ?><p><?php echo esc_html( $step['text'] ); ?></p><?php endif; ?></li>
			<?php endforeach; ?>
		</ol>
		<p class="section__lede"><?php echo esc_html( log_text( 'process_tagline', 'תכנון מדויק מתחיל בנתונים חכמים.' ) ); ?></p>
	</div>
</section>

<section class="section section--featured" data-section="featured">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'featured_title', 'פרויקטים נבחרים' ) ); ?></h2>
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
		<h2 class="section__title"><?php echo esc_html( log_text( 'numbers_title', 'במספרים' ) ); ?></h2>
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
		<h2 class="section__title"><?php echo esc_html( log_text( 'clients_title', 'לקוחות ושותפים' ) ); ?></h2>
		<?php get_template_part( 'template-parts/clients' ); ?>
	</div>
</section>

<section class="section section--news" data-section="news">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'news_title', 'מה חדש' ) ); ?></h2>
		<?php get_template_part( 'template-parts/news-grid' ); ?>
	</div>
</section>

<section class="section section--careers" data-section="careers">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_title', 'מאחורי כל קו אופק, יש תכנית שלמה.' ) ); ?></h2>
		<?php $open = wp_count_posts( 'log_job' )->publish ?? 0; ?>
		<a class="btn btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) ); ?>"><?php echo esc_html( log_text( 'careers_cta', 'למשרות הפתוחות' ) ); ?><?php if ( $open ) : ?> (<?php echo (int) $open; ?>)<?php endif; ?></a>
	</div>
</section>
<?php get_footer();
