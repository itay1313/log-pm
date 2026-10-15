<?php
/**
 * Job application form (spec §13). $args['job'] = job post ID, or 0 for a general application.
 */
$job = (int) ( $args['job'] ?? 0 );
$privacy = get_privacy_policy_url();
?>
<form class="form form--apply" data-endpoint="apply" enctype="multipart/form-data" novalidate>
	<?php get_template_part( 'template-parts/form-fields' ); ?>
	<input type="hidden" name="job" value="<?php echo $job; ?>">
	<div class="form__grid">
		<label class="field"><span>שם מלא</span><input type="text" name="name" required autocomplete="name"></label>
		<label class="field"><span>טלפון</span><input type="tel" name="phone" required autocomplete="tel" inputmode="tel"></label>
		<label class="field"><span>מייל</span><input type="email" name="email" required autocomplete="email"></label>
		<label class="field"><span>LinkedIn <small>(לא חובה)</small></span><input type="url" name="linkedin" placeholder="https://www.linkedin.com/in/…"></label>
		<label class="field field--wide"><span>קורות חיים <small>PDF או Word, עד 5MB</small></span><input type="file" name="cv" accept=".pdf,.doc,.docx" required></label>
		<label class="field field--wide"><span>איך שמעתם עלינו? <small>(לא חובה)</small></span><input type="text" name="heard"></label>
	</div>
	<label class="check"><input type="checkbox" name="consent" value="1" required> קראתי את <a href="<?php echo esc_url( $privacy ); ?>" target="_blank" rel="noopener">מדיניות הפרטיות</a> ומאשר/ת שימוש בפרטים לצורך הגיוס.</label>
	<label class="check"><input type="checkbox" name="keep" value="1"> אפשר לשמור את קורות החיים שלי למשרות עתידיות.</label>
	<p class="form__error" role="alert" hidden></p>
	<button class="btn btn--primary" type="submit">שליחת מועמדות</button>
	<p class="form__success" hidden></p>
</form>
