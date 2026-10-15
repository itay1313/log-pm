<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">דילוג לתוכן</a>

<header class="site-header" id="top">
	<div class="site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="לוג ניהול פרויקטים – לעמוד הבית">
			<?php get_template_part( 'template-parts/logo' ); ?>
		</a>

		<nav class="site-nav" aria-label="ניווט ראשי">
			<?php wp_nav_menu( [
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => 'site-nav__list',
				'fallback_cb'    => false,
				'depth'          => 1,
			] ); ?>
		</nav>

		<div class="site-header__tools">
			<button class="search-toggle" type="button" aria-expanded="false" aria-controls="site-search">חיפוש</button>
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( log_text( 'header_cta', 'בואו נדבר', 'option' ) ); ?></a>
			<button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="תפריט"><span></span></button>
		</div>
	</div>

	<div class="site-search" id="site-search" hidden>
		<?php get_search_form(); ?>
	</div>

	<nav class="mobile-nav" id="mobile-nav" aria-label="ניווט" hidden>
		<?php wp_nav_menu( [
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'mobile-nav__list',
			'fallback_cb'    => false,
			'depth'          => 1,
		] ); ?>
		<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( log_text( 'header_cta', 'בואו נדבר', 'option' ) ); ?></a>
		<?php get_search_form(); ?>
	</nav>
</header>

<?php get_template_part( 'template-parts/twin-nav' ); ?>

<main id="main" class="site-main">
