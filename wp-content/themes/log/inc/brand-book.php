<?php
/**
 * Small helpers for the web brand book at /brand (page-brand.php, template-parts/brand/*).
 * The book is a stack of 1280×720 pages; each page sets one headline, one thin line and one orange dot,
 * and the dot is the headline's own full stop.
 */

defined( 'ABSPATH' ) || exit;

/** A page headline. $text comes without the full stop; the stop is added as the page's orange dot. */
function log_bk_title( string $text, string $tag = 'h2', string $class = '' ): void {
	printf(
		'<%1$s class="bk-title %2$s">%3$s<span class="bk-dot">.</span></%1$s>',
		tag_escape( $tag ),
		esc_attr( $class ),
		wp_kses( $text, [ 'br' => [] ] )
	);
}

/** The folio under every inner page: the book's name, the chapter, the page number (a CSS counter). */
function log_bk_folio( string $chapter ): void {
	printf(
		'<footer class="bk-folio"><span>לוג · ספר המותג</span><span class="bk-folio__ch">%s</span><span class="bk-folio__n" aria-hidden="true"></span></footer>',
		esc_html( $chapter )
	);
}

/**
 * One colour swatch. Only the token's name is written here: its value and contrast are read from the
 * live CSS custom properties by src/brand/main.js, so the book can never disagree with the site.
 * $against: a token to measure text contrast against (the page's ground), or '' for none.
 */
function log_bk_swatch( string $token, string $role, string $against = '' ): void {
	?>
	<figure class="bk-swatch" style="--sw: var(--<?php echo esc_attr( $token ); ?>)">
		<span class="bk-swatch__chip"></span>
		<figcaption>
			<code class="bk-token">--<?php echo esc_html( $token ); ?></code>
			<span class="bk-swatch__hex" data-token-value="--<?php echo esc_attr( $token ); ?>"></span>
			<span class="bk-swatch__role"><?php echo esc_html( $role ); ?></span>
			<?php if ( $against ) : ?>
				<span class="bk-swatch__ratio" data-contrast="--<?php echo esc_attr( $token ); ?> --<?php echo esc_attr( $against ); ?>"></span>
			<?php endif; ?>
		</figcaption>
	</figure>
	<?php
}
