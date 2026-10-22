<?php
/**
 * The control company (spec v6 §11): LOG's second world, kept apart from project management.
 * Blocks: opening, what it does, three roles, method, team (unit "חברת הבקרה"), numbers (approved
 * control data only), professional items (knowledge marked "קשור לבקרה"), partners, contact.
 * Nothing here comes from the projects data, and control data never reaches the projects map.
 * The way back to project management is the twin button.
 */
get_header();
the_post();

$roles = log_rows( 'roles', [
	[ 'name' => 'מעקב וניתוח ארצי', 'text' => 'תמונת מצב של כל פרויקטי הפינוי-בינוי במדינה, מעודכנת ומאומתת.' ],
	[ 'name' => 'התרת חסמים בירוקרטיים', 'text' => 'זיהוי מה תוקע פרויקט, ומי יכול לשחרר אותו.' ],
	[ 'name' => 'חיבור בין השטח לממשל', 'text' => 'מינהלות, מוסדות תכנון ומשרדי ממשלה סביב שולחן אחד.' ],
] );
$method = log_rows( 'method', [
	[ 'name' => 'איסוף', 'text' => 'נתונים מהרשויות, מהמינהלות ומהמערכות הממשלתיות.' ],
	[ 'name' => 'טיוב ובקרה', 'text' => 'אימות, השלמה ואחידות של כל רשומה.' ],
	[ 'name' => 'זיהוי מגמות וחסמים', 'text' => 'מה מתקדם, מה נתקע, ואיפה.' ],
	[ 'name' => 'החזרת תובנות', 'text' => 'ממצאים והמלצות לרשות ולמשרדי הממשלה.' ],
] );
$numbers  = array_filter( log_rows( 'numbers', [] ), static fn( $n ) => ! empty( $n['value'] ) && ! empty( $n['approved'] ) ); // approved control data only
$partners = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', log_text( 'partners', "הרשות הממשלתית להתחדשות עירונית\nמינהלות להתחדשות עירונית\nמוסדות תכנון\nמשרדי ממשלה" ) ) ) );

$unit = get_term_by( 'slug', 'control', 'log_unit' );
$team = $unit ? new WP_Query( [
	'post_type'      => 'log_person',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order title',
	'order'          => 'ASC',
	'no_found_rows'  => true,
	'tax_query'      => [ [ 'taxonomy' => 'log_unit', 'terms' => $unit->term_id ] ],
] ) : null;
$items = new WP_Query( [
	'post_type'      => 'log_knowledge',
	'posts_per_page' => 6,
	'no_found_rows'  => true,
	'meta_query'     => [ [ 'key' => 'is_control', 'value' => '1' ] ],
] );
?>
<section class="control-hero">
	<figure class="control-hero__media">
		<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'log-wide', [ 'fetchpriority' => 'high' ] ); elseif ( current_user_can( 'edit_pages' ) ) : // an empty slot is shown to editors only ?><span class="placeholder placeholder--media">מקום לתמונת פתיחה (רק עורכים רואים את זה)</span><?php endif; ?>
	</figure>
	<div class="control-hero__strip">
		<div class="container">
			<h1 class="control-hero__title"><?php echo log_route_shield(); ?><?php echo esc_html( log_text( 'hero_title', 'חברת הבקרה' ) ); ?></h1>
			<p class="control-hero__line"><?php echo esc_html( log_text( 'hero_eyebrow', 'לוג היא חברת הבקרה של הרשות הממשלתית להתחדשות עירונית.' ) ); ?></p>
		</div>
	</div>
</section>

<section class="section" data-section="what">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'what_title', 'מה הפעילות' ) ); ?></h2>
		<div class="section__prose">
			<?php if ( get_the_content() ) : the_content(); else : ?>
				<p>כחברת הבקרה, לוג מלווה את פרויקטי הפינוי-בינוי בכל הארץ ומחזיקה עבור הרשות את תמונת המצב הלאומית.</p>
				<p>ההחלטות של הרשות נשענות על הנתונים האלה: איפה להשקיע, מה לקדם, ואיפה צריך להתערב.</p>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="section section--roles" data-section="roles">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'roles_title', 'שלושה תפקידים' ) ); ?></h2>
		<ol class="roles">
			<?php foreach ( array_slice( $roles, 0, 3 ) as $i => $r ) : ?>
				<li class="roles__item"><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $r['name'] ?? '' ); ?></h3><p><?php echo esc_html( $r['text'] ?? '' ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="section section--method" data-section="method">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'method_title', 'שיטת העבודה' ) ); ?></h2>
		<ol class="process">
			<?php foreach ( array_slice( $method, 0, 4 ) as $i => $m ) : ?>
				<li><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $m['name'] ?? '' ); ?></h3><p><?php echo esc_html( $m['text'] ?? '' ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<?php if ( $team && $team->have_posts() ) : ?>
	<section class="section section--team" data-section="team">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'team_title', 'הצוות' ) ); ?></h2>
			<div class="people">
				<?php while ( $team->have_posts() ) : $team->the_post(); get_template_part( 'template-parts/card-person' ); endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $numbers ) : ?>
	<section class="section section--numbers" data-section="numbers">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'numbers_title', 'במספרים' ) ); ?></h2>
			<dl class="numbers">
				<?php foreach ( $numbers as $n ) : ?>
					<div><dd><?php echo log_number( $n['value'] ); ?></dd><dt><?php echo esc_html( $n['label'] ?? '' ); ?><?php if ( ! empty( $n['updated'] ) ) : ?> <small>נכון ל-<?php echo esc_html( wp_date( 'n/Y', strtotime( $n['updated'] ) ) ); ?></small><?php endif; ?></dt></div>
				<?php endforeach; ?>
			</dl>
		</div>
	</section>
<?php endif; ?>

<?php if ( $items->have_posts() ) : ?>
	<section class="section section--items" data-section="items">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'items_title', 'פריטים מקצועיים' ) ); ?></h2>
			<div class="grid grid--knowledge">
				<?php while ( $items->have_posts() ) : $items->the_post(); get_template_part( 'template-parts/card-knowledge' ); endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
<?php endif; ?>

<?php if ( $partners ) : ?>
	<section class="section section--partners" data-section="partners">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'partners_title', 'שותפי העבודה' ) ); ?></h2>
			<ul class="partners-row"><?php foreach ( $partners as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
		</div>
	</section>
<?php endif; ?>

<section class="section section--cta" data-section="contact">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'contact_title', 'פנייה לחברת הבקרה' ) ); ?></h2>
		<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/contact/?topic=control' ) ); ?>"><?php echo esc_html( log_text( 'cta', 'לטופס הפנייה' ) ); ?></a>
	</div>
</section>
<?php get_footer();
