<?php
/**
 * The site's gate until launch: the site is published, and one password opens it.
 *
 * A visitor without the password gets a single page with a field for it (401, never indexed). The right
 * password leaves a cookie in that browser for 30 days and the whole site opens the way the public will see
 * it: no account, no WordPress bar. Signed-in editors pass without it. The brand book keeps its own lock.
 * wp-admin, the login page, AJAX and cron are untouched. The REST API answers editors, visitors who unlocked,
 * and GitHub's deploy, which proves itself with its own token (deploy.php).
 *
 * Settings → General → "גישה לאתר": the password, and the switch that opens the site on launch day.
 * The switch is still stored as `log_coming_soon`, the name it had when this was a holding page, so the live
 * site stayed closed across the deploy that changed it.
 */

defined( 'ABSPATH' ) || exit;

const LOG_GATE_COOKIE = 'log_gate';
const LOG_GATE_DAYS   = 30;
const LOG_GATE_TRIES  = 8;   // wrong passwords from one address before it has to wait
const LOG_GATE_WAIT   = 600; // seconds

function log_gate_on(): bool {
	return (bool) get_option( 'log_coming_soon', '1' );
}

/**
 * The password. Nobody types the first one: it is made here, at random, the first time the gate needs it,
 * and an administrator reads or replaces it in Settings → General.
 */
function log_gate_password(): string {
	$password = trim( (string) get_option( 'log_gate_password', '' ) );
	if ( $password === '' ) {
		$password = wp_generate_password( 14, false );
		update_option( 'log_gate_password', $password, false );
	}
	return $password;
}

/** What the cookie holds: a keyed hash of the password, so replacing the password signs everyone out. */
function log_gate_token( ?string $password = null ): string {
	return hash_hmac( 'sha256', $password ?? log_gate_password(), wp_salt( 'auth' ) );
}

function log_gate_unlocked(): bool {
	$cookie = (string) ( $_COOKIE[ LOG_GATE_COOKIE ] ?? '' );
	return $cookie !== '' && hash_equals( log_gate_token(), $cookie );
}

add_action( 'admin_init', function () {
	register_setting( 'general', 'log_coming_soon', [
		'type'              => 'string',
		'default'           => '1',
		'sanitize_callback' => fn( $v ) => $v ? '1' : '0',
	] );
	register_setting( 'general', 'log_gate_password', [
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => function ( $v ) {
			static $told = false; // WordPress may sanitize a new option twice
			$v = trim( (string) $v );
			if ( mb_strlen( $v ) >= 4 ) { // short is allowed: the wait after wrong tries is what slows guessing
				return $v;
			}
			if ( ! $told ) {
				$told = true;
				add_settings_error( 'log_gate_password', 'short', 'סיסמת האתר צריכה 4 תווים לפחות. הסיסמה הקודמת נשארה.' );
			}
			return log_gate_password();
		},
	] );
	add_settings_field( 'log_coming_soon', 'גישה לאתר', function () {
		printf(
			'<fieldset><label><input type="checkbox" name="log_coming_soon" id="log_coming_soon" value="1" %s> האתר סגור בסיסמה</label>'
			. '<p><label for="log_gate_password">הסיסמה</label><br><input type="text" class="regular-text code" dir="ltr" name="log_gate_password" id="log_gate_password" value="%s" autocomplete="off" spellcheck="false"></p>'
			. '<p class="description">מי שמקליד אותה רואה את האתר המלא, בלי חשבון, ונשאר בפנים %d יום באותו דפדפן. החלפת הסיסמה מוציאה את כולם.<br>מחוברים עם הרשאת עריכה נכנסים בלעדיה. ביום ההשקה מסירים את הסימון, והאתר נפתח לכולם.</p></fieldset>',
			checked( log_gate_on(), true, false ),
			esc_attr( log_gate_password() ),
			(int) LOG_GATE_DAYS
		);
	}, 'general' );
} );

// A reminder in the admin bar, so nobody forgets the public can't see the site.
add_action( 'admin_bar_menu', function ( WP_Admin_Bar $bar ) {
	if ( log_gate_on() && current_user_can( 'manage_options' ) ) {
		$bar->add_node( [
			'id'    => 'log-gate',
			'title' => 'האתר סגור בסיסמה',
			'href'  => admin_url( 'options-general.php#log_coming_soon' ),
		] );
	}
}, 100 );

add_action( 'template_redirect', function () {
	if ( ! log_gate_on() ) {
		return;
	}
	$preview = isset( $_GET['gate'] ); // editors can see what visitors get: /?gate
	// The brand book has its own lock (a WP page password) and is never indexed: its password is enough.
	if ( ! $preview && is_page( LOG_BRAND_BOOK_SLUG ) ) {
		return;
	}
	// While the gate stands nothing is indexed, and nothing is kept by a cache that could hand an opened
	// page to the next visitor.
	header( 'X-Robots-Tag: noindex, nofollow' );
	nocache_headers();
	defined( 'DONOTCACHEPAGE' ) || define( 'DONOTCACHEPAGE', true );
	if ( ! $preview && ( current_user_can( 'edit_posts' ) || log_gate_unlocked() ) ) {
		return;
	}

	$error = '';
	if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['log_gate_password'] ) ) {
		$key   = 'log_gate_tries_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$tries = (int) get_transient( $key );
		$given = trim( (string) wp_unslash( $_POST['log_gate_password'] ) );
		if ( $tries >= LOG_GATE_TRIES ) {
			$error = 'wait';
		} elseif ( hash_equals( log_gate_token(), log_gate_token( $given ) ) ) {
			delete_transient( $key );
			setcookie( LOG_GATE_COOKIE, log_gate_token(), [
				'expires'  => time() + LOG_GATE_DAYS * DAY_IN_SECONDS,
				'path'     => COOKIEPATH ?: '/',
				'domain'   => (string) COOKIE_DOMAIN,
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			] );
			wp_safe_redirect( remove_query_arg( 'gate' ), 303 ); // back to the page they asked for
			exit;
		} else {
			set_transient( $key, $tries + 1, LOG_GATE_WAIT );
			$error = 'wrong';
		}
	}
	status_header( 401 );
	log_gate_page( $error );
	exit;
}, 0 );

/**
 * The REST API holds the site's content too. While the gate stands it answers editors (this runs before
 * WordPress drops a cookie login that came without a nonce, which is how the theme's own scripts call it),
 * visitors who unlocked, and the deploy route.
 */
add_filter( 'rest_authentication_errors', function ( $result ) {
	if ( is_wp_error( $result ) || ! log_gate_on() || current_user_can( 'edit_posts' ) || log_gate_unlocked() ) {
		return $result;
	}
	$route = untrailingslashit( (string) ( $GLOBALS['wp']->query_vars['rest_route'] ?? '' ) );
	if ( $route === '/log/v1/deploy' ) {
		return $result;
	}
	return new WP_Error( 'log_gate', 'האתר סגור בסיסמה.', [ 'status' => 401 ] );
}, 5 );

/**
 * The page is one full-screen gantry sign, the same plate as the theme's menu: route number 00 and the
 * legend, a lane marking that is painted as far as the works plate and stops there, and under it the field.
 * Self-contained (inline CSS, the theme's self-hosted fonts, no script), so it never depends on the build.
 * Its colours and fonts are the theme's tokens: assets/brand/tokens.css, written from src/tokens/tokens.json.
 *
 * @param string $error '' on arrival, 'wrong' after a wrong password, 'wait' after too many.
 */
function log_gate_page( string $error = '' ): void {
	$fonts  = get_template_directory_uri() . '/assets/fonts/';
	$name   = get_bloginfo( 'name' );
	$file   = get_template_directory() . '/assets/brand/tokens.css';
	$tokens = is_readable( $file ) ? str_replace( '</', '', (string) file_get_contents( $file ) ) : '';
	if ( $tokens === '' ) { // another theme is active: the few values this page needs
		$tokens = ':root { --night: #13212f; --night-line: #3a4e62; --night-deep: #0b131b; --night-ink: #f2f4f1; --night-muted: #a3b3bf; --works: #f89e2d; --logo-orange-deep: #f67b1c; --sign-ink: #15202b; --works-light: #fbc48a; --line-w: 1.5px; --rule-w: 1px; --r-control: 8px; --r-button: 999px; --font-display: \'Assistant\', system-ui, sans-serif; --font-headline: \'Rubik\', \'Assistant\', system-ui, sans-serif; --font-text: \'Assistant\', system-ui, sans-serif; --ease-out: cubic-bezier(.16, 1, .3, 1); }';
	}
	?>
<!doctype html>
<html lang="he-IL" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#0b131b">
<title><?php echo esc_html( $name ); ?> · כניסה</title>
<link rel="preload" href="<?php echo esc_url( $fonts . 'rubik-hebrew.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="<?php echo esc_url( $fonts . 'assistant-hebrew.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
<style>
@font-face { font-family: 'Rubik'; src: url('<?php echo esc_url( $fonts . 'rubik-hebrew.woff2' ); ?>') format('woff2'); font-weight: 300 900; font-display: swap; unicode-range: U+0307-0308, U+0590-05FF, U+200C-2010, U+20AA, U+25CC, U+FB1D-FB4F; }
@font-face { font-family: 'Rubik'; src: url('<?php echo esc_url( $fonts . 'rubik-latin.woff2' ); ?>') format('woff2'); font-weight: 300 900; font-display: swap; unicode-range: U+0000-00FF, U+2000-206F, U+20AC, U+2122, U+2212; }
@font-face { font-family: 'Assistant'; src: url('<?php echo esc_url( $fonts . 'assistant-hebrew.woff2' ); ?>') format('woff2'); font-weight: 200 800; font-display: swap; unicode-range: U+0307-0308, U+0590-05FF, U+200C-2010, U+20AA, U+25CC, U+FB1D-FB4F; }
@font-face { font-family: 'Assistant'; src: url('<?php echo esc_url( $fonts . 'assistant-latin.woff2' ); ?>') format('woff2'); font-weight: 200 800; font-display: swap; unicode-range: U+0000-00FF, U+2000-206F, U+20AC, U+2122, U+2212; }
<?php echo $tokens; // the theme's own generated file ?>

:root {
  --ground: var(--night-deep); /* behind the sign */
  --plate: var(--night); --plate-line: var(--night-line); --legend: var(--night-ink); --legend-muted: var(--night-muted);
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

/* The gantry: navy ground, a thin white keyline inset 10px, 20px corners (the theme's .gantry__plate).
   The keyline is a bordered layer rather than an inset shadow: on this dark ground a shadow leaves a white
   fringe on the outer corner. It is the language's one line weight, --line-w. */
.sign {
  position: relative; display: grid; grid-template-rows: auto 1fr auto; border-radius: 20px; background: var(--plate);
  padding: clamp(30px, 3.6vw, 52px) clamp(34px, 5vw, 84px) clamp(30px, 3.2vw, 46px);
}
.sign::before { content: ''; position: absolute; inset: 10px; border: var(--line-w) solid color-mix(in srgb, var(--legend) 70%, transparent); border-radius: 10px; pointer-events: none; }
/* The logo: the theme's log_logo(), white and orange on night (the same hooks as the theme's header.css). */
.logo { --logo-w: 8.5rem; --logo-a: var(--legend); --logo-b: var(--works); --logo-b-deep: var(--logo-orange-deep); justify-self: start; display: inline-grid; width: var(--logo-w); color: var(--logo-a); }
.logo__mark { display: block; width: 100%; height: auto; }
.logo--text { width: auto; font: 700 1.5rem/1 var(--font-display); letter-spacing: -0.02em; }
.title-end { white-space: nowrap; }
.period { display: inline-block; color: var(--works); transform-origin: 50% 50%; }
/* The full stop is drawn round, as on the site (base.css .period--dot): Rubik's own is a soft square. */
h1 .period { width: .2em; height: .2em; margin-inline-start: .05em; overflow: hidden; border-radius: 50%; background: var(--works); color: transparent; line-height: 0; vertical-align: baseline; }

.legend { align-self: center; padding-block: clamp(2rem, 6vh, 4.5rem); }
h1 {
  margin: 0; overflow: hidden; padding-top: .08em;
  font: 900 clamp(3rem, 7.6vw, 7.5rem)/.94 var(--font-headline); letter-spacing: -0.01em;
}
h1 > span { display: inline-block; }
/* The route number, as the theme sets it (base.css .shield): 00 is home in the header order. An orange figure at the
   top of the headline's start, the way the brand book numbers a chapter, with no plate around it. */
.shield {
  display: inline-block; vertical-align: top; margin: .3em 0 0; margin-inline-end: .55em;
  font: 600 max(.8125rem, .19em)/1 var(--font-display); letter-spacing: 0; font-variant-numeric: tabular-nums;
  color: var(--works);
}

/* The lane marking is painted forward from the legend and stops at the works plate. Whole dashes only (round). */
.lane { display: flex; align-items: center; gap: clamp(14px, 1.6vw, 24px); margin-block: clamp(1.6rem, 4.2vh, 2.75rem) clamp(1.4rem, 3.4vh, 2.25rem); }
.lane i { flex: 1; height: var(--line-w); background: linear-gradient(to left, var(--works) 22px, transparent 0) right / 40px 100% round; }
.works {
  flex: none; margin: 0; padding: .7rem 1.25rem .68rem; border-radius: var(--r-button); white-space: nowrap;
  color: var(--sign-ink); font: 600 1.125rem/1 var(--font-display);
  background: color-mix(in srgb, var(--works) 90%, transparent) linear-gradient(to bottom, color-mix(in srgb, var(--night-ink) 22%, transparent), transparent 55%);
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--works-light) 90%, transparent), inset 0 -10px 14px -13px color-mix(in srgb, var(--night-ink) 55%, transparent);
}
.lede { max-width: 34rem; margin: 0; color: var(--legend-muted); font-size: clamp(1.0625rem, 1.3vw, 1.3125rem); line-height: 1.5; text-wrap: balance; }

/* The key: a field on the sheet is the thin line closed around the place to write, and beside it the way in. */
.key { display: grid; justify-items: start; gap: .55rem; margin-top: clamp(1.5rem, 3.6vh, 2.25rem); }
.key label { font: 600 1rem/1.2 var(--font-display); color: var(--legend-muted); }
.key__row { display: flex; align-items: stretch; gap: .9rem; width: 100%; max-width: 34rem; }
.key input {
  flex: 1; min-width: 0; height: 3.5rem; padding: 0 1.1rem; border: var(--line-w) solid var(--plate-line); border-radius: var(--r-control);
  background: transparent; color: var(--legend); font: 400 1.125rem/1 var(--font-text); letter-spacing: .08em; direction: ltr; text-align: left;
  transition: border-color .3s var(--ease-out);
}
.key input:hover { border-color: var(--legend-muted); }
.key input:focus { border-color: var(--legend); outline: 0; }
.key input[aria-invalid="true"] { border-color: var(--works); }
.key__error { margin: .15rem 0 0; font: 600 1rem/1.4 var(--font-display); color: var(--works); }

.foot {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem 2rem;
  padding-top: clamp(1.1rem, 2.6vh, 1.6rem); border-top: var(--rule-w) solid var(--plate-line);
  font: 600 .9375rem var(--font-display); color: var(--legend-muted); font-variant-numeric: tabular-nums;
}
/* The way in is the site's button (buttons.css): a capsule of smoked glass with a bright rim on top and light at its
   foot. Hover turns it white with an ink legend and the arrow steps forward. No script here, so no pointer highlight. */
.go {
  position: relative; display: inline-flex; align-items: center; justify-content: center; gap: .7em; padding: 0 1.65em; border: 0; border-radius: var(--r-button);
  color: var(--legend); text-decoration: none; font: 600 1.0625rem/1.2 var(--font-display); cursor: pointer;
  background: color-mix(in srgb, var(--night-ink) 10%, transparent) linear-gradient(to bottom, color-mix(in srgb, var(--night-ink) 14%, transparent), transparent 50%);
  box-shadow: inset 0 1px 0 color-mix(in srgb, var(--night-ink) 50%, transparent), inset 0 0 0 1px color-mix(in srgb, var(--night-ink) 30%, transparent), inset 0 -12px 16px -15px color-mix(in srgb, var(--night-ink) 40%, transparent);
  transition: background-color .45s var(--ease-out), color .35s var(--ease-out), scale .5s var(--ease-out);
}
button.go { flex: none; }
.go:hover, .go:focus-visible { background-color: color-mix(in srgb, var(--legend) 92%, transparent); color: var(--sign-ink); }
.go:active { scale: .97; }
.foot a { color: inherit; text-underline-offset: .3em; text-decoration-thickness: 1px; transition: color .3s var(--ease-out); }
.foot a:hover, .foot a:focus-visible { color: var(--legend); }
.arrow {
  display: inline-block; flex: none; width: 1.3em; height: .9em; background: currentColor;
  -webkit-mask: var(--icon-arrow) center / contain no-repeat; mask: var(--icon-arrow) center / contain no-repeat;
  transition: translate .45s var(--ease-out);
}
.go:hover .arrow, .go:focus-visible .arrow { translate: -6px 0; }

@media (max-width: 760px) {
  .lane i { background-size: 30px 100%; background-image: linear-gradient(to left, var(--works) 16px, transparent 0); }
  .works { font-size: 1rem; padding: .62rem 1.05rem .6rem; }
}
@media (max-width: 560px) {
  body { padding: 6px; }
  .sign { border-radius: 14px; padding: 24px 24px 22px; }
  .sign::before { inset: 7px; border-radius: 7px; }
  .logo { --logo-w: 6.5rem; }
  .key__row { flex-direction: column; }
  .key input { flex: none; }
  button.go { min-height: 3.5rem; }
  .go { justify-content: space-between; }
  .foot { flex-direction: column-reverse; align-items: start; }
}

/* Mounting, as the theme's GSAP does it: the sign unrolls, the legend rises, the lane is painted, the works plate drops in.
   Nothing here runs or hides anything under reduced motion, and nothing runs again when the page comes back
   with a wrong password. */
@media (prefers-reduced-motion: no-preference) {
  .is-back * { animation: none !important; }
  .sign { animation: unroll .8s cubic-bezier(.87, 0, .13, 1) both; }
  .logo { animation: arrive .9s cubic-bezier(.19, 1, .22, 1) .4s both; }
  h1 > span { animation: rise 1.15s cubic-bezier(.165, .84, .44, 1) .45s both; }
  .lane i { animation: paint 1s cubic-bezier(.65, 0, .35, 1) .75s both; }
  .period { animation: land .7s cubic-bezier(.34, 1.8, .64, 1) 1.3s both; } /* the junction lands after its words */
  .works { animation: mount .8s cubic-bezier(.19, 1, .22, 1) 1.5s both; }
  .lede { animation: arrive 1s cubic-bezier(.19, 1, .22, 1) .9s both; }
  .key { animation: arrive 1s cubic-bezier(.19, 1, .22, 1) 1.05s both; }
  .foot { animation: arrive 1s cubic-bezier(.19, 1, .22, 1) 1.2s both; }
}
@keyframes unroll { from { clip-path: inset(0 0 100% 0 round 20px); } to { clip-path: inset(0 0 0 0 round 20px); } }
@keyframes rise { from { transform: translateY(112%); } }
@keyframes paint { from { clip-path: inset(0 0 0 100%); } to { clip-path: inset(0); } } /* forward is left: paint from the start edge */
@keyframes mount { from { clip-path: inset(0 0 100% 0 round 999px); } to { clip-path: inset(0 0 0 0 round 999px); } }
@keyframes land { from { transform: scale(0); } }
@keyframes arrive { from { opacity: 0; transform: translateY(14px); } }
</style>
</head>
<body<?php echo $error ? ' class="is-back"' : ''; ?>>
<main class="sign">
	<?php
	// The theme's wordmark. log_logo() returns '' if its SVG is missing; the name stands in then, and when another theme is active.
	$logo = function_exists( 'log_logo' ) ? log_logo( [ 'variant' => 'night', 'label' => $name ] ) : '';
	echo $logo !== '' ? $logo : '<div class="logo logo--text">' . esc_html( $name ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in log_logo()
	?>
	<div class="legend">
		<h1><span><span class="shield" aria-hidden="true">00</span>כניסה <span class="title-end">למוזמנים<span class="period">.</span></span></span></h1>
		<div class="lane"><i aria-hidden="true"></i><p class="works">לפני ההשקה</p></div>
		<p class="lede">האתר החדש עוד לא נפתח לציבור. קיבלתם מאיתנו סיסמה? מכאן נכנסים.</p>
		<form class="key" method="post">
			<label for="log-gate-password">סיסמה</label>
			<div class="key__row">
				<input type="password" id="log-gate-password" name="log_gate_password" required autofocus autocomplete="current-password" autocapitalize="none" spellcheck="false"<?php echo $error ? ' aria-invalid="true" aria-describedby="log-gate-error"' : ''; ?>>
				<button class="go" type="submit">כניסה<i class="arrow" aria-hidden="true"></i></button>
			</div>
			<?php if ( $error ) : ?>
				<p class="key__error" id="log-gate-error" role="alert"><?php echo $error === 'wait' ? 'יותר מדי ניסיונות. חכו עשר דקות ונסו שוב.' : 'זו לא הסיסמה. בדקו אותיות גדולות וקטנות ונסו שוב.'; ?></p>
			<?php endif; ?>
		</form>
	</div>
	<footer class="foot">
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $name ); ?></span>
		<a href="https://www.instagram.com/log_projectmanagement/" rel="noopener">בינתיים, באינסטגרם</a>
	</footer>
</main>
</body>
</html>
	<?php
}
