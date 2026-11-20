<?php
/**
 * Chapter 14 and the way out: what is never done, four things to remember, and the last sheet.
 */
?>
<?php log_bk_open( '14', 'מה לא עושים', 'bk-donts' ); ?>
	<?php log_bk_head( '14', 'מה לא עושים' ); ?>
	<?php
	log_bk_rules( [
		'לא מוסיפים צבע שלישי בשביל גיוון.',
		'לא צובעים, לא מותחים ולא מציירים מחדש את הלוגו.',
		'לא יותר מנקודה כתומה אחת בעמוד. חוץ ממפה וגרף, שבהם כל נקודה היא נתון.',
		'לא קווים על פנים של אנשים.',
		'לא קווים עבים ולא מסגרות מלאות. פינה מעוגלת רק בקו דק.',
		'לא טקסט שיוצא מהמסגרת, גם לא תווית של גרף.',
		'לא עיגול עם טבעת סביב נקודה.',
		'לא קו שעוצר באמצע העמוד בלי סיבה.',
	], 2, 'bk-rules--2 bk-rules--big' );
	?>
<?php log_bk_close(); ?>

<section class="bk-page bk-page--night bk-remember" id="end" data-chapter="" aria-label="ארבעה דברים לזכור">
	<?php echo log_bk_photo( 'aerial-night.jpg', '', 'bk-photo--bleed', 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<span class="bk-cross" style="--x: 139; --y: 603" aria-hidden="true"><i class="bk-cross__v"></i><i class="bk-cross__h"></i><i class="bk-cross__dot"></i></span>
	<header class="bk-head">
		<p class="bk-kicker"><b>ספר המותג</b> · לסיום</p>
		<?php log_bk_title( 'ארבעה דברים לזכור' ); ?>
	</header>
	<?php
	log_bk_rows( [
		[ 'אמיתי', 'האנשים שלנו, באור טבעי, והפרויקטים שלנו.' ],
		[ 'קו אחד דק', 'אותו עובי לכל הקווים, עד הקצה.' ],
		[ 'כתום אחד', 'נקודה כתומה אחת בעמוד, על הדבר שחשוב.' ],
		[ 'מקום או אדם', 'צילום מקום עם טקסט מקבל שכבה שמאפירה. צילום אדם נשאר נקי, בצבע.' ],
	] );
	?>
</section>

<section class="bk-page bk-page--night bk-end" data-chapter="" aria-label="סוף הספר">
	<?php echo log_bk_photo( 'night-city.jpg', '', 'bk-photo--bleed', 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<span class="bk-cross" style="--x: 139; --y: 603" aria-hidden="true"><i class="bk-cross__v"></i><i class="bk-cross__h"></i><i class="bk-cross__dot"></i></span>
	<div class="bk-end__logo"><?php echo log_logo( [ 'variant' => 'night', 'tagline' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<?php // The way back to the cover: LOG's O with the road going up through it. src/brand/main.js drives there. ?>
	<a class="bk-again" href="#start">
		<?php echo log_circle( 'split', '<svg class="bk-again__arrow" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20V4M5.5 10.5 12 4l6.5 6.5"/></svg>', [ 'draw' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_circle() ?>
		<span class="bk-again__text"><b>חזרה להתחלה</b><small>אל השער של הספר</small></span>
	</a>
</section>
