<?php
/**
 * Chapter 07, the thin line. Every specimen here is the site's own component (inc/lines.php, lines.css), not a
 * picture of one: change the line system and this chapter follows. Measures are read from the live tokens.
 */

$lines = [
	[ 'solid', [], 'רציף', 'מה שקיים או אושר: מבנה העמוד, צירים, שלב שהסתיים.' ],
	[ 'dashed', [], 'מקווקו', 'רק מה שלא קיים בפועל: תכנון שעוד לא נבנה, או קו ייחוס כמו ממוצע.' ],
	[ 'switch', [ 'at' => 45 ], 'מחליף צבע', 'אפור עד הנקודה שבה אנחנו נכנסים, ומשם כתום. המעבר רך וקצר.' ],
	[ 'dot', [], 'קו עם נקודה', 'הקו נגמר בנקודה כתומה מלאה, בלי טבעת. מצביע על מה שחשוב.' ],
	[ 'cross', [ 'at' => 50 ], 'חוצה, עם נקודה', 'קווים נחתכים ולא מתעגלים. הנקודה יושבת בחיתוך.' ],
];

$circles = [
	[ 'full', '7', 'מלא', 'מספר שסופרים: כמה מועדים, כמה פרויקטים.' ],
	[ 'dashed', '3', 'מקווקו', 'מה שמתוכנן ועוד לא קרה. לא סביב אנשים.' ],
	[ 'split', 'person-3.jpg', 'מפוצל', 'מסגרת לאדם: תמונה או מספור בצוות. ארבעה רבעים, כתום ואפור לסירוגין, כמו בלוגו.' ],
	[ 'halves', 'person-6.jpg', 'שני חצאים, בשחור', 'מסגרת לאדם בקו שחור: שני חצאים, חיתוך למעלה ולמטה. בלי צבע.' ],
];

// When what: [sample markup, name, when, not].
$when = [
	[ log_line( 'solid', [ 'draw' => '' ] ), 'קו רציף', 'מבנה העמוד, צירים וגרפים. כל מה שקיים או קרה. באתר: שלבים שהפרויקט עבר.', 'לא נעצר באמצע. לא עובר על פנים.' ],
	[ log_line( 'dashed', [ 'draw' => '' ] ), 'קו מקווקו', 'תכנון שעוד לא נבנה, או קו ייחוס. באתר: השלבים שעוד לפני הפרויקט.', 'לא כקישוט, לא על צילום, לא סביב אנשים.' ],
	[ log_line( 'switch', [ 'at' => 50, 'draw' => '' ] ), 'קו מחליף צבע', 'הרגע שבו תוכנית יוצאת מהנייר לשטח. באתר: הקו בצד העמוד, כתום עד המקום שאליו הגעתם.', 'פעם אחת בקו, ורק כשיש לזה משמעות.' ],
	[ log_dot(), 'נקודה', 'איפה שקורה משהו: חיתוך של קווים, סוף קו, פרויקט במפה, סוף כותרת.', 'מלאה, בלי טבעת. כתומה אחת בעמוד.' ],
	[ log_circle( 'full', '7', [ 'draw' => '' ] ), 'עיגול מלא', 'מספר שסופרים.', 'לא סביב תמונה. לא עיגול בתוך עיגול.' ],
	[ log_circle( 'split', '1', [ 'draw' => '' ] ), 'עיגול מפוצל', 'אדם: מסגרת לתמונה, או מספור בעמוד הצוות.', 'בלי תג מספר על התמונה. בלי עיגול נוסף סביבו.' ],
	[ log_logo( [ 'variant' => 'color' ] ), 'לוגו', 'שער, סגירה, ובפוטר כסימן בלבד.', 'לא כאלמנט גרפי, לא כמסגרת, לא כרקע.' ],
];
?>
<?php log_bk_open( '07', 'קו אחד, באותו עובי', 'bk-lines' ); ?>
	<?php log_bk_head( '07', 'קו אחד, באותו עובי', 'כל הקווים בשפה דקים ובאותו עובי. הם נמשכים עד הקצה וחוצים זה את זה.' ); ?>
	<ul class="bk-lines__kinds">
		<?php foreach ( $lines as [ $kind, $args, $name, $text ] ) : ?>
			<li>
				<?php echo log_line( $kind, $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in log_line() ?>
				<h3><?php echo esc_html( $name ); ?></h3>
				<p><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	log_bk_rules( [
		[ 'עובי', '2 פיקסלים בקנבס 1280, בערך 1.5 נקודות במצגת, 3 פיקסלים בפוסט 1080.' ],
		[ 'לא עבה', 'מסגרת נבנית מקווים דקים חוצים. חריג: פינה מעוגלת אחת, סביב צילום או כותרת, באותו קו דק.' ],
		[ 'עד הקצה', 'קו מבני ממשיך עד שולי השקף, לא נעצר באמצע.' ],
		[ 'נקודה', '10 עד 14 פיקסלים, מלאה, בכתום או בלבן. בלי טבעת סביבה.' ],
		[ 'החלפת צבע', 'פעם אחת בקו, ורק כשיש לה משמעות.' ],
		[ 'צבעים', 'אספלט, אפור, כתום. לבן על צילום כהה.' ],
	], 4, 'bk-rules--2' );
	?>
<?php log_bk_close(); ?>

<?php log_bk_open( '07', 'עיגולים כמו בלוגו', 'bk-circles' ); ?>
	<?php log_bk_head( '07', 'עיגולים כמו בלוגו', 'עיגול הוא קו דק שנסגר: מלא, מקווקו, או מפוצל לארבעה רבעים כמו ה־O של לוג.' ); ?>
	<ul class="bk-circles__kinds">
		<?php foreach ( $circles as [ $kind, $in, $name, $text ] ) : ?>
			<li>
				<?php echo str_ends_with( $in, '.jpg' ) ? log_bk_person( $in, $kind ) : log_circle( $kind, esc_html( $in ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_circle() ?>
				<h3><?php echo esc_html( $name ); ?></h3>
				<p><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
	log_bk_rules( [
		'עובי העיגול כעובי הקו, גם כשהעיגול גדול.',
		'אין עיגול עבה, אין עיגול בגרדיאנט.',
		'אין עיגול על עיגול. חוץ מתמונת אדם בתוך עיגול מפוצל.',
		'עיגול אחד לכל דבר: אדם, מספר או נתון. לא כקישוט.',
	], 2, 'bk-rules--2' );
	?>
<?php log_bk_close(); ?>

<?php log_bk_open( '07', 'מתי מה', 'bk-when' ); ?>
	<?php log_bk_head( '07', 'מתי מה', 'כל רכיב עושה עבודה אחת. אם אי אפשר להגיד מה הוא מסמן, לא שמים אותו.' ); ?>
	<table class="bk-table bk-when__table">
		<thead><tr><th scope="col">רכיב</th><th scope="col">מתי</th><th scope="col">לא</th></tr></thead>
		<tbody>
			<?php foreach ( $when as [ $sample, $name, $use, $not ] ) : ?>
				<tr>
					<th scope="row"><span class="bk-when__sample"><?php echo $sample; // phpcs:ignore WordPress.Security.EscapeOutput -- component markup ?></span><?php echo esc_html( $name ); ?></th>
					<td data-label="מתי"><?php echo esc_html( $use ); ?></td>
					<td data-label="לא"><?php echo esc_html( $not ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php log_bk_close(); ?>
