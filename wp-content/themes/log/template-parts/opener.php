<?php
/**
 * The page opener: every inner page opens the way the brand book opens a chapter, and the way the home page's first
 * chapter and the menu do. One thin line crosses the window. The title stands heavy on it; the page's number is
 * drawn huge in outline behind it, cut by the line; a second line cuts straight through, the dot where they meet.
 * The line is grey from the start side and turns orange at the crossing: under the grey stands the text, under the
 * orange what the page counts (how many projects, a grid reference, a job number).
 *
 * $args:
 *   title   escaped HTML for the h1: log_title( $text, true ) for a headline, the bare name for a project or a job
 *   n       the figure drawn in outline; the page's number in the header order by default, '' for none
 *   kicker  callable: what stands over the title (the way back)
 *   body    callable: what stands under the line on the start side (a lede, tags, actions)
 *   side    callable: what stands under the orange stretch (a count, a reference)
 *   media   callable: the place behind the sheet (a photograph, a film). The book greys a place that carries text
 *           and gives it one even layer; the sheet does both.
 *   class   opener--compact (one item of a list, a search, a plain page) · opener--tall (a full window)
 *   tag     header (default) or div, when the opener sits inside a header of its own
 */
$n      = $args['n'] ?? null;
$n      = $n ?? ( ( $r = log_route_number() ) ? sprintf( '%02d', $r ) : '' );
$tag    = ( $args['tag'] ?? 'header' ) === 'div' ? 'div' : 'header';
$call   = static function ( string $key ) use ( $args ): string {
	if ( empty( $args[ $key ] ) || ! is_callable( $args[ $key ] ) ) {
		return '';
	}
	ob_start();
	$args[ $key ]();
	return trim( ob_get_clean() );
};
$kicker = $call( 'kicker' );
$body   = $call( 'body' );
$side   = $call( 'side' );
$media  = $call( 'media' );
$class  = trim( 'opener ' . ( $media ? 'opener--place ' : '' ) . ( $side ? '' : 'opener--alone ' ) . ( $args['class'] ?? '' ) );
?>
<<?php echo $tag; ?> class="<?php echo esc_attr( $class ); ?>">
	<?php if ( $media ) : ?><div class="opener__place"><?php echo $media; // phpcs:ignore WordPress.Security.EscapeOutput -- printed by the template's own callable ?></div><?php endif; ?>
	<div class="opener__top container">
		<?php if ( $n !== '' ) : ?><p class="opener__n" aria-hidden="true"><?php echo esc_html( $n ); ?></p><?php endif; ?>
		<?php if ( $kicker ) : ?><div class="opener__kicker"><?php echo $kicker; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
		<h1 class="opener__title"><?php echo $args['title'] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by the caller (log_title(), esc_html()) ?></h1>
	</div>
	<div class="opener__band container">
		<span class="opener__cross" aria-hidden="true"><i class="opener__h"></i><i class="opener__v"></i><i class="opener__dot"></i></span>
		<div class="opener__text"><?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<?php if ( $side ) : ?><div class="opener__side"><?php echo $side; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
	</div>
</<?php echo $tag; ?>>
