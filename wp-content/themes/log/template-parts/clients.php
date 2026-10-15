<?php
/**
 * Client logos in one tone, only those with written approval. Grouped by body type.
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
$groups = [ 'authority' => 'רשויות מקומיות', 'government' => 'גופי ממשלה', 'developer' => 'יזמים', 'other' => 'שותפים' ];
$by = [];
while ( $clients->have_posts() ) {
	$clients->the_post();
	$by[ log_field( 'body_type', get_the_ID(), 'other' ) ][] = get_post();
}
wp_reset_postdata();
if ( ! $by ) : ?>
	<p class="placeholder">לוגואים בגוון אחד, לפי סוג גוף. מוצגים רק לקוחות שסומנו "אישור שימוש בלוגו".</p>
	<?php return;
endif;
foreach ( $groups as $key => $label ) : if ( empty( $by[ $key ] ) ) { continue; } ?>
	<div class="clients__group">
		<h3 class="clients__label"><?php echo esc_html( $label ); ?></h3>
		<ul class="clients">
			<?php foreach ( $by[ $key ] as $c ) :
				$svg = log_field( 'logo_svg', $c->ID );
				$src = $svg['url'] ?? get_the_post_thumbnail_url( $c->ID, 'medium' ); ?>
				<li class="clients__item">
					<?php if ( $src ) : ?><img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $c->post_title ); ?>" loading="lazy"><?php else : ?><span><?php echo esc_html( $c->post_title ); ?></span><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endforeach;
