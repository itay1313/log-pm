<?php
/**
 * Project card (spec §8): image in a ring crop, stage line, domain, "name | city", key number, year.
 */
$id    = get_the_ID();
$stage = (int) log_field( 'stage', $id, 1 );
$units = log_field( 'units', $id );
$dunam = log_field( 'dunam', $id );
$city  = log_field( 'city', $id );
?>
<article class="card card--project" data-stage="<?php echo $stage; ?>" data-domain="<?php echo esc_attr( log_first_term_slug( $id, 'log_domain' ) ?? '' ); ?>" data-scale="<?php echo esc_attr( log_first_term_slug( $id, 'log_scale' ) ?? '' ); ?>">
	<a class="card__link" href="<?php the_permalink(); ?>">
		<figure class="card__media">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'log-card', [ 'loading' => 'lazy' ] ); ?>
			<?php else : ?>
				<span class="card__placeholder" aria-hidden="true"></span>
			<?php endif; ?>
		</figure>
		<div class="card__body">
			<?php echo log_stage_ring( $stage, false ); ?>
			<p class="card__meta"><?php echo log_project_meta_line( $id ); ?></p>
			<h3 class="card__title"><?php the_title(); ?><?php if ( $city ) : ?> <span class="card__city">| <?php echo esc_html( $city ); ?></span><?php endif; ?></h3>
			<p class="card__figure">
				<?php if ( $units ) : ?><strong><?php echo log_number( $units ); ?></strong> יח"ד<?php elseif ( $dunam ) : ?><strong><?php echo log_number( $dunam ); ?></strong> דונם<?php endif; ?>
				<?php if ( $year = log_field( 'year', $id ) ) : ?><span class="card__year"><?php echo esc_html( $year ); ?></span><?php endif; ?>
			</p>
		</div>
	</a>
</article>
