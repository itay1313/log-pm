<?php
/**
 * The control company (spec §10). Only control-company content; the twin button is the way back.
 * Texts are ACF fields with fallbacks; page body = the "what it means" paragraph.
 */
get_header();
the_post();

$roles = log_rows( 'roles', [
	[ 'name' => 'מעקב וניתוח ארצי', 'text' => 'איסוף נתונים מהרשויות, טיוב ובקרה, זיהוי מגמות וחסמים בכל פרויקט פינוי-בינוי במדינה.' ],
	[ 'name' => 'התרת חסמים בירוקרטיים', 'text' => 'ליווי תהליכי עבודה, תיעוד ישיבות, זיהוי מה תוקע ומי יכול לשחרר.' ],
	[ 'name' => 'חיבור בין השטח לממשל', 'text' => 'המינהלות העירוניות, מוסדות התכנון ומשרדי הממשלה מדברים דרך שולחן אחד.' ],
] );
$method = log_rows( 'method', [
	[ 'name' => 'איסוף', 'text' => 'נתונים מהרשויות, מהמינהלות ומהמערכות הממשלתיות.' ],
	[ 'name' => 'טיוב ובקרה', 'text' => 'אימות, השלמה וסטנדרטיזציה של כל רשומה.' ],
	[ 'name' => 'ניתוח', 'text' => 'מגמות, חסמים וסיכונים ברמה ארצית ועירונית.' ],
	[ 'name' => 'החזרת תובנות', 'text' => 'דוחות והמלצות לרשויות ולמשרדי הממשלה.' ],
] );
$numbers  = array_filter( log_rows( 'numbers', [] ), static fn( $n ) => ! empty( $n['value'] ) && ! empty( $n['approved'] ) ); // approved control data only
$partners = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', log_text( 'partners', "הרשות הממשלתית להתחדשות עירונית\nמינהלות עירוניות\nמוסדות תכנון\nמשרדי ממשלה" ) ) ) );
?>
<section class="control-hero">
	<figure class="control-hero__media">
		<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'log-wide', [ 'fetchpriority' => 'high' ] ); else : ?><span class="placeholder placeholder--media">תמונה כהה של העיר</span><?php endif; ?>
	</figure>
	<div class="control-hero__strip">
		<div class="container">
			<p class="eyebrow eyebrow--light"><?php echo esc_html( log_text( 'hero_eyebrow', 'חברת הבקרה של הרשות הממשלתית להתחדשות עירונית' ) ); ?></p>
			<h1 class="control-hero__title"><?php echo esc_html( log_text( 'hero_title', 'הזרוע שמנהלת את הידע הלאומי בהתחדשות עירונית.' ) ); ?></h1>
		</div>
	</div>
</section>

<section class="section" data-section="what">
	<div class="container prose">
		<?php if ( get_the_content() ) : the_content(); else : ?>
			<p>כחברת הבקרה של הרשות הממשלתית להתחדשות עירונית, לוג מלווה את כלל פרויקטי הפינוי-בינוי בישראל ומספקת לרשות כלים לניתוח, מעקב, זיהוי חסמים והצעת פתרונות בשטח. ההחלטות הגדולות של ההתחדשות העירונית נשענות על הנתונים האלה.</p>
		<?php endif; ?>
	</div>
</section>

<section class="section section--roles" data-section="roles">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'roles_title', 'שלושה תפקידים' ) ); ?></h2>
		<ol class="roles">
			<?php foreach ( $roles as $i => $r ) : ?>
				<li class="roles__item"><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $r['name'] ?? '' ); ?></h3><p><?php echo esc_html( $r['text'] ?? '' ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="section section--method" data-section="method">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'method_title', 'איך זה עובד' ) ); ?></h2>
		<ol class="process">
			<?php foreach ( $method as $i => $m ) : ?>
				<li><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $m['name'] ?? '' ); ?></h3><p><?php echo esc_html( $m['text'] ?? '' ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<?php if ( $numbers ) : ?>
	<section class="section section--numbers" data-section="numbers">
		<div class="container">
			<h2 class="section__title">במספרים</h2>
			<p class="section__lede">רק נתונים שהרשות אישרה לפרסום.</p>
			<dl class="numbers">
				<?php foreach ( $numbers as $n ) : ?><div><dd><?php echo log_number( $n['value'] ); ?></dd><dt><?php echo esc_html( $n['label'] ?? '' ); ?></dt></div><?php endforeach; ?>
			</dl>
		</div>
	</section>
<?php endif; ?>

<section class="section section--partners" data-section="partners">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'partners_title', 'עם מי עובדים' ) ); ?></h2>
		<ul class="partners-row"><?php foreach ( $partners as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
	</div>
</section>

<section class="section section--cta">
	<div class="container cta-row">
		<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/?topic=control' ) ); ?>"><?php echo esc_html( log_text( 'cta', 'פנייה בנושא הבקרה' ) ); ?></a>
		<a class="btn" href="<?php echo esc_url( get_post_type_archive_link( 'log_project' ) ); ?>"><?php echo esc_html( log_text( 'cta_back', 'לניהול פרויקטים' ) ); ?></a>
	</div>
</section>
<?php get_footer();
