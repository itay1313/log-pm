<?php
/**
 * Home (spec v6 §7). Blocks in order: projects map, who we are, practice areas, how we work,
 * the control company, clients and partners. The twin buttons hang in the header (bottom bar on phones).
 * Every text is an ACF field with fallback copy until LOG's texts file arrives.
 * The 3D map mounts into .hero-stage and is loaded lazily by main.js, never on other pages.
 */
get_header();
$stats   = log_site_stats();
$process = log_rows( 'process', [
	[ 'name' => 'איתור והיתכנות', 'text' => 'איפה יש פוטנציאל, ומה צריך כדי לממש אותו.' ],
	[ 'name' => 'תכנון וקידום סטטוטורי', 'text' => 'מהרעיון ועד תכנית שאפשר להפקיד.' ],
	[ 'name' => 'ליווי ואישור', 'text' => 'מוסדות תכנון, התנגדויות, מתן תוקף.' ],
	[ 'name' => 'בקרה ומעקב', 'text' => 'מה קורה בשטח אחרי האישור, ומה צריך לתקן.' ],
] );
$projects_url = get_post_type_archive_link( 'log_project' );
?>
<section class="hero" id="hero" data-section="map" aria-labelledby="hero-title">
	<div class="hero-stage" role="region" aria-label="מפת הפרויקטים של לוג בתלת-ממד. גרירה מזיזה, Ctrl וגלילה מקרבים. כל סימן הוא כפתור עם שם הפרויקט; Enter פותח חלונית ו-Esc סוגר."></div>
	<div class="hero-labels" aria-hidden="true"></div>
	<div class="hero-pins"></div>
	<div class="hero-edges"></div>
	<div class="hero-card" role="dialog" aria-live="polite" hidden></div>
	<p class="hero__loading" role="status">טוענים את המפה…</p>

	<div class="hero-lead">
		<h1 class="hero__title" id="hero-title"><?php
			$title = log_text( 'hero_title', 'מובילים את התכנון וההתחדשות העירונית בישראל' );
			// The last word is the destination: it prints in works orange.
			echo preg_match( '/^(.+)\s(\S+)$/u', $title, $m ) ? esc_html( $m[1] ) . ' <em>' . esc_html( $m[2] ) . '</em>' : esc_html( $title );
		?></h1>
		<p class="hero__lede"><?php echo esc_html( log_text( 'hero_lede', 'תכנון עירוני בכל קנה מידה: מתכניות מפורטות ועד תכניות מתאר, כוללניות ואסטרטגיות.' ) ); ?></p>

		<form class="hero-tools" role="search" aria-label="חיפוש וסינון במפה" action="<?php echo esc_url( $projects_url ); ?>">
			<div class="hero-search">
				<label class="hero-search__label" for="hero-q">לאן?</label>
				<input type="search" id="hero-q" name="q" placeholder="פרויקט, עיר או רשות" autocomplete="off" role="combobox" aria-expanded="false" aria-controls="hero-suggest" aria-autocomplete="list">
				<button type="submit" class="hero-search__go" aria-label="חיפוש במפה"><i class="arrow" aria-hidden="true"></i></button>
				<ul class="hero-suggest" id="hero-suggest" role="listbox" hidden></ul>
			</div>
			<button type="button" class="hero-filter-toggle plate" aria-expanded="false" aria-controls="hero-filters">סינון<span class="hero-filter-toggle__more"> לפי תחום, שלב ורשות</span></button>
			<div class="hero-filters" id="hero-filters">
				<?php get_template_part( 'template-parts/filter-selects' ); ?>
				<button type="button" class="hero-filters__done">הצגת התוצאות</button>
			</div>
			<div class="hero-dist">
				<dl class="hero__stats" aria-live="polite">
					<?php foreach ( [ 'projects' => 'פרויקטים', 'units' => 'יחידות דיור', 'sqm' => 'סך מ"ר' ] as $key => $label ) : ?>
						<div data-stat="<?php echo esc_attr( $key ); ?>"<?php echo $stats[ $key ] ? '' : ' hidden'; ?>><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo log_number( $stats[ $key ] ); ?></dd></div>
					<?php endforeach; ?>
				</dl>
				<p class="hero-empty" hidden>לא נמצאו פרויקטים. <button type="button" class="hero-clear">ניקוי סינון</button></p>
				<a class="hero-all-link" href="<?php echo esc_url( $projects_url ); ?>">לכל הפרויקטים<i class="arrow" aria-hidden="true"></i></a>
			</div>
		</form>
	</div>

	<div class="hero-bar">
		<button type="button" class="hero-list-btn" data-list="national" aria-expanded="false" aria-controls="hero-drawer">פריסה ארצית <span></span></button>
		<button type="button" class="hero-list-btn" data-list="all" aria-expanded="false" aria-controls="hero-drawer">תצוגת רשימה <span></span></button>
		<span class="hero-scale" aria-hidden="true"><i></i><span></span></span>
		<button type="button" class="hero-compass" aria-label="כל הארץ: חזרה לתצוגת הפתיחה"><svg viewBox="0 0 32 32" aria-hidden="true"><circle cx="16" cy="16" r="13"/><path d="M16 5 L19.5 16 L16 14.5 L12.5 16 Z"/><path d="M16 27 L12.5 16 L16 17.5 L19.5 16 Z" class="s"/></svg></button>
		<div class="hero-zoom" role="group" aria-label="זום">
			<button type="button" class="hero-zoom__in" aria-label="התקרבות"><i class="icon icon--plus" aria-hidden="true"></i></button>
			<button type="button" class="hero-zoom__out" aria-label="התרחקות"><i class="icon icon--minus" aria-hidden="true"></i></button>
		</div>
	</div>
	<div class="hero-drawer" id="hero-drawer" hidden></div>
	<p class="hero__hint" aria-hidden="true">כדי להתקרב: Ctrl וגלילה</p>
</section>

<section class="section section--who" data-section="who">
	<div class="container">
		<div class="who__text">
			<h2 class="section__title"><?php echo esc_html( log_text( 'who_title', 'מתכננים. מחברים. מובילים.' ) ); ?></h2>
			<div class="section__prose">
				<?php echo wpautop( esc_html( log_text( 'who_text', "לוג מנהלת ומקדמת תהליכי תכנון עירוני בכל קנה מידה, מהמתחם ועד תכניות ארציות.\n\nאנחנו עובדים עם רשויות, משרדי ממשלה ויזמים, ומחברים בין התכנון, מוסדות התכנון והשטח.\n\nלצד ניהול הפרויקטים, לוג משמשת חברת הבקרה של הרשות הממשלתית להתחדשות עירונית." ) ) ); ?>
			</div>
			<ul class="link-row">
				<li><a href="<?php echo esc_url( home_url( '/about/#expertise' ) ); ?>">המומחיות שלנו</a></li>
				<li><a href="<?php echo esc_url( home_url( '/about/' ) ); ?>">אודות</a></li>
				<li><a href="<?php echo esc_url( $projects_url ); ?>">הפרויקטים</a></li>
			</ul>
		</div>
		<figure class="sheet-index">
			<a href="<?php echo esc_url( add_query_arg( 'view', 'map', $projects_url ) ); ?>" class="sheet-index__link"><?php echo log_sheet_index_svg(); ?></a>
			<figcaption>מפתח גיליונות · כל פרויקט של לוג כנקודה על רשת ישראל</figcaption>
		</figure>
	</div>
</section>

<?php
// Practice areas: one card per domain with published projects, counted live.
$domain_counts = [];
foreach ( log_public_projects() as $p ) {
	foreach ( wp_get_post_terms( $p->ID, 'log_domain', [ 'fields' => 'slugs' ] ) as $slug ) {
		$domain_counts[ $slug ] = ( $domain_counts[ $slug ] ?? 0 ) + 1;
	}
}
$domains = array_filter( log_ordered_terms( 'log_domain' ), static fn( $t ) => ! empty( $domain_counts[ $t->slug ] ) );
if ( $domains ) : ?>
	<section class="section section--domains" data-section="domains">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'domains_title', 'תחומי פעילות' ) ); ?></h2>
			<ul class="domains">
				<?php foreach ( $domains as $d ) : ?>
					<li class="domain">
						<a class="domain__link" href="<?php echo esc_url( add_query_arg( 'domain', $d->slug, $projects_url ) ); ?>">
							<h3 class="domain__name"><?php echo esc_html( $d->name ); ?></h3>
							<?php if ( $line = log_field( 'sentence', 'term_' . $d->term_id ) ) : ?><p class="domain__line"><?php echo esc_html( $line ); ?></p><?php endif; ?>
							<p class="domain__count"><?php echo $domain_counts[ $d->slug ] === 1 ? 'פרויקט אחד' : esc_html( sprintf( '%s פרויקטים', number_format_i18n( $domain_counts[ $d->slug ] ) ) ); ?></p>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<section class="section section--process" data-section="process">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'process_title', 'איך אנחנו עובדים' ) ); ?></h2>
		<ol class="process">
			<?php foreach ( array_slice( $process, 0, 4 ) as $i => $step ) : ?>
				<li><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $step['name'] ); ?></h3><?php if ( ! empty( $step['text'] ) ) : ?><p><?php echo esc_html( $step['text'] ); ?></p><?php endif; ?></li>
			<?php endforeach; ?>
		</ol>
		<p class="section__lede"><?php echo esc_html( log_text( 'process_tagline', 'נתונים, GIS, כלי עבודה ושיתוף בין אנשי מקצוע הם חלק מהעבודה, לא תוספת לה.' ) ); ?></p>
	</div>
</section>

<section class="section section--control" data-section="control">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'control_title', 'חברת הבקרה' ) ); ?></h2>
		<p class="section__lede"><?php echo esc_html( log_text( 'control_text', 'לוג משמשת חברת הבקרה של הרשות הממשלתית להתחדשות עירונית: מעקב וניתוח ארצי, התרת חסמים, וחיבור בין השטח לממשל.' ) ); ?></p>
		<a class="btn" href="<?php echo esc_url( home_url( '/control/' ) ); ?>">לעמוד חברת הבקרה</a>
	</div>
</section>

<?php if ( log_has_clients() || log_show_empty() ) : ?>
<section class="section section--clients" data-section="clients">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'clients_title', 'לקוחות ושותפים' ) ); ?></h2>
		<?php get_template_part( 'template-parts/clients' ); ?>
	</div>
</section>
<?php endif; ?>
<?php get_footer();
