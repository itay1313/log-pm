<?php
/**
 * One office: name, address, phone, email, and a navigation link (opens a navigation app or a map).
 * $args: an item from log_offices().
 */
$o = $args;
?>
<address class="office">
	<strong class="office__name"><?php echo esc_html( $o['name'] ); ?></strong>
	<?php if ( $o['address'] ) : ?><span class="office__address"><?php echo esc_html( $o['address'] ); ?></span><?php endif; ?>
	<?php if ( $o['phone'] ) : ?><a class="office__phone" href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $o['phone'] ) ); ?>"><?php echo esc_html( $o['phone'] ); ?></a><?php endif; ?>
	<?php if ( $o['email'] ) : ?><a class="office__email" href="mailto:<?php echo esc_attr( $o['email'] ); ?>"><?php echo esc_html( $o['email'] ); ?></a><?php endif; ?>
	<?php
	$nav = $o['nav'] ?: ( $o['address'] ? 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode( $o['address'] ) : '' );
	if ( $nav ) : ?><a class="office__nav" href="<?php echo esc_url( $nav ); ?>" rel="noopener" target="_blank">ניווט</a><?php endif; ?>
</address>
