<?php
/**
 * The LOG logo. $args go straight to log_logo() (inc/brand.php): variant, animate, label, tagline, width.
 */
echo log_logo( (array) ( $args ?? [] ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- built and escaped in log_logo().
