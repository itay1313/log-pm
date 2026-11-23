<?php
/**
 * A plain page's opener: the title, an optional meta line and lede, and the way back.
 * $args: title, lede, meta (legacy: eyebrow), back ([ url, label ]), class (opener classes; compact by default).
 */
$title = $args['title'] ?? get_the_title();
$lede  = $args['lede'] ?? '';
$meta  = $args['meta'] ?? ( $args['eyebrow'] ?? '' );
$back  = $args['back'] ?? null;
get_template_part( 'template-parts/opener', null, [
	'title'  => log_title( wp_strip_all_tags( $title ), true ),
	'class'  => $args['class'] ?? 'opener--compact',
	'kicker' => $back ? static function () use ( $back ) {
		printf( '<a class="back-link" href="%s" data-back>%s</a>', esc_url( $back[0] ), esc_html( $back[1] ) );
	} : null,
	'body'   => static function () use ( $meta, $lede ) {
		if ( $meta ) {
			printf( '<p class="opener__meta">%s</p>', esc_html( $meta ) );
		}
		if ( $lede ) {
			printf( '<p class="opener__lede">%s</p>', esc_html( $lede ) );
		}
	},
] );
