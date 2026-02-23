<?php
/* --------------------------------------------------------- *
 *  API & Webhooks Settings – EventPrime
 * --------------------------------------------------------- */

$global_settings  = new Eventprime_Global_Settings;
$global_options   = $global_settings->ep_get_settings();

// Current values
$enable_api = ! empty( $global_options->enable_api );

$api_key = get_option( 'ep_api_key', '' );
$meta = get_option( 'ep_api_key_meta', array() );
$created = isset( $meta['created_at'] ) ? esc_html( $meta['created_at'] ) : '—';
$last_used = isset( $meta['last_used'] ) ? esc_html( $meta['last_used'] ) : '—';
    $endpoints = array();
$last_ip = isset( $meta['last_used_ip'] ) ? esc_html( $meta['last_used_ip'] ) : '—';

?>

<div class="ep-setting-tab-content">
    <h2><?php esc_html_e( 'API & Webhooks', 'eventprime-event-calendar-management' ); ?></h2>
    <input type="hidden" name="em_setting_type" value="api_settings">
</div>

<table class="form-table">
<tbody>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label><?php esc_html_e( 'API Endpoint', 'eventprime-event-calendar-management' ); ?></label>
    </th>
    <td class="forminp">
        <p>
            <code><?php echo esc_html( home_url( '/wp-json/eventprime/v1/integration' ) ); ?></code>
        </p>
        <p class="description">
            <?php esc_html_e( 'Unified integration endpoint. Use the "action" or "trigger" parameter to request resources or perform integration operations.', 'eventprime-event-calendar-management' ); ?>
        </p>
        <p class="description">
            <?php esc_html_e( 'Examples:', 'eventprime-event-calendar-management' ); ?>
            <br>
            <code>?action=get_events</code> &nbsp;&mdash;&nbsp; <?php esc_html_e( 'List events', 'eventprime-event-calendar-management' ); ?>
            <br>
            <code>?action=get_tickets&event_id=123</code> &nbsp;&mdash;&nbsp; <?php esc_html_e( 'List tickets for event ID 123', 'eventprime-event-calendar-management' ); ?>
            <br>
            <code>?trigger=create_event</code> &nbsp;&mdash;&nbsp; <?php esc_html_e( 'Get a sample payload for a newly created event', 'eventprime-event-calendar-management' ); ?>
        </p>
    </td>
</tr>

<!-- Enable API master toggle -->
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="enable_api"><?php esc_html_e( 'Enable API', 'eventprime-event-calendar-management' ); ?></label>
    </th>
    <td class="forminp">
        <label class="ep-toggle-btn">
            <input name="enable_api" id="enable_api" type="checkbox" value="1" <?php checked( $enable_api ); ?>>
            <span class="ep-toogle-slider round"></span>
        </label>
        <div class="ep-help-tip-info ep-my-2 ep-text-muted">
            <?php esc_html_e( 'Toggle the EventPrime REST API on or off. When disabled, API endpoints will return 404 responses.', 'eventprime-event-calendar-management' ); ?>
        </div>
    </td>
</tr>
</tbody>
</table>
