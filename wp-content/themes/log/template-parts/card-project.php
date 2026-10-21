<?php
/**
 * Project tile (spec v6 §9): image · domain · name · city and authority · stage · one meaningful number,
 * plus the project's grid reference, set like a survey sheet's margin.
 * With a full page the whole card links to it; without one, the card shows what's known
 * and a small "לראות במפה" link to the project on the home map.
 * data-* attributes feed the client-side search and filters (projects.js).
 */
$id     = get_the_ID();
$stage  = log_project_stage( $id );
$page   = log_project_has_page( $id );
$units  = log_field( 'units', $id );
$sqm    = log_field( 'sqm_employment', $id );
$domain = log_first_term( $id, 'log_domain' );
$place  = log_project_place( $id );
$tag    = $page ? 'a' : 'div';
$grid   = log_project_grid_ref( $id );
?>
<article class="card card--project<?php echo $page ? '' : ' card--nopage'; ?><?php echo empty( $args['featured'] ) ? '' : ' card--featured'; ?>"
	data-domain="<?php echo esc_attr( $domain?->slug ?? '' ); ?>"
	data-stage="<?php echo esc_attr( $stage['slug'] ); ?>"
	data-authority="<?php echo esc_attr( rawurldecode( log_first_term_slug( $id, 'log_authority' ) ?? '' ) ); ?>"
	data-search="<?php echo esc_attr( log_project_search_text( $id ) ); ?>">
	<<?php echo $tag; ?> class="card__link"<?php echo $page ? ' href="' . esc_url( get_permalink() ) . '"' : ''; ?>>
		<figure class="card__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'log-card', [ 'loading' => 'lazy' ] ); ?>
			<?php else : // one fallback image per domain, drawn by CSS ?>
				<span class="card__placeholder" data-id="<?php echo (int) $id; ?>" data-domain="<?php echo esc_attr( $domain?->slug ?? '' ); ?>" data-stage="<?php echo (int) ( $stage['completed'] ? 4 : $stage['number'] ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
		</figure>
		<div class="card__body">
			<p class="card__meta"><span><?php echo esc_html( $domain?->name ?? '' ); ?></span><?php if ( $grid ) : ?><span class="grid-ref" title="רשת ישראל"><?php echo esc_html( $grid ); ?></span><?php endif; ?></p>
			<h3 class="card__title"><?php the_title(); ?></h3>
			<?php if ( $place ) : ?><p class="card__place"><?php echo esc_html( $place ); ?></p><?php endif; ?>
			<?php echo log_stage_row( $stage, 'card__stage' ); ?>
			<?php if ( $units ) : ?>
				<p class="card__figure"><strong><?php echo log_number( $units ); ?></strong> יח"ד</p>
			<?php elseif ( $sqm ) : ?>
				<p class="card__figure"><strong><?php echo log_number( $sqm ); ?></strong> מ"ר</p>
			<?php endif; ?>
		</div>
	</<?php echo $tag; ?>>
	<?php if ( ! $page ) : ?>
		<a class="card__map" href="<?php echo esc_url( log_project_map_url( $id ) ); ?>">לראות במפה</a>
	<?php endif; ?>
</article>
