<?php
/**
 * Contact (spec §14): the two offices, phone/email, socials, and one form routed by topic.
 * ?topic=jobs swaps the contact form for the application form; ?project=ID pre-links a project.
 */
get_header();
$topic   = sanitize_key( $_GET['topic'] ?? '' );
$project = (int) ( $_GET['project'] ?? 0 );
$job     = (int) ( $_GET['job'] ?? 0 );
$privacy = get_privacy_policy_url();
$offices = [
	[ 'תל אביב', log_option( 'tlv_address', 'רח\' הארבעה 18 א\', תל אביב-יפו' ), log_option( 'tlv_phone', '03-5121551' ), log_option( 'tlv_nav_url' ) ],
	[ 'ירושלים', log_option( 'jlm_address', 'גבעת שאול, ירושלים' ), log_option( 'jlm_phone' ), log_option( 'jlm_nav_url' ) ],
];
?>
<?php get_template_part( 'template-parts/page-header', null, [
	'title' => $topic === 'jobs' ? 'מצאתם את התפקיד שלכם?' : 'נשמח לדבר איתכם.',
	'lede'  => $topic === 'jobs' ? 'שלחו קורות חיים ונחזור אליכם אם יש התאמה.' : 'שני משרדים, טלפון אחד, ומייל שמישהו באמת קורא.',
] ); ?>

<div class="container contact">
	<div class="contact__form">
		<?php if ( $topic === 'jobs' ) : ?>
			<?php get_template_part( 'template-parts/form-apply', null, [ 'job' => $job ] ); ?>
			<p class="contact__switch"><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">לא מועמדות? לטופס הפנייה הרגיל</a></p>
		<?php else : ?>
			<form class="form form--contact" data-endpoint="contact" novalidate>
				<?php get_template_part( 'template-parts/form-fields' ); ?>
				<input type="hidden" name="project" value="<?php echo $project; ?>">
				<?php if ( $project && get_post_type( $project ) === 'log_project' ) : ?>
					<p class="form__context">בנוגע לפרויקט <strong><?php echo esc_html( get_the_title( $project ) ); ?></strong></p>
				<?php endif; ?>
				<div class="form__grid">
					<label class="field"><span>שם</span><input type="text" name="name" required autocomplete="name"></label>
					<label class="field"><span>ארגון <small>(לא חובה)</small></span><input type="text" name="org" autocomplete="organization"></label>
					<label class="field"><span>טלפון <small>(לא חובה)</small></span><input type="tel" name="phone" autocomplete="tel" inputmode="tel"></label>
					<label class="field"><span>מייל</span><input type="email" name="email" required autocomplete="email"></label>
					<label class="field field--wide"><span>נושא</span>
						<select name="topic" required>
							<?php foreach ( log_contact_topics() as $key => [ $label ] ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $topic ?: ( $project ? 'business' : '' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="field field--wide"><span>הודעה</span><textarea name="message" rows="5" required></textarea></label>
				</div>
				<label class="check"><input type="checkbox" name="consent" value="1" required> קראתי את <a href="<?php echo esc_url( $privacy ); ?>" target="_blank" rel="noopener">מדיניות הפרטיות</a> ומאשר/ת שתחזרו אליי.</label>
				<p class="form__error" role="alert" hidden></p>
				<button class="btn btn--primary" type="submit">שליחה</button>
				<p class="form__success" hidden></p>
			</form>
		<?php endif; ?>
	</div>

	<aside class="contact__side">
		<?php foreach ( $offices as [ $name, $address, $phone, $nav ] ) : if ( ! $address && ! $phone ) { continue; } ?>
			<address class="office">
				<strong class="office__name"><?php echo esc_html( $name ); ?></strong>
				<?php if ( $address ) : ?><span class="office__address"><?php echo esc_html( $address ); ?></span><?php endif; ?>
				<?php if ( $phone ) : ?><a class="office__phone" href="tel:<?php echo esc_attr( preg_replace( '/\D/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
				<?php if ( $nav ) : ?><a class="office__nav" href="<?php echo esc_url( $nav ); ?>" rel="noopener" target="_blank">ניווט</a><?php endif; ?>
			</address>
		<?php endforeach; ?>
		<?php if ( $email = log_option( 'email', 'info@log-pm.co.il' ) ) : ?>
			<p class="contact__email"><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p>
		<?php endif; ?>
		<ul class="social">
			<?php foreach ( [ 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn' ] as $key => $label ) :
				if ( $url = log_option( $key ) ) : ?><li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $label ); ?></a></li><?php endif;
			endforeach; ?>
		</ul>
	</aside>
</div>
<?php get_footer();
