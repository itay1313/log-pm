<?php
/**
 * Team card (spec §11): B&W photo in the O-lens that reveals the colour photo on hover/focus,
 * name, role, one personal sentence. $args['size'] = 'lg' for owners/management.
 */
$id    = get_the_ID();
$size  = $args['size'] ?? 'md';
$color = log_field( 'photo_color', $id );
$domains = get_the_terms( $id, 'log_domain' );
?>
<article class="person person--<?php echo esc_attr( $size ); ?>" data-domain="<?php echo esc_attr( $domains && ! is_wp_error( $domains ) ? $domains[0]->slug : '' ); ?>">
	<figure class="person__photo">
		<?php if ( has_post_thumbnail() ) : ?>
			<?php the_post_thumbnail( $size === 'lg' ? 'log-portrait' : 'log-card', [ 'class' => 'person__bw', 'loading' => 'lazy' ] ); ?>
		<?php else : ?>
			<span class="person__placeholder" aria-hidden="true"></span>
		<?php endif; ?>
		<?php if ( $color ) : ?>
			<img class="person__color" src="<?php echo esc_url( $color['sizes'][ $size === 'lg' ? 'log-portrait' : 'log-card' ] ?? $color['url'] ); ?>" alt="" loading="lazy" aria-hidden="true">
		<?php endif; ?>
	</figure>
	<div class="person__body">
		<h3 class="person__name"><?php the_title(); ?></h3>
		<?php if ( $role = log_field( 'role', $id ) ) : ?><p class="person__role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
		<?php if ( $size === 'lg' && ( $bio = log_field( 'bio', $id ) ) ) : ?><p class="person__bio"><?php echo esc_html( $bio ); ?></p><?php endif; ?>
		<?php if ( $sentence = log_field( 'sentence', $id ) ) : ?><p class="person__sentence">„<?php echo esc_html( $sentence ); ?>”</p><?php endif; ?>
		<?php if ( $size === 'lg' && ( $video = log_field( 'video', $id ) ) ) : ?>
			<video class="person__video" src="<?php echo esc_url( $video['url'] ); ?>" controls preload="none" playsinline></video>
		<?php endif; ?>
		<?php if ( $size === 'lg' && ( $li = log_field( 'linkedin', $id ) ) ) : ?>
			<a class="person__linkedin" href="<?php echo esc_url( $li ); ?>" rel="noopener" target="_blank">LinkedIn</a>
		<?php endif; ?>
	</div>
</article>
