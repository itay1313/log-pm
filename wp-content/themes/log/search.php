<?php
/**
 * Search results grouped by type (spec §18).
 */
get_header();
get_template_part( 'template-parts/page-header', null, [
	'title' => sprintf( 'תוצאות עבור "%s"', get_search_query() ),
] );

$groups = [ 'log_project' => 'פרויקטים', 'log_job' => 'משרות', 'log_knowledge' => 'מרכז ידע', 'page' => 'עמודים' ];
$by_type = [];
while ( have_posts() ) { the_post(); $by_type[ get_post_type() ][] = get_post(); }
?>
<div class="container">
	<?php get_search_form(); ?>
	<?php if ( ! $by_type ) : ?>
		<p><?php echo esc_html( log_text( 'search_empty', 'לא נמצא כלום. אפשר לנסות ניסוח אחר, או לפנות אלינו.', 'option' ) ); ?> <a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>">צור קשר</a></p>
	<?php endif; ?>
	<?php foreach ( $groups as $type => $label ) : if ( empty( $by_type[ $type ] ) ) { continue; } ?>
		<section class="search-group">
			<h2><?php echo esc_html( $label ); ?> <span>(<?php echo count( $by_type[ $type ] ); ?>)</span></h2>
			<ul>
				<?php foreach ( $by_type[ $type ] as $p ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $p ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a>
						<?php if ( $type === 'log_project' ) : ?><span class="meta"><?php echo log_project_meta_line( $p->ID ); ?></span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
	<?php endforeach; ?>
	<?php the_posts_pagination(); ?>
</div>
<?php get_footer();
