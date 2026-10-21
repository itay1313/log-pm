<?php
/**
 * Cookie banner (spec v6 §6, §18): on the first visit, "אישור" and "דחייה" side by side at the same
 * weight, with a link to the policies. Measurement loads only after "אישור". The choice can be changed
 * from "הגדרות עוגיות" in the footer. On phones it sits low and never covers the whole map.
 */
$cookies = get_page_by_path( 'cookies' );
$privacy = get_privacy_policy_url();
?>
<div class="cookie-banner" role="region" aria-label="הסכמה לעוגיות" hidden>
	<p class="cookie-banner__text">
		<?php echo esc_html( log_text( 'cookie_text', 'אנחנו משתמשים בעוגיות מדידה כדי להבין איך משתמשים באתר. הן נטענות רק אם תאשרו.', 'option' ) ); ?>
		<?php if ( $cookies ) : ?><a href="<?php echo esc_url( get_permalink( $cookies ) ); ?>">מדיניות עוגיות</a><?php endif; ?>
		<?php if ( $privacy ) : ?> · <a href="<?php echo esc_url( $privacy ); ?>">מדיניות פרטיות</a><?php endif; ?>
	</p>
	<div class="cookie-banner__actions">
		<button type="button" class="btn" data-consent="yes">אישור</button>
		<button type="button" class="btn" data-consent="no">דחייה</button>
	</div>
</div>
