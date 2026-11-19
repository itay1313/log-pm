<?php
/**
 * Team card (spec v6 §12): photo in the split circle, name, role. No personal page, no popup, no link.
 * data-unit feeds the unit tabs on the about page.
 */
$id    = get_the_ID();
$units = get_the_terms( $id, 'log_unit' );
?>
<article class="person" data-unit="<?php echo esc_attr( $units && ! is_wp_error( $units ) ? implode( ' ', wp_list_pluck( $units, 'slug' ) ) : '' ); ?>">
	<figure class="person__photo">
		<?php
		// The book frames a person in the split circle, in natural colour. Without a photo yet, the circle holds initials.
		$inner = has_post_thumbnail()
			? get_the_post_thumbnail( $id, 'log-card', [ 'loading' => 'lazy' ] )
			: esc_html( implode( '', array_map( static fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/u', trim( get_the_title() ) ), 0, 2 ) ) ) );
		echo log_circle( 'split', $inner ); // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_circle(), $inner escaped above
		?>
	</figure>
	<div class="person__body">
		<h3 class="person__name"><?php the_title(); ?></h3>
		<?php if ( $role = log_field( 'role', $id ) ) : ?><p class="person__role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
	</div>
</article>
