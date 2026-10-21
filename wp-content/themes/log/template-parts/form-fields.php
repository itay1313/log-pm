<?php
/**
 * Hidden fields every form carries: honeypot, start time, consented source tracking.
 * Source values are filled by JS from the URL/referrer only after consent is ticked.
 */
?>
<input type="text" name="website" tabindex="-1" autocomplete="off" class="hp" aria-hidden="true">
<input type="hidden" name="started" value="<?php echo (int) time(); ?>">
<input type="hidden" name="utm_source"><input type="hidden" name="utm_medium"><input type="hidden" name="utm_campaign">
<input type="hidden" name="referrer"><input type="hidden" name="landing"><input type="hidden" name="page">
