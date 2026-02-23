<?php
if (!defined('ABSPATH')) exit;

/**
 * Utilities used by plugin
 */
class EMEC_Utils {
    public static function emec_tz_now() {
        $tz = wp_timezone();
        return new DateTimeImmutable('now', $tz);
    }
}
