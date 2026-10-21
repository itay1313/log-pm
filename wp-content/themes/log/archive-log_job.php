<?php
/**
 * /careers (spec v6 §14). Blocks: opening with two buttons, how it looks (photos; videos play only on
 * click, with captions), the people, the hiring path, values in practice, open jobs (#jobs).
 * Applying happens only from a job page; there is no form here.
 * Texts live on "הגדרות לוג → טקסטים"; values come from the about page so they're written once.
 */
get_header();
$path = log_rows( 'careers_path', [
	[ 'name' => 'קורות חיים', 'text' => 'שולחים מתוך עמוד המשרה.' ],
	[ 'name' => 'שיחת היכרות', 'text' => 'שיחה קצרה עם מי שמגייס לצוות.' ],
	[ 'name' => 'משימת בית וראיון מקצועי', 'text' => 'משימה קצרה מהעבודה עצמה, ושיחה עליה.' ],
	[ 'name' => 'ברוכים הבאים', 'text' => 'חפיפה מסודרת ואדם שמלווה אתכם בהתחלה.' ],
], 'option' );
$gallery = log_field( 'careers_gallery', 'option' );
$videos  = array_filter( (array) log_field( 'careers_videos', 'option' ), static fn( $v ) => ! empty( $v['video']['url'] ) );
$image   = log_field( 'careers_image', 'option' );

$about  = get_page_by_path( 'about' );
$values = $about ? (array) log_field( 'values', $about->ID ) : [];

$jobs = new WP_Query( [
	'post_type'      => 'log_job',
	'posts_per_page' => -1,
	'meta_key'       => 'is_open',
	'meta_value'     => '1',
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
] );
?>
<section class="careers-hero">
	<figure class="careers-hero__media">
		<?php if ( is_array( $image ) && ! empty( $image['url'] ) ) : ?>
			<img src="<?php echo esc_url( $image['sizes']['log-wide'] ?? $image['url'] ); ?>" alt="<?php echo esc_attr( $image['alt'] ?? '' ); ?>" fetchpriority="high">
		<?php elseif ( current_user_can( 'edit_pages' ) ) : // an empty slot is shown to editors only ?><span class="placeholder placeholder--media">מקום לתמונת צוות (רק עורכים רואים את זה)</span><?php endif; ?>
	</figure>
	<div class="container careers-hero__copy">
		<h1 class="page-header__title"><?php echo esc_html( log_text( 'careers_title', 'בואו לעשות שינוי. בקריירה שלכם ובתכנון פני המדינה.', 'option' ) ); ?></h1>
		<p class="page-header__lede"><?php echo esc_html( log_text( 'careers_lede', 'עבודה על תכניות שמשנות ערים, בצוות שמלמד אחד את השני.', 'option' ) ); ?></p>
		<div class="cta-row">
			<a class="btn btn--primary" href="#jobs">למשרות הפתוחות</a>
			<a class="btn" href="#path">איך מתקבלים</a>
		</div>
	</div>
</section>

<?php if ( ( is_array( $gallery ) && $gallery ) || $videos || log_show_empty() ) : ?>
<section class="section" data-section="culture">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_culture_title', 'איך זה נראה', 'option' ) ); ?></h2>
		<?php if ( is_array( $gallery ) && $gallery ) : ?>
			<div class="gallery__grid">
				<?php foreach ( $gallery as $img ) : ?><figure><img src="<?php echo esc_url( $img['sizes']['log-card'] ?? $img['url'] ); ?>" alt="<?php echo esc_attr( $img['alt'] ); ?>" loading="lazy"></figure><?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( $videos ) : ?>
			<div class="careers-videos">
				<?php foreach ( $videos as $v ) : ?>
					<figure class="careers-video">
						<video controls preload="none" playsinline<?php echo ! empty( $v['poster']['url'] ) ? ' poster="' . esc_url( $v['poster']['url'] ) . '"' : ''; ?>>
							<source src="<?php echo esc_url( $v['video']['url'] ); ?>" type="<?php echo esc_attr( $v['video']['mime_type'] ?? 'video/mp4' ); ?>">
							<?php if ( ! empty( $v['captions']['url'] ) ) : ?><track kind="captions" srclang="he" label="עברית" src="<?php echo esc_url( $v['captions']['url'] ); ?>" default><?php endif; ?>
						</video>
						<?php if ( ! empty( $v['title'] ) ) : ?><figcaption><?php echo esc_html( $v['title'] ); ?></figcaption><?php endif; ?>
					</figure>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
		<?php if ( ! $gallery && ! $videos && log_show_empty() ) : ?><p class="placeholder">תמונות וסרטונים של העובדים, המשרדים והאווירה.</p><?php endif; ?>
	</div>
</section>
<?php endif; ?>

<section class="section" data-section="people">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_people_title', 'האנשים', 'option' ) ); ?></h2>
		<p class="section__lede"><?php echo esc_html( log_text( 'careers_people_text', 'אדריכלים, מתכננים, כלכלנים ואנשי נתונים, באותו צוות ועל אותן תכניות.', 'option' ) ); ?></p>
		<a class="link-more" href="<?php echo esc_url( home_url( '/about/#team' ) ); ?>">להכיר את הצוות</a>
	</div>
</section>

<section class="section" data-section="path" id="path">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_path_title', 'מסלול הגיוס', 'option' ) ); ?></h2>
		<ol class="process">
			<?php foreach ( array_slice( $path, 0, 4 ) as $i => $step ) : ?>
				<li><?php echo log_stage_ring( $i + 1 ); ?><h3><?php echo esc_html( $step['name'] ?? '' ); ?></h3><?php if ( ! empty( $step['text'] ) ) : ?><p><?php echo esc_html( $step['text'] ); ?></p><?php endif; ?></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<?php if ( $values ) : ?>
	<section class="section" data-section="values">
		<div class="container">
			<h2 class="section__title"><?php echo esc_html( log_text( 'careers_values_title', 'הערכים בפועל', 'option' ) ); ?></h2>
			<ul class="values">
				<?php foreach ( array_slice( $values, 0, 4 ) as $v ) : if ( empty( $v['name'] ) ) { continue; } ?>
					<li class="values__item"><h3><?php echo esc_html( $v['name'] ); ?></h3><?php if ( ! empty( $v['practice_1'] ) ) : ?><p><?php echo esc_html( $v['practice_1'] ); ?></p><?php endif; ?></li>
				<?php endforeach; ?>
			</ul>
			<a class="link-more" href="<?php echo esc_url( home_url( '/about/#values' ) ); ?>">הערכים שלנו</a>
		</div>
	</section>
<?php endif; ?>

<section class="section" data-section="jobs" id="jobs">
	<div class="container">
		<h2 class="section__title"><?php echo esc_html( log_text( 'careers_jobs_title', 'משרות פתוחות', 'option' ) ); ?></h2>
		<?php if ( $jobs->have_posts() ) : ?>
			<?php get_template_part( 'template-parts/jobs-list', null, [ 'query' => $jobs ] ); ?>
		<?php else : ?>
			<p><?php echo esc_html( log_text( 'careers_empty', 'אין כרגע משרות פתוחות. משרות חדשות מתפרסמות גם ברשתות.', 'option' ) ); ?></p>
			<ul class="social">
				<?php foreach ( [ 'linkedin' => 'LinkedIn', 'instagram' => 'Instagram' ] as $key => $label ) :
					if ( $url = log_option( $key ) ) : ?><li><a href="<?php echo esc_url( $url ); ?>" rel="noopener" target="_blank"><?php echo esc_html( $label ); ?></a></li><?php endif;
				endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
<?php get_footer();
