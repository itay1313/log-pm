<?php
/**
 * 404 (spec v6 §16): a title, a sentence, and links home, to projects, careers and contact. Real 404 status.
 */
get_header();
?>
<section class="not-found container">
	<p class="eyebrow">404</p>
	<h1><?php echo esc_html( log_text( 'notfound_title', 'הכתובת הזו לא נמצאת במפה.', 'option' ) ); ?></h1>
	<p><?php echo esc_html( log_text( 'notfound_text', 'אולי העמוד זז, אולי הקישור השתבש. אפשר להמשיך מכאן:', 'option' ) ); ?></p>
	<ul class="not-found__links">
		<li><a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>">לעמוד הבית</a></li>
		<li><a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">הפרויקטים</a></li>
		<li><a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) ); ?>">קריירה</a></li>
		<li><a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">צור קשר</a></li>
	</ul>
</section>
<?php get_footer();
