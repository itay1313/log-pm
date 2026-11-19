<?php
/**
 * Chapter 03, the thin line. Every specimen here is the site's own component (inc/lines.php, lines.css), not a
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
	[ 'split', '1', 'מפוצל', 'אדם: מסגרת לתמונה, או המספור בעמוד הצוות. ארבעה רבעים, כתום ואפור לסירוגין, כמו ה־O.' ],
	[ 'halves', '2', 'שני חצאים', 'אדם, בשחור בלבד: שני חצאים, חתוכים למעלה ולמטה. בלי צבע.' ],
];

// When what: [sample markup, name, when, not].
$when = [
	[ log_line( 'solid', [ 'draw' => '' ] ), 'קו רציף', 'מבנה העמוד, צירים וגרפים. כל מה שקיים או קרה. באתר: שלבים שהפרויקט עבר.', 'לא נעצר באמצע. לא עובר על פנים.' ],
	[ log_line( 'dashed', [ 'draw' => '' ] ), 'קו מקווקו', 'תכנון שעוד לא נבנה, או קו ייחוס. באתר: השלבים שעוד לפני הפרויקט.', 'לא כקישוט, לא על צילום, לא סביב אנשים.' ],
	[ log_line( 'switch', [ 'at' => 50, 'draw' => '' ] ), 'קו מחליף צבע', 'הרגע שבו תוכנית יוצאת מהנייר לשטח. באתר: הקו בצד העמוד, כתום עד המקום שאליו הגעתם.', 'פעם אחת בקו, ורק כשיש לזה משמעות.' ],
	[ log_dot(), 'נקודה', 'איפה שקורה משהו: חיתוך של קווים, סוף קו, פרויקט במפה, סוף כותרת.', 'מלאה, בלי טבעת. כתומה אחת בעמוד.' ],
	[ log_circle( 'full', '7', [ 'draw' => '' ] ), 'עיגול מלא', 'מספר שסופרים.', 'לא סביב תמונה. לא עיגול בתוך עיגול.' ],
	[ log_circle( 'split', '1', [ 'draw' => '' ] ), 'עיגול מפוצל', 'אדם: מסגרת לתמונה, או מספור בעמוד הצוות.', 'בלי תג מספר על התמונה. בלי עיגול נוסף סביבו.' ],
];
?>
<section class="bk-page bk-page--night bk-opener" aria-label="פרק 3: הקו הדק">
	<p class="bk-opener__n" aria-hidden="true">03</p>
	<div class="bk-opener__body">
		<?php log_bk_title( 'הקו הדק' ); ?>
		<p class="bk-lede">כל הקווים בשפה דקים ובאותו עובי. הם נמשכים עד הקצה, חוצים זה את זה, ונגמרים בנקודה במקום שבו משהו קורה. כמו כבישים במפה.</p>
	</div>
	<i class="bk-line bk-line--v" aria-hidden="true"></i>
</section>

<section class="bk-page bk-page--day bk-lines" aria-label="חמישה קווים">
	<div class="bk-lines__head">
		<p class="bk-kicker bk-kicker--muted">03 · הקו הדק</p>
		<?php log_bk_title( 'קו אחד, באותו עובי' ); ?>
		<p class="bk-lede">חמישה קווים, וכל אחד מסמן דבר אחד. אם אי אפשר להגיד מה קו מסמן, הוא לא נכנס לעמוד.</p>
	</div>
	<ul class="bk-lines__kinds">
		<?php foreach ( $lines as [ $kind, $args, $name, $text ] ) : ?>
			<li>
				<?php echo log_line( $kind, $args ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in log_line() ?>
				<h3><?php echo esc_html( $name ); ?></h3>
				<p><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
	<dl class="bk-lines__rules">
		<div><dt>עובי</dt><dd>בעמוד של 1280: <span data-size-range="--line-w"></span>. בפוסט של 1080: 3px. במצגת: כנקודה וחצי.</dd></div>
		<div><dt>עד הקצה</dt><dd>קו של מבנה ממשיך עד שולי העמוד. הוא לא נעצר באמצע.</dd></div>
		<div><dt>נקודה</dt><dd>מלאה, בקוטר <span data-size-range="--dot"></span>. כתומה, או לבנה על צילום כהה. בלי טבעת סביבה.</dd></div>
		<div><dt>החלפת צבע</dt><dd>פעם אחת בקו, ורק כשיש לה משמעות.</dd></div>
		<div><dt>צבעים</dt><dd>אספלט, אפור וכתום. לבן על צילום כהה.</dd></div>
		<div><dt>לא עבה</dt><dd>מסגרת נבנית מקווים דקים שחוצים זה את זה, לא מקו עבה.</dd></div>
	</dl>
	<?php log_bk_folio( 'הקו הדק' ); ?>
</section>

<section class="bk-page bk-page--day bk-circles" aria-label="ארבעה עיגולים">
	<div class="bk-lines__head">
		<p class="bk-kicker bk-kicker--muted">03 · הקו הדק</p>
		<?php log_bk_title( 'עיגולים כמו בלוגו' ); ?>
		<p class="bk-lede">עיגול הוא קו דק שנסגר: מלא, מקווקו, או מפוצל לארבעה רבעים כמו ה־O של לוג. עובי העיגול כעובי הקו, גם כשהעיגול גדול.</p>
	</div>
	<ul class="bk-circles__kinds">
		<?php foreach ( $circles as [ $kind, $n, $name, $text ] ) : ?>
			<li>
				<?php echo log_circle( $kind, esc_html( $n ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_circle(), inner escaped ?>
				<h3><?php echo esc_html( $name ); ?></h3>
				<p><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="bk-circles__rule">עיגול אחד לכל דבר: אדם, מספר או נתון. אין עיגול עבה, אין עיגול בגרדיאנט, ואין עיגול על עיגול, חוץ מתמונה של אדם בתוך עיגול מפוצל.</p>
	<?php log_bk_folio( 'הקו הדק' ); ?>
</section>

<section class="bk-page bk-page--day bk-when" aria-label="מתי מה">
	<div class="bk-lines__head">
		<p class="bk-kicker bk-kicker--muted">03 · הקו הדק</p>
		<?php log_bk_title( 'מתי מה' ); ?>
	</div>
	<table class="bk-when__table">
		<thead><tr><th scope="col">רכיב</th><th scope="col">מתי</th><th scope="col">לא</th></tr></thead>
		<tbody>
			<?php foreach ( $when as [ $sample, $name, $use, $not ] ) : ?>
				<tr>
					<th scope="row"><span class="bk-when__sample"><?php echo $sample; // phpcs:ignore WordPress.Security.EscapeOutput -- component markup ?></span><?php echo esc_html( $name ); ?></th>
					<td><?php echo esc_html( $use ); ?></td>
					<td><?php echo esc_html( $not ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<?php log_bk_folio( 'הקו הדק' ); ?>
</section>
