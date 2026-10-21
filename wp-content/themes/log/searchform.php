<?php
/**
 * Site search: /search?q=… (spec v6 §4, §16).
 */
$sid = wp_unique_id( 'search-' );
?>
<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/search/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $sid ); ?>">חיפוש באתר</label>
	<input type="search" id="<?php echo esc_attr( $sid ); ?>" name="q" placeholder="פרויקט, עיר, משרה…" value="<?php echo esc_attr( trim( get_search_query( false ) ) ); ?>" autocomplete="off">
	<button type="submit">חיפוש</button>
</form>
