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

// [ sample, font token, weight token, the face, its job ].
$faces = [
	[ 'אנחנו הצומת.', '--font-headline', '--fw-bold', 'Ploni Tzar Bold 700', 'כותרות ראשיות בלבד · שתיים עד ארבע מילים', 'xl' ],
	[ 'מחברים. מובילים. מתכננים.', '--font-display', '--fw-medium', 'Ploni Tzar Medium 500', 'כותרות משנה ותוויות', 'l' ],
	[ 'הפרויקטים שלכם בשפה שמבינים.', '--font-text', '--fw-regular', 'Ploni Regular 400', 'גוף טקסט, באפור כהה', 'm' ],
	[ '01  99.4K', '--font-figure', '--fw-light', 'Ploni Light 300', 'מספרים גדולים ומספור', 'fig' ],
];

$sizes = [
	[ 'כותרת', '57', '72', '56 עד 72' ],
	[ 'כותרת משנה', '30', '40', '28' ],
	[ 'גוף', '18 עד 22', '30', '18' ],
	[ 'כיתוב ומקור', '14', '24', '14' ],
	[ 'תווית פרק', '17', '26', '15' ],
];

$formats = [
	[ 'slide', 'שקף · 1280 על 720', 'שוליים 67 מימין ומשמאל. תווית בגובה 29, כותרת בגובה 68, קו פוטר בגובה 672.' ],
	[ 'square', 'ריבוע · 1080 על 1080', 'שוליים 80 מכל צד.' ],
	[ 'post', 'פוסט · 1080 על 1350', 'שוליים 80 מכל צד.' ],
	[ 'story', 'סטורי · 1080 על 1920', 'שוליים 80. בלי טקסט בשליש התחתון.' ],
];
?>
<?php log_bk_open( '05', 'כתום אחד', 'bk-colors' ); ?>
	<?php log_bk_head( '05', 'כתום אחד', 'אספלט, נייר ואפורים בונים את העמוד. הכתום מסמן דבר אחד: מה שחשוב עכשיו.' ); ?>
	<div class="bk-colors__row">
		<?php
		log_bk_swatch( 'night', 'אספלט', 'רקע כהה, כותרות, קווים' );
		log_bk_swatch( 'paper', 'נייר', 'רקע בהיר' );
		log_bk_swatch( 'works', 'כתום', 'הדבר האחד שחשוב' );
		log_bk_swatch( 'works-light', 'כתום בהיר', 'עמודות בגרף, תגית שנייה' );
		log_bk_swatch( 'rule', 'אפור', 'עמודות, מספור, קווים' );
		log_bk_swatch( 'slate', 'אפור כהה', 'גוף טקסט, כיתובים' );
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
			<p>האספלט של לוג הוא נייבי: כביש בלילה. סביבו משפחה אחת של גוונים, למפה, לחברת הבקרה ולפוטר.</p>
			<p class="bk-nightfam__chips" aria-hidden="true"><i style="--c: var(--night-deep)"></i><i style="--c: var(--night)"></i><i style="--c: var(--night-2)"></i><i style="--c: var(--night-line)"></i><i style="--c: var(--night-muted)"></i></p>
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

<?php log_bk_open( '06', 'משפחה אחת', 'bk-type' ); ?>
	<?php log_bk_head( '06', 'משפחה אחת', 'Ploni Tzar לכותרות, Ploni לכל השאר. כותרת קצרה ונגמרת בנקודה.' ); ?>
	<div class="bk-type__cols">
		<ul class="bk-type__faces">
			<?php foreach ( $faces as [ $sample, $font, $weight, $face, $job, $size ] ) : ?>
				<li>
					<p class="bk-type__sample bk-type__sample--<?php echo esc_attr( $size ); ?>" style="font-family: var(<?php echo esc_attr( $font ); ?>); font-weight: var(<?php echo esc_attr( $weight ); ?>)"><?php echo esc_html( $sample ); ?></p>
					<p class="bk-cap"><span dir="ltr"><?php echo esc_html( $face ); ?></span> · <?php echo esc_html( $job ); ?></p>
				</li>
			<?php endforeach; ?>
		</ul>
		<div class="bk-type__side">
			<table class="bk-table bk-type__sizes">
				<thead><tr><th scope="col">רמה</th><th scope="col">שקף 1280</th><th scope="col">פוסט 1080</th><th scope="col">אתר</th></tr></thead>
				<tbody>
					<?php foreach ( $sizes as [ $level, $slide, $post, $site ] ) : ?>
						<tr><th scope="row"><?php echo esc_html( $level ); ?></th><td><?php echo esc_html( $slide ); ?></td><td><?php echo esc_html( $post ); ?></td><td><?php echo esc_html( $site ); ?></td></tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php log_bk_rules( [ 'הדגשה בכתום רק על מילה או ביטוי אחד, לא על פסקה.', 'אין מקף לפני מספר בעברית ("ב־2027"). מנסחים מחדש.', 'מספרים בטבלאות ובצירים בספרות ברוחב קבוע.' ] ); ?>
		</div>
	</div>
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
		<li><?php echo log_bk_mini( 'toc', 0.3, [ 'label' => 'שקף תוכן עניינים' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3>תוכן עניינים</h3><p>שורות שוות עם קו דק ביניהן. מספר דק באפור, שם הפרק מודגש, עמוד בקטן משמאל.</p></li>
		<li><?php echo log_bk_mini( 'table', 0.3, [ 'label' => 'שקף טבלה' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3>טבלה</h3><p>קו כהה מתחת לכותרות, קו דק בין השורות. כתום משמש להדגשת ערך.</p></li>
		<li><?php echo log_bk_mini( 'quote', 0.3, [ 'label' => 'שקף ציטוט' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><h3>ציטוט</h3><p>השם על הצילום, מעל קו שנכנס מהקצה ונגמר בנקודה כתומה.</p></li>
	</ul>
	<p class="bk-note">בתמונות: נתונים, שמות וציטוט לדוגמה בלבד.</p>
<?php log_bk_close(); ?>
