<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>(function(h){try{if(!matchMedia('(prefers-reduced-motion: reduce)').matches){h.classList.add('motion');if(!sessionStorage.getItem('log-logo'))h.classList.add('logo-intro')<?php echo is_front_page() ? ";if(!sessionStorage.getItem('log-ra'))h.classList.add('ra-intro')" : ''; ?>}if(sessionStorage.getItem('log-wipe'))h.classList.add('is-wiping');if(sessionStorage.getItem('log-cover'))h.classList.add('is-covered')}catch(e){}})(document.documentElement)</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">דילוג לתוכן</a>

<?php
/**
 * The gantry (spec v6 §6): logo, the two worlds as a lane split, and the menu. The header hangs over every
 * page: transparent over the map and the title band, a solid navy bar once the page moves. One outlined
 * control in it, the menu; "talk to us" is a plain link with the arrow.
 * On phones the lane split leaves the header and rides the bottom edge of the screen.
 */
$nav       = log_nav_items();
$distances = log_nav_distances();
?>
<header class="site-header" id="top">
	<div class="site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="לוג ניהול פרויקטים – לעמוד הבית">
			<?php get_template_part( 'template-parts/logo', null, [ 'variant' => 'night', 'animate' => true, 'label' => '' ] ); ?>
		</a>

		<?php get_template_part( 'template-parts/twin-nav' ); ?>

		<div class="site-header__tools">
			<a class="site-header__talk" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( log_text( 'header_cta', 'בואו נדבר', 'option' ) ); ?><i class="arrow" aria-hidden="true"></i></a>
			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="site-menu" aria-haspopup="dialog">
				<span class="menu-toggle__label">תפריט</span>
				<span class="menu-toggle__lanes" aria-hidden="true"><i></i><i></i></span>
			</button>
		</div>
	</div>
</header>

<?php
// The menu is a night sheet set like the book's openers: a thin line under the top bar crosses a second line that
// parts the destinations from the side column, the dot where they meet. The first is grey from the start side and
// turns orange at the crossing. Behind the side column lies one of LOG's places at night, greyed under one even
// layer (a place that carries text); without the photograph the column is plain night.
$menu_place = log_bk_photo_url( 'night-city-s-g.jpg' ) ?: log_bk_photo_url( 'night-city-g.jpg' ) ?: log_bk_photo_url( 'night-city.jpg' );
?>
<dialog class="gantry" id="site-menu" aria-label="תפריט">
	<div class="gantry__plate">
		<div class="gantry__top">
			<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="לוג ניהול פרויקטים – לעמוד הבית"><?php get_template_part( 'template-parts/logo', null, [ 'variant' => 'night', 'label' => '' ] ); ?></a>
			<button class="gantry__close" type="button" autofocus><span>סגירה</span><i class="icon icon--close" aria-hidden="true"></i></button>
		</div>
		<span class="gantry__cross" aria-hidden="true"><i class="gantry__cross-h"></i><i class="gantry__cross-v"></i><i class="gantry__cross-dot"></i></span>

		<nav class="gantry__nav" aria-label="ניווט ראשי">
			<ul class="dest">
				<?php foreach ( $nav as $i => [ $label, $url, $current ] ) : ?>
					<li class="dest__item<?php echo $current ? ' is-current' : ''; ?>">
						<a class="dest__row" href="<?php echo esc_url( $url ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>>
							<span class="dest__n" aria-hidden="true"><?php printf( '%02d', $i + 1 ); ?></span>
							<span class="dest__name"><span><?php echo esc_html( $label ); ?></span></span>
							<span class="dest__dist"><?php echo esc_html( $distances[ $i ] ?? '' ); ?></span>
							<i class="arrow" aria-hidden="true"></i>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="gantry__side">
			<?php
			if ( $menu_place ) {
				printf( '<img class="%s" src="%s" alt="" loading="lazy" decoding="async">', esc_attr( 'gantry__place' . ( str_contains( $menu_place, '-g.' ) ? '' : ' gantry__place--colour' ) ), esc_url( $menu_place ) );
			}
			?>
			<?php get_template_part( 'template-parts/twin-nav', null, [ 'sign' => true ] ); ?>
			<?php get_search_form(); ?>
			<div class="gantry__contact">
				<?php foreach ( log_offices() as $office ) : ?>
					<p><b><?php echo esc_html( $office['name'] ); ?></b><?php if ( $office['phone'] ) : ?> <a href="tel:<?php echo esc_attr( preg_replace( '/[^\d+]/', '', $office['phone'] ) ); ?>"><?php echo esc_html( $office['phone'] ); ?></a><?php endif; ?></p>
				<?php endforeach; ?>
				<?php if ( $email = log_option( 'email' ) ) : ?><p><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></p><?php endif; ?>
				<a class="btn btn--works" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( log_text( 'header_cta', 'בואו נדבר', 'option' ) ); ?><i class="arrow" aria-hidden="true"></i></a>
			</div>
		</div>
	</div>
</dialog>

<main id="main" class="site-main">
