<?php
/**
 * /projects (spec §8): opening text, filter row, gallery ↔ map toggle, all approved projects.
 * Filters and view live in the URL hash (projects.js). National plans have no pin: they're listed under the map.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => log_text( 'projects_title', 'מתכננים את המחר', 'option' ),
	'lede'  => log_text( 'projects_lede', 'הפרויקטים שלנו: תחומים, קני מידה ופריסה ארצית.', 'option' ),
] );

$approved = log_approved_projects_query( [ 'posts_per_page' => -1 ] );
$national = array_values( array_filter( $approved->posts, static fn( $p ) => (bool) log_field( 'is_national', $p->ID ) ) );
?>
<div class="container">
	<div class="filters" role="group" aria-label="סינון פרויקטים">
		<?php foreach ( [ 'log_domain' => 'תחום', 'log_scale' => 'היקף' ] as $tax => $label ) :
			$terms = get_terms( [ 'taxonomy' => $tax, 'hide_empty' => true ] );
			if ( ! $terms || is_wp_error( $terms ) ) { continue; } ?>
			<fieldset class="filters__group">
				<legend><?php echo esc_html( $label ); ?></legend>
				<?php foreach ( $terms as $t ) : ?>
					<label><input type="checkbox" name="<?php echo esc_attr( $tax ); ?>" value="<?php echo esc_attr( $t->slug ); ?>"> <?php echo esc_html( $t->name ); ?></label>
				<?php endforeach; ?>
			</fieldset>
		<?php endforeach; ?>
		<div class="filters__view" role="group" aria-label="תצוגה">
			<button type="button" class="is-active" data-view="grid" aria-pressed="true">גלריה</button>
			<button type="button" data-view="map" aria-pressed="false">מפה</button>
		</div>
		<p class="filters__count" aria-live="polite"></p>
		<button type="button" class="filters__clear">ניקוי סינון</button>
	</div>

	<?php if ( $approved->have_posts() ) : ?>
		<div class="grid grid--projects" id="projects-grid">
			<?php while ( $approved->have_posts() ) : $approved->the_post();
				get_template_part( 'template-parts/card-project' );
			endwhile; wp_reset_postdata(); ?>
		</div>

		<div class="projects-map" id="projects-map" hidden tabindex="0" role="region" aria-label="מפת הפרויקטים. לחיצה על טבעת פותחת כרטיס, Esc סוגר."></div>

		<?php if ( $national ) : ?>
			<section class="national" id="projects-national" hidden>
				<h2 class="national__title">תכניות ארציות</h2>
				<p class="national__lede">בלי מיקום אחד על המפה.</p>
				<ul class="national__list">
					<?php foreach ( $national as $p ) : ?>
						<li data-domain="<?php echo esc_attr( log_first_term_slug( $p->ID, 'log_domain' ) ?? '' ); ?>" data-scale="<?php echo esc_attr( log_first_term_slug( $p->ID, 'log_scale' ) ?? '' ); ?>">
							<a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
							<span class="meta"><?php echo log_project_meta_line( $p->ID ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<p class="placeholder" id="projects-empty" hidden>אין פרויקטים שמתאימים לסינון הזה. <button type="button" class="filters__clear">ניקוי סינון</button></p>
	<?php else : ?>
		<p class="placeholder">עדיין אין פרויקטים מאושרים לפרסום.</p>
	<?php endif; ?>
</div>
<?php get_footer();
