<?php
/**
 * About & team (spec §11). Order is final: opening, owners, management, whole team, values,
 * offices, clients, CTA. Texts are ACF fields with fallbacks; page body = opening paragraph.
 */
get_header();
the_post();

$people = static fn( string $group ) => new WP_Query( [
	'post_type'      => 'log_person',
	'posts_per_page' => -1,
	'orderby'        => 'menu_order title',
	'order'          => 'ASC',
	'meta_key'       => 'group',
	'meta_value'     => $group,
	'no_found_rows'  => true,
] );
$owners     = $people( 'owner' );
$management = $people( 'management' );
$team       = new WP_Query( [ 'post_type' => 'log_person', 'posts_per_page' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'no_found_rows' => true ] );
$domains    = get_terms( [ 'taxonomy' => 'log_domain', 'hide_empty' => false ] );

$values = log_rows( 'values', [
	[ 'name' => 'אחריות אישית', 'line' => 'כל אחת ואחד אצלנו מחזיקים את הפרויקט שלהם מקצה לקצה.' ],
	[ 'name' => 'שותפות מקצועית', 'line' => 'רשויות, יזמים ומוסדות תכנון הם שותפים, לא צדדים.' ],
	[ 'name' => 'הדדיות', 'line' => 'למידת עמיתים היא חלק מהעבודה, לא תוספת לה.' ],
	[ 'name' => 'חושבים פתרונות', 'line' => 'כל חסם הוא שאלה שעוד לא נענתה. אנחנו עונים.' ],
] );
$galleries = [
	[ 'ירושלים', log_option( 'jlm_address', 'גבעת שאול' ), log_field( 'gallery_jlm' ) ],
	[ 'תל אביב', log_option( 'tlv_address', 'רח\' הארבעה 18 א\'' ), log_field( 'gallery_tlv' ) ],
];
?>
<section class="about-hero">
	<figure class="about-hero__media">
		<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'log-wide', [ 'fetchpriority' => 'high' ] ); else : ?><span class="placeholder placeholder--media">תמונת צוות גדולה עם עדשת ה-O</span><?php endif; ?>
	</figure>
	<div class="container about-hero__copy">
		<h1 class="page-header__title"><?php echo esc_html( log_text( 'hero_title', 'התמונה המלאה מורכבת מהאנשים שלנו.' ) ); ?></h1>
		<?php if ( get_the_content() ) : ?><div class="prose"><?php the_content(); ?></div><?php else : ?>
			<p class="page-header__lede">הצוות שלנו רב-תחומי ומגיע מרקעים מגוונים: אדריכלות, תכנון עיר ואזור, מדיניות ציבורית, הנדסה, גיאוגרפיה, חברה ועוד.</p>
		<?php endif; ?>
	</div>
</section>

<?php foreach ( [ [ $owners, log_text( 'owners_label', 'הבעלים' ), 'owners' ], [ $management, log_text( 'management_label', 'הנהלה' ), 'management' ] ] as [ $q, $label, $key ] ) : if ( ! $q->have_posts() ) { continue; } ?>
	<section class="section section--<?php echo $key; ?>" data-section="<?php echo $key; ?>">
		<div class="container">
			<p class="eyebrow"><?php echo esc_html( $label ); ?></p>
			<div class="people people--lg">
				<?php while ( $q->have_posts() ) : $q->the_post(); get_template_part( 'template-parts/card-person', null, [ 'size' => 'lg' ] ); endwhile; wp_reset_postdata(); ?>
			</div>
		</div>
	</section>
<?php endforeach; ?>

<section class="section section--team" data-section="team">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'team_title', 'כל הצוות' ) ); ?></h2>
		<?php if ( $team->have_posts() ) : ?>
			<?php if ( $domains && ! is_wp_error( $domains ) ) : ?>
				<div class="filters filters--chips" role="group" aria-label="סינון לפי תחום">
					<button type="button" class="chip is-active" data-filter="" aria-pressed="true">הכל</button>
					<?php foreach ( $domains as $d ) : ?><button type="button" class="chip" data-filter="<?php echo esc_attr( $d->slug ); ?>" aria-pressed="false"><?php echo esc_html( $d->name ); ?></button><?php endforeach; ?>
				</div>
			<?php endif; ?>
			<div class="people" id="team-grid">
				<?php while ( $team->have_posts() ) : $team->the_post(); get_template_part( 'template-parts/card-person' ); endwhile; wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<p class="placeholder">כאן תופיע רשת הצוות: תמונה, שם, תפקיד, תחום ומשפט אישי. מתמלא מ"צוות" במערכת הניהול.</p>
		<?php endif; ?>
	</div>
</section>

<section class="section section--values" data-section="values">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'values_title', 'מתכננים. מחברים. מובילים.' ) ); ?></h2>
		<ol class="values">
			<?php foreach ( $values as $i => $v ) : ?>
				<li class="values__item">
					<span class="values__n"><?php echo $i + 1; ?></span>
					<h3><?php echo esc_html( $v['name'] ?? '' ); ?></h3>
					<?php if ( ! empty( $v['line'] ) ) : ?><p><?php echo esc_html( $v['line'] ); ?></p><?php endif; ?>
					<?php $practices = array_filter( [ $v['practice_1'] ?? '', $v['practice_2'] ?? '' ] ); if ( $practices ) : ?>
						<ul class="values__practices"><?php foreach ( $practices as $p ) : ?><li><?php echo esc_html( $p ); ?></li><?php endforeach; ?></ul>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="section section--offices" data-section="offices">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'offices_title', 'המשרדים' ) ); ?></h2>
		<div class="offices">
			<?php foreach ( $galleries as [ $name, $addr, $gallery ] ) : ?>
				<figure class="offices__item">
					<?php if ( is_array( $gallery ) && $gallery ) : ?>
						<div class="offices__gallery">
							<?php foreach ( array_slice( $gallery, 0, 6 ) as $img ) : ?><img src="<?php echo esc_url( $img['sizes']['log-card'] ?? $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy"><?php endforeach; ?>
						</div>
					<?php else : ?>
						<span class="placeholder placeholder--media">גלריה · <?php echo esc_html( $name ); ?></span>
					<?php endif; ?>
					<figcaption><strong><?php echo esc_html( $name ); ?></strong> · <?php echo esc_html( $addr ); ?></figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
		<p class="section__lede"><?php echo esc_html( log_text( 'offices_line', 'כולל הכלבים.' ) ); ?></p>
	</div>
</section>

<section class="section section--clients" data-section="clients">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'clients_title', 'לקוחות ושותפים' ) ); ?></h2>
		<?php get_template_part( 'template-parts/clients' ); ?>
	</div>
</section>

<section class="section section--cta">
	<div class="container cta-row">
		<a class="btn btn--primary" href="<?php echo esc_url( get_post_type_archive_link( 'log_job' ) ); ?>"><?php echo esc_html( log_text( 'cta_careers', 'קריירה בלוג' ) ); ?></a>
		<a class="btn" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html( log_text( 'cta_contact', 'צור קשר' ) ); ?></a>
	</div>
</section>
<?php get_footer();
