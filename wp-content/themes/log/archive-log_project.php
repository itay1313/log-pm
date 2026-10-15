<?php
/**
 * /projects (spec §8): opening text, filter row, gallery ↔ map toggle, all approved projects.
 * Filters are client-side on data-* attributes for now; the map view shares the hero's data endpoint.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => 'מתכננים את המחר',
	'lede'  => 'הפרויקטים שלנו: תחומים, קני מידה ופריסה ארצית.',
] );
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
	</div>

	<div class="projects-map" id="projects-map" hidden></div>

	<?php
	// Only approved projects, regardless of what was published.
	global $wp_query;
	$approved = log_approved_projects_query( [ 'posts_per_page' => 24, 'paged' => max( 1, get_query_var( 'paged' ) ), 'no_found_rows' => false ] );
	if ( $approved->have_posts() ) : ?>
		<div class="grid grid--projects" id="projects-grid">
			<?php while ( $approved->have_posts() ) : $approved->the_post();
				get_template_part( 'template-parts/card-project' );
			endwhile; ?>
		</div>
		<?php
		$wp_query = $approved;
		the_posts_pagination( [ 'prev_text' => 'הקודם', 'next_text' => 'הבא' ] );
		wp_reset_query();
	else : ?>
		<p class="placeholder">עדיין אין פרויקטים מאושרים לפרסום.</p>
	<?php endif; ?>
</div>
<?php get_footer();
