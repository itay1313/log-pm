<?php
/**
 * The cover and the contents. The cover is the book's one picture of the idea: a roundabout from the air, the city
 * drawn in lines standing up beside it, two thin lines that run to the edges and cross, and the orange dot where they meet.
 */

$chapters = log_bk_chapters();
?>
<section class="bk-page bk-page--night bk-cover" id="start" data-chapter="" aria-label="שער">
	<?php echo log_bk_photo( 'aerial-night.jpg', '', 'bk-photo--bleed', 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php // The plan over the place: the city in lines stands up out of the photograph, and answers the scroll (src/fx/city.js). ?>
	<?php echo log_city( [ 'seed' => 5, 'cols' => 16, 'rows' => 11, 'scale' => 0.76, 'theta' => 35, 'cx' => 0.25, 'cy' => 0.78, 'plan' => [ [ 6, 4 ], [ 9, 4 ], [ 9, 6 ], [ 10, 6 ], [ 10, 8 ], [ 6, 8 ] ], 'ghosts' => true, 'dims' => false, 'area' => false, 'class' => 'bk-cover__city' ] ); // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_city() ?>
	<span class="bk-cross" style="--x: 139; --y: 603" aria-hidden="true"><i class="bk-cross__v"></i><i class="bk-cross__h"></i><i class="bk-cross__dot"></i></span>
	<div class="bk-cover__logo"><?php echo log_logo( [ 'variant' => 'night', 'tagline' => true, 'label' => 'לוג' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
	<div class="bk-cover__body">
		<p class="bk-kicker bk-kicker--works">השפה העיצובית של לוג</p>
		<?php log_bk_title( 'ספר המותג', 'h1', 'bk-title--cover' ); ?>
		<p class="bk-lede">קו דק, כתום אחד ומקום לכל פרויקט.</p>
	</div>
	<p class="bk-cover__version">גרסה 4 · נובמבר 2026 · לאתר, לרשתות ולמצגות</p>
</section>

<?php log_bk_open( '', 'תוכן', 'bk-toc' ); ?>
	<header class="bk-head">
		<p class="bk-kicker"><b>ספר המותג</b> · תוכן</p>
		<?php log_bk_title( 'מה בספר' ); ?>
	</header>
	<ol class="bk-toc__list">
		<?php foreach ( $chapters as $n => $name ) : ?>
			<li><a href="#ch-<?php echo esc_attr( $n ); ?>" data-toc="<?php echo esc_attr( $n ); ?>"><span class="bk-toc__n" aria-hidden="true"><?php echo esc_html( $n ); ?></span><b><?php echo esc_html( $name ); ?></b><span class="bk-toc__page" data-toc-page></span></a></li>
		<?php endforeach; ?>
	</ol>
<?php log_bk_close(); ?>
