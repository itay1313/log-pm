<?php
/**
 * Chapters 05 and 06: colour, then type and the grid. Nothing here holds a colour value: swatches and contrast
 * ratios are read from the theme's custom properties by src/brand/main.js. Change a token, and these sheets follow.
 */

// [ text token, ground token, the pair's name, what it is for ].
$pairs = [
	[ 'ink', 'paper', 'דיו על נייר', 'טקסט, ברירת המחדל' ],
	[ 'night-ink', 'night', 'נייר על אספלט', 'טקסט הפוך' ],
	[ 'sign-ink', 'works', 'אספלט על כתום', 'תיבה כתומה עם טקסט כהה' ],
	[ 'works', 'night', 'כתום על אספלט', 'הדגשה על רקע כהה' ],
	[ 'slate', 'paper', 'אפור כהה על נייר', 'טקסט משני' ],
	[ 'night-muted', 'night', 'אפור על אספלט', 'טקסט משני על כהה' ],
	[ 'works', 'paper', 'כתום על נייר', 'לא משתמשים: כתום כטקסט רק על כהה' ],
	[ 'paper', 'works', 'נייר על כתום', 'לא משתמשים: נייר על כתום לא נקרא' ],
];

// The scale (src/tokens/tokens.json): one row per level, the sample at its size on the site, then where it is set and
// at what size in each format. [ tag, job, sample, row, face, leading and tracking, site (desktop · phone), slide 1280, post 1080 ].
$scale = [
	[ 'H1', 'כותרת עמוד', 'אנחנו הצומת.', 'h1', 'Rubik Black 900', 'שורה 0.92 · ריווח ‎-1%', '64 · 40', '57', '72' ],
	[ 'H2', 'כותרת פרק', 'מחברים. מובילים.', 'h2', 'Rubik Black 900', 'שורה 0.92 · ריווח ‎-1%', '52 · 32', '', '' ],
	[ 'H3', 'כותרת משנה', 'הפרויקטים שלכם בשפה שמבינים.', 'h3', 'Assistant ExtraBold 800', 'שורה 1.02 · ריווח ‎-1%', '40 · 32', '30', '40' ],
	[ 'H4', 'כותרת קטנה', 'תכנון עירוני בכל קנה מידה', 'h4', 'Assistant ExtraBold 800', 'שורה 1.02 · ריווח ‎-1%', '28 · 24', '', '' ],
	[ 'תווית', 'פרק, כפתור, טבלה', '06 · טיפוגרפיה וגריד', 'label', 'Assistant SemiBold 600', 'שורה 1.2', '16', '17', '26' ],
	[ 'גוף', 'טקסט רץ, באפור כהה', 'כותרת קצרה, ומשפט אחד שמסביר אותה.', 'body', 'Assistant Regular 400', 'שורה 1.6', '19', '18 עד 22', '30' ],
	[ 'כיתוב', 'מקור והערה', 'מקור: דוח תנועת הגולשים באתר לוג.', 'cap', 'Assistant Regular 400', 'שורה 1.4', '15', '14', '24' ],
	[ 'מספר', 'נתון גדול ומספור', '01  99.4K', 'fig', 'Assistant Light 300', 'ריווח ‎-2%', '64 · 40', '', '' ],
];

$formats = [
	[ 'slide', 'שקף · 1280 על 720', 'שוליים 67 מימין ומשמאל. תווית בגובה 29, כותרת בגובה 68, קו פוטר בגובה 672.' ],
	[ 'square', 'ריבוע · 1080 על 1080', 'שוליים 80 מכל צד.' ],
	[ 'post', 'פוסט · 1080 על 1350', 'שוליים 80 מכל צד.' ],
	[ 'story', 'סטורי · 1080 על 1920', 'שוליים 80. בלי טקסט בשליש התחתון.' ],
];
?>
<?php log_bk_open( '05', 'כתום אחד', 'bk-colors bk-page--hold' ); ?>
	<?php log_bk_head( '05', 'כתום אחד', 'אספלט, נייר ואפורים בונים את העמוד. הכתום מסמן דבר אחד: מה שחשוב עכשיו.' ); ?>
	<div class="bk-bands">
		<?php
		log_bk_band( 'night', 'night-ink', 22, 'אספלט', 'רקע כהה, כותרות, קווים' );
		log_bk_band( 'paper', 'ink', 62, 'נייר', 'רקע בהיר' );
		log_bk_band( 'works', 'sign-ink', 5, 'כתום', 'הדבר האחד שחשוב' );
		log_bk_band( 'works-light', 'sign-ink', 2, 'כתום בהיר', 'עמודות בגרף, תגית שנייה' );
		log_bk_band( 'rule', 'ink', 6, 'אפור', 'עמודות, מספור, קווים' );
		log_bk_band( 'slate', 'night-ink', 3, 'אפור כהה', 'גוף טקסט, כיתובים' );
		?>
	</div>
	<div class="bk-colors__foot">
		<div class="bk-ratio">
			<h3>יחס בעמוד</h3>
			<p class="bk-ratio__bar" aria-hidden="true"><i style="--w: 62; --c: var(--paper-2)"></i><i style="--w: 22; --c: var(--night)"></i><i style="--w: 11; --c: var(--rule)"></i><i style="--w: 5; --c: var(--works)"></i></p>
			<p class="bk-ratio__names" aria-hidden="true"><span style="--w: 62">נייר</span><span style="--w: 22">אספלט</span><span style="--w: 11">אפורים</span><span style="--w: 5">כתום</span></p>
		</div>
		<div class="bk-nightfam">
			<h3>אספלט בלילה</h3>
			<p class="bk-nightfam__chips" aria-hidden="true"><i style="--c: var(--night-deep)"></i><i style="--c: var(--night)"></i><i style="--c: var(--night-2)"></i><i style="--c: var(--night-line)"></i><i style="--c: var(--night-muted)"></i></p>
			<p class="bk-nightfam__text">האספלט של לוג הוא נייבי: כביש בלילה. סביבו משפחה אחת של גוונים, למפה, לחברת הבקרה ולפוטר.</p>
		</div>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '05', 'חוקיות צבעים', 'bk-contrast' ); ?>
	<?php log_bk_head( '05', 'חוקיות צבעים', 'יחסי הניגודיות מחושבים מהצבעים עצמם, בזמן אמת. מתחת ליחס 4.5 לא משתמשים לטקסט.' ); ?>
	<table class="bk-table bk-contrast__table">
		<tbody>
			<?php foreach ( $pairs as [ $fg, $bg, $name, $use ] ) : ?>
				<tr>
					<td><span class="bk-contrast__chip" style="color: var(--<?php echo esc_attr( $fg ); ?>); background: var(--<?php echo esc_attr( $bg ); ?>)" aria-hidden="true">אבג</span></td>
					<th scope="row"><?php echo esc_html( $name ); ?></th>
					<td class="bk-contrast__ratio" data-ratio="--<?php echo esc_attr( "$fg --$bg" ); ?>"></td>
					<td class="bk-contrast__verdict" data-verdict></td>
					<td><?php echo esc_html( $use ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="bk-note">החישוב לפי נוסחת הניגודיות של WCAG 2.1, מהצבעים שבעמוד הקודם.</p>
<?php log_bk_close(); ?>

<?php log_bk_open( '06', 'שתי משפחות', 'bk-type' ); ?>
	<?php log_bk_head( '06', 'שתי משפחות', 'Rubik Black לכותרת, Assistant לכל השאר. כל רמה קטנה בבירור מזו שמעליה.' ); ?>
	<?php // One table for the whole scale, so every level and every format read along the same lines. ?>
	<table class="bk-scale">
		<thead><tr><th scope="col">רמה</th><th scope="col">דוגמה, בגודל שלה באתר</th><th scope="col">משקל</th><th scope="col">אתר</th><th scope="col">שקף</th><th scope="col">פוסט</th></tr></thead>
		<tbody>
			<?php foreach ( $scale as [ $tag, $job, $sample, $row, $face, $set, $site, $slide, $post ] ) : ?>
				<tr class="bk-scale__row bk-scale__row--<?php echo esc_attr( $row ); ?>">
					<th scope="row" class="bk-scale__tag"><b><?php echo esc_html( $tag ); ?></b><span><?php echo esc_html( $job ); ?></span></th>
					<td class="bk-scale__sample"><?php echo esc_html( $sample ); ?></td>
					<td class="bk-scale__spec"><b dir="ltr"><?php echo esc_html( $face ); ?></b><span><?php echo esc_html( $set ); ?></span></td>
					<td class="bk-scale__n" data-label="אתר"><?php echo esc_html( $site ); ?></td>
					<td class="bk-scale__n" data-label="שקף"><?php echo esc_html( $slide !== '' ? $slide : '—' ); ?></td>
					<td class="bk-scale__n" data-label="פוסט"><?php echo esc_html( $post !== '' ? $post : '—' ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<p class="bk-note">הדגשה בכתום רק על מילה אחת, לא על פסקה. בלי מקף לפני מספר בעברית. מספרים בטבלאות ובצירים בספרות ברוחב קבוע.</p>
<?php log_bk_close(); ?>

<?php log_bk_open( '06', 'כולם מתחילים באותה נקודה', 'bk-grid' ); ?>
	<?php log_bk_head( '06', 'כולם מתחילים באותה נקודה', 'בשקף: כל כותרת, תווית ומשפט משנה מיושרים לאותו קו מימין. המרווחים ביניהם שווים.' ); ?>
	<div class="bk-grid__cols">
		<figure class="bk-grid__sheet" aria-label="שקף עם מידות הגריד">
			<i class="bk-grid__margin bk-grid__margin--start" aria-hidden="true"></i><i class="bk-grid__margin bk-grid__margin--end" aria-hidden="true"></i>
			<p class="bk-grid__label" style="--y: 29"><span>תווית פרק</span><b>29</b></p>
			<p class="bk-grid__title" style="--y: 68"><span>כותרת השקף<i class="bk-dot">.</i></span><b>68</b></p>
			<p class="bk-grid__sub" style="--y: 143"><span>משפט משנה</span><b>143</b></p>
			<p class="bk-grid__content">תוכן</p>
			<p class="bk-grid__foot" style="--y: 672"><b>672</b></p>
			<p class="bk-grid__m bk-grid__m--start" aria-hidden="true">67</p><p class="bk-grid__m bk-grid__m--end" aria-hidden="true">67</p>
		</figure>
		<?php
		log_bk_rules( [
			[ 'שוליים', '67 פיקסלים מימין ומשמאל בקנבס 1280. כל טקסט מתחיל מהקו הימני.' ],
			[ 'שקף תוכן', 'תווית פרק בגובה 29, כותרת בגובה 68, משפט משנה בגובה 143. אותו מרחק בין השורות.' ],
			[ 'פתיחת פרק', 'תווית, כותרת ומשפט משנה צמודים לקו האופקי, במרווח שווה של 28. הקו כתום לכל אורך משפט המשנה ועד הקצה הימני, ואפור משם שמאלה.' ],
			[ 'שורות עם מספר', 'המספר והכותרת ממורכזים זה לזה ובאמצע הרווח שבין שני קווים.' ],
			[ 'פוטר', 'קו בגובה 672, סימן הלוגו משמאל, שם ומספר עמוד מימין.' ],
		] );
		?>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '06', 'אותו גריד בכל פורמט', 'bk-formats' ); ?>
	<?php log_bk_head( '06', 'אותו גריד בכל פורמט', 'שקף, ריבוע, פוסט וסטורי: שוליים קבועים, וכל טקסט מתחיל מהקו הימני.' ); ?>
	<ul class="bk-formats__row">
		<?php foreach ( $formats as [ $format, $name, $text ] ) : ?>
			<li>
				<div class="bk-format bk-format--<?php echo esc_attr( $format ); ?>" aria-hidden="true"><i class="bk-format__margin"></i><b>כותרת<span class="bk-dot">.</span></b><?php echo log_logo( [ 'variant' => 'color' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
				<h3><?php echo esc_html( $name ); ?></h3><p><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	log_bk_rules( [
		[ 'נקודת התחלה', 'הפינה הימנית העליונה של השוליים. כותרת, תווית ומשפט משנה מיושרים אליה.' ],
		[ 'לוגו', 'בפינה השמאלית התחתונה, בתוך השוליים.' ],
	], 0, 'bk-rules--2' );
	?>
<?php log_bk_close(); ?>

<?php log_bk_open( '06', 'השקף שממנו לומדים', 'bk-model' ); ?>
	<?php log_bk_head( '06', 'השקף שממנו לומדים', '"מה הלאה" הוא המודל לשקף רשימה על צילום: קו אנכי אחד, שורות שוות ושלושה משקלים.' ); ?>
	<div class="bk-model__cols">
		<?php echo log_bk_mini( 'list', 0.56, [ 'label' => 'שקף "מה הלאה"' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php
		log_bk_rows( [
			[ 'קו אנכי אחד', 'כתום, עובר את כל העמוד. נקודה כתומה בחיתוך עם קו התחתית.' ],
			[ 'שורות שוות', 'אותו מרווח בין הקווים. המספר והטקסט ממורכזים ביניהם.' ],
			[ 'נקודת התחלה אחת', 'כל הטקסטים מתחילים באותו קו, באותו מרחק מהמספר.' ],
			[ 'שלושה משקלים', 'כותרת כבדה, טקסט רגיל, מספרים דקים. לא יותר.' ],
			[ 'קווים בין השוליים', 'השורות נגמרות בשוליים. רק קו התחתית רץ מקצה לקצה.' ],
		], 'bk-rows--tight' );
		?>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '06', 'תוכן, טבלה וציטוט', 'bk-templates' ); ?>
	<?php log_bk_head( '06', 'תוכן, טבלה וציטוט', 'תבניות לשקף: אותו גריד, אותם קווים, אותו כתום אחד.' ); ?>
	<ul class="bk-minis bk-minis--3">
		<li><?php echo log_bk_mini( 'toc', 0.2875, [ 'label' => 'שקף תוכן עניינים' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3>תוכן עניינים</h3><p>שורות שוות עם קו דק ביניהן. מספר דק באפור, שם הפרק מודגש, עמוד בקטן משמאל.</p></li>
		<li><?php echo log_bk_mini( 'table', 0.2875, [ 'label' => 'שקף טבלה' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3>טבלה</h3><p>קו כהה מתחת לכותרות, קו דק בין השורות. כתום משמש להדגשת ערך.</p></li>
		<li><?php echo log_bk_mini( 'quote', 0.2875, [ 'label' => 'שקף ציטוט' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3>ציטוט</h3><p>השם על הצילום, מעל קו שנכנס מהקצה ונגמר בנקודה כתומה.</p></li>
	</ul>
	<p class="bk-note">בתמונות: נתונים, שמות וציטוט לדוגמה בלבד.</p>
<?php log_bk_close(); ?>
