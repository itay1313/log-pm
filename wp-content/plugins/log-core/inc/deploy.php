<?php
/**
 * POST /wp-json/log/v1/deploy
 * GitHub Actions uploads the built theme and log-core as one zip, and this installs it.
 *
 * There is no shared secret. The request carries a GitHub OIDC token (RS256, signed by GitHub) whose claims
 * prove it came from deploy.yml in this repo. The workflow asks for the audience "log-pm-deploy:<sha256 of the
 * zip>", so a token only ever installs the zip it was minted for, and each token is accepted once.
 *
 * The zip holds themes/log/ and plugins/log-core/. Both are extracted next to the live folders and swapped in
 * with rename(). The version they replace stays in wp-content/upgrade/log-deploy/previous/ until the next deploy.
 */

defined( 'ABSPATH' ) || exit;

const LOG_DEPLOY_ISSUER   = 'https://token.actions.githubusercontent.com';
const LOG_DEPLOY_REPO     = 'itay1313/log-pm';
const LOG_DEPLOY_REPO_ID  = '1428886628'; // Pinned as well, so a deleted and re-created repo with the same name can't deploy.
const LOG_DEPLOY_WORKFLOW = 'itay1313/log-pm/.github/workflows/deploy.yml@';

add_action( 'rest_api_init', function () {
	register_rest_route( 'log/v1', '/deploy', [
		'methods'             => 'POST',
		'callback'            => 'log_deploy_handle',
		'permission_callback' => 'log_deploy_authorize',
	] );
} );

/** Zip folder => [ live folder, a file that must be in it ]. */
function log_deploy_targets(): array {
	return [
		'themes/log'       => [ get_theme_root() . '/log', 'style.css' ],
		'plugins/log-core' => [ WP_PLUGIN_DIR . '/log-core', 'log-core.php' ],
	];
}

function log_deploy_error( int $status, string $message ): WP_Error {
	return new WP_Error( 'log_deploy', $message, [ 'status' => $status ] );
}

/** Holds the verified claims between the permission check and the handler. */
function log_deploy_claims( ?array $claims = null ): array {
	static $verified = [];
	if ( $claims !== null ) {
		$verified = $claims;
	}
	return $verified;
}

/**
 * The token comes in X-Log-Deploy-Token rather than Authorization: some PHP setups drop the Authorization header.
 */
function log_deploy_authorize( WP_REST_Request $req ) {
	$jwt = trim( (string) $req->get_header( 'x_log_deploy_token' ) );
	if ( $jwt === '' ) {
		return log_deploy_error( 401, 'Missing X-Log-Deploy-Token.' );
	}
	$claims = log_deploy_verify_jwt( $jwt );
	if ( is_wp_error( $claims ) ) {
		return $claims;
	}
	$check = log_deploy_check_claims( $claims, hash( 'sha256', $req->get_body() ) );
	if ( is_wp_error( $check ) ) {
		return $check;
	}
	log_deploy_claims( $claims );
	return true;
}

function log_deploy_b64url( string $s ): string {
	return (string) base64_decode( strtr( $s, '-_', '+/' ) . str_repeat( '=', ( 4 - strlen( $s ) % 4 ) % 4 ), true );
}

/** Checks the signature against GitHub's published keys and returns the claims. */
function log_deploy_verify_jwt( string $jwt ) {
	$parts = explode( '.', $jwt );
	if ( count( $parts ) !== 3 ) {
		return log_deploy_error( 401, 'Malformed token.' );
	}
	[ $h64, $p64, $s64 ] = $parts;
	$header = json_decode( log_deploy_b64url( $h64 ), true );
	$claims = json_decode( log_deploy_b64url( $p64 ), true );
	if ( ! is_array( $header ) || ! is_array( $claims ) || ( $header['alg'] ?? '' ) !== 'RS256' || empty( $header['kid'] ) ) {
		return log_deploy_error( 401, 'Unsupported token.' );
	}
	$pem = log_deploy_github_key( (string) $header['kid'] );
	if ( is_wp_error( $pem ) ) {
		return $pem;
	}
	if ( openssl_verify( "$h64.$p64", log_deploy_b64url( $s64 ), $pem, OPENSSL_ALGO_SHA256 ) !== 1 ) {
		return log_deploy_error( 401, 'Bad token signature.' );
	}
	return $claims;
}

function log_deploy_github_key( string $kid ) {
	$keys = get_transient( 'log_deploy_jwks' );
	// An unknown kid usually means GitHub rotated its keys. Refetch, but at most once every five minutes,
	// so forged tokens with made-up kids can't make the site hammer GitHub.
	if ( ( ! is_array( $keys ) || ! isset( $keys[ $kid ] ) ) && ! get_transient( 'log_deploy_jwks_fetched' ) ) {
		set_transient( 'log_deploy_jwks_fetched', 1, 5 * MINUTE_IN_SECONDS );
		$keys = log_deploy_fetch_jwks();
		if ( is_wp_error( $keys ) ) {
			return $keys;
		}
		set_transient( 'log_deploy_jwks', $keys, DAY_IN_SECONDS );
	}
	return is_array( $keys ) && isset( $keys[ $kid ] ) ? $keys[ $kid ] : log_deploy_error( 401, 'Unknown signing key.' );
}

function log_deploy_fetch_jwks() {
	$res  = wp_remote_get( LOG_DEPLOY_ISSUER . '/.well-known/jwks', [ 'timeout' => 15 ] );
	$body = is_wp_error( $res ) ? null : json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( empty( $body['keys'] ) || ! is_array( $body['keys'] ) ) {
		return log_deploy_error( 502, "Couldn't fetch GitHub's signing keys." );
	}
	$keys = [];
	foreach ( $body['keys'] as $k ) {
		if ( ( $k['kty'] ?? '' ) === 'RSA' && ! empty( $k['kid'] ) && ! empty( $k['n'] ) && ! empty( $k['e'] ) ) {
			$keys[ $k['kid'] ] = log_deploy_rsa_pem( log_deploy_b64url( $k['n'] ), log_deploy_b64url( $k['e'] ) );
		}
	}
	return $keys;
}

/** A JWK's modulus and exponent as a PEM public key (DER SubjectPublicKeyInfo), which is what openssl_verify takes. */
function log_deploy_rsa_pem( string $n, string $e ): string {
	$der = static function ( string $tag, string $body ): string {
		$len = strlen( $body );
		if ( $len < 0x80 ) {
			return $tag . chr( $len ) . $body;
		}
		$bytes = ltrim( pack( 'N', $len ), "\0" );
		return $tag . chr( 0x80 | strlen( $bytes ) ) . $bytes . $body;
	};
	$int  = static fn( string $x ): string => $der( "\x02", ord( $x[0] ) > 0x7f ? "\0" . $x : $x );
	$rsa  = $der( "\x30", $int( $n ) . $int( $e ) );
	$alg  = $der( "\x30", "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00" ); // rsaEncryption, NULL
	$spki = $der( "\x30", $alg . $der( "\x03", "\0" . $rsa ) );
	return "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( $spki ), 64, "\n" ) . "-----END PUBLIC KEY-----\n";
}

function log_deploy_check_claims( array $c, string $zip_sha ) {
	$now   = time();
	$event = $c['event_name'] ?? '';
	$rules = [
		'issuer'     => ( $c['iss'] ?? '' ) === LOG_DEPLOY_ISSUER,
		'audience'   => in_array( 'log-pm-deploy:' . $zip_sha, (array) ( $c['aud'] ?? [] ), true ),
		'repository' => ( $c['repository'] ?? '' ) === LOG_DEPLOY_REPO && (string) ( $c['repository_id'] ?? '' ) === LOG_DEPLOY_REPO_ID,
		'workflow'   => str_starts_with( (string) ( $c['workflow_ref'] ?? '' ), LOG_DEPLOY_WORKFLOW ),
		// Pushes deploy only from main; a hand-run deploy may test any branch.
		'event'      => $event === 'workflow_dispatch' || ( $event === 'push' && ( $c['ref'] ?? '' ) === 'refs/heads/main' ),
		'time'       => (int) ( $c['exp'] ?? 0 ) > $now - 60 && (int) ( $c['nbf'] ?? $c['iat'] ?? PHP_INT_MAX ) <= $now + 60,
	];
	foreach ( $rules as $rule => $ok ) {
		if ( ! $ok ) {
			return log_deploy_error( 403, "Token rejected: $rule." );
		}
	}
	$jti = 'log_deploy_jti_' . md5( (string) ( $c['jti'] ?? '' ) );
	if ( empty( $c['jti'] ) || get_transient( $jti ) ) {
		return log_deploy_error( 403, 'Token already used.' );
	}
	set_transient( $jti, 1, HOUR_IN_SECONDS );
	return true;
}

function log_deploy_handle( WP_REST_Request $req ) {
	if ( get_transient( 'log_deploy_running' ) ) {
		return log_deploy_error( 409, 'Another deploy is running.' );
	}
	set_transient( 'log_deploy_running', 1, 10 * MINUTE_IN_SECONDS );
	try {
		$installed = log_deploy_install( $req->get_body() );
	} finally {
		delete_transient( 'log_deploy_running' );
	}
	if ( is_wp_error( $installed ) ) {
		return $installed;
	}
	$c = log_deploy_claims();
	return rest_ensure_response( [
		'deployed'  => $c['sha'] ?? '',
		'ref'       => $c['ref'] ?? '',
		'run'       => $c['run_id'] ?? '',
		'installed' => $installed,
	] );
}

function log_deploy_install( string $zip_bytes ) {
	if ( ! class_exists( 'ZipArchive' ) ) {
		return log_deploy_error( 500, 'ZipArchive is missing on this server.' );
	}
	$targets = log_deploy_targets();
	$base    = WP_CONTENT_DIR . '/upgrade/log-deploy';
	$stage   = "$base/incoming";
	$prev    = "$base/previous";
	log_deploy_rmtree( $stage );
	if ( ! wp_mkdir_p( $stage ) || file_put_contents( "$base/incoming.zip", $zip_bytes ) === false ) {
		return log_deploy_error( 500, "Can't write to wp-content/upgrade." );
	}

	$zip = new ZipArchive();
	if ( $zip->open( "$base/incoming.zip" ) !== true ) {
		unlink( "$base/incoming.zip" );
		return log_deploy_error( 400, 'Not a zip file.' );
	}
	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		$name = (string) $zip->getNameIndex( $i );
		// Entries inside a target, or the bare parent folder entries (themes/, plugins/) zip adds on the way.
		$inside = array_filter( array_keys( $targets ), fn( $dir ) => str_starts_with( $name, "$dir/" ) || ( str_starts_with( "$dir/", $name ) && str_ends_with( $name, '/' ) ) );
		if ( ! $inside || str_contains( $name, '..' ) || str_contains( $name, '\\' ) ) {
			$zip->close();
			unlink( "$base/incoming.zip" );
			return log_deploy_error( 400, "Unexpected path in the zip: $name" );
		}
	}
	$extracted = $zip->extractTo( $stage );
	$zip->close();
	unlink( "$base/incoming.zip" );
	if ( ! $extracted ) {
		return log_deploy_error( 500, 'Extracting the zip failed.' );
	}
	foreach ( $targets as $dir => [ , $must ] ) {
		if ( ! is_file( "$stage/$dir/$must" ) ) {
			log_deploy_rmtree( $stage );
			return log_deploy_error( 400, "The zip has no $dir/$must." );
		}
	}

	// Swap. If the second folder fails, the first goes back, so the site never runs a new theme with an old plugin.
	log_deploy_rmtree( $prev );
	wp_mkdir_p( "$prev/themes" );
	wp_mkdir_p( "$prev/plugins" );
	$swapped = [];
	foreach ( $targets as $dir => [ $live ] ) {
		if ( is_dir( $live ) && ! rename( $live, "$prev/$dir" ) ) {
			log_deploy_undo( $swapped, $prev );
			return log_deploy_error( 500, "Couldn't move the live $dir aside." );
		}
		if ( ! rename( "$stage/$dir", $live ) ) {
			if ( is_dir( "$prev/$dir" ) ) {
				rename( "$prev/$dir", $live );
			}
			log_deploy_undo( $swapped, $prev );
			return log_deploy_error( 500, "Couldn't move the new $dir into place." );
		}
		$swapped[] = $dir;
		if ( function_exists( 'wp_opcache_invalidate_directory' ) ) {
			wp_opcache_invalidate_directory( $live );
		}
	}
	log_deploy_rmtree( $stage );
	return $swapped;
}

function log_deploy_undo( array $swapped, string $prev ): void {
	$targets = log_deploy_targets();
	foreach ( $swapped as $dir ) {
		if ( is_dir( "$prev/$dir" ) ) {
			log_deploy_rmtree( $targets[ $dir ][0] );
			rename( "$prev/$dir", $targets[ $dir ][0] );
		}
	}
}

function log_deploy_rmtree( string $dir ): void {
	if ( ! is_dir( $dir ) ) {
		return;
	}
	$items = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::CHILD_FIRST
	);
	foreach ( $items as $item ) {
		$item->isDir() && ! $item->isLink() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
	}
	rmdir( $dir );
}
