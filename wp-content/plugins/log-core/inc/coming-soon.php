<?php
/**
 * "Under construction" gate until launch.
 *
 * Visitors who aren't logged in with editing rights get a single holding page (503 + Retry-After,
 * so search engines come back later instead of indexing it). wp-admin, login, REST, AJAX and cron
 * are untouched. Switch off in Settings → General → "האתר בבנייה" on launch day.
 */

defined( 'ABSPATH' ) || exit;

function log_coming_soon_on(): bool {
	return (bool) get_option( 'log_coming_soon', '1' );
}

add_action( 'admin_init', function () {
	register_setting( 'general', 'log_coming_soon', [
		'type'              => 'string',
		'default'           => '1',
		'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
	] );
	add_settings_field( 'log_coming_soon', 'האתר בבנייה', function () {
		printf(
			'<label><input type="checkbox" name="log_coming_soon" value="1" %s> מבקרים שלא מחוברים רואים עמוד "האתר בבנייה"</label><p class="description">מחוברים עם הרשאת עריכה רואים את האתר המלא. להסיר את הסימון ביום ההשקה.</p>',
			checked( log_coming_soon_on(), true, false )
		);
	}, 'general' );
} );

// A reminder in the admin bar, so nobody forgets the public can't see the site.
add_action( 'admin_bar_menu', function ( WP_Admin_Bar $bar ) {
	if ( log_coming_soon_on() && current_user_can( 'manage_options' ) ) {
		$bar->add_node( [
			'id'    => 'log-coming-soon',
			'title' => 'מצב בנייה פעיל',
			'href'  => admin_url( 'options-general.php#log_coming_soon' ),
		] );
	}
}, 100 );

add_action( 'template_redirect', function () {
	$preview = isset( $_GET['coming-soon'] ); // editors can see what visitors get: /?coming-soon
	if ( ! $preview && ( ! log_coming_soon_on() || current_user_can( 'edit_posts' ) ) ) {
		return;
	}
	status_header( 503 );
	header( 'Retry-After: 86400' );
	header( 'X-Robots-Tag: noindex, nofollow' );
	nocache_headers();
	log_coming_soon_page();
	exit;
}, 0 );

/**
 * The page is one full-screen gantry sign, the same plate as the theme's menu: route shield 00 and the
 * legend, then a lane marking that is painted as far as the works plate and stops there.
 * Self-contained (inline CSS, the theme's self-hosted fonts, no script), so it never depends on the build.
 */
function log_coming_soon_page(): void {
	$fonts = get_template_directory_uri() . '/assets/fonts/';
	$name  = get_bloginfo( 'name' );
	?>
<!doctype html>
<html lang="he-IL" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#091018">
<title><?php echo esc_html( $name ); ?> · בקרוב</title>
<link rel="preload" href="<?php echo esc_url( $fonts . 'ploni_tzar_bold.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
<style>
@font-face { font-family: 'Ploni'; src: url('<?php echo esc_url( $fonts . 'ploni_regular.woff2' ); ?>') format('woff2'); font-weight: 400; font-display: swap; }
@font-face { font-family: 'Ploni Tzar'; src: url('<?php echo esc_url( $fonts . 'ploni_tzar_medium.woff2' ); ?>') format('woff2'); font-weight: 500; font-display: swap; }
@font-face { font-family: 'Ploni Tzar'; src: url('<?php echo esc_url( $fonts . 'ploni_tzar_bold.woff2' ); ?>') format('woff2'); font-weight: 700; font-display: swap; }
/* A copy of the tokens this page uses from the theme's src/styles/tokens.css; swap both together. */
:root {
  --ground: #091018; /* behind the sign: the menu's backdrop, made solid */
  --plate: #13212f; --plate-line: #3a4e62; --legend: #f2f4f1; --legend-muted: #a3b3bf;
  --works: #f7931d; --sign-ink: #15202b;
  --font-display: 'Ploni Tzar', 'Ploni', system-ui, sans-serif;
  --font-text: 'Ploni', system-ui, sans-serif;
  --ease-out: cubic-bezier(.16, 1, .3, 1);
  --icon-arrow: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 26 18'%3E%3Cpath d='M0 9 10.5 0v5.6H26v6.8H10.5V18z'/%3E%3C/svg%3E");
  color-scheme: dark;
}
*, *::before, *::after { box-sizing: border-box; }
html { background: var(--ground); }
body {
  margin: 0; min-height: 100vh; min-height: 100dvh; padding: 10px; display: grid;
  background: var(--ground); color: var(--legend); font: 400 1.0625rem/1.6 var(--font-text);
  -webkit-font-smoothing: antialiased; text-rendering: optimizeLegibility;
}
::selection { background: var(--works); color: var(--plate); }
:focus-visible { outline: 3px solid var(--works); outline-offset: 3px; }

/* The gantry: navy ground, white keyline inset 10px, 20px corners (the theme's .gantry__plate).
   The keyline is a bordered layer rather than the theme's two inset shadows: on this dark ground the shadows
   leave a white fringe on the outer corner. */
.sign {
  position: relative; display: grid; grid-template-rows: auto 1fr auto; border-radius: 20px; background: var(--plate);
  padding: clamp(30px, 3.6vw, 52px) clamp(34px, 5vw, 84px) clamp(30px, 3.2vw, 46px);
}
.sign::before { content: ''; position: absolute; inset: 10px; border: 3px solid var(--legend); border-radius: 10px; pointer-events: none; }
.logo { display: inline-flex; align-items: center; gap: .5rem; font: 700 1.5rem/1 var(--font-display); letter-spacing: -0.02em; }
.logo i { display: block; width: .8em; height: .8em; border-radius: 50%; border: .18em solid var(--works); }

.legend { align-self: center; padding-block: clamp(2.5rem, 7vh, 5rem); }
h1 {
  margin: 0; overflow: hidden; padding-top: .08em;
  font: 700 clamp(3.75rem, 11vw, 11.5rem)/.92 var(--font-display); letter-spacing: -0.035em;
}
h1 > span { display: inline-block; }
/* The route shield: 00 is home in the header order. */
.shield {
  display: inline-block; vertical-align: .5em; margin-inline-end: .45em; padding: .3em .5em .24em; border-radius: .35em;
  font: 700 max(.8125rem, .26em)/1 var(--font-display); letter-spacing: 0; font-variant-numeric: tabular-nums;
  background: var(--works); color: var(--sign-ink); box-shadow: inset 0 0 0 .17em var(--works), inset 0 0 0 .3em var(--sign-ink);
}

/* The lane marking is painted forward from the legend and stops at the works plate. Whole dashes only (round). */
.lane { display: flex; align-items: center; gap: clamp(14px, 1.6vw, 24px); margin-block: clamp(1.6rem, 4.2vh, 2.75rem) clamp(1.4rem, 3.4vh, 2.25rem); }
.lane i { flex: 1; height: 4px; background: linear-gradient(to left, var(--works) 30px, transparent 0) right / 54px 100% round; }
.works {
  flex: none; margin: 0; padding: .8rem 1.2rem .75rem; border-radius: 10px; white-space: nowrap;
  background: var(--works); color: var(--plate); font: 700 1.25rem/1 var(--font-display);
  box-shadow: inset 0 0 0 4px var(--works), inset 0 0 0 6px var(--plate);
}
.lede { max-width: 40ch; margin: 0; color: var(--legend-muted); font-size: clamp(1.0625rem, 1.3vw, 1.3125rem); line-height: 1.5; text-wrap: pretty; }

.foot {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem 2rem;
  padding-top: clamp(1.1rem, 2.6vh, 1.6rem); border-top: 2px solid var(--plate-line);
  font: 500 .9375rem var(--font-display); color: var(--legend-muted); font-variant-numeric: tabular-nums;
}
/* The way forward is a sign you can press: navy plate, white keyline that turns orange, the arrow steps forward. */
.go {
  position: relative; display: inline-flex; align-items: center; gap: .75em; padding: 1.05em 1.6em; border-radius: 10px;
  color: var(--legend); text-decoration: none; font: 700 1.0625rem/1.2 var(--font-display);
}
.go::before { content: ''; position: absolute; inset: 5px; border: 2px solid var(--legend); border-radius: 5px; transition: border-color .3s var(--ease-out); }
.go:hover::before, .go:focus-visible::before { border-color: var(--works); }
.arrow {
  display: inline-block; flex: none; width: 1.3em; height: .9em; background: currentColor;
  -webkit-mask: var(--icon-arrow) center / contain no-repeat; mask: var(--icon-arrow) center / contain no-repeat;
  transition: translate .45s var(--ease-out);
}
.go:hover .arrow, .go:focus-visible .arrow { translate: -6px 0; }

@media (max-width: 760px) {
  .lane i { height: 3px; background-size: 34px 100%; background-image: linear-gradient(to left, var(--works) 18px, transparent 0); }
  .works { font-size: 1.0625rem; padding: .7rem 1rem .65rem; }
}
@media (max-width: 560px) {
  body { padding: 6px; }
  .sign { border-radius: 14px; padding: 24px 24px 22px; }
  .sign::before { inset: 7px; border-width: 2px; border-radius: 7px; }
  .logo { font-size: 1.25rem; }
  .foot { flex-direction: column-reverse; align-items: stretch; }
  .go { justify-content: space-between; }
}

/* Mounting, as the theme's GSAP does it: the sign unrolls, the legend rises, the lane is painted, the works plate drops in.
   Nothing here runs or hides anything under reduced motion. */
@media (prefers-reduced-motion: no-preference) {
  .sign { animation: unroll .8s cubic-bezier(.87, 0, .13, 1) both; }
  .logo { animation: arrive .9s cubic-bezier(.19, 1, .22, 1) .4s both; }
  h1 > span { animation: rise 1.15s cubic-bezier(.165, .84, .44, 1) .45s both; }
  .lane i { animation: paint 1s cubic-bezier(.65, 0, .35, 1) .75s both; }
  .works { animation: mount .8s cubic-bezier(.19, 1, .22, 1) 1.5s both; }
  .lede { animation: arrive 1s cubic-bezier(.19, 1, .22, 1) .9s both; }
  .foot { animation: arrive 1s cubic-bezier(.19, 1, .22, 1) 1.05s both; }
}
@keyframes unroll { from { clip-path: inset(0 0 100% 0 round 20px); } to { clip-path: inset(0 0 0 0 round 20px); } }
@keyframes rise { from { transform: translateY(112%); } }
@keyframes paint { from { clip-path: inset(0 0 0 100%); } to { clip-path: inset(0); } } /* forward is left: paint from the start edge */
@keyframes mount { from { clip-path: inset(0 0 100% 0 round 10px); } to { clip-path: inset(0 0 0 0 round 10px); } }
@keyframes arrive { from { opacity: 0; transform: translateY(14px); } }
</style>
</head>
<body>
<main class="sign">
	<div class="logo"><i></i>לוג ניהול פרויקטים</div>
	<div class="legend">
		<h1><span><span class="shield" aria-hidden="true">00</span>האתר בבנייה.</span></h1>
		<div class="lane"><i aria-hidden="true"></i><p class="works">אתר חדש · בתכנון מתקדם</p></div>
		<p class="lede">אנחנו מתכננים שכונות וערים, ועכשיו גם את הבית החדש שלנו ברשת. נחזור בקרוב עם מפת כל הפרויקטים.</p>
	</div>
	<footer class="foot">
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $name ); ?></span>
		<a class="go" href="https://www.instagram.com/log_projectmanagement/" rel="noopener">בינתיים, באינסטגרם<i class="arrow" aria-hidden="true"></i></a>
	</footer>
</main>
</body>
</html>
	<?php
}
