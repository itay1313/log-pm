<?php
/**
 * The two twin buttons (spec v6 §6): "ניהול פרויקטים" ↔ "חברת הבקרה", set as a lane split: two lanes
 * leaving the same road, each with its own arrow. Marked only inside its own world. They hang in the
 * header on desktop and ride the bottom edge of the screen on phones, so both worlds are one tap away.
 * $args['sign']: set as a standing lane-split sign of two plates (the menu).
 */
$state  = log_twin_state();
$worlds = [
	'projects' => [ get_post_type_archive_link( 'log_project' ), log_text( 'twin_projects', 'ניהול פרויקטים', 'option' ), log_text( 'twin_projects_line', 'תכנון וניהול ברמת הרשות והמתחם', 'option' ) ],
	'control'  => [ home_url( '/control/' ), log_text( 'twin_control', 'חברת הבקרה', 'option' ), log_text( 'twin_control_line', 'הבקרה הלאומית בהתחדשות עירונית', 'option' ) ],
];
?>
<nav class="twin-nav<?php echo empty( $args['sign'] ) ? '' : ' twin-nav--sign'; ?>" aria-label="שני העולמות של לוג">
	<?php foreach ( $worlds as $key => [ $url, $name, $line ] ) : $on = $state === $key; ?>
		<a class="twin-nav__item twin-nav__item--<?php echo esc_attr( $key ); ?><?php echo $on ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>>
			<span class="twin-nav__text"><span class="twin-nav__name"><?php echo esc_html( $name ); ?></span><?php if ( $line ) : ?><span class="twin-nav__line"><?php echo esc_html( $line ); ?></span><?php endif; ?></span>
			<i class="arrow arrow--lane" aria-hidden="true"></i>
		</a>
	<?php endforeach; ?>
</nav>
