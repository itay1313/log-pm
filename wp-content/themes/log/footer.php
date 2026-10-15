</main>

<footer class="site-footer">
	<div class="site-footer__inner">
		<div class="site-footer__brand">
			<?php get_template_part( 'template-parts/logo' ); ?>
			<p class="site-footer__line">מתכננים. מחברים. מובילים.</p>
		</div>

		<?php
		$offices = [
			[ 'תל אביב', log_option( 'tlv_address' ), log_option( 'tlv_phone' ), log_option( 'tlv_nav_url' ) ],
			[ 'ירושלים', log_option( 'jlm_address' ), log_option( 'jlm_phone' ), log_option( 'jlm_nav_url' ) ],
		];
		foreach ( $offices as [ $name, $address, $phone, $nav ] ) :
			if ( ! $address && ! $phone ) {
				continue;
			}
			?>
			<address class="office">
				<strong class="office__name"><?php echo esc_html( $name ); ?></strong>
				<?php if ( $address ) : ?><span class="office__address"><?php echo esc_html( $address ); ?></span><?php endif; ?>
				<?php if ( $phone ) : ?><a class="office__phone" href="tel:<?php echo esc_attr( preg_replace( '/\D/', '', $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a><?php endif; ?>
				<?php if ( $nav ) : ?><a class="office__nav" href="<?php echo esc_url( $nav ); ?>" rel="noopener" target="_blank">ניווט</a><?php endif; ?>
			</address>
		<?php endforeach; ?>

		<div class="site-footer__contact">
			<?php if ( $email = log_option( 'email' ) ) : ?>
				<a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a>
			<?php endif; ?>
			<ul class="social">
				<?php foreach ( [ 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn' ] as $key => $label ) :
					if ( $url = log_option( $key ) ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $label ); ?></a></li>
					<?php endif;
				endforeach; ?>
			</ul>
		</div>

		<nav class="site-footer__nav" aria-label="קישורים משפטיים">
			<?php wp_nav_menu( [
				'theme_location' => 'footer',
				'container'      => false,
				'menu_class'     => 'site-footer__list',
				'fallback_cb'    => false,
				'depth'          => 1,
			] ); ?>
			<p class="site-footer__copy">© <?php echo esc_html( date_i18n( 'Y' ) ); ?> לוג ניהול פרויקטים בע"מ</p>
		</nav>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
