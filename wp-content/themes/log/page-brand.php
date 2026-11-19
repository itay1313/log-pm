<?php
/**
 * The web brand book (/brand). A shell of its own: no site header, footer, Lenis or map, only the brand
 * entry (src/brand/main.js) through wp_head()/wp_footer(). Every chapter is a template part in
 * template-parts/brand/ made of section.bk-page sheets, 1280×720 on screen and in print.
 * The page is password-protected and never indexed (log-core/inc/brand-book.php).
 */

defined( 'ABSPATH' ) || exit;

the_post();
?>
<!doctype html>
<html dir="rtl" lang="he">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bk' ); ?>>
<?php wp_body_open(); ?>
<main class="bk-book">
	<?php if ( post_password_required() ) : ?>
		<section class="bk-page bk-page--night bk-lock" aria-label="ספר המותג">
			<?php echo log_logo( [ 'variant' => 'night' ] ); ?>
			<?php log_bk_title( 'ספר המותג', 'h1', 'bk-title--cover' ); ?>
			<div class="bk-lock__form">
				<p>הספר פתוח למי שקיבל סיסמה מלוג.</p>
				<?php // Core's form, in Hebrew whatever the site language: same action and field names. ?>
				<form action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>" method="post">
					<input type="hidden" name="redirect_to" value="<?php echo esc_url( get_permalink() ); ?>">
					<p>
						<label for="bk-pass">סיסמה<input name="post_password" id="bk-pass" type="password" autocomplete="current-password" spellcheck="false" required></label>
						<input type="submit" name="Submit" value="פתיחת הספר">
					</p>
				</form>
			</div>
		</section>
	<?php else : ?>
		<?php
		foreach ( [ 'cover', 'junction', 'color-type', 'line' ] as $chapter ) {
			get_template_part( 'template-parts/brand/' . $chapter );
		}
		?>
	<?php endif; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
