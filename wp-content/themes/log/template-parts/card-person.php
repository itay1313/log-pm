<?php
/**
 * Team card (spec v6 §12): photo, name, role. No personal page, no popup, no link.
 * data-unit feeds the unit tabs on the about page.
 */
$id    = get_the_ID();
$units = get_the_terms( $id, 'log_unit' );
?>
<article class="person" data-unit="<?php echo esc_attr( $units && ! is_wp_error( $units ) ? implode( ' ', wp_list_pluck( $units, 'slug' ) ) : '' ); ?>">
	<figure class="person__photo">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( 'log-card', [ 'loading' => 'lazy' ] ); ?>
		<?php else : ?>
			<span class="person__placeholder" aria-hidden="true"><?php echo esc_html( implode( '', array_map( static fn( $w ) => mb_substr( $w, 0, 1 ), array_slice( preg_split( '/\s+/u', trim( get_the_title() ) ), 0, 2 ) ) ) ); ?></span>
		<?php endif; ?>
	</figure>
	<div class="person__body">
		<h3 class="person__name"><?php the_title(); ?></h3>
		<?php if ( $role = log_field( 'role', $id ) ) : ?><p class="person__role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
	</div>
</article>
