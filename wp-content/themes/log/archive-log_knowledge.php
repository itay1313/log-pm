<?php
/**
 * /knowledge (spec v6 §13). Blocks: opening, type tabs + search + year, featured item (only without
 * filters), 12 cards then "טעינת עוד", and "מה חדש" from Instagram.
 * Filtering runs client-side (knowledge.js) on every published item; state in ?type=&q=&year=.
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => log_text( 'knowledge_title', 'מרכז ידע', 'option' ),
	'lede'  => log_text( 'knowledge_lede', 'מאמרים, ניירות עמדה, חדשות החברה ואזכורים בתקשורת.', 'option' ),
] );

$all = new WP_Query( [ 'post_type' => 'log_knowledge', 'posts_per_page' => -1, 'no_found_rows' => true ] );
$featured = null;
foreach ( $all->posts as $p ) {
	if ( log_field( 'featured', $p->ID ) ) {
		$featured = $p;
		break;
	}
}
$types = array_values( array_filter(
	array_map( static fn( $slug ) => get_term_by( 'slug', $slug, 'log_knowledge_type' ), array_keys( log_knowledge_type_terms() ) ),
	static fn( $t ) => $t && $t->count > 0
) );
$years = array_values( array_unique( array_map( static fn( $p ) => get_the_date( 'Y', $p ), $all->posts ) ) );
rsort( $years );
?>
<div class="container">
	<?php if ( $all->have_posts() ) : ?>
		<form class="filters filters--knowledge" role="search" aria-label="סינון וחיפוש במרכז הידע" action="<?php echo esc_url( get_post_type_archive_link( 'log_knowledge' ) ); ?>">
			<div class="type-tabs" role="group" aria-label="סוג תוכן">
				<button type="button" class="chip is-active" data-type="" aria-pressed="true">הכל</button>
				<?php foreach ( $types as $t ) : ?><button type="button" class="chip" data-type="<?php echo esc_attr( $t->slug ); ?>" aria-pressed="false"><?php echo esc_html( $t->name ); ?></button><?php endforeach; ?>
			</div>
			<label class="filters__search"><span class="screen-reader-text">חיפוש במרכז הידע</span><input type="search" name="q" placeholder="חיפוש" autocomplete="off"></label>
			<?php if ( count( $years ) > 1 ) : ?>
				<label class="filters__group"><span>שנה</span>
					<select name="year"><option value="">כל השנים</option><?php foreach ( $years as $y ) : ?><option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option><?php endforeach; ?></select>
				</label>
			<?php endif; ?>
		</form>

		<?php if ( $featured ) : ?>
			<section class="knowledge-featured" id="knowledge-featured" aria-label="פריט נבחר">
				<?php $post = $featured; setup_postdata( $post ); get_template_part( 'template-parts/card-knowledge', null, [ 'lead' => true ] ); wp_reset_postdata(); ?>
			</section>
		<?php endif; ?>

		<div class="grid grid--knowledge" id="knowledge-grid">
			<?php foreach ( $all->posts as $post ) : if ( $featured && $post->ID === $featured->ID ) { continue; } setup_postdata( $post );
				get_template_part( 'template-parts/card-knowledge' );
			endforeach; wp_reset_postdata(); ?>
		</div>
		<p class="projects-more"><button type="button" class="btn" id="knowledge-more" hidden>טעינת עוד</button></p>
		<p class="placeholder" id="knowledge-empty" hidden>לא נמצאו פריטים. <button type="button" class="filters__clear">ניקוי סינון</button></p>
	<?php else : ?>
		<p class="placeholder">עדיין אין פריטי ידע.</p>
	<?php endif; ?>
</div>

<?php
$posts_ig = log_instagram_items( 8 );
if ( $posts_ig ) : ?>
	<section class="section section--news" data-section="news">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'news_title', 'מה חדש', 'option' ) ); ?></h2>
			<ul class="news">
				<?php foreach ( $posts_ig as $it ) : ?>
					<li class="news__item">
						<a class="news__link" href="<?php echo esc_url( $it['url'] ); ?>" target="_blank" rel="noopener">
							<figure class="news__media"><?php if ( $it['image'] ) : ?><img src="<?php echo esc_url( $it['image'] ); ?>" alt="" loading="lazy"><?php else : ?><span class="news__placeholder" aria-hidden="true"></span><?php endif; ?></figure>
							<p class="news__text"><?php echo esc_html( $it['text'] ); ?></p>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php if ( $ig = log_option( 'instagram' ) ) : ?><a class="link-more" href="<?php echo esc_url( $ig ); ?>" target="_blank" rel="noopener">לחשבון האינסטגרם שלנו</a><?php endif; ?>
		</div>
	</section>
<?php endif;
get_footer();
