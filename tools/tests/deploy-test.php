<?php
/**
 * Tests for log-core/inc/deploy.php without WordPress: a few stubs, a local RSA key standing in for GitHub's,
 * and a throwaway wp-content. Run with tools/test-deploy.sh.
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'MINUTE_IN_SECONDS', 60 ); define( 'HOUR_IN_SECONDS', 3600 ); define( 'DAY_IN_SECONDS', 86400 );
define( 'WP_CONTENT_DIR', __DIR__ . '/wpc' );
define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
function get_theme_root() { return WP_CONTENT_DIR . '/themes'; }
class WP_Error { function __construct( public $code = '', public $message = '', public $data = null ) {} }
function is_wp_error( $x ) { return $x instanceof WP_Error; }
$GLOBALS['t'] = []; $GLOBALS['fetches'] = 0;
function get_transient( $k ) { return $GLOBALS['t'][ $k ] ?? false; }
function set_transient( $k, $v, $e = 0 ) { $GLOBALS['t'][ $k ] = $v; return true; }
function delete_transient( $k ) { unset( $GLOBALS['t'][ $k ] ); }
function add_action( ...$a ) {}
function wp_mkdir_p( $d ) { return is_dir( $d ) || mkdir( $d, 0777, true ); }
function wp_remote_get( $u, $a = [] ) { $GLOBALS['fetches']++; return [ 'body' => $GLOBALS['jwks'] ]; }
function wp_remote_retrieve_body( $r ) { return $r['body']; }
function rest_ensure_response( $x ) { return $x; }
class WP_REST_Request { function __construct( public array $h, public string $body ) {} function get_header( $k ) { return $this->h[ $k ] ?? null; } function get_body() { return $this->body; } }
require __DIR__ . '/deploy.php';

function b64u( $s ) { return rtrim( strtr( base64_encode( $s ), '+/', '-_' ), '=' ); }
$key = openssl_pkey_get_private( file_get_contents( __DIR__ . '/k1.pem' ) );
$det = openssl_pkey_get_details( $key );
$GLOBALS['jwks'] = json_encode( [ 'keys' => [ [ 'kty' => 'RSA', 'kid' => 'k1', 'n' => b64u( $det['rsa']['n'] ), 'e' => b64u( $det['rsa']['e'] ) ] ] ] );
function mint( array $claims, $key, array $header = [ 'alg' => 'RS256', 'kid' => 'k1' ] ) {
	$si = b64u( json_encode( $header ) ) . '.' . b64u( json_encode( $claims ) );
	openssl_sign( $si, $sig, $key, OPENSSL_ALGO_SHA256 );
	return $si . '.' . b64u( $sig );
}
function makezip( array $files ) {
	static $i = 0; $f = __DIR__ . '/tmp' . ( ++$i ) . '.zip'; $z = new ZipArchive(); $z->open( $f, ZipArchive::CREATE | ZipArchive::OVERWRITE );
	foreach ( $files as $n => $c ) { $z->addFromString( $n, $c ); }
	$z->close(); $b = file_get_contents( $f ); unlink( $f ); return $b;
}
$pass = 0; $fail = 0;
function check( $label, $got, $want ) {
	global $pass, $fail;
	$g = is_wp_error( $got ) ? $got->message : ( is_array( $got ) ? json_encode( $got ) : var_export( $got, true ) );
	$ok = str_contains( $g, $want );
	$ok ? $pass++ : $fail++;
	printf( "%s  %-38s %s\n", $ok ? 'PASS' : 'FAIL', $label, $g );
}

// Round trip: the PEM built from n/e is the same key.
$pem = log_deploy_rsa_pem( $det['rsa']['n'], $det['rsa']['e'] );
check( 'PEM from n/e matches the key', openssl_pkey_get_details( openssl_pkey_get_public( $pem ) )['rsa']['n'] === $det['rsa']['n'], 'true' );

// Live site: old theme with a file the new version drops.
wp_mkdir_p( WP_CONTENT_DIR . '/themes/log' ); wp_mkdir_p( WP_CONTENT_DIR . '/plugins/log-core' );
file_put_contents( WP_CONTENT_DIR . '/themes/log/style.css', 'old' );
file_put_contents( WP_CONTENT_DIR . '/themes/log/dropped.php', 'old only' );
file_put_contents( WP_CONTENT_DIR . '/plugins/log-core/log-core.php', 'old' );

$zip  = makezip( [ 'themes/log/style.css' => 'new', 'themes/log/assets/dist/main.js' => 'js', 'plugins/log-core/log-core.php' => 'new' ] );
$sha  = hash( 'sha256', $zip );
$base = [ 'iss' => LOG_DEPLOY_ISSUER, 'aud' => "log-pm-deploy:$sha", 'repository' => 'itay1313/log-pm', 'repository_id' => '1428886628',
	'workflow_ref' => 'itay1313/log-pm/.github/workflows/deploy.yml@refs/heads/main', 'event_name' => 'push', 'ref' => 'refs/heads/main',
	'exp' => time() + 300, 'nbf' => time() - 5, 'iat' => time() - 5, 'sha' => 'abc123', 'run_id' => '42' ];
$req = fn( $claims, $body = null, $hdr = null ) => new WP_REST_Request( [ 'x_log_deploy_token' => $claims === null ? null : mint( $claims + [ 'jti' => uniqid( '', true ) ], $GLOBALS['key'], $hdr ?? [ 'alg' => 'RS256', 'kid' => 'k1' ] ) ], $body ?? $GLOBALS['zip'] );
$GLOBALS['key'] = $key; $GLOBALS['zip'] = $zip;

check( 'no token', log_deploy_authorize( $req( null ) ), 'Missing X-Log-Deploy-Token' );
check( 'alg none', log_deploy_authorize( $req( $base, null, [ 'alg' => 'none', 'kid' => 'k1' ] ) ), 'Unsupported token' );
check( 'other zip than the token was minted for', log_deploy_authorize( $req( $base, $zip . 'x' ) ), 'Token rejected: audience' );
check( 'other repository', log_deploy_authorize( $req( [ 'repository' => 'evil/log-pm' ] + $base ) ), 'Token rejected: repository' );
check( 'same name, other repository id', log_deploy_authorize( $req( [ 'repository_id' => '1' ] + $base ) ), 'Token rejected: repository' );
check( 'other workflow file', log_deploy_authorize( $req( [ 'workflow_ref' => 'itay1313/log-pm/.github/workflows/x.yml@refs/heads/main' ] + $base ) ), 'Token rejected: workflow' );
check( 'push from a feature branch', log_deploy_authorize( $req( [ 'ref' => 'refs/heads/feat/x' ] + $base ) ), 'Token rejected: event' );
check( 'pull_request event', log_deploy_authorize( $req( [ 'event_name' => 'pull_request' ] + $base ) ), 'Token rejected: event' );
check( 'expired', log_deploy_authorize( $req( [ 'exp' => time() - 120 ] + $base ) ), 'Token rejected: time' );
check( 'not valid yet', log_deploy_authorize( $req( [ 'nbf' => time() + 600 ] + $base ) ), 'Token rejected: time' );
$tk = mint( $base + [ "jti" => "x" ], $key ); $p = explode( ".", $tk ); $p[1] = b64u( json_encode( [ 'repository' => 'evil/x' ] + $base ) );
check( 'claims edited after signing', log_deploy_authorize( new WP_REST_Request( [ 'x_log_deploy_token' => implode( '.', $p ) ], $zip ) ), 'Bad token signature' );
$other = openssl_pkey_get_private( file_get_contents( __DIR__ . '/k2.pem' ) );
check( 'signed by someone else', log_deploy_authorize( new WP_REST_Request( [ 'x_log_deploy_token' => mint( $base + [ 'jti' => 'y' ], $other ) ], $zip ) ), 'Bad token signature' );
$f0 = $GLOBALS['fetches'];
check( 'unknown kid', log_deploy_authorize( $req( $base, null, [ 'alg' => 'RS256', 'kid' => 'nope' ] ) ), 'Unknown signing key' );
check( 'unknown kid again: no refetch', log_deploy_authorize( $req( $base, null, [ 'alg' => 'RS256', 'kid' => 'nope2' ] ) ), 'Unknown signing key' );
check( 'JWKS fetched at most once', $GLOBALS['fetches'] - $f0, '0' );

$tok = mint( $base + [ 'jti' => 'once' ], $key );
check( 'valid push to main', log_deploy_authorize( new WP_REST_Request( [ 'x_log_deploy_token' => $tok ], $zip ) ), 'true' );
check( 'install', log_deploy_handle( new WP_REST_Request( [], $zip ) ), '"installed":["themes\/log","plugins\/log-core"]' );
check( 'live theme is new', file_get_contents( WP_CONTENT_DIR . '/themes/log/style.css' ), 'new' );
check( 'dropped file is gone from live', var_export( file_exists( WP_CONTENT_DIR . '/themes/log/dropped.php' ), true ), 'false' );
check( 'previous version kept', file_get_contents( WP_CONTENT_DIR . '/upgrade/log-deploy/previous/themes/log/dropped.php' ), 'old only' );
check( 'live plugin is new', file_get_contents( WP_CONTENT_DIR . '/plugins/log-core/log-core.php' ), 'new' );
check( 'no leftovers', json_encode( array_values( array_diff( scandir( WP_CONTENT_DIR . '/upgrade/log-deploy' ), [ '.', '..' ] ) ) ), '["previous"]' );
check( 'same token twice', log_deploy_authorize( new WP_REST_Request( [ 'x_log_deploy_token' => $tok ], $zip ) ), 'Token already used' );
check( 'hand-run deploy of a branch', log_deploy_authorize( $req( [ 'event_name' => 'workflow_dispatch', 'ref' => 'refs/heads/feat/x', 'workflow_ref' => 'itay1313/log-pm/.github/workflows/deploy.yml@refs/heads/feat/x' ] + $base ) ), 'true' );

check( 'zip escaping its folder', log_deploy_install( makezip( [ 'themes/log/style.css' => 'a', 'plugins/log-core/log-core.php' => 'a', 'themes/log/../../evil.php' => 'x' ] ) ), 'Unexpected path' );
check( 'zip with a stray folder', log_deploy_install( makezip( [ 'themes/log/style.css' => 'a', 'plugins/log-core/log-core.php' => 'a', 'mu-plugins/x.php' => 'x' ] ) ), 'Unexpected path' );
check( 'zip without log-core.php', log_deploy_install( makezip( [ 'themes/log/style.css' => 'a', 'plugins/log-core/readme.txt' => 'a' ] ) ), 'no plugins/log-core/log-core.php' );
check( 'not a zip', log_deploy_install( 'hello' ), 'Not a zip' );
check( 'live untouched after bad zips', file_get_contents( WP_CONTENT_DIR . '/themes/log/style.css' ), 'new' );
check( 'second deploy replaces previous', log_deploy_install( makezip( [ 'themes/log/style.css' => 'v3', 'plugins/log-core/log-core.php' => 'v3' ] ) ), '["themes\/log","plugins\/log-core"]' );
check( 'previous is now v2', file_get_contents( WP_CONTENT_DIR . '/upgrade/log-deploy/previous/themes/log/style.css' ), 'new' );

check( 'bare parent folder entries are fine', log_deploy_install( makezip( [ 'themes/' => '', 'plugins/' => '', 'themes/log/style.css' => 'v4', 'plugins/log-core/log-core.php' => 'v4' ] ) ), '["themes\/log","plugins\/log-core"]' );
check( 'but not a sibling folder', log_deploy_install( makezip( [ 'themes/' => '', 'themes/other/' => '', 'themes/log/style.css' => 'a', 'plugins/log-core/log-core.php' => 'a' ] ) ), 'Unexpected path in the zip: themes/other/' );
echo "\n$pass passed, $fail failed\n";
