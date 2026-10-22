<?php
/**
 * The sheet's title block: title, optional meta line and lede, and the sheet label in the end margin.
 * $args: title, lede, meta (legacy: eyebrow).
 */
$title = $args['title'] ?? get_the_title();
$lede  = $args['lede'] ?? '';
$meta  = $args['meta'] ?? ( $args['eyebrow'] ?? '' );
?>
<header class="page-header">
	<h1 class="page-header__title"><?php echo log_route_shield(); ?><?php echo esc_html( $title ); ?></h1>
	<?php if ( $meta ) : ?><p class="page-header__meta"><?php echo esc_html( $meta ); ?></p><?php endif; ?>
	<?php if ( $lede ) : ?><p class="page-header__lede"><?php echo esc_html( $lede ); ?></p><?php endif; ?>
	<?php echo log_sheet_label(); ?>
</header>
