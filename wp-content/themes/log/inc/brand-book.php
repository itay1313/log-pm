<?php
/**
 * Helpers for the web brand book at /brand (page-brand.php, template-parts/brand/*).
 *
 * The book follows LOG's brand book chapter by chapter (01 to 14) and is a run of 1280×720 sheets, the same
 * sheets on screen and in the PDF (tools/brand-pdf.mjs). A sheet opens with its chapter label, one headline that
 * ends in the page's one orange dot, and a sentence; it closes with the folio. Values come from the theme's
 * tokens; photographs are LOG's own and live in uploads/log-brand/, outside the public repository.
 */

defined( 'ABSPATH' ) || exit;

/** The chapters, in order: number => name. The contents sheet, the rail and every sheet's label read this. */
function log_bk_chapters(): array {
	return [
		'01' => 'איך משתמשים בספר',
		'02' => 'הרעיון',
		'03' => 'הטון שלנו',
		'04' => 'הלוגו',
		'05' => 'צבע',
		'06' => 'טיפוגרפיה וגריד',
		'07' => 'הקו הדק',
		'08' => 'רקעים',
		'09' => 'צילום',
		'10' => 'אייקונים',
		'11' => 'נתונים',
		'12' => 'רשתות',
		'13' => 'דוגמאות לשימוש',
		'14' => 'מה לא עושים',
	];
}

/**
 * Opens a sheet. $n is its chapter ('' for the cover, contents and closing); the chapter's first sheet carries
 * the anchor the contents and the rail link to. $ground: day | night.
 */
function log_bk_open( string $n, string $label, string $class = '', string $ground = 'day' ): void {
	static $seen = [];
	$first      = $n !== '' && empty( $seen[ $n ] );
	$seen[ $n ] = true;
	if ( $first ) {
		log_bk_opener( $n );
	}
	printf(
		'<section class="bk-page bk-page--%1$s %2$s" data-chapter="%3$s" aria-label="%4$s">',
		$ground === 'night' ? 'night' : 'day',
		esc_attr( $class ),
		esc_attr( $n ),
		esc_attr( $label )
	);
}

/**
 * A chapter's opening sheet: night over one of LOG's places, the chapter's number drawn huge in the thin line,
 * its name set heavy on the horizontal line, and the two lines crossing somewhere new each time. It carries the
 * anchor the contents and the rail link to.
 */
function log_bk_opener( string $n ): void {
	$chapters = log_bk_chapters();
	$i        = (int) $n - 1;
	$photo    = [ 'aerial-night.jpg', 'night-city.jpg', 'aerial.jpg' ][ $i % 3 ];
	// Two chapters open on the place drawn instead of photographed: the one that sets the grounds, and the one with the map.
	$drawn    = [ '08' => 5, '11' => 31 ][ $n ] ?? 0;
	$x        = [ 139, 420, 260, 139, 560, 330 ][ $i % 6 ];
	$y        = [ 560, 500, 600, 470, 540 ][ $i % 5 ];
	printf(
		'<section class="bk-page bk-page--night bk-opener" id="ch-%1$s" data-chapter="%1$s" aria-label="%2$s" style="--y: %5$d; --focus: %6$d%%">%3$s<span class="bk-cross" style="--x: %4$d; --y: %5$d" aria-hidden="true"><i class="bk-cross__v"></i><i class="bk-cross__h"></i><i class="bk-cross__dot"></i></span><p class="bk-opener__n" aria-hidden="true">%1$s</p><div class="bk-opener__body"><p class="bk-kicker bk-kicker--works">פרק %1$s מתוך %7$d</p>',
		esc_attr( $n ),
		esc_attr( $chapters[ $n ] ?? '' ),
		$drawn ? log_city( [ 'seed' => $drawn, 'cols' => 15, 'rows' => 9, 'scale' => 0.74, 'theta' => 30 + $drawn % 9, 'cx' => 0.36, 'cy' => 0.66, 'class' => 'bk-opener__city' ] ) : log_bk_photo( $photo, '', 'bk-photo--bleed', 'dark' ), // phpcs:ignore WordPress.Security.EscapeOutput
		$x,
		$y,
		[ 30, 62, 46, 78, 20 ][ $i % 5 ],
		count( $chapters )
	);
	log_bk_title( $chapters[ $n ] ?? '', 'h2', 'bk-title--opener' );
	echo '</div>';
	if ( $n === '08' ) {
		echo log_bk_downloads(); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in log_bk_downloads()
	}
	echo '</section>';
}

/**
 * The grounds people can take with them: the drawn city (inc/city.php) from six places, each a 3840×2160 JPG in
 * assets/brand/backgrounds/. slug => [ name, the city's settings ]. The files are drawn from these settings by
 * tools/brand-backgrounds.mjs: change one here, run the tool, commit the pictures it writes.
 */
function log_bk_backgrounds(): array {
	return [
		'west'    => [ 'עיר משמאל', [ 'seed' => 5, 'cols' => 18, 'rows' => 12, 'scale' => 0.96, 'theta' => 35, 'cx' => 0.3, 'cy' => 0.7 ] ],
		'east'    => [ 'עיר מימין', [ 'seed' => 29, 'cols' => 18, 'rows' => 12, 'scale' => 0.92, 'theta' => 52, 'cx' => 0.74, 'cy' => 0.66 ] ],
		'horizon' => [ 'קו רקיע', [ 'seed' => 11, 'cols' => 26, 'rows' => 9, 'scale' => 0.82, 'theta' => 31, 'cx' => 0.5, 'cy' => 0.9, 'fade' => [ 120, 420 ] ] ],
		'centre'  => [ 'מרכז העיר', [ 'seed' => 3, 'cols' => 10, 'rows' => 9, 'scale' => 1.7, 'theta' => 42, 'cx' => 0.5, 'cy' => 0.62, 'density' => 1 ] ],
		'far'     => [ 'מבט מרחוק', [ 'seed' => 17, 'cols' => 30, 'rows' => 24, 'scale' => 0.54, 'theta' => 28, 'cx' => 0.5, 'cy' => 0.5 ] ],
		'plan'    => [ 'תכנית', [ 'seed' => 23, 'cols' => 20, 'rows' => 16, 'scale' => 0.92, 'theta' => 38, 'cx' => 0.5, 'cy' => 0.5, 'dims' => false, 'area' => false, 'plan' => [ [ 7, 6 ], [ 12, 6 ], [ 12, 8 ], [ 13, 8 ], [ 13, 10 ], [ 9, 10 ], [ 9, 9 ], [ 7, 9 ] ], 'pins' => [ [ 5, 5 ], [ 15, 11.5 ] ] ] ],
	];
}

/** Where a ground's picture lives. $small: the thumbnail the book shows. '' until tools/brand-backgrounds.mjs has drawn it. */
function log_bk_background_url( string $slug, bool $small = false ): string {
	$file = '/assets/brand/backgrounds/log-background-' . $slug . ( $small ? '-s' : '' ) . '.jpg';
	return is_readable( get_theme_file_path( $file ) ) ? get_theme_file_uri( $file ) : '';
}

/**
 * The grounds as things to take: six small pictures under the line of chapter 08's opening sheet. Each is a link
 * that saves its full-size JPG. Nothing is printed until the pictures have been drawn.
 */
function log_bk_downloads(): string {
	$items = '';
	foreach ( log_bk_backgrounds() as $slug => [ $name ] ) {
		$file  = log_bk_background_url( $slug );
		$small = log_bk_background_url( $slug, true );
		if ( $file === '' || $small === '' ) {
			continue;
		}
		$items .= sprintf(
			'<li><a href="%1$s" download="LOG-background-%2$s.jpg" aria-label="%3$s"><img src="%4$s" alt="" width="480" height="270" loading="lazy" decoding="async"><span>%5$s</span></a></li>',
			esc_url( $file ),
			esc_attr( $slug ),
			esc_attr( 'הורדת הרקע "' . $name . '", קובץ JPG' ),
			esc_url( $small ),
			esc_html( $name )
		);
	}
	if ( $items === '' ) {
		return '';
	}
	return '<div class="bk-get"><p class="bk-get__label"><svg viewBox="0 0 12 12" aria-hidden="true" focusable="false"><path d="M6 1v8M2.5 5.5 6 9l3.5-3.5M1.5 11h9"/></svg><b>רקעים להורדה</b> · לוחצים על תמונה והיא נשמרת במחשב · <span dir="ltr">JPG 3840×2160</span></p><ul>' . $items . '</ul></div>';
}

/** Closes a sheet with its folio: the logo's mark at the end side, the book's name and the page number at the start. */
function log_bk_close( bool $folio = true ): void {
	if ( $folio ) {
		echo '<footer class="bk-folio"><span class="bk-folio__name">ספר המותג של לוג · <i class="bk-folio__n" aria-hidden="true"></i></span>'
			. log_logo( [ 'variant' => 'color' ] ) . '</footer>'; // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_logo()
	}
	echo '</section>';
}

/** A headline. $text comes without its full stop: the stop is added as the page's orange dot. */
function log_bk_title( string $text, string $tag = 'h2', string $class = '' ): void {
	printf(
		'<%1$s class="bk-title %2$s">%3$s<span class="bk-dot">.</span></%1$s>',
		tag_escape( $tag ),
		esc_attr( $class ),
		wp_kses( $text, [ 'br' => [] ] )
	);
}

/** The head of a sheet: "07 · הקו הדק", the headline, and one sentence under it. */
function log_bk_head( string $n, string $title, string $lede = '' ): void {
	$chapters = log_bk_chapters();
	echo '<header class="bk-head">';
	if ( isset( $chapters[ $n ] ) ) {
		printf( '<p class="bk-kicker"><b>%s</b> · %s</p>', esc_html( $n ), esc_html( $chapters[ $n ] ) );
	}
	log_bk_title( $title );
	if ( $lede !== '' ) {
		printf( '<p class="bk-lede">%s</p>', esc_html( $lede ) );
	}
	echo '</header>';
}

/** The URL of one of LOG's photographs in uploads/log-brand/, or '' while it hasn't been uploaded. */
function log_bk_photo_url( string $file ): string {
	static $up = null;
	$up ??= wp_upload_dir();
	return is_readable( $up['basedir'] . '/log-brand/' . $file ) ? $up['baseurl'] . '/log-brand/' . rawurlencode( $file ) : '';
}

/**
 * A photograph in its frame. Without the file the frame still stands, on the night ground, so the book reads whole.
 * $scrim: '' (people: never a layer), or light | mid | dark (places under text: the one transparent, greying layer).
 */
function log_bk_photo( string $file, string $alt = '', string $class = '', string $scrim = '' ): string {
	// A place under a layer is grey anyway: when a grey copy ("-g") was uploaded it is used as it is, which spares the
	// browser a filter on every frame and the PDF a re-drawn bitmap per photograph.
	if ( $scrim && log_bk_photo_url( str_replace( '.jpg', '-g.jpg', $file ) ) !== '' ) {
		$file   = str_replace( '.jpg', '-g.jpg', $file );
		$class .= ' bk-photo--grey';
	}
	$url = log_bk_photo_url( $file );
	// The first photograph of the book is the cover's: it is fetched at once, and the opening waits for it.
	static $first = true;
	$load  = $first ? 'loading="eager" fetchpriority="high"' : 'loading="lazy"';
	$first = false;
	return sprintf(
		'<figure class="bk-photo %1$s%2$s%3$s">%4$s</figure>',
		esc_attr( $class ),
		$scrim ? ' bk-photo--scrim bk-photo--' . esc_attr( $scrim ) : '',
		$url ? '' : ' bk-photo--none',
		$url ? sprintf( '<img src="%s" alt="%s" %s decoding="async">', esc_url( $url ), esc_attr( $alt ), $load ) : ''
	);
}

/**
 * The rules under a specimen: short lines between thin rules, each led by a dot. A rule is "text" or
 * [ 'label', 'text' ]; $key is the index of the one rule that gets the orange dot.
 */
function log_bk_rules( array $rules, int $key = -1, string $class = '' ): void {
	echo '<ul class="bk-rules ' . esc_attr( $class ) . '">';
	foreach ( $rules as $i => $rule ) {
		[ $label, $text ] = is_array( $rule ) ? $rule : [ '', $rule ];
		printf(
			'<li%s>%s%s</li>',
			$i === $key ? ' class="is-key"' : '',
			$label !== '' ? '<b>' . esc_html( $label ) . ':</b> ' : '',
			esc_html( $text )
		);
	}
	echo '</ul>';
}

/** Numbered rows between thin rules: a light figure, a heavy name, a line of text. [ name, text ] each. */
function log_bk_rows( array $rows, string $class = '' ): void {
	echo '<ol class="bk-rows ' . esc_attr( $class ) . '">';
	foreach ( array_values( $rows ) as $i => [ $name, $text ] ) {
		printf(
			'<li><span class="bk-rows__n" aria-hidden="true">%02d</span><h3>%s</h3><p>%s</p></li>',
			$i + 1,
			esc_html( $name ),
			esc_html( $text )
		);
	}
	echo '</ol>';
}

/**
 * One colour as a band of the sheet. Only the token's name is written here: its value is read from the live CSS
 * by src/brand/main.js, so the book can never disagree with the site. $on is the token text takes on it; $share
 * is its share of a typical page (the bands open in those proportions, then even out as the sheet is scrolled).
 */
function log_bk_band( string $token, string $on, int $share, string $name, string $role ): void {
	printf(
		'<figure class="bk-band" style="--sw: var(--%1$s); --on: var(--%2$s); --share: %3$d"><figcaption><b>%4$s</b><span class="bk-band__hex" data-token-value="--%1$s"></span><span class="bk-band__role">%5$s</span></figcaption></figure>',
		esc_attr( $token ),
		esc_attr( $on ),
		$share,
		esc_html( $name ),
		esc_html( $role )
	);
}

/* ------------------------------------------------------------------ data pieces, full size and inside miniatures */

/** The bar chart of chapter 11. Visits by month; the months the headline talks about are orange, the rest grey. */
function log_bk_chart(): string {
	$months = [ 'ינו' => 149, 'פבר' => 143, 'מרץ' => 161, 'אפר' => 516, 'מאי' => 575, 'יוני' => 1972, 'יולי' => 3107, 'אוג' => 1606, 'ספט' => 1294 ];
	$max    = max( $months );
	$bars   = '';
	foreach ( $months as $name => $value ) {
		$bars .= sprintf(
			'<li%s style="--v:%s"><span class="bk-chart__value">%s</span><i class="bk-chart__bar"></i><span class="bk-chart__month">%s</span></li>',
			in_array( $name, [ 'יולי', 'אוג', 'ספט' ], true ) ? ' class="is-now"' : '',
			esc_attr( round( $value / $max, 4 ) ),
			esc_html( number_format_i18n( $value ) ),
			esc_html( $name )
		);
	}
	$avg = static fn( int $v, string $label ) => sprintf(
		'<p class="bk-chart__avg" style="--v:%s"><span>%s: %s</span></p>',
		esc_attr( round( $v / $max, 4 ) ),
		esc_html( $label ),
		esc_html( number_format_i18n( $v ) )
	);
	return '<figure class="bk-chart"><div class="bk-chart__plot">' . $avg( 2002, 'ממוצע יולי עד ספטמבר' ) . $avg( 546, 'ממוצע אפריל ומאי' )
		. '<ol class="bk-chart__bars">' . $bars . '</ol></div><figcaption>מקור: דוח תנועת הגולשים באתר לוג. ביקורים, לא מבקרים ייחודיים.</figcaption></figure>';
}

/** The timeline of chapter 11: the line runs edge to edge, a counted circle per month, the last month in orange. */
function log_bk_timeline(): string {
	$months = [ [ 'יולי', 6, '6.7', 'פגישת הצעה' ], [ 'אוגוסט', 7, '11.8', 'החווה החקלאית' ], [ 'ספטמבר', 4, '3.9', 'יום צילום' ], [ 'אוקטובר', 5, '1.10', 'מילון המונחים' ] ];
	$out    = '<div class="bk-timeline"><i class="bk-timeline__line" aria-hidden="true"></i><ol>';
	foreach ( $months as $i => [ $name, $count, $date, $event ] ) {
		$out .= sprintf(
			'<li%s>%s<h3>%s</h3><p><b>%s</b> %s</p></li>',
			$i === 3 ? ' class="is-now"' : '',
			log_circle( 'full', '<span>' . (int) $count . '</span><small>מועדים</small>', [ 'draw' => '' ] ),
			esc_html( $name ),
			esc_html( $date ),
			esc_html( $event )
		);
	}
	return $out . '</ol></div>';
}

/** A person in a circle: 'split' (orange and grey quarters) or 'halves' (black only), the photograph inset from the ring. */
function log_bk_person( string $file, string $kind = 'split' ): string {
	$url = log_bk_photo_url( $file );
	return log_circle( $kind, $url ? '<img src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async">' : '', [ 'draw' => '' ] );
}

/**
 * A miniature: a slide, post, story or banner drawn live at a fraction of its real size ($scale of a sheet pixel),
 * with the same lines, dots and type as the real thing. Every example in the book is one of these, not a picture.
 */
function log_bk_mini( string $type, float $scale = 0.3, array $a = [] ): string {
	// A miniature is small on the sheet: it takes the 900px copy of a photograph ("-s") when one was uploaded.
	$small = static fn( string $file ) => log_bk_photo_url( str_replace( '.jpg', '-s.jpg', $file ) ) !== '' ? str_replace( '.jpg', '-s.jpg', $file ) : $file;
	if ( isset( $a['photo'] ) ) {
		$a['photo'] = $small( $a['photo'] );
	}
	$cross = static fn( int $x, int $y ) => sprintf( '<span class="bk-cross" style="--x:%d;--y:%d" aria-hidden="true"><i class="bk-cross__v"></i><i class="bk-cross__h"></i><i class="bk-cross__dot"></i></span>', $x, $y );
	$title = static fn( string $t, string $class = '' ) => '<p class="m-title ' . $class . '">' . esc_html( $t ) . '<span class="bk-dot">.</span></p>';
	$mark  = '<span class="m-mark">' . log_logo( [ 'variant' => 'color' ] ) . '</span>';
	$name  = '<p class="m-name"><b>שם פרטי ומשפחה</b><span>תפקיד בלוג</span><i class="ln ln--dot" aria-hidden="true"><i class="ln__dot"></i></i></p>';
	$three = '<ol class="m-three"><li><i>01</i><b>מחברים.</b></li><li><i>02</i><b>מובילים.</b></li><li><i>03</i><b>מתכננים.</b></li></ol>';

	$format = 'slide';
	$ground = 'day';
	switch ( $type ) {
		case 'cover':
		case 'opener':
			$ground = 'night';
			$body   = log_bk_photo( $a['photo'] ?? $small( 'aerial-night.jpg' ), '', 'bk-photo--bleed', 'dark' ) . $cross( 139, $type === 'cover' ? 603 : 470 )
				. '<div class="m-body m-body--on-line">' . ( isset( $a['kicker'] ) ? '<p class="m-kicker">' . esc_html( $a['kicker'] ) . '</p>' : '' )
				. $title( $a['title'] ?? 'אנחנו הצומת', $type === 'cover' ? 'm-title--xl' : '' )
				. '<p class="m-sub">' . esc_html( $a['sub'] ?? 'הפרויקטים שלכם בשפה שמבינים.' ) . '</p></div>';
			break;
		case 'title':
			$body = '<i class="m-corner" aria-hidden="true"></i><div class="m-body m-body--mid">' . $title( $a['title'] ?? 'סיכום פעילות', 'm-title--xl' ) . '<p class="m-sub">' . esc_html( $a['sub'] ?? 'יולי עד אוקטובר 2026' ) . '</p></div>' . $mark;
			break;
		case 'person':
			$body = log_bk_photo( $a['photo'] ?? $small( 'person-2.jpg' ), '', 'm-side' ) . '<div class="m-body m-body--side">' . $title( 'מחברים. מובילים. מתכננים', 'm-title--s' ) . $three . '</div>' . $mark;
			break;
		case 'quote':
			$body = log_bk_photo( $a['photo'] ?? $small( 'person-1.jpg' ), '', 'm-side m-side--end' ) . '<div class="m-body m-body--quote"><p class="m-quote">"תוכנית טובה מתחילה בלהקשיב."</p>' . $name . '</div>';
			break;
		case 'team':
			$people = '';
			foreach ( [ 'person-3.jpg', 'person-5.jpg', 'person-6.jpg', 'person-4.jpg' ] as $file ) {
				$people .= '<li>' . log_bk_person( $file ) . '<b>שם פרטי ומשפחה</b><span>תפקיד בלוג</span></li>';
			}
			$body = '<div class="m-body">' . $title( 'הצוות שמאחורי הפרויקטים', 'm-title--s' ) . '</div><ul class="m-team">' . $people . '</ul>' . $mark;
			break;
		case 'chart':
			$body = '<div class="m-body">' . $title( 'פי 3.7 ביקורים בחודש', 'm-title--s' ) . '</div>' . log_bk_chart() . $mark;
			break;
		case 'timeline':
			$body = '<div class="m-body">' . $title( 'ציר הזמן של הפעילות', 'm-title--s' ) . '</div>' . log_bk_timeline() . $mark;
			break;
		case 'toc':
			$rows = '';
			foreach ( [ [ 'מה עשינו', 3 ], [ 'מה למדנו', 7 ], [ 'מה הלאה', 12 ], [ 'נספחים', 15 ] ] as $i => [ $row, $page ] ) {
				$rows .= sprintf( '<li><i>%02d</i><b>%s</b><span>%d</span></li>', $i + 1, esc_html( $row ), $page );
			}
			$body = '<div class="m-body">' . $title( 'תוכן', 'm-title--s' ) . '</div><ol class="m-list">' . $rows . '</ol>' . $mark;
			break;
		case 'table':
			$rows = '';
			foreach ( [ [ 'רמת השרון', 'תכנון', '240' ], [ 'בת ים', 'הקמה', '410' ], [ 'חצור', 'הושלם', '120' ], [ 'אילת', 'תכנון', '95' ] ] as $i => $r ) {
				$rows .= '<tr' . ( $i === 1 ? ' class="is-key"' : '' ) . '><th scope="row">' . esc_html( $r[0] ) . '</th><td>' . esc_html( $r[1] ) . '</td><td>' . esc_html( $r[2] ) . '</td></tr>';
			}
			$body = '<div class="m-body">' . $title( 'טבלה', 'm-title--s' ) . '</div><table class="m-table"><thead><tr><th scope="col">פרויקט</th><th scope="col">שלב</th><th scope="col">יחידות</th></tr></thead><tbody>' . $rows . '</tbody></table><p class="m-source">מקור: נתוני דוגמה</p>' . $mark;
			break;
		case 'list':
			$ground = 'night';
			$rows   = '';
			foreach ( [ 'מאשרים כיוון', 'סוגרים קווים, אייקונים וגוון האספלט', 'מעצבים את האתר והפוסטים' ] as $i => $row ) {
				$rows .= sprintf( '<li><i>%02d</i><span>%s</span></li>', $i + 1, esc_html( $row ) );
			}
			$body = log_bk_photo( $small( 'night-city.jpg' ), '', 'bk-photo--bleed', 'dark' ) . $cross( 139, 603 ) . '<div class="m-body m-body--list">' . $title( 'מה הלאה', 'm-title--xl' ) . '<ol class="m-next">' . $rows . '</ol></div>';
			break;
		case 'post-person':
			$format = 'post';
			$body   = log_bk_photo( $a['photo'] ?? 'person-4.jpg', '', 'm-top' ) . '<div class="m-panel"><i class="ln ln--dot" aria-hidden="true"><i class="ln__dot"></i></i>' . $title( 'אנחנו הצומת' ) . '<p class="m-sub">הפרויקטים שלכם בשפה שמבינים.</p></div>' . $mark;
			break;
		case 'post-place':
			$format = 'post';
			$ground = 'night';
			$body   = log_bk_photo( $small( 'aerial.jpg' ), '', 'bk-photo--bleed', 'mid' ) . $cross( 400, 760 ) . '<div class="m-body m-body--post">' . $title( 'אנחנו הצומת' ) . '<p class="m-sub">מחברים. מובילים. מתכננים.</p></div>';
			break;
		case 'story':
			$format = 'story';
			$ground = 'night';
			$body   = log_bk_photo( $small( 'night-city.jpg' ), '', 'bk-photo--bleed', 'dark' ) . $cross( 160, 1180 ) . '<div class="m-body m-body--story">' . $title( 'אנחנו הצומת' ) . '</div>';
			break;
		case 'banner':
			$format = 'banner';
			$ground = 'night';
			$body   = log_bk_photo( $small( 'aerial-night.jpg' ), '', 'bk-photo--bleed', 'dark' ) . $cross( 396, 250 ) . '<div class="m-body m-body--banner"><p class="m-kicker">מחברים. מובילים. מתכננים.</p>' . $title( 'אנחנו הצומת', 'm-title--xl' ) . '<p class="m-sub">הפרויקטים שלכם בשפה שמבינים.</p></div>';
			break;
		case 'facebook':
			$format = 'facebook';
			$body   = log_bk_photo( $small( 'team.jpg' ), '', 'm-side m-side--wide' ) . '<div class="m-body m-body--fb">' . $title( 'אנחנו הצומת', 'm-title--s' ) . '<i class="ln ln--dot" aria-hidden="true"><i class="ln__dot"></i></i><p class="m-sub">הפרויקטים שלכם בשפה שמבינים.</p></div>' . $mark;
			break;
		default:
			$body = '';
	}

	return sprintf(
		'<div class="bk-mini bk-mini--%1$s bk-mini--%2$s mt-%3$s" style="--s:%4$s" role="img" aria-label="%5$s">%6$s</div>',
		esc_attr( $format ),
		esc_attr( $ground ),
		esc_attr( $type ),
		esc_attr( (string) $scale ),
		esc_attr( $a['label'] ?? 'דוגמה' ),
		$body
	);
}
