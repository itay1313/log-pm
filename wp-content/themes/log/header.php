<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>try{if(sessionStorage.getItem('log-wipe'))document.documentElement.classList.add('is-wiping')}catch(e){}</script>
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

		<div class="site-header__tools">
			<button class="search-toggle" type="button" aria-expanded="false" aria-controls="site-search">חיפוש</button>
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( log_text( 'header_cta', 'בואו נדבר', 'option' ) ); ?></a>
		</div>

		<nav class="site-nav" aria-label="ניווט ראשי">
			<ul class="site-nav__list">
				<?php foreach ( log_nav_items() as [ $label, $url, $current ] ) : ?>
					<li<?php echo $current ? ' class="is-current"' : ''; ?>><a href="<?php echo esc_url( $url ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $label ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	</div>

	<div class="site-search" id="site-search" hidden>
		<?php get_search_form(); ?>
	</div>
</header>

<?php if ( ! is_front_page() ) { get_template_part( 'template-parts/twin-nav' ); } // home: right after the map ?>

<main id="main" class="site-main">
