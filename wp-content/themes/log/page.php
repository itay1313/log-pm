<?php
/**
 * A plain page: the legal pages (accessibility, privacy, cookies) and any page without a template of its own.
 * The text stands in the content column, the way a section's rows do. A page with several parts gets their index
 * in the start column: each h2 in the text becomes an anchor, numbered the way the home road's index is.
 */
get_header();
while ( have_posts() ) : the_post();
	get_template_part( 'template-parts/page-header' );

	$index   = [];
	$content = preg_replace_callback( '~<h2\b([^>]*)>(.*?)</h2>~is', static function ( array $m ) use ( &$index ): string {
		$own     = preg_match( '~\bid="([^"]+)"~', $m[1], $has ) ? $has[1] : '';
		$anchor  = $own ?: 'part-' . ( count( $index ) + 1 );
		$index[] = [ $anchor, wp_strip_all_tags( $m[2] ) ];
		return $own ? $m[0] : '<h2' . $m[1] . ' id="' . esc_attr( $anchor ) . '">' . $m[2] . '</h2>';
	}, str_replace( ']]>', ']]&gt;', apply_filters( 'the_content', get_the_content() ) ) );
	?>
	<div class="container page-body">
		<?php if ( count( $index ) > 2 ) : ?>
			<nav class="page-index" aria-label="בעמוד הזה">
				<ol>
					<?php foreach ( $index as $i => [ $anchor, $label ] ) : ?>
						<li><a href="#<?php echo esc_attr( $anchor ); ?>"><span aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			</nav>
		<?php endif; ?>
		<div class="prose"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput -- the page's own content, through the_content's filters ?></div>
	</div>
	<?php
endwhile;
get_footer();
