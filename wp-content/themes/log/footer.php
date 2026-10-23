</main>

<?php
/**
 * Footer (spec v6 §6): logo and a line about LOG, links to every level-2 page, both offices,
 * phone and email, LinkedIn and Instagram, accessibility statement, privacy and cookie policies,
 * cookie settings, copyright.
 */
$legal = array_filter( [
	'הצהרת נגישות'  => get_page_by_path( 'accessibility' ),
	'מדיניות פרטיות' => get_page_by_path( 'privacy' ),
	'מדיניות עוגיות' => get_page_by_path( 'cookies' ),
] );
?>
<footer class="site-footer">
	<div class="site-footer__inner">
		<?php if ( ! is_page( 'contact' ) ) : ?>
			<a class="site-footer__cta" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><span class="site-footer__cta-text"><?php echo esc_html( log_text( 'header_cta', 'בואו נדבר', 'option' ) ); ?></span><span class="site-footer__go" aria-hidden="true"><i class="arrow"></i></span></a>
		<?php endif; ?>

		<?php // The destination board: every level-2 page as a row of the last guide sign, with what lies down each road. ?>
		<nav class="site-footer__board" aria-labelledby="footer-board-label">
			<p class="site-footer__ask" id="footer-board-label">לאן עכשיו?</p>
			<ul>
				<?php $distances = log_nav_distances(); foreach ( log_nav_items() as $i => [ $label, $url ] ) : ?>
					<li><a href="<?php echo esc_url( $url ); ?>"><?php echo log_route_shield( $i + 1 ); ?><span class="site-footer__dest"><?php echo esc_html( $label ); ?></span><span class="site-footer__dist"><?php echo esc_html( $distances[ $i ] ?? '' ); ?></span><i class="arrow" aria-hidden="true"></i></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="site-footer__side">
			<div class="site-footer__brand">
				<?php get_template_part( 'template-parts/logo' ); ?>
				<p class="site-footer__line"><?php echo esc_html( log_text( 'footer_line', 'מתכננים. מחברים. מובילים.', 'option' ) ); ?></p>
			</div>
			<?php if ( $offices = log_offices() ) : ?>
				<div class="site-footer__offices">
					<?php foreach ( $offices as $office ) { get_template_part( 'template-parts/office', null, $office ); } ?>
				</div>
			<?php endif; ?>
			<div class="site-footer__contact">
				<?php if ( $email = log_option( 'email' ) ) : ?><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a><?php endif; ?>
				<ul class="social">
					<?php foreach ( [ 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram' ] as $key => $label ) :
						if ( $url = log_option( $key ) ) : ?><li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $label ); ?></a></li><?php endif;
					endforeach; ?>
				</ul>
			</div>
		</div>

		<?php // The road ends here: the lane line turns and runs across, a stop line under the last sign. ?>
		<span class="site-footer__end" aria-hidden="true"></span>
		<nav class="site-footer__nav" aria-label="מדיניות ונגישות">
			<ul class="site-footer__list">
				<?php foreach ( $legal as $label => $p ) : ?><li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( $label ); ?></a></li><?php endforeach; ?>
				<li><button type="button" class="link-button" data-cookie-settings>הגדרות עוגיות</button></li>
			</ul>
			<p class="site-footer__copy">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> לוג ניהול פרויקטים בע"מ</p>
		</nav>
	</div>
</footer>

<?php get_template_part( 'template-parts/cookie-banner' ); ?>
<?php wp_footer(); ?>
</body>
</html>
