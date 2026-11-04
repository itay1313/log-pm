<?php
/**
 * Client and partner logos (spec v6 §7, §12): only those with written approval. One running strip,
 * like the old site: grayscale, full color on hover, paused while hovered. The list is printed twice
 * so the loop is seamless; the copy is hidden from screen readers. Reduced motion gets a still, wrapped row.
 */
$clients = new WP_Query( [
	'post_type'      => 'log_client',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order title',
	'order'          => 'ASC',
	'meta_key'       => 'logo_approved',
	'meta_value'     => '1',
	'no_found_rows'  => true,
] );
$logos = [];
while ( $clients->have_posts() ) {
	$clients->the_post();
	$svg = log_field( 'logo_svg', get_the_ID() );
	$src = $svg['url'] ?? get_the_post_thumbnail_url( get_the_ID(), 'medium' );
	if ( $src ) {
		$logos[] = [ 'src' => $src, 'name' => get_the_title() ];
	}
}
wp_reset_postdata();
if ( ! $logos ) : if ( ! log_show_empty() ) { return; } ?>
	<p class="placeholder">כאן ירוצו לוגואים של לקוחות. מוצגים רק לקוחות עם לוגו שסומנו "אישור שימוש בלוגו".</p>
	<?php return;
endif;
?>
<div class="logo-run" style="--count: <?php echo (int) count( $logos ); ?>">
	<div class="logo-run__track">
		<?php for ( $copy = 0; $copy < 2; $copy++ ) : ?>
			<ul class="logo-run__list"<?php echo $copy ? ' aria-hidden="true"' : ''; ?>>
				<?php foreach ( $logos as $logo ) : ?>
					<li><img src="<?php echo esc_url( $logo['src'] ); ?>" alt="<?php echo $copy ? '' : esc_attr( $logo['name'] ); ?>" loading="lazy" decoding="async"></li>
				<?php endforeach; ?>
			</ul>
		<?php endfor; ?>
	</div>
</div>
