<?php
/**
 * 404 (spec §18): short message, the map, three links.
 */
get_header();
?>
<section class="not-found container">
	<p class="eyebrow">404</p>
	<h1>הכתובת הזו לא נמצאת במפה.</h1>
	<p>אולי העמוד זז, אולי הקישור השתבש. הנה שלוש דרכים להמשיך:</p>
	<ul class="not-found__links">
		<li><a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">מפת הפרויקטים</a></li>
		<li><a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) ); ?>">המשרות הפתוחות</a></li>
		<li><a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">צור קשר</a></li>
	</ul>
</section>
<?php get_footer();
