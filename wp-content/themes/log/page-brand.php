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
	<?php // The opening veil (brand.css, src/brand/main.js) is asked for here, before the first paint. If the script never takes over, it lets go by itself. ?>
	<script>(function(h){if(matchMedia('(prefers-reduced-motion: reduce)').matches)return;h.classList.add('bk-loading');setTimeout(function(){if(!h.classList.contains('bk-opening'))h.classList.remove('bk-loading')},6000)})(document.documentElement)</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bk' ); ?>>
<?php wp_body_open(); ?>
<div class="bk-veil<?php echo post_password_required() ? ' bk-veil--centre' : ''; ?>" aria-hidden="true"><i class="bk-veil__half"></i><i class="bk-veil__half"></i><i class="bk-veil__v"></i><i class="bk-veil__h"></i><?php
	// LOG's O: four quarters on r=49, cut where the logo cuts it (the geometry of log_circle( 'split' )).
	$log_bk_pt = static fn( float $deg ) => sprintf( '%.3f %.3f', 50 + 49 * sin( deg2rad( $deg ) ), 50 - 49 * cos( deg2rad( $deg ) ) );
	echo '<svg class="bk-veil__o" viewBox="0 0 100 100" focusable="false">';
	foreach ( [ 0, 90, 180, 270 ] as $log_bk_from ) {
		printf( '<path d="M%s A49 49 0 0 1 %s"/>', $log_bk_pt( $log_bk_from + 11 ), $log_bk_pt( $log_bk_from + 79 ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}
	echo '</svg>';
?><i class="bk-veil__dot"></i><?php if ( ! post_password_required() ) : ?><div class="bk-veil__logo"><?php echo log_logo( [ 'variant' => 'night', 'tagline' => true ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?></div>
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
		foreach ( [ 'cover', 'idea', 'logo', 'color-type', 'line', 'visual', 'social', 'closing' ] as $chapter ) {
			get_template_part( 'template-parts/brand/' . $chapter );
		}
		?>
	<?php endif; ?>
</main>
<?php wp_footer(); ?>
</body>
</html>
