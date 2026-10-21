<?php
/**
 * Knowledge card (spec v6 §13): type · title · excerpt · date · image.
 * A media mention without a body links straight to the original piece, in a new tab.
 * data-* attributes feed the client-side search and filters (knowledge.js). $args['lead'] = the featured item.
 */
$kid      = get_the_ID();
$type     = get_the_terms( $kid, 'log_knowledge_type' );
$type     = $type && ! is_wp_error( $type ) ? $type[0] : null;
$external = ! trim( get_the_content() ) ? log_field( 'external_url', $kid ) : null;
$lead     = ! empty( $args['lead'] );
?>
<article class="card card--knowledge<?php echo $lead ? ' card--lead' : ''; ?>"
	data-type="<?php echo esc_attr( $type?->slug ?? '' ); ?>"
	data-year="<?php echo esc_attr( get_the_date( 'Y' ) ); ?>"
	data-search="<?php echo esc_attr( mb_strtolower( get_the_title() . ' ' . get_the_excerpt() . ' ' . log_field( 'outlet', $kid, '' ) ) ); ?>">
	<a class="card__link" href="<?php echo esc_url( $external ?: get_permalink() ); ?>"<?php echo $external ? ' target="_blank" rel="noopener"' : ''; ?>>
		<?php if ( has_post_thumbnail() ) : ?><figure class="card__media"><?php the_post_thumbnail( $lead ? 'log-wide' : 'log-card', [ 'loading' => $lead ? 'eager' : 'lazy' ] ); ?></figure><?php endif; ?>
		<div class="card__body">
			<p class="card__meta"><?php echo esc_html( implode( ' · ', array_filter( [ $type?->name, log_field( 'outlet', $kid ), get_the_date() ] ) ) ); ?></p>
			<h3 class="card__title"><?php the_title(); ?></h3>
			<?php if ( has_excerpt() ) : ?><p class="card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
		</div>
	</a>
</article>
