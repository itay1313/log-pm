<?php
/**
 * The two twin buttons (spec v6 §6): "ניהול פרויקטים" ↔ "חברת הבקרה". Each: the world's name and a short
 * sentence (hidden on phones). Marked only inside its own world. Stuck under the header on desktop,
 * to the bottom of the screen on phones; on the home page they appear right after the map.
 */
$state  = log_twin_state();
$worlds = [
	'projects' => [ get_post_type_archive_link( 'log_project' ), log_text( 'twin_projects', 'ניהול פרויקטים', 'option' ), log_text( 'twin_projects_line', 'תכנון וניהול ברמת הרשות והמתחם', 'option' ) ],
	'control'  => [ home_url( '/control/' ), log_text( 'twin_control', 'חברת הבקרה', 'option' ), log_text( 'twin_control_line', 'הבקרה הלאומית בהתחדשות עירונית', 'option' ) ],
];
?>
<nav class="twin-nav" aria-label="שני העולמות של לוג">
	<?php foreach ( $worlds as $key => [ $url, $name, $line ] ) : $on = $state === $key; ?>
		<a class="twin-nav__item<?php echo $on ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>"<?php echo $on ? ' aria-current="page"' : ''; ?>>
			<span class="twin-nav__name"><?php echo esc_html( $name ); ?></span>
			<?php if ( $line ) : ?><span class="twin-nav__line"><?php echo esc_html( $line ); ?></span><?php endif; ?>
		</a>
	<?php endforeach; ?>
</nav>
