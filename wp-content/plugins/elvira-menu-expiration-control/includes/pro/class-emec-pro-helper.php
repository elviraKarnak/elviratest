<?php
if (!defined('ABSPATH')) exit;

/**
 * Pro helper (placeholder) — utility methods used by pro features.
 */
class EMEC_Pro_Helper {
    public static function init() {
        // future helpers
    }

    public static function emec_normalize_rules($raw) {
        // simple sanitizer for pro rules
        $out = [];
        foreach ((array)$raw as $k => $r) {
            $rule = [
                'start' => isset($r['start']) ? sanitize_text_field($r['start']) : '',
                'end'   => isset($r['end']) ? sanitize_text_field($r['end']) : '',
                'days'  => isset($r['days']) ? array_values(array_map('sanitize_key',(array)$r['days'])) : [],
                'roles' => isset($r['roles']) ? array_values(array_map('sanitize_key',(array)$r['roles'])) : [],
                'users' => isset($r['users']) ? preg_replace('/[^0-9,]/', '', $r['users']) : '',
            ];
            if (implode('', $rule) === '') continue;
            $out[$k] = $rule;
        }
        return $out;
    }
}
