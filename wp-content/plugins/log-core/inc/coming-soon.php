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
<title><?php echo esc_html( $name ); ?> · בקרוב</title>
<style>
@font-face { font-family: 'Ploni'; src: url('<?php echo esc_url( $fonts ); ?>ploni_light.woff2') format('woff2'); font-weight: 300; font-display: swap; }
@font-face { font-family: 'Ploni'; src: url('<?php echo esc_url( $fonts ); ?>ploni_demibold.woff2') format('woff2'); font-weight: 600; font-display: swap; }
:root { --ink: #121922; --line: rgba(255,246,236,.12); --paper: #fbfaf8; --mute: #8f99a6; --orange: #f7931d; }
* { box-sizing: border-box; margin: 0; }
html, body { height: 100%; overflow: hidden; }
body { background: var(--ink); color: var(--paper); font: 300 18px/1.6 'Ploni', system-ui, sans-serif; overflow: hidden;
  background-image: radial-gradient(90% 70% at 20% 85%, #2b3d50 0%, transparent 60%),
    repeating-linear-gradient(0deg, var(--line) 0 1px, transparent 1px 72px),
    repeating-linear-gradient(90deg, var(--line) 0 1px, transparent 1px 72px); }
main { min-height: 100%; display: grid; grid-template-rows: auto 1fr auto; padding: clamp(24px, 5vw, 64px); position: relative; overflow: hidden; height: 100%; }
.mark { font-weight: 600; font-size: 22px; letter-spacing: .02em; display: flex; align-items: center; gap: 10px; }
.mark i { display: block; flex: none; width: 18px; height: 18px; border: 3px solid var(--orange); border-radius: 50%; }
.copy { align-self: center; position: relative; z-index: 1; max-width: 34ch; }
.kicker { color: var(--orange); font-weight: 600; font-size: 15px; letter-spacing: .08em; margin-bottom: 18px; }
h1 { font-weight: 600; font-size: clamp(44px, 8vw, 104px); line-height: .95; letter-spacing: -.01em; }
p.lead { margin-top: 24px; color: #c9cfd6; max-width: 30ch; }
footer { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 12px; font-size: 14px; color: var(--mute); }
footer a { color: var(--paper); text-decoration: none; border-bottom: 1px solid var(--orange); }
.ring { position: absolute; left: clamp(-180px, -8vw, -60px); bottom: clamp(-180px, -10vw, -60px); width: min(70vw, 620px); aspect-ratio: 1; pointer-events: none; }
.ring circle { fill: none; stroke: var(--orange); stroke-width: 22; stroke-dasharray: 1200; stroke-dashoffset: 1200; animation: draw 2.4s cubic-bezier(.65,0,.35,1) .3s forwards; }
@keyframes draw { to { stroke-dashoffset: 300; } }
@media (prefers-reduced-motion: reduce) { .ring circle { animation: none; stroke-dashoffset: 300; } }
</style>
</head>
<body>
<main>
	<div class="mark"><i></i>לוג ניהול פרויקטים</div>
	<div class="copy">
		<div class="kicker">אתר חדש · בתכנון מתקדם</div>
		<h1>האתר בבנייה.</h1>
		<p class="lead">אנחנו מתכננים שכונות וערים, ועכשיו גם את הבית החדש שלנו ברשת. נחזור בקרוב עם מפת כל הפרויקטים.</p>
	</div>
	<footer>
		<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php echo esc_html( $name ); ?></span>
		<a href="https://www.instagram.com/log_projectmanagement/" rel="noopener">בינתיים, באינסטגרם</a>
	</footer>
	<svg class="ring" viewBox="0 0 440 440" aria-hidden="true"><circle cx="220" cy="220" r="190" transform="rotate(-90 220 220)"/></svg>
</main>
</body>
</html>
	<?php
}
