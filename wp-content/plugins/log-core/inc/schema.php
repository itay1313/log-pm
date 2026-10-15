<?php
/**
 * Structured data Yoast's free tier doesn't cover:
 * - Organization with phone, address, email and socials (merged into Yoast's graph).
 * - JobPosting for open jobs (Google Jobs, spec §17).
 * - Place-like data on project pages.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Enrich Yoast's Organization node instead of printing a competing one.
 */
add_filter( 'wpseo_schema_organization', function ( array $data ): array {
	$data['legalName'] = 'לוג ניהול פרויקטים בע"מ';
	$data['telephone'] = log_option( 'tlv_phone', '03-5121551' );
	$data['email']     = log_option( 'email', 'info@log-pm.co.il' );
	$data['address']   = [
		'@type'           => 'PostalAddress',
		'streetAddress'   => 'רח\' הארבעה 18 א\'',
		'addressLocality' => 'תל אביב-יפו',
		'addressCountry'  => 'IL',
	];
	$data['description'] = 'לוג ניהול פרויקטים מתמחה בקידום תכנון עירוני בכל קנה מידה, ומשמשת כחברת הבקרה של הרשות הממשלתית להתחדשות עירונית.';
	return $data;
} );

/**
 * JobPosting on single job pages. Closed jobs get no schema (Google treats them as expired).
 */
add_filter( 'wpseo_schema_graph_pieces', function ( array $pieces, $context ): array {
	if ( is_singular( 'log_job' ) ) {
		$pieces[] = new class( $context ) extends \Yoast\WP\SEO\Generators\Schema\Abstract_Schema_Piece {
			public function __construct( $context ) { $this->context = $context; }
			public function is_needed(): bool { return (bool) log_field( 'is_open', get_the_ID() ); }
			public function generate(): array { return log_job_posting_schema( get_the_ID() ); }
		};
	}
	return $pieces;
}, 10, 2 );

function log_job_posting_schema( int $id ): array {
	$post     = get_post( $id );
	$location = log_field( 'location', $id, 'tel-aviv' );
	$place    = [
		'tel-aviv'  => [ 'תל אביב-יפו', log_option( 'tlv_address', 'רח\' הארבעה 18 א\'' ) ],
		'jerusalem' => [ 'ירושלים', log_option( 'jlm_address', 'גבעת שאול' ) ],
		'hybrid'    => [ 'תל אביב-יפו', log_option( 'tlv_address', 'רח\' הארבעה 18 א\'' ) ],
	][ $location ] ?? [ 'תל אביב-יפו', '' ];

	$type = [ 'full' => 'FULL_TIME', 'part' => 'PART_TIME', 'student' => 'PART_TIME' ][ log_field( 'scope', $id, 'full' ) ] ?? 'FULL_TIME';

	$description = implode( "\n\n", array_filter( [
		log_field( 'about', $id ),
		log_field( 'what_you_do', $id ),
		log_field( 'requirements', $id ),
	] ) );

	$schema = [
		'@type'              => 'JobPosting',
		'@id'                => get_permalink( $id ) . '#jobposting',
		'title'              => get_the_title( $id ),
		'description'        => wpautop( esc_html( $description ?: get_the_excerpt( $id ) ) ),
		'datePosted'         => get_the_date( 'c', $id ),
		'validThrough'       => gmdate( 'c', strtotime( $post->post_date_gmt . ' +90 days' ) ),
		'employmentType'     => $type,
		'hiringOrganization' => [ '@id' => home_url( '/#organization' ) ],
		'jobLocation'        => [
			'@type'   => 'Place',
			'address' => [
				'@type'           => 'PostalAddress',
				'streetAddress'   => $place[1],
				'addressLocality' => $place[0],
				'addressCountry'  => 'IL',
			],
		],
		'directApply'        => true,
	];
	if ( $location === 'hybrid' ) {
		$schema['jobLocationType'] = 'TELECOMMUTE';
	}
	return $schema;
}
