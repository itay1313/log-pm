<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="s">חיפוש באתר</label>
	<input type="search" id="s" name="s" placeholder="פרויקט, עיר, משרה…" value="<?php echo get_search_query(); ?>" autocomplete="off">
	<button type="submit">חיפוש</button>
</form>
