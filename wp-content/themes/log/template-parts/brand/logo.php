<?php
/**
 * Chapter 04, the logo: as it is, what the language takes from it, and what is never done to it.
 * Every logo here is log_logo(), the same file the site's header draws.
 */

$wrong = [
	[ 'recolor', 'לא צובעים', 'לא מחליפים את הכתום והאפור, גם לא כדי להתאים לרקע.' ],
	[ 'stretch', 'לא מותחים', 'רוחב וגובה משתנים יחד, תמיד.' ],
	[ 'busy', 'לא על צילום עמוס', 'על צילום מקום: שכבה שקופה שמאפירה, ואז הגרסה הלבנה.' ],
	[ 'rotate', 'לא מסובבים', 'הלוגו תמיד ישר, גם כשהקווים סביבו באלכסון.' ],
];
?>
<?php log_bk_open( '04', 'הלוגו כמו שהוא', 'bk-logo' ); ?>
	<?php log_bk_head( '04', 'הלוגו כמו שהוא', 'שלוש גרסאות לשימוש.' ); ?>
	<ul class="bk-logo__versions">
		<li>
			<div class="bk-frame bk-frame--light"><?php echo log_logo( [ 'variant' => 'color', 'tagline' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<h3>צבע על בהיר</h3><p>הגרסה הראשית. רקע נייר או לבן.</p>
		</li>
		<li>
			<div class="bk-frame bk-frame--night"><?php echo log_logo( [ 'variant' => 'night', 'tagline' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<h3>לבן וכתום על כהה</h3><p>על רקע אספלט או על צילום אוויר כהה.</p>
		</li>
		<li>
			<div class="bk-frame bk-frame--light bk-frame--mark"><span class="bk-logo__clear"><?php echo log_logo( [ 'variant' => 'color' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?><small>מרווח סביב: חצי מגובה הסימן</small></span></div>
			<h3>סימן בלבד</h3><p>בפוטר של שקף ופוסט, קטן, בפינה השמאלית התחתונה.</p>
		</li>
	</ul>
	<?php
	log_bk_rules( [
		'גודל מינימום לסימן: 40 פיקסלים רוחב במסך, 12 מ"מ בדפוס.',
		'שורת השם רק מרוחב 90 פיקסלים ומעלה. מתחת לזה, סימן בלבד.',
		'על צילום מקום: רק מעל שכבה שקופה שמאפירה.',
		'לא מגדילים מעבר לרזולוציה של הקובץ. משתמשים בקבצים מתיקיית המותג.',
	], -1, 'bk-rules--2' );
	?>
<?php log_bk_close(); ?>

<?php log_bk_open( '04', 'מהלוגו לשפה', 'bk-anatomy bk-page--hold' ); ?>
	<?php log_bk_head( '04', 'מהלוגו לשפה', 'מהלוגו לוקחים את החיתוך ואת החלפת הצבע. לא את העובי: בשפה הקווים דקים.' ); ?>
	<div class="bk-anatomy__stage">
		<div class="bk-anatomy__logo"><?php echo log_logo( [ 'variant' => 'color' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<dl class="bk-anatomy__notes">
			<div><dt><?php echo log_line( 'dot', [ 'draw' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>הצורה נחתכת.</dt><dd>ה־O בנוי מארבעה רבעים עם חיתוך ישר ביניהם. מכאן העיגול המפוצל.</dd></div>
			<div><dt><?php echo log_line( 'dot', [ 'draw' => '' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>הצבע מתחלף באמצע.</dt><dd>אפור הופך לכתום בתוך אותה צורה. מכאן הקו שמחליף צבע.</dd></div>
		</dl>
		<div class="bk-anatomy__language">
			<p class="bk-cap">בשפה, בעובי של 2 פיקסלים:</p>
			<?php echo log_circle( 'split' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo log_line( 'switch', [ 'at' => 45 ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '04', 'שימוש נכון בלוגו', 'bk-wrong' ); ?>
	<?php log_bk_head( '04', 'שימוש נכון בלוגו', 'מה לא עושים. המרווח והגודל המינימלי בעמוד הלוגו.' ); ?>
	<ul class="bk-wrong__grid">
		<?php foreach ( $wrong as [ $how, $name, $text ] ) : ?>
			<li>
				<div class="bk-frame bk-frame--light bk-wrong__case bk-wrong__case--<?php echo esc_attr( $how ); ?>">
					<i class="bk-wrong__x" aria-hidden="true"></i>
					<?php
					if ( $how === 'busy' ) {
						echo log_bk_photo( 'aerial.jpg', '', 'bk-photo--bleed' ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					echo log_logo( [ 'variant' => 'color', 'tagline' => $how !== 'busy' ] ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
				<h3><?php echo esc_html( $name ); ?></h3><p><?php echo esc_html( $text ); ?></p>
			</li>
		<?php endforeach; ?>
	</ul>
<?php log_bk_close(); ?>
