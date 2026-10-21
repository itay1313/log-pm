<?php
/**
 * Site search (spec v6 §16): results grouped by type with a count per type. Each result: type, title,
 * one line. A project without a page leads to it on the map. Results come from log_search() in log-core.
 */
get_header();
$query  = trim( get_search_query( false ) );
$groups = array_filter( log_search( $query ), static fn( $g ) => $g['items'] );
$total  = array_sum( array_map( static fn( $g ) => count( $g['items'] ), $groups ) );
?>
<header class="page-header">
	<h1 class="page-header__title"><?php echo $query !== '' ? esc_html( sprintf( 'חיפוש: %s', $query ) ) : 'חיפוש באתר'; ?></h1>
	<?php if ( $query !== '' ) : ?><p class="page-header__lede" aria-live="polite"><?php echo $total === 1 ? 'תוצאה אחת' : esc_html( sprintf( '%s תוצאות', number_format_i18n( $total ) ) ); ?></p><?php endif; ?>
	<?php echo log_sheet_label(); ?>
</header>
<div class="container" data-search-term="<?php echo esc_attr( $query ); ?>" data-search-results="<?php echo (int) $total; ?>">
	<?php get_search_form(); ?>

	<?php if ( $groups ) : ?>
		<nav class="search-jump" aria-label="קפיצה לסוג תוצאה">
			<?php foreach ( $groups as $key => $g ) : ?><a href="#results-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $g['label'] ); ?> <span><?php echo count( $g['items'] ); ?></span></a><?php endforeach; ?>
		</nav>
		<?php foreach ( $groups as $key => $g ) : ?>
			<section class="search-group" id="results-<?php echo esc_attr( $key ); ?>">
				<h2><?php echo esc_html( $g['label'] ); ?> <span>(<?php echo count( $g['items'] ); ?>)</span></h2>
				<ul class="search-results">
					<?php foreach ( $g['items'] as $it ) : ?>
						<li>
							<span class="search-results__type"><?php echo esc_html( $it['type'] ); ?></span>
							<a class="search-results__title" href="<?php echo esc_url( $it['url'] ); ?>"><?php echo esc_html( $it['title'] ); ?></a>
							<?php if ( $it['line'] ) : ?><span class="search-results__line"><?php echo esc_html( $it['line'] ); ?></span><?php endif; ?>
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
