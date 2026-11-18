<?php
/**
 * Chapter 01, the junction: the idea the whole language comes from. LOG's O is a roundabout seen from
 * above, and every line, dot and colour change in the book is read as part of a road.
 */

$principles = [
	[ 'הקווים הם כבישים', 'הקו דק, אחד בעמוד, ונמשך עד הקצה. הוא לא נעצר באמצע הדף ולא נגמר בחץ.' ],
	[ 'הנקודה היא הצומת', 'נקודה כתומה אחת בעמוד, במקום שבו משהו נסגר. בכותרת היא הנקודה שבסוף המשפט.' ],
	[ 'הצבע מתחלף באמצע', 'אפור הופך לכתום ברגע שתוכנית יוצאת מהנייר לשטח. באתר זה הקו שמתמלא כשגוללים.' ],
	[ 'מקווקו עוד לא נבנה', 'מה שעוד לפנינו מסומן בקו מקווקו, כמו שלב בתוכנית שעדיין לא התחיל.' ],
	[ 'נייבי הוא אספלט בלילה', 'המפה, חברת הבקרה והפוטר יושבים על כביש בלילה. עמודי הקריאה והאנשים הם נייר ביום.' ],
];
?>
<section class="bk-page bk-page--night bk-opener" aria-label="פרק 1: הצומת">
	<p class="bk-opener__n" aria-hidden="true">01</p>
	<div class="bk-opener__body">
		<?php log_bk_title( 'הצומת' ); ?>
		<p class="bk-lede">ה־O של לוג הוא כיכר שנראית מלמעלה. ארבע זרועות נפגשות בה, תנועה נכנסת ויוצאת, ומשהו מתחבר. זה מה שלוג עושה בכל פרויקט, ומזה נבנתה כל השפה.</p>
	</div>
	<i class="bk-line bk-line--v" aria-hidden="true"></i>
</section>

<section class="bk-page bk-page--day bk-principles" aria-label="חמישה כללים">
	<div class="bk-principles__head">
		<?php log_bk_title( 'אנחנו הצומת' ); ?>
		<p class="bk-lede">חמישה כללים, כולם מהכביש. מי שזוכר אותם יכול לעצב עמוד חדש בלי לפתוח את הספר.</p>
	</div>
	<i class="bk-line" aria-hidden="true"></i>
	<ol class="bk-principles__list">
		<?php foreach ( $principles as $i => [ $name, $text ] ) : ?>
			<li>
				<span class="bk-num" aria-hidden="true"><?php echo (int) $i + 1; ?></span>
				<h3><?php echo esc_html( $name ); ?>.</h3>
				<p><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php log_bk_folio( 'הצומת' ); ?>
</section>
