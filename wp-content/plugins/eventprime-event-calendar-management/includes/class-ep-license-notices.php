<?php
/**
 * Admin license notices handler.
 *
 * Adds contextual admin notices for license state without touching existing flows.
 */

defined( 'ABSPATH' ) || exit;

class EventPrime_License_Notices {
	const DISMISS_DELAY_DAYS = 7;

	/**
	 * Map notice types to user meta keys.
	 *
	 * @var array
	 */
	private $notice_meta_keys = array(
		'expired'                => 'ep_notice_license_expired',
		'no_license_paid_active' => 'ep_notice_license_missing',
		'expiring_soon'          => 'ep_notice_license_expiring',
	);

	/**
	 * Account dashboard URL for renewals.
	 *
	 * @var string
	 */
	private $renewal_url = 'https://theeventprime.com/checkout/order-history/';

	/**
	 * Render license admin notice when needed.
	 */
	public function maybe_render_notice() {
		if ( ! $this->should_render_notice() ) {
			return;
		}

		$notice = $this->get_notice_payload();
		if ( empty( $notice ) || empty( $notice['type'] ) || $this->is_dismissed( $notice['type'] ) ) {
			return;
		}

		$this->output_notice_html( $notice );
	}

	/**
	 * AJAX: persist dismissal timestamp per user.
	 */
	public function ajax_dismiss_notice() {
		$nonce = filter_input( INPUT_POST, 'nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ep_dismissable_notice_nonce' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Failed security check.', 'eventprime-event-calendar-management' ) ), 400 );
		}

		$notice_type = sanitize_text_field( wp_unslash( filter_input( INPUT_POST, 'notice_type' ) ) );
		if ( empty( $notice_type ) || ! isset( $this->notice_meta_keys[ $notice_type ] ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Invalid request.', 'eventprime-event-calendar-management' ) ), 400 );
		}

		update_user_meta( get_current_user_id(), $this->notice_meta_keys[ $notice_type ], current_time( 'timestamp', true ) );
		wp_send_json_success();
	}

	/**
	 * Decide whether we are on a relevant admin screen.
	 *
	 * @return bool
	 */
	private function should_render_notice() {
		if ( ! is_admin() || wp_doing_ajax() ) {
			return false;
		}

		if ( is_network_admin() && function_exists( 'is_plugin_active_for_network' ) && defined( 'EP_PLUGIN_BASE' ) && ! is_plugin_active_for_network( EP_PLUGIN_BASE ) ) {
			return false;
		}

		// Capability gate.
		if ( ! ( current_user_can( 'manage_options' ) || current_user_can( 'publish_em_events' ) || current_user_can( 'manage_network_options' ) ) ) {
			return false;
		}

		$screen        = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$is_dashboard  = $screen && 'dashboard' === $screen->base;
		$ep_functions  = new Eventprime_Basic_Functions();
		$is_ep_screen  = $ep_functions->is_eventprime_plugin_page();
		//$allowed_screens = $is_dashboard || $is_ep_screen;
                $allowed_screens = true;
		return (bool) $allowed_screens;
	}

	/**
	 * Build notice payload based on license status.
	 *
	 * @return array
	 */
	private function get_notice_payload() {
		if ( ! $this->has_active_paid_extension() ) {
			return array();
		}

		$license_state = $this->determine_license_state();
		if ( empty( $license_state ) ) {
			return array();
		}

		$current_time_gmt = current_time( 'timestamp', true );
                //print_r($current_time_gmt);die;
		$expires_ts       = isset( $license_state['expires_ts'] ) ? absint( $license_state['expires_ts'] ) : 0;
		$status           = strtolower( $license_state['status'] ?? '' );
		$has_key          = ! empty( $license_state['has_key'] );

		$invalid_statuses = array( 'expired', 'invalid', 'deactivated', 'disabled', 'revoked' );
		$bucket           = '';

		// Expired has the highest priority.
		if ( ( $expires_ts && $expires_ts < $current_time_gmt ) || in_array( $status, $invalid_statuses, true ) ) {
			$bucket = 'expired';
		} elseif ( ! $has_key || ( $has_key && '' === $status && ! $expires_ts ) ) {
			// Missing or not connected license.
			$bucket = 'no_license_paid_active';
		} elseif ( $expires_ts ) {
			$threshold = $current_time_gmt + ( DAY_IN_SECONDS * 15 );
			if ( $expires_ts <= $threshold ) {
				$bucket = 'expiring_soon';
			}
		}

		// Default to active => no notice.
		if ( empty( $bucket ) ) {
			return array();
		}

		return $this->build_notice_copy( $bucket, $expires_ts, $license_state );
	}

	/**
	 * Render notice markup.
	 *
	 * @param array $notice Notice data.
	 */
	private function output_notice_html( $notice ) {
		$classes     = esc_attr( implode( ' ', $notice['classes'] ) );
		$style_attr  = ! empty( $notice['style'] ) ? ' style="' . esc_attr( $notice['style'] ) . '"' : '';
		$data_attr   = ! empty( $notice['type'] ) ? ' data-notice-type="' . esc_attr( $notice['type'] ) . '"' : '';
		$primary_btn = '';
		if ( ! empty( $notice['primary_cta_html'] ) ) {
			$primary_btn = wp_kses_post( $notice['primary_cta_html'] );
		} elseif ( ! empty( $notice['primary_cta'] ) ) {
			$primary_btn = sprintf(
			'<a class="button button-primary" href="%1$s" target="%2$s" rel="noopener">%3$s</a>',
			esc_url( $notice['primary_cta']['url'] ),
			isset( $notice['primary_cta']['target'] ) ? esc_attr( $notice['primary_cta']['target'] ) : '_blank',
			esc_html( $notice['primary_cta']['label'] )
			);
		}

		$secondary_btn = ! empty( $notice['secondary_cta'] ) ? sprintf(
			'<a class="button" href="%1$s" target="%2$s" rel="noopener">%3$s</a>',
			esc_url( $notice['secondary_cta']['url'] ),
			isset( $notice['secondary_cta']['target'] ) ? esc_attr( $notice['secondary_cta']['target'] ) : '_blank',
			esc_html( $notice['secondary_cta']['label'] )
		) : '';

		?>
		<div class="<?php echo $classes; ?>"<?php echo $style_attr . $data_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<p><strong><?php echo esc_html( $notice['heading'] ); ?></strong> <?php echo esc_html( $notice['message'] ); ?></p>
			<?php if ( $primary_btn || $secondary_btn ) : ?>
				<p class="ep-license-notice-actions">
					<?php echo $primary_btn; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $secondary_btn; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Determine if notice was dismissed recently.
	 *
	 * @param string $type Notice type.
	 * @return bool
	 */
	private function is_dismissed( $type ) {
		if ( empty( $this->notice_meta_keys[ $type ] ) ) {
			return false;
		}

		$dismissed_at = (int) get_user_meta( get_current_user_id(), $this->notice_meta_keys[ $type ], true );
		if ( empty( $dismissed_at ) ) {
			return false;
		}

		$next_visible_at = $dismissed_at + ( DAY_IN_SECONDS * self::DISMISS_DELAY_DAYS );

		return current_time( 'timestamp', true ) < $next_visible_at;
	}

	/**
	 * Build notice copy/styling.
	 *
	 * @param string $bucket    Notice type/bucket.
	 * @param int    $expires_ts Expiration timestamp.
	 * @param array  $license_state Current license state.
	 * @return array
	 */
	private function build_notice_copy( $bucket, $expires_ts, $license_state ) {
		$license_tab_url = admin_url( 'edit.php?post_type=em_event&page=ep-settings&tab=license' );
		$tab_nonce       = wp_create_nonce( 'ep_settings_tab' );
		$license_tab_url = add_query_arg( array( 'tab_nonce' => $tab_nonce ), $license_tab_url );
                $utc = new DateTimeZone( 'UTC' );
		$date_label      = $expires_ts ? wp_date( get_option( 'date_format' ), $expires_ts, $utc ) : '';
		$renew_label     = esc_html__( 'Renew license', 'eventprime-event-calendar-management' );
		$renew_url       = $this->build_renewal_url( $license_state );
		$current_tab     = sanitize_key( filter_input( INPUT_GET, 'tab' ) );
		$current_page    = sanitize_key( filter_input( INPUT_GET, 'page' ) );
		$is_license_tab  = ( 'license' === $current_tab && 'ep-settings' === $current_page );

		$discount_note = apply_filters( 'ep_license_notice_discount_text', '' );

		$notices = array(
			'expired'                => array(
				'heading'      => esc_html__( 'Your EventPrime license has expired.', 'eventprime-event-calendar-management' ),
				'message'      => trim(
					esc_html__( 'Renew now to keep receiving updates, security fixes, and support.', 'eventprime-event-calendar-management' ) . ' ' . $discount_note
				),
				'classes'      => array( 'notice', 'notice-error', 'is-dismissible', 'ep-license-notice' ),
				'style'        => 'background:#fff4f4;border-left-color:#d63638;color:#7a0b0b;',
				'primary_cta'  => array(
					'url'    => $renew_url,
					'label'  => $renew_label,
					'target' => '_blank',
				),
				'secondary_cta' => array(
					'url'    => $this->renewal_url,
					'label'  => esc_html__( 'Account dashboard', 'eventprime-event-calendar-management' ),
					'target' => '_blank',
				),
			),
			'no_license_paid_active' => array(
				'heading'      => esc_html__( "Your EventPrime license isn't connected yet.", 'eventprime-event-calendar-management' ),
				'message'      => esc_html__( 'Add your license key to keep updates, security fixes, and support active.', 'eventprime-event-calendar-management' ),
				'classes'      => array( 'notice', 'notice-info', 'is-dismissible', 'ep-license-notice' ),
				'style'        => 'background:#f0f6ff;border-left-color:#1d4ed8;color:#0f265c;',
				'primary_cta'  => $is_license_tab
					? array()
					: array(
						'url'    => $license_tab_url . '#ep-license-manager_modal',
						'label'  => esc_html__( 'Add License', 'eventprime-event-calendar-management' ),
						'target' => '_self',
					),
				'primary_cta_html' => $is_license_tab
					? '<button type="button" class="button button-primary ep-open-modal" data-id="ep_license-manager_modal" id="ep_license-manager">' . esc_html__( 'Add License', 'eventprime-event-calendar-management' ) . '</button>'
					: '',
				'secondary_cta' => array(
					'url'    => $this->renewal_url,
					'label'  => esc_html__( 'Account dashboard', 'eventprime-event-calendar-management' ),
					'target' => '_blank',
				),
			),
			'expiring_soon'          => array(
				'heading'      => sprintf(
					/* translators: %s expiry date. */
					esc_html__( 'Your EventPrime license expires on %s.', 'eventprime-event-calendar-management' ),
					esc_html( $date_label )
				),
				'message'      => esc_html__( 'Renew early to avoid interruption and keep updates/support active.', 'eventprime-event-calendar-management' ),
				'classes'      => array( 'notice', 'notice-warning', 'is-dismissible', 'ep-license-notice' ),
				'style'        => 'background:#fff9e8;border-left-color:#f0b429;color:#8a5a00;',
				'primary_cta'  => array(
					'url'    => $renew_url,
					'label'  => esc_html__( 'Renew early', 'eventprime-event-calendar-management' ),
					'target' => '_blank',
				),
			),
		);

		if ( empty( $notices[ $bucket ] ) ) {
			return array();
		}

		$payload              = $notices[ $bucket ];
		$payload['type']      = $bucket;
		$payload['date']      = $date_label;
		$payload['data_attr'] = $bucket;

		// Add data attribute to top-level class for JS dismissal tracking.
		$payload['classes'][] = 'ep-license-notice-' . sanitize_html_class( $bucket );

		return $payload;
	}

	/**
	 * Determine current license state.
	 *
	 * @return array
	 */
	private function determine_license_state() {
		// Prefer new metagauss license data if present.
		$meta_license_data = get_option( 'metagauss_license_data', array() );
		if ( ! empty( $meta_license_data ) && is_array( $meta_license_data ) ) {
			foreach ( $meta_license_data as $license_key => $license_entry ) {
				if ( empty( $license_key ) || empty( $license_entry['plugins'] ) || ! is_array( $license_entry['plugins'] ) ) {
					continue;
				}
				$first_plugin = array();
				$download_id  = '';
				foreach ( $license_entry['plugins'] as $plugin_download_id => $plugin_data ) {
					$first_plugin = $plugin_data;
					$download_id  = $plugin_download_id;
					break;
				}

				$expiration_ts = isset( $first_plugin['expiration'] ) && is_numeric( $first_plugin['expiration'] ) ? (int) $first_plugin['expiration'] : 0;
				if ( ! $expiration_ts && ! empty( $first_plugin['expire_date'] ) ) {
					$maybe_ts = strtotime( $first_plugin['expire_date'] );
					$expiration_ts = $maybe_ts ? $maybe_ts : 0;
				}

				return array(
					'has_key'     => true,
					'status'      => $first_plugin['status'] ?? '',
					'expires_ts'  => $expiration_ts,
					'license_key' => $license_key,
					'download_id' => $download_id,
				);
			}
		}

		// Fallback to legacy premium license record.
		$global_settings = new Eventprime_Global_Settings();
		$options         = $global_settings->ep_get_settings();
		$license_helper  = new EventPrime_License();
		$license         = $license_helper->ep_get_license_detail( 'ep_premium', $options );

		$has_key     = ! empty( $license->license_key );
		$status      = '';
		$download_id = '';
		if ( ! empty( $license->license_status ) ) {
			$status = $license->license_status;
		} elseif ( ! empty( $license->license_response ) && is_object( $license->license_response ) && ! empty( $license->license_response->license ) ) {
			$status = $license->license_response->license;
		}

		$license_response = $license->license_response;
		if ( is_string( $license_response ) ) {
			$decoded = json_decode( $license_response );
			if ( json_last_error() === JSON_ERROR_NONE && is_object( $decoded ) ) {
				$license_response = $decoded;
			}
		}

		$expires_ts = 0;
		if ( is_object( $license_response ) && ! empty( $license_response->expires ) && 'lifetime' !== strtolower( $license_response->expires ) ) {
			$maybe_ts = strtotime( $license_response->expires );
			$expires_ts = $maybe_ts ? $maybe_ts : 0;
		}

		return array(
			'has_key'     => $has_key,
			'status'      => $status,
			'expires_ts'  => $expires_ts,
			'license_key' => $license->license_key ?? '',
			'download_id' => $license->item_id ?? '',
		);
	}

	/**
	 * Check if any paid add-on is active.
	 *
	 * @return bool
	 */
	private function has_active_paid_extension() {
		$license_helper = new EventPrime_License();
		$active_ext     = $license_helper->ep_get_activate_extensions();
		if ( empty( $active_ext ) ) {
			return false;
		}

		foreach ( $active_ext as $ext_details ) {
			if ( isset( $ext_details[2] ) && 'paid' === $ext_details[2] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build renewal URL with license key and download id when present.
	 *
	 * @param array $license_state Current license state.
	 * @return string
	 */
	private function build_renewal_url( $license_state ) {
		$base_url    = 'https://theeventprime.com/checkout/';
		$license_key = isset( $license_state['license_key'] ) ? $license_state['license_key'] : '';
		$download_id = isset( $license_state['download_id'] ) ? $license_state['download_id'] : '';

		if ( empty( $license_key ) || empty( $download_id ) ) {
			return $this->renewal_url;
		}

		return add_query_arg(
			array(
				'edd_license_key' => $license_key,
				'download_id'     => $download_id,
			),
			$base_url
		);
	}

	/**
	 * Provide inline hint data for the License tab when we appear to be using cached status.
	 *
	 * @return array
	 */
	public function get_license_tab_hint() {
		$state      = $this->determine_license_state();
		$has_key    = ! empty( $state['has_key'] );
		$status     = strtolower( $state['status'] ?? '' );
		$expires_ts = isset( $state['expires_ts'] ) ? absint( $state['expires_ts'] ) : 0;

		$connection_flag = ( $this->has_active_paid_extension() && $has_key && '' === $status && 0 === $expires_ts );
		$connection_flag = $connection_flag || (bool) get_transient( 'ep_license_connection_issue' );

		if ( ! $connection_flag ) {
			return array();
		}

		return array(
			'message' => esc_html__( "Couldn't reach license server, using last known status.", 'eventprime-event-calendar-management' ),
			'cta'     => array(
				'url'    => admin_url( 'admin-ajax.php?action=ep_check_license_status' ),
				'label'  => esc_html__( 'Retry check', 'eventprime-event-calendar-management' ),
				'target' => '_self',
			),
		);
	}
}
