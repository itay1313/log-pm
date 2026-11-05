<?php
/**
 * A background video: a graded loop under a navy scrim, for bands with white text on them.
 * The files come from tools/video-bg.sh (<name>-1600.mp4, <name>-960.mp4, <name>.jpg) in assets/video/.
 *
 * $args: name (file stem), focus (object-position, default "50% 50%"), bleed (true: break out of a container
 * to the window's width).
 *
 * The video is decoration, so assistive tech skips it. src/video-bg.js gives it a source only when it nears
 * the screen, never under reduced motion or Save-Data, and pauses it off screen. The button stops every
 * background video on the site and remembers it (WCAG 2.2.2: motion that runs past five seconds must stop).
 * Without JS the band stays plain navy.
 */
$name = sanitize_file_name( $args['name'] ?? '' );
$dir  = LOG_THEME_DIR . '/assets/video/';
if ( ! $name || ! file_exists( "$dir$name-1600.mp4" ) ) {
	return;
}
$url   = static fn( string $file ): string => esc_url( LOG_THEME_URI . "/assets/video/$file?v=" . filemtime( $dir . $file ) );
$focus = $args['focus'] ?? '50% 50%';
?>
<div class="vbg<?php echo empty( $args['bleed'] ) ? '' : ' vbg--bleed'; ?>" data-vbg style="--vbg-focus: <?php echo esc_attr( $focus ); ?>">
	<video class="vbg__video" muted loop playsinline preload="none" disablepictureinpicture disableremoteplayback aria-hidden="true" tabindex="-1"
		data-poster="<?php echo $url( "$name.jpg" ); ?>"
		data-wide="<?php echo $url( "$name-1600.mp4" ); ?>"
		data-narrow="<?php echo $url( "$name-960.mp4" ); ?>"></video>
	<button type="button" class="vbg__toggle" aria-pressed="false" hidden><span class="screen-reader-text">עצירת סרטוני הרקע</span></button>
</div>
