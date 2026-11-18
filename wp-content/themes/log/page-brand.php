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
			<p class="bk-kicker">לוג ניהול פרויקטים</p>
			<?php log_bk_title( 'ספר המותג', 'h1', 'bk-title--cover' ); ?>
			<div class="bk-lock__form">
				<p>הספר פתוח למי שקיבל סיסמה מלוג.</p>
				<?php echo get_the_password_form(); // phpcs:ignore WordPress.Security.EscapeOutput -- core markup ?>
			</div>
		</section>
	<?php else : ?>
		<?php
		foreach ( [ 'cover', 'junction', 'color-type' ] as $chapter ) {
			get_template_part( 'template-parts/brand/' . $chapter );
		}
		?>
	<?php endif; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
