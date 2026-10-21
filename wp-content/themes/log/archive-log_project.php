<?php
/**
 * /projects (spec v6 §9). Blocks: opening, featured projects, search and filters, gallery ↔ map, all projects.
 * State lives in the query string (projects.js): ?q=&domain=&stage=&authority=&view=map&p={slug}.
 * One value per filter, or all. Featured projects first, then newest; 24 cards, then "טעינת עוד".
 * National plans have no pin: they're listed under the map.
 */
get_header();
?>
<header class="page-header">
	<h1 class="page-header__title"><?php echo esc_html( log_text( 'projects_title', 'הפרויקטים שלנו', 'option' ) ); ?></h1>
	<div class="page-header__prose"><?php echo wpautop( esc_html( log_text( 'projects_lede', "לוג מנהלת ומקדמת תכניות בכל קנה מידה: התחדשות עירונית, תכניות מתאר ותכניות מפורטות, ברשויות בכל הארץ.\n\nבכל פרויקט אנחנו מחזיקים את התהליך מקצה לקצה: מהאיתור וההיתכנות, דרך מוסדות התכנון, ועד המעקב אחרי מה שקורה בשטח.", 'option' ) ) ); ?></div>
</header>
<?php
// Featured first (stable), then newest first.
$public = log_public_projects();
usort( $public, static fn( $a, $b ) => ( (int) log_field( 'featured', $b->ID ) <=> (int) log_field( 'featured', $a->ID ) ) ?: strcmp( $b->post_date, $a->post_date ) ?: $a->ID <=> $b->ID );
$featured = array_slice( array_values( array_filter( $public, static fn( $p ) => log_field( 'featured', $p->ID ) && log_project_has_page( $p->ID ) ) ), 0, 6 );
$national = array_values( array_filter( $public, static fn( $p ) => (bool) log_field( 'is_national', $p->ID ) ) );

if ( count( $featured ) >= 1 ) : ?>
	<section class="container featured" id="projects-featured" aria-labelledby="featured-title">
		<h2 class="featured__title" id="featured-title"><?php echo esc_html( log_text( 'featured_title', 'פרויקטים נבחרים', 'option' ) ); ?></h2>
		<div class="featured__grid">
			<?php foreach ( $featured as $post ) : setup_postdata( $post ); // template scope is global scope
				get_template_part( 'template-parts/card-project', null, [ 'featured' => true ] );
			endforeach; wp_reset_postdata(); ?>
		</div>
	</section>
<?php endif; ?>
<div class="container">
	<form class="filters" role="search" aria-label="חיפוש וסינון פרויקטים" action="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">
		<label class="filters__search"><span class="screen-reader-text">חיפוש פרויקט, עיר או רשות</span>
			<input type="search" name="q" placeholder="שם פרויקט, עיר או רשות" autocomplete="off">
		</label>
		<?php get_template_part( 'template-parts/filter-selects', null, [ 'projects' => $public ] ); ?>
		<div class="filters__view" role="group" aria-label="תצוגה">
			<button type="button" class="is-active" data-view="grid" aria-pressed="true">גלריה</button>
			<button type="button" data-view="map" aria-pressed="false">מפה</button>
		</div>
		<p class="filters__count" aria-live="polite"></p>
		<button type="button" class="filters__clear" hidden>ניקוי סינון</button>
	</form>

	<?php if ( $public ) : ?>
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

		<p class="projects-more"><button type="button" class="btn" id="projects-more" hidden>טעינת עוד</button></p>

		<p class="placeholder" id="projects-empty" hidden>לא נמצאו פרויקטים. <button type="button" class="filters__clear">ניקוי סינון</button></p>
	<?php else : ?>
		<p class="placeholder">עדיין אין פרויקטים מאושרים לפרסום.</p>
	<?php endif; ?>
</div>
<?php get_footer();
