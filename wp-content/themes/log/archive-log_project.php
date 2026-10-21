<?php
/**
 * /projects (spec v6 §9): opening text, search and filter row, gallery ↔ map toggle, all public projects.
 * State lives in the query string (projects.js): ?q=&domain=&stage=&authority=&view=map&p={slug}.
 * One value per filter, or all. National plans have no pin: they're listed under the map.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => log_text( 'projects_title', 'מתכננים את המחר', 'option' ),
	'lede'  => log_text( 'projects_lede', 'הפרויקטים שלנו: תחומים, קני מידה ופריסה ארצית.', 'option' ),
] );

$approved = log_approved_projects_query( [ 'posts_per_page' => -1 ] );
$national = array_values( array_filter( $approved->posts, static fn( $p ) => log_project_is_public( $p->ID ) && log_field( 'is_national', $p->ID ) ) );
?>
<div class="container">
	<form class="filters" role="search" aria-label="חיפוש וסינון פרויקטים" action="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">
		<label class="filters__search"><span class="screen-reader-text">חיפוש פרויקט, עיר או רשות</span>
			<input type="search" name="q" placeholder="שם פרויקט, עיר או רשות" autocomplete="off">
		</label>
		<?php
		$public = array_values( array_filter( $approved->posts, static fn( $p ) => log_project_is_public( $p->ID ) ) );
		foreach ( [ 'domain' => [ 'log_domain', 'תחום' ], 'stage' => [ 'log_stage', 'שלב או סטטוס' ], 'authority' => [ 'log_authority', 'רשות' ] ] as $param => [ $tax, $label ] ) :
			$terms = $tax === 'log_domain' ? log_ordered_terms( $tax, true ) : get_terms( [ 'taxonomy' => $tax, 'hide_empty' => true ] );
			if ( ! $terms || is_wp_error( $terms ) ) { continue; }
			if ( $tax === 'log_stage' ) { // 1, 2, 3, 4, then completed
				$rank = array_flip( array_keys( log_stage_terms() ) );
				usort( $terms, static fn( $a, $b ) => ( $rank[ $a->slug ] ?? 99 ) <=> ( $rank[ $b->slug ] ?? 99 ) );
			}
			$counts = [];
			foreach ( $public as $p ) {
				foreach ( wp_get_post_terms( $p->ID, $tax, [ 'fields' => 'slugs' ] ) as $slug ) {
					$counts[ $slug ] = ( $counts[ $slug ] ?? 0 ) + 1;
				}
			} ?>
			<label class="filters__group"><span><?php echo esc_html( $label ); ?></span>
				<select name="<?php echo esc_attr( $param ); ?>">
					<option value="">הכל</option>
					<?php foreach ( $terms as $t ) : if ( empty( $counts[ $t->slug ] ) ) { continue; } // Hebrew slugs are stored encoded; the URL and data-* carry them decoded ?>
						<option value="<?php echo esc_attr( rawurldecode( $t->slug ) ); ?>" data-label="<?php echo esc_attr( $t->name ); ?>"><?php echo esc_html( $t->name ); ?> (<?php echo (int) $counts[ $t->slug ]; ?>)</option>
					<?php endforeach; ?>
				</select>
			</label>
		<?php endforeach; ?>
		<div class="filters__view" role="group" aria-label="תצוגה">
			<button type="button" class="is-active" data-view="grid" aria-pressed="true">גלריה</button>
			<button type="button" data-view="map" aria-pressed="false">מפה</button>
		</div>
		<p class="filters__count" aria-live="polite"></p>
		<button type="button" class="filters__clear" hidden>ניקוי סינון</button>
	</form>

	<?php if ( $approved->have_posts() ) : ?>
		<div class="grid grid--projects" id="projects-grid">
			<?php foreach ( $public as $post ) : setup_postdata( $post ); // template scope is global scope
				get_template_part( 'template-parts/card-project' );
			endforeach; wp_reset_postdata(); ?>
		</div>

		<div class="projects-map" id="projects-map" hidden tabindex="0" role="region" aria-label="מפת הפרויקטים. לחיצה על סיכה פותחת חלונית, Esc סוגר."></div>

		<?php if ( $national ) : ?>
			<section class="national" id="projects-national" hidden>
				<h2 class="national__title">פריסה ארצית</h2>
				<p class="national__lede">בלי מיקום אחד על המפה.</p>
				<ul class="national__list">
					<?php foreach ( $national as $p ) : ?>
						<li data-domain="<?php echo esc_attr( log_first_term_slug( $p->ID, 'log_domain' ) ?? '' ); ?>" data-stage="<?php echo esc_attr( log_project_stage( $p->ID )['slug'] ); ?>" data-authority="<?php echo esc_attr( rawurldecode( log_first_term_slug( $p->ID, 'log_authority' ) ?? '' ) ); ?>" data-search="<?php echo esc_attr( log_project_search_text( $p->ID ) ); ?>">
							<?php if ( log_project_has_page( $p->ID ) ) : ?><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a><?php else : echo esc_html( get_the_title( $p ) ); endif; ?>
							<span class="meta"><?php echo esc_html( implode( ' · ', array_filter( [ ( $c = log_field( 'client', $p->ID ) ) ? get_the_title( $c ) : '', log_project_stage( $p->ID )['label'] ] ) ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<p class="placeholder" id="projects-empty" hidden>לא נמצאו פרויקטים. <button type="button" class="filters__clear">ניקוי סינון</button></p>
	<?php else : ?>
		<p class="placeholder">עדיין אין פרויקטים מאושרים לפרסום.</p>
	<?php endif; ?>
</div>
<?php get_footer();
