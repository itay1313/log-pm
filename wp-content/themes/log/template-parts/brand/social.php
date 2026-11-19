<?php
/**
 * Chapters 12 and 13: the same language on social networks, then the slides to start from. Every example is a
 * live miniature (log_bk_mini), drawn with the real lines, type and photographs.
 */

$highlights = [ [ 'logo', 'הלוגו' ], [ 'jobs', 'משרות' ], [ 'person-5.jpg', 'האנשים שלנו' ], [ 'projects', 'פרויקטים' ], [ 'team.jpg', 'מאחורי הקלעים' ], [ 'dogs', 'כלבי המשרד' ] ];
// Thin white glyphs for the highlight covers: one touch of orange each, nothing filled.
$glyph = [
	'jobs'     => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M14 8h14l8 8v24H14z"/><path d="M28 8v8h8"/><path class="is-works" d="M19 24h12M19 30h12M19 36h7"/></svg>',
	'projects' => '<svg viewBox="0 0 48 48" aria-hidden="true"><path d="M6 40h36M12 40V20h8v20M24 40V10h10v30"/><path class="is-works" d="M28 16h2M28 22h2M28 28h2"/></svg>',
	'dogs'     => '<svg viewBox="0 0 48 48" aria-hidden="true"><rect x="10" y="10" width="28" height="28" rx="6"/><path class="is-works" d="M24 32c-6-4-8-7-8-10a4 4 0 0 1 8-1 4 4 0 0 1 8 1c0 3-2 6-8 10z"/></svg>',
];
?>
<?php log_bk_open( '12', 'פוסט וסטורי', 'bk-social' ); ?>
	<?php log_bk_head( '12', 'פוסט וסטורי', 'אותה שפה בריבוע ובמסך מלא. כותרת, קו דק, נקודה אחת.' ); ?>
	<div class="bk-social__cols">
		<ul class="bk-social__frames">
			<li><?php echo log_bk_mini( 'post-person', 0.2, [ 'label' => 'פוסט אנשים' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><p class="bk-cap">פוסט אנשים · 1080 על 1350</p></li>
			<li><?php echo log_bk_mini( 'post-place', 0.2, [ 'label' => 'פוסט מקום' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><p class="bk-cap">פוסט מקום · 1080 על 1350</p></li>
			<li><?php echo log_bk_mini( 'story', 0.1407, [ 'label' => 'סטורי' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><p class="bk-cap">סטורי · 1080 על 1920</p></li>
		</ul>
		<?php
		log_bk_rules( [
			[ 'גדלים', 'פוסט 1080 על 1350 (4:5) או 1080 על 1080. סטורי 1080 על 1920.' ],
			[ 'פוסט אנשים', 'צילום בצבע למעלה, פאנל נייר עם כותרת למטה.' ],
			[ 'פוסט מקום', 'צילום אוויר עם שכבה שקופה שמאפירה, כותרת לבנה, קו מחליף צבע.' ],
			[ 'נקודה כתומה אחת', 'בכל פריים, בחיתוך של קווים.' ],
			[ 'שוליים', '80 פיקסלים מכל צד. לא מניחים טקסט בשליש התחתון של סטורי.' ],
			[ 'לוגו', 'סימן בלבד, קטן, בפינה השמאלית התחתונה.' ],
		], 3 );
		?>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '12', 'עיצובים להיילייטס', 'bk-highlights', 'night' ); ?>
	<?php log_bk_head( '12', 'עיצובים להיילייטס', 'אותה מסגרת לכולם: ה־O של הלוגו. בפנים סימן, אייקון בקו דק או צילום.' ); ?>
	<ul class="bk-highlights__row">
		<?php foreach ( $highlights as [ $what, $name ] ) : ?>
			<li>
				<?php
				if ( str_ends_with( $what, '.jpg' ) ) {
					echo log_bk_person( $what ); // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo log_circle( 'split', $what === 'logo' ? log_logo( [ 'variant' => 'night' ] ) : $glyph[ $what ] ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
				<h3><?php echo esc_html( $name ); ?></h3>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="bk-note">רקע אספלט. ה־O בקו דק, כתום ואפור לסירוגין. אייקון בקו לבן דק עם נגיעה אחת של כתום. צילום של אדם או צוות בצבע, בתוך ה־O.</p>
<?php log_bk_close(); ?>

<?php log_bk_open( '12', 'באנר ללינקדאין', 'bk-banner' ); ?>
	<?php log_bk_head( '12', 'באנר ללינקדאין', 'כמו שער הספר, בלי לוגו. תמונת הפרופיל של הדף היא הלוגו.' ); ?>
	<div class="bk-banner__stage">
		<?php echo log_bk_mini( 'banner', 0.7235, [ 'label' => 'באנר ללינקדאין' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<div class="bk-banner__avatar"><?php echo log_logo( [ 'variant' => 'color', 'tagline' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	</div>
	<h3>לינקדאין · 1584 על 396</h3>
	<p class="bk-banner__text">צילום אוויר עם שכבה שמאפירה. הקווים נחתכים ברבע השמאלי, מעל תמונת הפרופיל, והטקסט מימין. בתמונה: הבאנר בדף החברה, עם תמונת הפרופיל.</p>
<?php log_bk_close(); ?>

<?php log_bk_open( '12', 'פייסבוק וחתימה', 'bk-fb' ); ?>
	<?php log_bk_head( '12', 'פייסבוק וחתימה', 'אותה שפה מחוץ לפוסטים: כותרת, קו דק ונקודה אחת.' ); ?>
	<div class="bk-fb__cols">
		<div>
			<?php echo log_bk_mini( 'facebook', 0.82, [ 'label' => 'תמונת נושא לפייסבוק' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h3>פייסבוק · 851 על 315</h3><p>הנקודה בחיתוך של הקו עם קצה הצילום.</p>
		</div>
		<div>
			<div class="bk-sign" role="img" aria-label="חתימה למייל">
				<p><b>שם פרטי ומשפחה</b> · תפקיד בלוג</p>
				<?php echo log_line( 'dot' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p>טלפון · מייל · אתר</p>
				<?php echo log_logo( [ 'variant' => 'color', 'tagline' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<h3>חתימה למייל · רוחב 600</h3><p>שם ותפקיד מעל קו עם נקודה, פרטי קשר מתחתיו. הלוגו כתמונה אחת.</p>
		</div>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '13', 'שער ופתיחה', 'bk-examples' ); ?>
	<?php log_bk_head( '13', 'שער ופתיחה', 'השקפים שפותחים מצגת, סיכום פעילות או פרק. אותו מבנה בכל פעם: תווית, כותרת ומשפט משנה צמודים לקו.' ); ?>
	<ul class="bk-minis bk-minis--3 bk-minis--tight">
		<li><?php echo log_bk_mini( 'cover', 0.3, [ 'kicker' => 'השפה העיצובית של לוג', 'label' => 'שקף שער' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>01</i> שער</h3></li>
		<li><?php echo log_bk_mini( 'title', 0.3, [ 'label' => 'שקף סיכום פעילות' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>02</i> סיכום פעילות</h3></li>
		<li><?php echo log_bk_mini( 'opener', 0.3, [ 'photo' => 'aerial.jpg', 'kicker' => 'פרק 01', 'label' => 'שקף פתיחת פרק על צילום אוויר' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>03</i> פתיחת פרק: צילום אוויר</h3></li>
		<li><?php echo log_bk_mini( 'opener', 0.3, [ 'photo' => 'team.jpg', 'kicker' => 'פרק 03', 'title' => 'הפנים של לוג', 'sub' => 'מי עושה את העבודה.', 'label' => 'שקף פתיחת פרק אנשים' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>04</i> פתיחת פרק: אנשים</h3></li>
		<li><?php echo log_bk_mini( 'opener', 0.3, [ 'photo' => 'night-city.jpg', 'kicker' => 'פרק 04', 'title' => 'מספרים בקו ישר', 'sub' => 'נתונים שמראים את התמונה המלאה.', 'label' => 'שקף פתיחת פרק מספרים' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>05</i> פתיחת פרק: מספרים</h3></li>
		<li><?php echo log_bk_mini( 'list', 0.3, [ 'label' => 'שקף סגירה' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>06</i> סגירה: מה הלאה</h3></li>
	</ul>
<?php log_bk_close(); ?>

<?php log_bk_open( '13', 'אנשים, נתונים וציר זמן', 'bk-examples' ); ?>
	<?php log_bk_head( '13', 'אנשים, נתונים וציר זמן', 'צילום בצבע על רקע נייר, עיגול מפוצל סביב אדם. כתום על מה שנוכחי, אפור לכל השאר.' ); ?>
	<ul class="bk-minis bk-minis--3 bk-minis--tight">
		<li><?php echo log_bk_mini( 'person', 0.3, [ 'label' => 'שקף אדם ושלוש מילים' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>07</i> אדם ושלוש מילים</h3></li>
		<li><?php echo log_bk_mini( 'team', 0.3, [ 'label' => 'שקף הצוות' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>08</i> הצוות</h3></li>
		<li><?php echo log_bk_mini( 'quote', 0.3, [ 'photo' => 'person-7.jpg', 'label' => 'שקף ציטוט' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>09</i> ציטוט</h3></li>
		<li><?php echo log_bk_mini( 'chart', 0.3, [ 'label' => 'שקף גרף' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>10</i> גרף</h3></li>
		<li><?php echo log_bk_mini( 'timeline', 0.3, [ 'label' => 'שקף ציר זמן' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>11</i> ציר זמן</h3></li>
		<li><?php echo log_bk_mini( 'table', 0.3, [ 'label' => 'שקף טבלה' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3><i>12</i> טבלה</h3></li>
	</ul>
<?php log_bk_close(); ?>
