<?php
/**
 * The two twin buttons (spec v6 §6): "ניהול פרויקטים" ↔ "חברת הבקרה", set as a lane split: two lanes
 * leaving the same road. Marked only inside its own world. They hang in the header on desktop and ride
 * the bottom edge of the screen on phones, so both worlds are one tap away.
 *
 * In the header the split is drawn once, between the two names: one thin line that forks, a branch to each
 * world. The branch of the world you are in, or point at, turns orange (header.css). On phones and in the
 * menu each world keeps its own lane arrow instead.
 * $args['sign']: set as a standing lane-split sign of two plates (the menu).
 */
$state  = log_twin_state();
$sign   = ! empty( $args['sign'] );
$worlds = [
	'projects' => [ get_post_type_archive_link( 'log_project' ), log_text( 'twin_projects', 'ניהול פרויקטים', 'option' ), log_text( 'twin_projects_line', 'תכנון וניהול ברמת הרשות והמתחם', 'option' ) ],
	'control'  => [ home_url( '/control/' ), log_text( 'twin_control', 'חברת הבקרה', 'option' ), log_text( 'twin_control_line', 'הבקרה הלאומית בהתחדשות עירונית', 'option' ) ],
];
?>
<nav class="twin-nav<?php echo $sign ? ' twin-nav--sign' : ''; ?>" aria-label="שני העולמות של לוג">
	<?php foreach ( $worlds as $key => [ $url, $name, $line ] ) : $on = $state === $key; ?>
		<a class="twin-nav__item twin-nav__item--<?php echo esc_attr( $key ); ?><?php echo $on ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>>
			<span class="twin-nav__text"><span class="twin-nav__name"><?php echo esc_html( $name ); ?></span><?php if ( $line ) : ?><span class="twin-nav__line"><?php echo esc_html( $line ); ?></span><?php endif; ?></span>
			<i class="arrow arrow--lane" aria-hidden="true"></i>
		</a>
		<?php if ( ! $sign && $key === 'projects' ) : // the fork stands between the two names; the stem is shared ?>
			<svg class="twin-nav__fork" viewBox="0 0 28 36" aria-hidden="true" focusable="false"><path class="twin-nav__way twin-nav__way--control" d="M14 20 3 7"/><path class="twin-nav__way twin-nav__way--projects" d="M14 20 25 7"/><path class="twin-nav__stem" d="M14 36V19"/></svg>
		<?php endif; ?>
	<?php endforeach; ?>
</nav>
