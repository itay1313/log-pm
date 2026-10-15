<?php
/**
 * "What's new" grid (spec §6.9): up to 8 square tiles, knowledge + Instagram + LinkedIn, newest first.
 * Knowledge tiles link inside; social tiles open the post in a new tab. Pure markup; CSS makes it a grid.
 */
$items = log_news_items( 8 );
if ( ! $items ) : ?>
	<p class="placeholder">שמונה משבצות: מרכז ידע, אינסטגרם ולינקדאין, לפי תאריך. מתמלא מ"מרכז ידע" ומ"פוסטים ברשתות".</p>
	<?php return;
endif; ?>
<ul class="news">
	<?php foreach ( $items as $it ) : ?>
		<li class="news__item news__item--<?php echo esc_attr( $it['source'] ); ?>">
			<a class="news__link" href="<?php echo esc_url( $it['url'] ); ?>"<?php echo $it['external'] ? ' target="_blank" rel="noopener"' : ''; ?>>
				<figure class="news__media">
					<?php if ( $it['image'] ) : ?><img src="<?php echo esc_url( $it['image'] ); ?>" alt="" loading="lazy"><?php else : ?><span class="news__placeholder" aria-hidden="true"></span><?php endif; ?>
				</figure>
				<div class="news__body">
					<p class="news__meta"><span class="news__source"><?php echo esc_html( $it['label'] ); ?></span> · <time datetime="<?php echo esc_attr( gmdate( 'c', $it['date'] ) ); ?>"><?php echo esc_html( wp_date( 'd.m.Y', $it['date'] ) ); ?></time></p>
					<?php if ( $it['title'] ) : ?><h3 class="news__title"><?php echo esc_html( $it['title'] ); ?></h3><?php endif; ?>
					<?php if ( $it['text'] ) : ?><p class="news__text"><?php echo esc_html( $it['text'] ); ?></p><?php endif; ?>
				</div>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
