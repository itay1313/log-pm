<?php
/**
 * Client and partner logos (spec v6 §7, §12): only those with written approval, grouped by body type
 * in tabs. A logo links nowhere. Tabs work without JS too: every panel is visible until main.js runs.
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
$groups = array_filter( $groups, static fn( $k ) => ! empty( $by[ $k ] ), ARRAY_FILTER_USE_KEY );
$uid    = wp_unique_id( 'clients-' );
?>
<div class="clients-tabs" data-tabs>
	<?php if ( count( $groups ) > 1 ) : ?>
		<div class="clients-tabs__list" role="tablist" aria-label="לקוחות לפי סוג גוף">
			<?php $first = true; foreach ( $groups as $key => $label ) : ?>
				<button type="button" role="tab" id="<?php echo esc_attr( "$uid-tab-$key" ); ?>" aria-controls="<?php echo esc_attr( "$uid-$key" ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>" tabindex="<?php echo $first ? '0' : '-1'; ?>"><?php echo esc_html( $label ); ?></button>
			<?php $first = false; endforeach; ?>
		</div>
	<?php endif; ?>
	<?php foreach ( $groups as $key => $label ) : ?>
		<div class="clients__group" role="tabpanel" id="<?php echo esc_attr( "$uid-$key" ); ?>" aria-labelledby="<?php echo esc_attr( "$uid-tab-$key" ); ?>">
			<?php if ( count( $groups ) === 1 ) : ?><h3 class="clients__label"><?php echo esc_html( $label ); ?></h3><?php endif; ?>
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
	<?php endforeach; ?>
</div>
