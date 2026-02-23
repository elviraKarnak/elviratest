<?php
if (!defined('ABSPATH')) exit;

class ELVISMNO_Utils {
    public static function elvismno_now_timestamp() {
        return current_time('timestamp');
    }

    /**
     * Convert a HTML datetime-local value (e.g. "2025-10-14T13:00")
     * or any datetime string saved by the plugin into a UNIX timestamp
     * aligned to the site's timezone (Settings -> General).
     *
     * Returns integer timestamp (seconds since epoch, UTC).
     */
    public static function elvismno_datetime_to_timestamp($datetime_str) {
        if (empty($datetime_str)) return 0;

        // normalize T format to space for DateTime
        $normalized = str_replace('T', ' ', $datetime_str);

        // Try to construct DateTime in the site timezone
        if (function_exists('wp_timezone')) {
            $tz = wp_timezone(); // WP 5.3+ returns DateTimeZone based on settings
        } else {
            // fallback: use timezone_string or gmt_offset
            $tz_string = get_option('timezone_string');
            if (!empty($tz_string)) {
                $tz = new DateTimeZone($tz_string);
            } else {
                // build timezone from gmt_offset
                $offset = get_option('gmt_offset', 0);
                $hours = (int)$offset;
                $mins = abs(($offset - $hours) * 60);
                $sign = ($offset >= 0) ? '+' : '-';
                $tz = new DateTimeZone(sprintf('%s%02d:%02d', $sign, abs($hours), (int)$mins));
            }
        }

        try {
            $dt = new DateTime($normalized, $tz);
            // convert to UTC timestamp
            return (int)$dt->getTimestamp();
        } catch (Exception $e) {
            // fallback to strtotime (best-effort)
            $ts = strtotime($normalized);
            return $ts ? (int)$ts : 0;
        }
    }
}
