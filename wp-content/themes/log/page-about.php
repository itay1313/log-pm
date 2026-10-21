<?php
/**
 * About, people and values (spec v6 §12). Blocks in order: opening, the story, expertise (#expertise),
 * vision, values (#values), team (#team, five units as tabs), offices and life, clients, careers invite.
 * Texts are ACF fields with fallbacks; the page body is the opening's two paragraphs.
 */
get_header();
the_post();

$team  = new WP_Query( [ 'post_type' => 'log_person', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true ] );
$units = log_ordered_terms( 'log_unit', true );

// Expertise: the editor's list, or the practice areas with their sentences.
$expertise = log_rows( 'expertise', array_map(
	static fn( WP_Term $t ) => [ 'name' => $t->name, 'text' => (string) log_field( 'sentence', 'term_' . $t->term_id, '' ) ],
	log_ordered_terms( 'log_domain' )
) );
$values = log_rows( 'values', [
	[ 'name' => 'אחריות אישית', 'line' => 'כל אחת ואחד מחזיקים את הפרויקט שלהם מקצה לקצה.', 'practice_1' => 'מנהל הפרויקט הוא הכתובת של הרשות, מהישיבה הראשונה ועד מתן התוקף.' ],
	[ 'name' => 'שותפות מקצועית', 'line' => 'רשויות, יזמים ומוסדות תכנון הם שותפים, לא צדדים.', 'practice_1' => 'כל החלטה תכנונית נבדקת מול מי שיחיה איתה.' ],
	[ 'name' => 'הדדיות', 'line' => 'למידת עמיתים היא חלק מהעבודה, לא תוספת לה.', 'practice_1' => 'צוותים מציגים זה לזה פרויקטים פתוחים, לא רק גמורים.' ],
	[ 'name' => 'חושבים פתרונות', 'line' => 'כל חסם הוא שאלה שעוד לא נענתה.', 'practice_1' => 'מגיעים לישיבה עם שתי דרכים קדימה, לא עם בעיה.' ],
] );
$galleries = array_filter( [
	'ירושלים'       => log_field( 'gallery_jlm' ),
	'תל אביב'       => log_field( 'gallery_tlv' ),
	'אירועים ויום עבודה' => log_field( 'gallery_life' ),
] );
?>
<section class="about-hero">
	<figure class="about-hero__media">
		<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'log-wide', [ 'fetchpriority' => 'high' ] ); elseif ( current_user_can( 'edit_pages' ) ) : // an empty slot is shown to editors only ?><span class="placeholder placeholder--media">מקום לתמונת צוות גדולה (רק עורכים רואים את זה)</span><?php endif; ?>
	</figure>
	<div class="container about-hero__copy">
		<h1 class="page-header__title"><?php echo esc_html( log_text( 'hero_title', 'התמונה המלאה מורכבת מהאנשים שלנו.' ) ); ?></h1>
		<div class="page-header__prose">
			<?php if ( get_the_content() ) : the_content(); else : ?>
				<p>הצוות של לוג רב-תחומי: אדריכלות, תכנון עיר ואזור, מדיניות ציבורית, הנדסה, גיאוגרפיה וחברה.</p>
				<p>אנחנו עובדים משני משרדים, בירושלים ובתל אביב, ומלווים רשויות, משרדי ממשלה ויזמים בכל הארץ.</p>
			<?php endif; ?>
		</div>
	</div>
</section>

<section class="section section--story" data-section="story">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'story_title', 'הסיפור שלנו' ) ); ?></h2>
		<div class="section__prose"><?php echo wpautop( esc_html( log_text( 'story_text', "לוג התחילה מצוות קטן של מתכננים שניהלו תכניות עבור רשויות, וגדלה לחברה שמחזיקה תכניות בכל קנה מידה.\n\nהיתרון שלנו הוא החיבור: בין התכנון לבין מוסדות התכנון, בין הרשות לבין התושבים, ובין הנתונים לבין ההחלטות." ) ) ); ?></div>
	</div>
</section>

<?php if ( $expertise ) : ?>
	<section class="section section--expertise" id="expertise" data-section="expertise">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'expertise_title', 'המומחיות שלנו' ) ); ?></h2>
			<ul class="expertise">
				<?php foreach ( $expertise as $e ) : if ( empty( $e['name'] ) ) { continue; } ?>
					<li><h3><?php echo esc_html( $e['name'] ); ?></h3><?php if ( ! empty( $e['text'] ) ) : ?><p><?php echo esc_html( $e['text'] ); ?></p><?php endif; ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<section class="section section--vision" data-section="vision">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'vision_title', 'החזון' ) ); ?></h2>
		<p class="vision__short"><?php echo esc_html( log_text( 'vision_short', 'מתכננים. מחברים. מובילים.' ) ); ?></p>
		<p class="section__lede"><?php echo esc_html( log_text( 'vision_long', 'להוביל תכנון עירוני שמחבר בין צרכי העיר, האנשים שגרים בה והכלים לממש אותו.' ) ); ?></p>
	</div>
</section>

<section class="section section--values" id="values" data-section="values">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'values_title', 'הערכים שלנו' ) ); ?></h2>
		<ol class="values">
			<?php foreach ( array_slice( $values, 0, 4 ) as $i => $v ) : ?>
				<li class="values__item">
					<span class="values__n"><?php echo $i + 1; ?></span>
					<h3><?php echo esc_html( $v['name'] ?? '' ); ?></h3>
					<?php if ( ! empty( $v['line'] ) ) : ?><p><?php echo esc_html( $v['line'] ); ?></p><?php endif; ?>
					<?php if ( ! empty( $v['practice_1'] ) ) : ?><p class="values__practice"><?php echo esc_html( $v['practice_1'] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<?php if ( $team->have_posts() || log_show_empty() ) : ?>
<section class="section section--team" id="team" data-section="team">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'team_title', 'הצוות' ) ); ?></h2>
		<p class="section__lede"><?php echo esc_html( log_text( 'team_lede', 'האנשים שמחזיקים את הפרויקטים, מהנהלה ועד התפעול.' ) ); ?></p>
		<?php if ( $team->have_posts() ) : ?>
			<?php if ( count( $units ) > 1 ) : ?>
				<div class="filters filters--chips" role="group" aria-label="סינון לפי יחידה">
					<button type="button" class="chip is-active" data-filter="" aria-pressed="true">כולם</button>
					<?php foreach ( $units as $u ) : ?><button type="button" class="chip" data-filter="<?php echo esc_attr( $u->slug ); ?>" aria-pressed="false"><?php echo esc_html( $u->name ); ?></button><?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="people" id="team-grid">
				<?php while ( $team->have_posts() ) : $team->the_post(); get_template_part( 'template-parts/card-person' ); endwhile; wp_reset_postdata(); ?>
			</div>
		<?php elseif ( log_show_empty() ) : ?>
			<p class="placeholder">כאן תופיע רשת הצוות: תמונה, שם ותפקיד, לפי יחידות. מתמלא מ"צוות" במערכת הניהול.</p>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php if ( $galleries || log_show_empty() ) : ?>
<section class="section section--offices" data-section="offices">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'offices_title', 'המשרדים והחיים בחברה' ) ); ?></h2>
		<p class="section__lede"><?php echo esc_html( log_text( 'offices_line', 'ירושלים ותל אביב, אירועים ויום עבודה רגיל.' ) ); ?></p>
		<?php if ( $galleries ) : ?>
			<div class="offices">
				<?php foreach ( $galleries as $name => $gallery ) : if ( ! is_array( $gallery ) ) { continue; } ?>
					<figure class="offices__item">
						<div class="offices__gallery">
							<?php foreach ( array_slice( $gallery, 0, 6 ) as $img ) : ?><img src="<?php echo esc_url( $img['sizes']['log-card'] ?? $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy"><?php endforeach; ?>
						</div>
						<figcaption><?php echo esc_html( $name ); ?></figcaption>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php elseif ( log_show_empty() ) : ?>
			<p class="placeholder">גלריה: המשרדים בירושלים ובתל אביב, אירועים ויום עבודה.</p>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php if ( log_has_clients() || log_show_empty() ) : ?>
<section class="section section--clients" data-section="clients">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'clients_title', 'לקוחות ושותפים' ) ); ?></h2>
		<?php get_template_part( 'template-parts/clients' ); ?>
	</div>
</section>
<?php endif; ?>

<section class="section section--invite" data-section="careers">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_title', 'רוצים לעבוד איתנו?' ) ); ?></h2>
		<p class="section__lede"><?php echo esc_html( log_text( 'careers_text', 'אנחנו מחפשים אנשים שאוהבים ערים, נתונים ואנשים, בערך באותה מידה.' ) ); ?></p>
		<a class="btn btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) ); ?>"><?php echo esc_html( log_text( 'cta_careers', 'לקריירה בלוג' ) ); ?></a>
	</div>
</section>
<?php get_footer();
