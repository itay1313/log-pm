<?php
/**
 * Chapter 02, colour and type. Nothing here holds a value: swatches and samples point at the theme's
 * custom properties (src/styles/tokens.css, generated from src/tokens/tokens.json), and src/brand/main.js
 * writes each value and contrast ratio from the live CSS. Change a token, and this chapter follows.
 */

$weights = [
	[ 'Ploni', '--font-text', 'var(--fw-light)', 'Light' ],
	[ 'Ploni', '--font-text', 'var(--fw-regular)', 'Regular' ],
	[ 'Ploni', '--font-text', 'var(--fw-medium)', 'Medium' ],
	[ 'Ploni Tzar', '--font-display', 'var(--fw-regular)', 'Regular' ],
	[ 'Ploni Tzar', '--font-display', 'var(--fw-medium)', 'Medium' ],
	[ 'Ploni Tzar', '--font-display', 'var(--fw-bold)', 'Bold' ],
];

// Type roles, largest first: [role token, size token, a line the site really sets in that role].
$roles = [
	[ 'type-h1', 'fs-h1', 'פרויקטים' ],
	[ 'type-h2', 'fs-h2', 'מתכננים יחד' ],
	[ 'type-h3', 'fs-h3', 'פינוי־בינוי בחולון' ],
	[ 'type-h4', 'fs-h4', 'שלב התכנון הסטטוטורי' ],
	[ 'type-title', 'fs-title', 'חברת הבקרה' ],
	[ 'type-name', 'fs-lead', 'מנהלת פרויקטים בכירה' ],
	[ 'type-legend', 'fs-base', 'בואו נדבר' ],
	[ 'type-label', 'fs-small', 'סינון לפי שלב' ],
	[ 'type-meta', 'fs-small', 'עודכן לפני שבוע, מחוז מרכז' ],
	[ 'type-micro', 'fs-micro', 'גוש 6024 · חלקה 112' ],
];
?>
<section class="bk-page bk-page--night bk-opener" aria-label="פרק 2: צבע ואות">
	<p class="bk-opener__n" aria-hidden="true">02</p>
	<div class="bk-opener__body">
		<?php log_bk_title( 'צבע ואות' ); ?>
		<p class="bk-lede">שתי משפחות צבע, לילה ויום, כתום אחד לעבודה פעילה ומשפחת אות אחת. כל ערך בפרק הזה נקרא בזמן אמת מהטוקנים של האתר.</p>
	</div>
	<i class="bk-line bk-line--v" aria-hidden="true"></i>
</section>

<section class="bk-page bk-page--night bk-colors" aria-label="צבעי לילה">
	<div class="bk-colors__head">
		<?php log_bk_title( 'לילה' ); ?>
		<p class="bk-lede">הנייבי הוא אספלט בלילה. הוא הקרקע של המפה, של חברת הבקרה ושל הפוטר, והעמוד הזה עומד עליו.</p>
		<p class="bk-ground">הקרקע של העמוד <code class="bk-token">--plate</code> <span data-token-value="--plate"></span></p>
	</div>
	<i class="bk-line" aria-hidden="true"></i>
	<div class="bk-colors__row">
		<?php
		log_bk_swatch( 'plate-deep', 'מאחורי הדף: הרקע שמאחורי המפה והספר' );
		log_bk_swatch( 'plate-2', 'משטח אחד מעל הלילה' );
		log_bk_swatch( 'plate-line', 'קו על לילה' );
		log_bk_swatch( 'legend-muted', 'טקסט משני על לילה', 'plate' );
		log_bk_swatch( 'legend', 'טקסט על לילה', 'plate' );
		?>
	</div>
	<?php log_bk_folio( 'צבע' ); ?>
</section>

<section class="bk-page bk-page--day bk-colors" aria-label="צבעי יום">
	<div class="bk-colors__head">
		<?php log_bk_title( 'יום' ); ?>
		<p class="bk-lede">נייר של כביש ביום. עמודי הקריאה, הצוות והידע יושבים עליו, עם דיו כהה ושני קווים רכים.</p>
		<p class="bk-ground">הקרקע של העמוד <code class="bk-token">--paper</code> <span data-token-value="--paper"></span></p>
	</div>
	<i class="bk-line" aria-hidden="true"></i>
	<div class="bk-colors__row">
		<?php
		log_bk_swatch( 'paper-2', 'משטח בהיר על נייר' );
		log_bk_swatch( 'rule-soft', 'קו רך: הפרדות בתוך רשימה' );
		log_bk_swatch( 'rule', 'קו: גבולות של שדות וטבלאות' );
		log_bk_swatch( 'slate', 'טקסט משני על נייר', 'paper' );
		log_bk_swatch( 'ink', 'טקסט על נייר', 'paper' );
		?>
	</div>
	<?php log_bk_folio( 'צבע' ); ?>
</section>

<section class="bk-page bk-page--day bk-works" aria-label="הכתום">
	<div class="bk-works__text">
		<?php log_bk_title( 'כתום אחד' ); ?>
		<p class="bk-lede">הכתום מסמן עבודה שקורית עכשיו: המצב הפעיל, ריחוף, פוקוס, הנקודה בסוף הכותרת. הוא לא רקע של עמוד שלם ולא מופיע פעמיים באותו מבט.</p>
		<i class="bk-line" aria-hidden="true"></i>
		<dl class="bk-works__rules">
			<div><dt>על הכתום</dt><dd>תמיד דיו כהה, אף פעם לא לבן. <span data-contrast="--sign-ink --works"></span></dd></div>
			<div><dt>טקסט קטן על נייר</dt><dd>רק בכתום העמוק, וגם הוא עוד צריך להתכהות. <span data-contrast="--works-deep --paper"></span></dd></div>
			<div><dt>על לילה</dt><dd>הכתום הרגיל מחזיק לבד. <span data-contrast="--works --plate"></span></dd></div>
		</dl>
	</div>
	<div class="bk-works__plate">
		<code class="bk-token">--works</code>
		<span data-token-value="--works"></span>
		<div class="bk-works__deep">
			<code class="bk-token">--works-deep</code>
			<span data-token-value="--works-deep"></span>
		</div>
	</div>
	<?php log_bk_folio( 'צבע' ); ?>
</section>

<section class="bk-page bk-page--night bk-specimen" aria-label="פלוני">
	<div class="bk-specimen__head">
		<?php log_bk_title( 'פלוני' ); ?>
		<p class="bk-lede">משפחה אחת בכל מקום. Ploni לטקסט, Ploni Tzar הצרה לכותרות, לשלטים ולתוויות. אין פונט שני.</p>
	</div>
	<p class="bk-specimen__glyphs" aria-hidden="true">אבג</p>
	<i class="bk-line" aria-hidden="true"></i>
	<ul class="bk-specimen__weights">
		<?php foreach ( $weights as [ $family, $font, $weight, $name ] ) : ?>
			<li style="font-family: var(<?php echo esc_attr( $font ); ?>); font-weight: <?php echo esc_attr( $weight ); ?>">
				<span>מחברים. מובילים. מתכננים.</span>
				<small><?php echo esc_html( "$family $name" ); ?></small>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php log_bk_folio( 'אות' ); ?>
</section>

<section class="bk-page bk-page--day bk-scale" aria-label="סולם הטיפוגרפיה">
	<div class="bk-scale__head">
		<?php log_bk_title( 'סולם' ); ?>
		<p class="bk-lede">עשרה תפקידים, כל אחד שורה אחת של CSS. הגודל נע בין טלפון למסך רחב, והטווח נקרא מהטוקן.</p>
	</div>
	<i class="bk-line bk-line--v" aria-hidden="true"></i>
	<ol class="bk-scale__rows">
		<?php foreach ( $roles as [ $role, $size, $sample ] ) : ?>
			<li>
				<span class="bk-scale__meta">
					<code class="bk-token">--<?php echo esc_html( $role ); ?></code>
					<span data-size-range="--<?php echo esc_attr( $size ); ?>"></span>
				</span>
				<span class="bk-scale__sample" style="font: var(--<?php echo esc_attr( $role ); ?>)"><?php echo esc_html( $sample ); ?></span>
			</li>
		<?php endforeach; ?>
	</ol>
	<?php log_bk_folio( 'אות' ); ?>
</section>
