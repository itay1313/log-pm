<?php
/**
 * 404 (spec §18): short message, the map, three links.
 */
get_header();
?>
<section class="not-found container">
	<p class="eyebrow">404</p>
	<h1><?php echo esc_html( log_text( 'notfound_title', 'הכתובת הזו לא נמצאת במפה.', 'option' ) ); ?></h1>
	<p><?php echo esc_html( log_text( 'notfound_text', 'אולי העמוד זז, אולי הקישור השתבש. הנה שלוש דרכים להמשיך:', 'option' ) ); ?></p>
	<ul class="not-found__links">
		<li><a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">מפת הפרויקטים</a></li>
		<li><a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) ); ?>">המשרות הפתוחות</a></li>
		<li><a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">צור קשר</a></li>
	</ul>
</section>
<?php get_footer();
