<?php
/**
 * The two twin buttons (spec §5): "ניהול פרויקטים" ↔ "חברת הבקרה".
 * Outside the primary menu; always visible; exactly one is active.
 */
$state = log_twin_state();
?>
<nav class="twin-nav" aria-label="שני העולמות של לוג">
	<a class="twin-nav__item<?php echo $state === 'projects' ? ' is-active' : ''; ?>"
	   href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>"
	   <?php echo $state === 'projects' ? 'aria-current="page"' : ''; ?>>ניהול פרויקטים</a>
	<a class="twin-nav__item<?php echo $state === 'control' ? ' is-active' : ''; ?>"
	   href="<?php echo esc_url( home_url( '/control/' ) ); ?>"
	   <?php echo $state === 'control' ? 'aria-current="page"' : ''; ?>>חברת הבקרה</a>
</nav>
