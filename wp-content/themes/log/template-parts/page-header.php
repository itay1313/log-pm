<?php
/**
 * Shared page header: short orange rule + title + optional lede.
 * $args: title, lede, eyebrow.
 */
$title   = $args['title'] ?? get_the_title();
$lede    = $args['lede'] ?? '';
$eyebrow = $args['eyebrow'] ?? '';
?>
<header class="page-header">
	<?php if ( $eyebrow ) : ?><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
	<h1 class="page-header__title"><?php echo esc_html( $title ); ?></h1>
	<?php if ( $lede ) : ?><p class="page-header__lede"><?php echo esc_html( $lede ); ?></p><?php endif; ?>
</header>
