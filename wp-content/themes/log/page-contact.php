<?php
/**
 * Contact (spec v6 §15): a title only, the two offices with LinkedIn and Instagram, and one form.
 * Topic and project arrive prefilled from the address (?topic=control, ?project={slug}) and stay editable.
 * After sending, a success message replaces the form, with a way back home or to the project.
 */
get_header();
the_post();
$topics  = log_contact_topics();
$topic   = sanitize_key( $_GET['topic'] ?? '' );
$topic   = isset( $topics[ $topic ] ) ? $topic : '';
$project = log_project_from_param( wp_unslash( $_GET['project'] ?? '' ) );
$privacy = get_privacy_policy_url();
?>
<header class="page-header">
	<h1 class="page-header__title"><?php echo esc_html( log_text( 'hero_title', 'דברו איתנו' ) ); ?></h1>
</header>

<div class="container contact">
	<div class="contact__form">
		<form class="form form--contact" data-endpoint="contact" novalidate>
			<?php get_template_part( 'template-parts/form-fields' ); ?>
			<input type="hidden" name="project" value="<?php echo $project ? esc_attr( rawurldecode( get_post_field( 'post_name', $project ) ) ) : ''; ?>">
			<?php if ( $project ) : ?>
				<p class="form__context">בנוגע לפרויקט <strong><?php echo esc_html( get_the_title( $project ) ); ?></strong> <button type="button" class="form__context-clear">הסרה</button></p>
			<?php endif; ?>
			<div class="form__grid">
				<label class="field"><span>שם</span><input type="text" name="name" required autocomplete="name"></label>
				<label class="field"><span>ארגון <small>(לא חובה)</small></span><input type="text" name="org" autocomplete="organization"></label>
				<label class="field"><span>טלפון</span><input type="tel" name="phone" required autocomplete="tel" inputmode="tel"></label>
				<label class="field"><span>מייל</span><input type="email" name="email" required autocomplete="email"></label>
				<label class="field field--wide"><span>נושא</span>
					<select name="topic" required>
						<option value="" disabled<?php selected( $topic ?: ( $project ? 'business' : '' ), '' ); ?>>בחרו נושא</option>
						<?php foreach ( $topics as $key => [ $label ] ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $topic ?: ( $project ? 'business' : '' ), $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="field field--wide"><span>הודעה <small>(לא חובה)</small></span><textarea name="message" rows="5"></textarea></label>
			</div>
			<label class="check"><input type="checkbox" name="consent" value="1" required> קראתי את <a href="<?php echo esc_url( $privacy ); ?>" target="_blank" rel="noopener">מדיניות הפרטיות</a> ומאשר/ת שתחזרו אליי.</label>
			<p class="form__error" role="alert" hidden></p>
			<button class="btn btn--primary" type="submit">שליחה</button>
			<p class="form__success" hidden></p>
			<p class="form__after" hidden>
				<?php if ( $project && log_project_has_page( $project ) ) : ?>
					<a class="link-more" href="<?php echo esc_url( get_permalink( $project ) ); ?>">חזרה לפרויקט <?php echo esc_html( get_the_title( $project ) ); ?></a>
				<?php else : ?>
					<a class="link-more" href="<?php echo esc_url( home_url( '/' ) ); ?>">חזרה לעמוד הבית</a>
				<?php endif; ?>
			</p>
		</form>
	</div>

	<aside class="contact__side">
		<?php foreach ( log_offices() as $office ) { get_template_part( 'template-parts/office', null, $office ); } ?>
		<ul class="social">
			<?php foreach ( [ 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram' ] as $key => $label ) :
				if ( $url = log_option( $key ) ) : ?><li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $label ); ?></a></li><?php endif;
			endforeach; ?>
		</ul>
	</aside>
</div>
<?php get_footer();
