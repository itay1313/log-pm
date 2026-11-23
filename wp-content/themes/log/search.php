<?php
/**
 * Site search (spec v6 §16): results grouped by type with a count per type. Each result: type, title,
 * one line. A project without a page leads to it on the map. Results come from log_search() in log-core.
 * The opener holds the question (the form, the jump links, how many were found); the paper holds the answers.
 */
get_header();
$query  = trim( get_search_query( false ) );
$groups = array_filter( log_search( $query ), static fn( $g ) => $g['items'] );
$total  = array_sum( array_map( static fn( $g ) => count( $g['items'] ), $groups ) );

get_template_part( 'template-parts/opener', null, [
	'class' => 'opener--compact opener--search',
	'n'     => '',
	'title' => $query !== '' ? esc_html( sprintf( 'חיפוש: %s', $query ) ) : 'חיפוש באתר',
	'body'  => static function () use ( $groups ) {
		get_search_form();
		if ( $groups ) : ?>
			<nav class="search-jump" aria-label="קפיצה לסוג תוצאה">
				<?php foreach ( $groups as $key => $g ) : ?><a href="#results-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $g['label'] ); ?> <span><?php echo count( $g['items'] ); ?></span></a><?php endforeach; ?>
			</nav>
		<?php endif;
	},
	'side'  => $query !== '' ? static function () use ( $total ) {
		printf( '<p class="opener__figure" aria-live="polite"><b>%s</b><span>%s</span></p>', esc_html( number_format_i18n( $total ) ), $total === 1 ? 'תוצאה' : 'תוצאות' );
	} : null,
] );
?>
<div class="container search-page" data-search-term="<?php echo esc_attr( $query ); ?>" data-search-results="<?php echo (int) $total; ?>">
	<?php if ( $groups ) : ?>
		<?php foreach ( $groups as $key => $g ) : ?>
			<section class="search-group" id="results-<?php echo esc_attr( $key ); ?>">
				<h2><?php echo esc_html( $g['label'] ); ?> <span><?php echo count( $g['items'] ); ?></span></h2>
				<?php // Not "search-results": WordPress puts that class on the body of this page, and every list on it would follow. ?>
				<ul class="results">
					<?php foreach ( $g['items'] as $it ) : ?>
						<li>
							<span class="results__type"><?php echo esc_html( $it['type'] ); ?></span>
							<a class="results__title" href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['title'] ); ?></a>
							<?php if ( $it['line'] ) : ?><span class="results__line"><?php echo esc_html( $it['line'] ); ?></span><?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endforeach; ?>
	<?php elseif ( $query !== '' ) : ?>
		<div class="search-empty">
			<p><?php echo esc_html( log_text( 'search_empty', 'לא מצאנו תוצאות. אפשר לנסות מילה אחרת, או להתחיל מכאן:', 'option' ) ); ?></p>
			<ul class="link-row">
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>">הפרויקטים</a></li>
				<li><a href="<?php echo esc_url( get_post_type_archive_link( 'log_knowledge' ) ); ?>">מרכז הידע</a></li>
				<li><a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">צור קשר</a></li>
			</ul>
		</div>
	<?php endif; ?>
</div>
<?php get_footer();
