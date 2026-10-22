<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<script>(function(h){try{if(!matchMedia('(prefers-reduced-motion: reduce)').matches)h.classList.add('motion');if(sessionStorage.getItem('log-wipe'))h.classList.add('is-wiping');if(sessionStorage.getItem('log-cover'))h.classList.add('is-covered')}catch(e){}})(document.documentElement)</script>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main">דילוג לתוכן</a>

<?php
/**
 * The gantry (spec v6 §6): logo, the two worlds as a lane split, and the menu. The header is a guide sign
 * hung over every page: transparent over the map and the title band, a solid navy bar once the page moves.
 * On phones the lane split leaves the header and rides the bottom edge of the screen.
 */
$nav       = log_nav_items();
$distances = log_nav_distances();
?>
<header class="site-header" id="top">
	<div class="site-header__inner">
		<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="לוג ניהול פרויקטים – לעמוד הבית">
			<?php get_template_part( 'template-parts/logo' ); ?>
		</a>

		<?php get_template_part( 'template-parts/twin-nav' ); ?>

		<div class="site-header__tools">
			<a class="site-header__talk" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( log_text( 'header_cta', 'בואו נדבר', 'option' ) ); ?><i class="arrow" aria-hidden="true"></i></a>
			<button class="menu-toggle plate" type="button" aria-expanded="false" aria-controls="site-menu" aria-haspopup="dialog">
				<span class="menu-toggle__lanes" aria-hidden="true"><i></i><i></i><i></i></span>
				<span class="menu-toggle__label">תפריט</span>
			</button>
		</div>
	</div>
</header>

<dialog class="gantry" id="site-menu" aria-label="תפריט">
	<div class="gantry__plate">
		<div class="gantry__top">
			<a class="site-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="לוג ניהול פרויקטים – לעמוד הבית"><?php get_template_part( 'template-parts/logo' ); ?></a>
			<button class="gantry__close plate" type="button" autofocus><span>סגירה</span><i class="icon icon--close" aria-hidden="true"></i></button>
		</div>

		<nav class="gantry__nav" aria-label="ניווט ראשי">
			<ul class="dest">
				<?php foreach ( $nav as $i => [ $label, $url, $current ] ) : ?>
					<li class="dest__item<?php echo $current ? ' is-current' : ''; ?>">
						<a class="dest__row" href="<?php echo esc_url( $url ); ?>"<?php echo $current ? ' aria-current="page"' : ''; ?>>
							<span class="dest__name"><span><?php echo log_route_shield( $i + 1 ); ?><?php echo esc_html( $label ); ?></span></span>
							<span class="dest__dist"><?php echo esc_html( $distances[ $i ] ?? '' ); ?></span>
							<i class="arrow" aria-hidden="true"></i>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</nav>

		<div class="gantry__side">
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
