<?php
/**
 * Chapters 08 to 11: grounds, photography, icons and data. The photographs are LOG's own (uploads/log-brand/);
 * the chart and the timeline are drawn live from their numbers.
 */
?>
<?php log_bk_open( '08', 'כהה למקום, בהיר לאדם', 'bk-grounds' ); ?>
	<?php log_bk_head( '08', 'כהה למקום, בהיר לאדם', 'שקיפות לתמונות, רק למקומות.' ); ?>
	<ul class="bk-minis bk-minis--2">
		<li>
			<?php echo log_bk_mini( 'opener', 0.44, [ 'photo' => 'night-city.jpg', 'kicker' => 'פרק 04', 'title' => 'מספרים בקו ישר', 'sub' => 'נתונים שמראים את התמונה המלאה.', 'label' => 'שקף על רקע כהה' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h3>כהה: אספלט</h3>
			<p>שער, פתיחת פרק, צילומי אוויר ומפה.</p>
			<p><b>עיבוד:</b> צילום מקום, בצבע או בשחור לבן, שיש עליו טקסט מקבל שכבה שקופה שמאפירה אותו, מאפור בהיר עד דיו לפי הצורך. על צילום אנשים לא שמים שכבה.</p>
		</li>
		<li>
			<?php echo log_bk_mini( 'person', 0.44, [ 'label' => 'שקף על רקע בהיר' ] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h3>בהיר: נייר</h3>
			<p>אנשים, נתונים ותוכן. צילום אנשים בצבע טבעי.</p>
			<p><b>בכל פורמט:</b> אותו כלל בשקף, בפוסט ובסטורי.</p>
		</li>
	</ul>
<?php log_bk_close(); ?>

<?php log_bk_open( '08', 'עיר בקווים', 'bk-city', 'night' ); ?>
	<?php echo log_city( [ 'seed' => 11, 'cols' => 17, 'rows' => 7, 'scale' => 0.7, 'cx' => 0.47, 'cy' => 0.79, 'fade' => [ 200, 360 ] ] ); // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_city() ?>
	<?php log_bk_head( '08', 'עיר בקווים', 'כשאין צילום של המקום, מציירים אותו כמו במפה שבאתר: נפחים בצבע האספלט, וקו כתום על הפינות ועל קו הגג.' ); ?>
	<?php
	log_bk_rules( [
		'קו אחד, בכתום, בעובי אחד. בבניינים אין מילוי צבע.',
		'רחוק מהמרכז הקו דוהה. הכבישים ממשיכים עד קצה העמוד.',
		'רקע לשער, לפתיחת פרק ולמפה. לא מאחורי טקסט רץ.',
	], -1, 'bk-rules--3' );
	?>
<?php log_bk_close( false ); ?>

<?php log_bk_open( '09', 'שני סוגי צילום', 'bk-photos' ); ?>
	<?php log_bk_head( '09', 'שני סוגי צילום', 'מלמעלה, כדי לראות את התמונה המלאה. ובגובה העיניים, כדי לראות מי עושה את העבודה.' ); ?>
	<div class="bk-photos__cols">
		<div>
			<?php echo log_bk_photo( 'aerial.jpg', 'צילום אוויר של כיכר וכבישים שנפגשים בה' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<h3>צילומי אוויר</h3>
			<?php log_bk_rules( [ 'בצבע או בשחור לבן. צמתים, כבישים ושכונות, עדיף של לוג.', 'טקסט עליהם: שכבה שקופה שמאפירה, מאפור בהיר עד דיו.', 'השכבה רק על מקומות. צילום אנשים בלי שכבה.' ] ); ?>
		</div>
		<div>
			<div class="bk-photos__people">
				<?php
				foreach ( [ 'person-3.jpg', 'person-6.jpg', 'person-5.jpg', 'person-4.jpg' ] as $file ) {
					echo log_bk_photo( $file, 'מישהו מצוות לוג, במשרד' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
			<h3>אנשים</h3>
			<?php log_bk_rules( [ 'בצבע טבעי, באור יום, במשרד או בשטח.', 'אותו רקע ואותו גובה מצלמה לכל הצוות.', 'קווי עזר רק בשולי התמונה. אף קו לא עובר על פנים.' ] ); ?>
		</div>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '09', 'השפה שלנו על תמונות', 'bk-scrims' ); ?>
	<?php log_bk_head( '09', 'השפה שלנו על תמונות', 'מקום: שכבה שקופה שמאפירה את הצילום. אדם: בלי שכבה, השם על קו מהקצה.' ); ?>
	<div class="bk-scrims__cols">
		<div class="bk-scrims__places">
			<ul>
				<?php foreach ( [ [ 'light', 'שכבה בהירה', 'נייר. טקסט כהה.' ], [ 'mid', 'שכבה בינונית', 'אפור. טקסט בהיר. רוב צילומי האוויר.' ], [ 'dark', 'שכבה כהה', 'דיו. טקסט בהיר. צילום בהיר או עמוס.' ] ] as [ $scrim, $name, $text ] ) : ?>
					<li>
						<div class="bk-scrims__case bk-scrims__case--<?php echo esc_attr( $scrim ); ?>"><?php echo log_bk_photo( 'aerial.jpg', '', 'bk-photo--bleed', $scrim ); // phpcs:ignore WordPress.Security.EscapeOutput ?><b aria-hidden="true">כותרת<span class="bk-dot">.</span></b></div>
						<h3><?php echo esc_html( $name ); ?></h3><p><?php echo esc_html( $text ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
			<?php log_bk_rules( [ 'שכבה אחת, שקופה, שווה על כל הצילום. מאפירה את הצבעים ומורידה את הבהירות.', 'שכבה בהירה: טקסט כהה. שכבה כהה: טקסט בהיר. מוסיפים רק כמה שצריך כדי שהטקסט ייקרא בקלות.', 'אותה רמה לכל העמודים באותה סדרה.' ] ); ?>
		</div>
		<div class="bk-scrims__person">
			<div class="bk-scrims__portrait">
				<?php echo log_bk_photo( 'person-1.jpg', 'מישהי מצוות לוג, במשרד', 'bk-photo--bleed' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="m-name"><b>שם פרטי ומשפחה</b><span>תפקיד בלוג</span><?php echo log_line( 'dot' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
			</div>
			<p><b>אדם:</b> השם בקצה הצילום, מעל קו שנכנס מהקצה ונגמר בנקודה. לא עובר על הפנים.</p>
		</div>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '10', 'שמונה עשר סימנים', 'bk-icons' ); ?>
	<?php log_bk_head( '10', 'שמונה עשר סימנים', 'קו דק, נגיעה אחת של כתום, ומקווקו למה שעוד לא נבנה. לוחצים על אייקון והוא נשמר במחשב כקובץ SVG.' ); ?>
	<?php // The link gets its file from src/brand/main.js, which writes the icon out with the live colours. Without it the icons are just shown. ?>
	<ul class="bk-icons__grid">
		<?php foreach ( log_icons() as $name => [ $label ] ) : ?>
			<li><a data-icon="<?php echo esc_attr( $name ); ?>" download="LOG-icon-<?php echo esc_attr( $name ); ?>.svg" aria-label="<?php echo esc_attr( 'הורדת האייקון "' . $label . '", קובץ SVG' ); ?>"><?php echo log_icon( $name ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup from log_icons() ?><span><?php echo esc_html( $label ); ?></span><i aria-hidden="true">SVG</i></a></li>
		<?php endforeach; ?>
	</ul>
	<?php log_bk_rules( [ 'אייקון חדש נבנה באותו עובי קו ובאותה פינה מעוגלת.', 'כתום רק על חלק אחד באייקון.', 'פלטפורמות: אותו ריבוע, אותו קו.' ], 1, 'bk-rules--3' ); ?>
<?php log_bk_close(); ?>

<?php log_bk_open( '11', 'גרף עמודות', 'bk-data' ); ?>
	<?php log_bk_head( '11', 'גרף עמודות', 'הגרף מוכיח את מה שהכותרת אומרת. כתום על התקופה שעליה מדברים, אפור על כל השאר.' ); ?>
	<div class="bk-data__cols">
		<?php echo log_bk_chart(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php
		log_bk_rules( [
			[ 'כתום בהיר', 'על העמודות של התקופה שלנו. אפור על מה שלפני.' ],
			[ 'ממוצע', 'קו מקווקו לכל רוחב הגרף, עם התווית בתוך המסגרת.' ],
			[ 'ערכים', 'מעל העמודות, בספרות מלאות. הם לא נחתכים בקו.' ],
			[ 'מקור', 'בשורה קטנה מתחת לגרף, תמיד. כולל מה לא נספר.' ],
			[ 'הגדרות הפלטפורמה', 'צפיות, חשיפות ומעורבות כמו שכל פלטפורמה מגדירה אותן.' ],
			[ 'גובה העמודה', 'מתאים בדיוק למספר שכתוב עליה.' ],
		], 0 );
		?>
	</div>
<?php log_bk_close(); ?>

<?php log_bk_open( '11', 'ציר זמן עד הקצה', 'bk-time bk-page--hold' ); ?>
	<?php log_bk_head( '11', 'ציר זמן עד הקצה', 'הקו עובר מקצה לקצה של העמוד. לכל חודש ציר אמצע אחד: העיגול, שם החודש והמועדים ממורכזים עליו.' ); ?>
	<?php echo log_bk_timeline(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	<?php
	log_bk_rules( [
		'הזמן זורם מימין לשמאל. החודש הראשון בימין.',
		'המספר בעיגול: כמה מועדים היו בחודש. מתחת לחודש: המועד המרכזי, עם תאריך.',
		'הקו אפור, ומחליף לכתום מהחודש האחרון והלאה.',
		'מקווקו כשהציר מציג תכנון. ציר של פעילות שבוצעה: רציף.',
	], -1, 'bk-rules--2' );
	?>
<?php log_bk_close(); ?>

<?php log_bk_open( '11', 'תכנית על המפה', 'bk-map', 'night' ); ?>
	<figure class="bk-map__fig">
		<?php
		echo log_city( [ // phpcs:ignore WordPress.Security.EscapeOutput -- built in log_city()
			'w' => 742, 'h' => 720, 'seed' => 23, 'cols' => 13, 'rows' => 11, 'scale' => 0.6, 'theta' => 38, 'cx' => 0.5, 'cy' => 0.53,
			'plan'  => [ [ 3, 3 ], [ 9, 3 ], [ 9, 5 ], [ 10, 5 ], [ 10, 8 ], [ 5, 8 ], [ 5, 7 ], [ 3, 7 ] ],
			'pins'  => [ [ 2, 2 ], [ 11, 9.5, 4 ] ],
			'label' => 'מפה מצוירת: עיר בקווים כתומים, ובמרכזה תכנית בכתום שקוף בתוך גבול מקווקו, עם שתי מידות והשטח בדונם.',
		] );
		?>
		<p class="bk-map__grid" aria-hidden="true"><b>192660 / 704620</b><span>קנ״מ 1:6,000</span></p>
		<p class="bk-map__scale" style="--len: 120" aria-hidden="true"><i></i><span>200 מ'</span></p>
	</figure>
	<?php log_bk_head( '11', 'תכנית על המפה', 'המפה היא הנתון הכי גדול שלנו, והיא מצוירת באותו קו.' ); ?>
	<?php
	log_bk_rules( [
		[ 'גבול תכנית', 'קו כתום מקווקו. מה שבתוכו עוד לא נבנה.' ],
		[ 'השטח', 'כתום שקוף, והרחובות של התכנית נראים דרכו.' ],
		[ 'מידות', 'קו בהיר ודק עם קצוות ישרים, והאורך במטרים לצדו.' ],
		[ 'פרויקט', 'נקודה כתומה. כמה פרויקטים באותו מקום: עיגול כהה עם המספר.' ],
		[ 'בשוליים', 'נקודת הציון ברשת ישראל וקנה המידה, כמו בגיליון מדידה.' ],
	], 3 );
	?>
<?php log_bk_close(); ?>
