<?php
// No namespace — matches EventPrime plugin style.
/**
 * REST API for EventPrime core
 */
class Eventprime_Rest_Api {

    /** Supported triggers */
    protected $supported_triggers = array(
        'create_event','update_event','delete_event','all_events',
        'create_venue','update_venue','delete_venue','all_venues',
        'create_performer','update_performer','delete_performer','all_performers',
        'confirm_booking','pending_booking','cancel_booking','refund_booking','failed_booking'
    );

    /** @var Eventprime_API_Integration_Helpers|null */
    protected $integration_helpers = null;

    /**
     * Lazy-load integration helpers to keep backward compatibility with conditional includes.
     *
     * @return Eventprime_API_Integration_Helpers|null
     */
    protected function get_integration_helpers() {
        if ( $this->integration_helpers ) {
            return $this->integration_helpers;
        }
        if ( class_exists( 'Eventprime_API_Integration_Helpers' ) ) {
            $this->integration_helpers = new Eventprime_API_Integration_Helpers();
        }
        return $this->integration_helpers;
    }

    /**
     * Enhanced initialization with support for REST, admin-ajax, and plain permalink bridges.
     */
    public function init() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'wp_ajax_eventprime_api', array( $this, 'handle_ajax_api' ) );
        add_action( 'wp_ajax_nopriv_eventprime_api', array( $this, 'handle_ajax_api' ) );
        add_action( 'init', array( $this, 'register_plain_permalink_support' ) );
    }

    /**
     * Register hooks that allow plain permalink requests to be intercepted.
     */
    public function register_plain_permalink_support() {
        add_rewrite_tag( '%ep_api%', '([^&]+)' );
        add_action( 'parse_request', array( $this, 'handle_early_api_requests' ), 5 );
    }

    /**
     * Intercept plain permalink requests early and funnel them through the universal handler.
     *
     * @param WP $wp WordPress bootstrap instance.
     */
    public function handle_early_api_requests( $wp ) {
        $is_ep_api = isset( $_GET['ep_action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            || isset( $_GET['ep_trigger'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            || ( isset( $_GET['rest_route'] ) && strpos( sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ), '/eventprime/' ) === 0 ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            || ( isset( $_GET['action'] ) && 'eventprime_api' === $_GET['action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ( $is_ep_api ) {
            $this->handle_ajax_api();
            exit;
        }
    }

    /**
     * Universal handler that bridges EventPrime REST requests across permalink structures.
     */
    public function handle_ajax_api() {
        $global = get_option( 'em_global_settings', null );
        if ( is_object( $global ) && isset( $global->enable_api ) && ! $global->enable_api ) {
            status_header( 403 );
            wp_send_json(
                array(
                    'status'  => 'error',
                    'message' => esc_html__( 'EventPrime API is disabled.', 'eventprime' ),
                    'code'    => 'ep_api_disabled',
                )
            );
        }

        $method = isset( $_SERVER['REQUEST_METHOD'] )
            ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
            : 'GET';
        $method = strtoupper( $method );

        $route           = '';
        $route_extra     = array();
        $candidate_route = '';

        if ( isset( $_REQUEST['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $candidate_route = wp_unslash( $_REQUEST['rest_route'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( empty( $candidate_route ) && isset( $_REQUEST['ep_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $candidate_route = wp_unslash( $_REQUEST['ep_route'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        }

        if ( ! empty( $candidate_route ) ) {
            $candidate_route = trim( $candidate_route );
            if ( false !== strpos( $candidate_route, '?' ) ) {
                list( $candidate_route, $maybe_query ) = explode( '?', $candidate_route, 2 );
                parse_str( $maybe_query, $route_extra );
            }
            $route = '/' . ltrim( $candidate_route, '/' );
        }

        // Merge GET/POST with any extracted query vars.
        $params = array_merge(
            $_GET, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $_POST, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $route_extra
        );

        // Inspect JSON payloads.
        $input = file_get_contents( 'php://input' ); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.FileGetContentsUnknown
        $json_params = array();
        if ( ! empty( $input ) ) {
            $decoded = json_decode( $input, true );
            if ( is_array( $decoded ) ) {
                $json_params = $decoded;
                $params      = array_merge( $params, $decoded );
            }
        }

        $namespace = '';
        if ( isset( $params['namespace'] ) ) {
            $namespace = sanitize_text_field( wp_unslash( $params['namespace'] ) );
        } elseif ( isset( $params['ep_namespace'] ) ) {
            $namespace = sanitize_text_field( wp_unslash( $params['ep_namespace'] ) );
        }

        // Determine action/trigger for integration fallback.
        $action_candidates = array( 'action', 'ep_action', 'ep_trigger', 'trigger' );
        $action            = '';
        foreach ( $action_candidates as $candidate ) {
            if ( empty( $action ) && isset( $params[ $candidate ] ) ) {
                $action = wp_unslash( $params[ $candidate ] );
            }
        }

        $explicit_route = ! empty( $route );

        if ( empty( $action ) ) {
            $action = 'integration';
        }

        if ( $explicit_route ) {
            $trimmed = trim( $route, '/' );
            $bits    = explode( '/', $trimmed );
            if ( count( $bits ) >= 2 && empty( $namespace ) ) {
                $namespace = $bits[0];
                if ( isset( $bits[1] ) ) {
                    $namespace .= '/' . $bits[1];
                }
            }
        } else {
            $namespace = ! empty( $namespace ) ? $namespace : 'eventprime/v1';
            $route     = '/' . trim( $namespace, '/' ) . '/integration';
        }

        $request = new WP_REST_Request( $method, $route );

        // Raw body passthrough ensures downstream consumers see untouched payload.
        if ( ! empty( $input ) ) {
            $request->set_body( $input );
        }

        // Preserve headers (auth tokens, custom keys, etc.).
        $headers = array();
        if ( function_exists( 'getallheaders' ) ) {
            $headers = getallheaders();
        }
        if ( empty( $headers ) || ! is_array( $headers ) ) {
            $headers = array();
            foreach ( $_SERVER as $server_key => $server_value ) {
                if ( strpos( $server_key, 'HTTP_' ) === 0 ) {
                    $header_name              = substr( $server_key, 5 );
                    $header_name              = str_replace( '_', '-', strtolower( $header_name ) );
                    $header_name              = ucwords( $header_name, '-' );
                    $headers[ $header_name ] = wp_unslash( $server_value );
                } elseif ( in_array( $server_key, array( 'CONTENT_TYPE', 'CONTENT_LENGTH' ), true ) ) {
                    $header_name              = str_replace( '_', '-', strtolower( $server_key ) );
                    $header_name              = ucwords( $header_name, '-' );
                    $headers[ $header_name ] = wp_unslash( $server_value );
                }
            }
        }

        foreach ( $headers as $header_key => $header_value ) {
            if ( is_array( $header_value ) ) {
                $header_value = implode( ',', $header_value );
            }
            $request->set_header( $header_key, $header_value );
        }

        // Sanitize parameters while keeping credential-like values intact.
        $pass_through_keys = array( 'password', 'pass', 'access_token', 'refresh_token', 'token', 'api_key', 'signature', 'secret', 'authorization', 'ep_token', 'x_ep_token' );
        $sanitized_params  = array();

        foreach ( $params as $key => $value ) {
            $normalized_key = is_string( $key ) ? strtolower( $key ) : $key;
            if ( is_array( $value ) ) {
                $sanitized = wp_unslash( $value );
            } else {
                $raw_value = wp_unslash( $value );
                if ( in_array( $normalized_key, $pass_through_keys, true ) || preg_match( '/(token|secret|signature|password)/i', (string) $normalized_key ) ) {
                    $sanitized = $raw_value;
                } else {
                    $sanitized = sanitize_text_field( $raw_value );
                }
            }

            $sanitized_params[ $key ] = $sanitized;

            if ( 'action' === $key ) {
                $action = $sanitized;
            }

            if ( 'GET' === $method ) {
                $request->set_param( $key, $sanitized );
            } else {
                $request->set_param( $key, $sanitized );
            }
        }

        if ( 'GET' === $method ) {
            $request->set_query_params( $sanitized_params );
        } else {
            $body_params = $sanitized_params;
            unset( $body_params['rest_route'], $body_params['namespace'], $body_params['ep_namespace'], $body_params['ep_route'] );
            $request->set_body_params( $body_params );
            if ( ! empty( $json_params ) && method_exists( $request, 'set_json_params' ) ) {
                $request->set_json_params( $json_params );
            }
        }

        $request->set_param( 'action', $action );
        $request->set_route( $route );

        $response = rest_do_request( $request );

        if ( is_wp_error( $response ) ) {
            $error_data = $response->get_error_data();
            $status     = is_array( $error_data ) && isset( $error_data['status'] ) ? (int) $error_data['status'] : 500;
            status_header( $status );
            wp_send_json(
                array(
                    'status'  => 'error',
                    'message' => $response->get_error_message(),
                    'code'    => $response->get_error_code(),
                )
            );
        }

        $response = rest_ensure_response( $response );
        $status   = $response->get_status();
        if ( $status ) {
            status_header( $status );
        }

        wp_send_json( $response->get_data() );
    }

    /**
     * Return combined activation status for Tickets and Check-in extensions.
     * If both active -> 200 { active: true }
     * If missing -> 404 { active: false, message: '...' }
     */
    public function extensions_combined_status( WP_REST_Request $request ) {
        $tickets_slug = 'eventprime-event-tickets';
        $checkin_slug = 'eventprime-attendee-event-check-in';

        $tickets_active = $this->is_extension_active( $tickets_slug );
        $checkin_active = $this->is_extension_active( $checkin_slug );

        if ( $tickets_active && $checkin_active ) {
            return rest_ensure_response( array( 'active' => true, 'tickets' => $tickets_active, 'checkin' => $checkin_active ) );
        }

        $missing = array();
        if ( ! $tickets_active ) $missing[] = 'tickets';
        if ( ! $checkin_active ) $missing[] = 'checkin';

        $msg = 'Extensions not installed: ' . implode( ',', $missing );
        return new WP_Error( 'extensions_missing', $msg, array( 'status' => 404 ) );
    }

    /**
     * Helper to detect whether an extension plugin is active by slug.
     * Returns true if active, false otherwise.
     */
    protected function is_extension_active( $slug ) {
        if ( function_exists( 'is_plugin_active' ) ) {
            // try common plugin file names
            $paths = array(
                $slug . '/' . $slug . '.php',
                $slug . '/' . 'index.php',
            );
            foreach ( $paths as $p ) {
                if ( is_plugin_active( $p ) ) return true;
            }
        }
        // fallback: check for main class existence
        if ( $slug === 'eventprime-event-tickets' && class_exists( 'Eventprime_Event_Tickets_REST_API' ) ) return true;
        if ( $slug === 'eventprime-attendee-event-check-in' && class_exists( 'Eventprime_Attendee_Check_In_REST_API' ) ) return true;
        return false;
    }

    public function is_enabled() {
        // Checkboxes and toggles removed — REST API is always enabled.
        return true;
    }

    /**
     * Helper to check per-endpoint toggles
     */
    public function ep_is_rest_endpoint_enabled( $endpoint ) {
        // Checkboxes removed — all endpoints are considered enabled.
        return true;
    }

    // API key checks removed — endpoints are controlled by settings and logged-in users.

    public function permission_callback( WP_REST_Request $request ) {
        // Detailed permission handling with API-key support and diagnostics.
        $method = strtoupper( $request->get_method() );

        // If a recent failed username/password exchange was recorded from this IP,
        // block further API access to prevent leaking data to clients that supplied
        // incorrect credentials. We still allow the token issuance action so
        // legitimate clients can retry with correct credentials.
        $client_ip = '';
        if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
            $client_ip = trim( $ips[0] );
        } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
            $client_ip = trim( $_SERVER['REMOTE_ADDR'] );
        }
        if ( ! empty( $client_ip ) ) {
            $flag_key = 'ep_failed_auth_' . md5( $client_ip );
            if ( function_exists( 'get_transient' ) && get_transient( $flag_key ) ) {
                // Allow only token issuance or a valid access token to proceed so clients can retry.
                $maybe_action = '';
                if ( is_callable( array( $request, 'get_param' ) ) ) {
                    $maybe_action = $request->get_param( 'action' );
                }
                if ( $maybe_action === 'get_access_token' ) {
                    // allow token issuance so the client can try again
                } else {
                    // Check if caller provided a bearer token or x-ep-token/access_token param that validates.
                    $bypass = false;
                    // try header first
                    $req_auth = $request->get_header( 'authorization' );
                    $req_x_ep = $request->get_header( 'x-ep-token' );
                    $tkn = '';
                    if ( ! empty( $req_auth ) && stripos( $req_auth, 'Bearer ' ) === 0 ) {
                        $tkn = trim( substr( $req_auth, 7 ) );
                    } elseif ( ! empty( $req_x_ep ) ) {
                        $tkn = trim( wp_unslash( $req_x_ep ) );
                    } else {
                        $tq = $request->get_param( 'access_token' );
                        if ( $tq ) $tkn = sanitize_text_field( $tq );
                    }
                    if ( ! empty( $tkn ) ) {
                        $valid = false;
                        if ( method_exists( $this, 'ep_validate_access_token' ) ) {
                            $valid = $this->ep_validate_access_token( $tkn );
                        }
                        if ( $valid && isset( $valid['user_id'] ) && $valid['user_id'] ) {
                            // allow through
                            wp_set_current_user( (int) $valid['user_id'] );
                            $bypass = true;
                        }
                    }
                    if ( ! $bypass ) {
                        return new WP_Error( 'rest_forbidden', 'Access denied due to recent failed authentication attempts from your IP.', array( 'status' => 403 ) );
                    }
                }
            }
        }

        // Helper: try to fetch an Authorization-like header from server vars
        $auth_header = '';
        if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            $auth_header = trim( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) );
        } elseif ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            $auth_header = trim( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
        } elseif ( function_exists( 'apache_request_headers' ) ) {
            $hdrs = apache_request_headers();
            if ( ! empty( $hdrs['Authorization'] ) ) {
                $auth_header = trim( $hdrs['Authorization'] );
            }
        }

        // Fetch possible API key from different locations
        $provided_key = '';
        // Bearer token
        if ( $auth_header && stripos( $auth_header, 'Bearer ' ) === 0 ) {
            $provided_key = trim( substr( $auth_header, 7 ) );
        }
        // X-API-KEY header
        if ( empty( $provided_key ) && isset( $_SERVER['HTTP_X_API_KEY'] ) ) {
            $provided_key = trim( wp_unslash( $_SERVER['HTTP_X_API_KEY'] ) );
        }
        // query param
        if ( empty( $provided_key ) ) {
            $qkey = $request->get_param( 'api_key' );
            if ( $qkey ) $provided_key = sanitize_text_field( $qkey );
        }

        // Server-configured key: prefer a constant, fallback to option
        $server_key = '';
        if ( defined( 'EP_REST_API_KEY' ) && EP_REST_API_KEY ) {
            $server_key = EP_REST_API_KEY;
        } else {
            $server_key = get_option( 'ep_rest_api_key', '' );
        }

        // Log attempt for debugging (avoid logging full keys in production)
        // Debug logging removed

        // Allow public GET requests by default (keeps read access open). However, if the caller
        // provides any Authorization-like credential (Bearer token, X-API-KEY, access_token query)
        // we will attempt to validate it and return 403 on invalid credentials instead of silently
        // falling back to public data. This prevents leaking data when an invalid token/key is supplied.
        if ( 'GET' === $method ) {
            // Determine if caller attempted to present credentials by checking request headers first
            $auth_attempt = false;
            $req_auth = $request->get_header( 'authorization' );
            $req_x_api = $request->get_header( 'x-api-key' );
            $req_x_ep = $request->get_header( 'x-ep-token' );
            if ( ! empty( $req_auth ) ) $auth_attempt = true;
            if ( ! empty( $req_x_api ) ) $auth_attempt = true;
            if ( ! empty( $req_x_ep ) ) $auth_attempt = true;
            $qp = $request->get_query_params();
            if ( isset( $qp['api_key'] ) || isset( $qp['access_token'] ) ) $auth_attempt = true;
            // Also consider server vars that may contain auth info
            if ( ! empty( $auth_header ) || ( isset( $_SERVER['HTTP_X_API_KEY'] ) && ! empty( $_SERVER['HTTP_X_API_KEY'] ) ) || ( isset( $_SERVER['HTTP_X_EP_TOKEN'] ) && ! empty( $_SERVER['HTTP_X_EP_TOKEN'] ) ) ) {
                $auth_attempt = true;
            }

            // If no auth attempt, allow anonymous GET as before
            if ( ! $auth_attempt ) {
                return true;
            }

            // If caller attempted auth, validate the credential(s). Allow if any valid method applies.
            if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
                return true;
            }

            // server key check (from earlier extraction)
            if ( ! empty( $server_key ) && ! empty( $provided_key ) && hash_equals( (string) $server_key, (string) $provided_key ) ) {
                return true;
            }

            // Token validation: prioritize headers present on the WP_REST_Request (these reliably reflect client headers)
            $tkn = '';
            if ( ! empty( $req_auth ) && stripos( $req_auth, 'Bearer ' ) === 0 ) {
                $tkn = trim( substr( $req_auth, 7 ) );
            } elseif ( ! empty( $req_x_ep ) ) {
                $tkn = trim( wp_unslash( $req_x_ep ) );
            } elseif ( ! empty( $req_x_api ) ) {
                // treat x-api-key as provided_key above; already checked
                $provided_key = sanitize_text_field( $req_x_api );
            }

            // If not found in headers, fallback to server vars or query param
            if ( empty( $tkn ) ) {
                if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) && stripos( $_SERVER['HTTP_AUTHORIZATION'], 'Bearer ' ) === 0 ) {
                    $tkn = trim( substr( $_SERVER['HTTP_AUTHORIZATION'], 7 ) );
                } elseif ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) && stripos( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 'Bearer ' ) === 0 ) {
                    $tkn = trim( substr( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 7 ) );
                } elseif ( isset( $_SERVER['HTTP_X_EP_TOKEN'] ) ) {
                    $tkn = trim( wp_unslash( $_SERVER['HTTP_X_EP_TOKEN'] ) );
                } else {
                    $tq = $request->get_param( 'access_token' );
                    if ( $tq ) $tkn = sanitize_text_field( $tq );
                }
            }

            if ( ! empty( $tkn ) ) {
                $valid = $this->ep_validate_access_token( $tkn );
                if ( $valid && isset( $valid['user_id'] ) && $valid['user_id'] ) {
                    wp_set_current_user( (int) $valid['user_id'] );
                    return true;
                }
            }

            return new WP_Error( 'rest_forbidden', 'Invalid or missing credentials. Access denied.', array( 'status' => 403 ) );
        }

        // Mutating requests require authentication. We support three ways to authenticate:
        // 1) logged-in WP user with capability (current behaviour)
        // 2) server-configured static server key (legacy)
        // 3) short-lived access token issued by get_access_token (recommended)
        // For convenience, allow public GETs (read-only) by default.
        $action = '';
        if ( is_callable( array( $request, 'get_param' ) ) ) {
            $action = $request->get_param( 'action' );
            if ( empty( $action ) ) {
                $action = $request->get_param( 'trigger' );
            }
        }
        // No-op: GET handled above.

        // For mutating requests, allow if:
        // - logged-in user with capability OR
        // - provided API key matches server key OR
        // - valid short-lived access token provided in Authorization: Bearer <token> or X-EP-TOKEN header or access_token param
        if ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) {
            return true;
        }

        // Allow token issuance without prior auth so clients can exchange credentials for a short-lived token
        if ( ! empty( $action ) && $action === 'get_access_token' ) {
            return true;
        }

        if ( ! empty( $server_key ) && ! empty( $provided_key ) && hash_equals( (string) $server_key, (string) $provided_key ) ) {
            return true;
        }

        // Access token validation
        $token = '';
        // Authorization: Bearer <token>
        if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) && stripos( $_SERVER['HTTP_AUTHORIZATION'], 'Bearer ' ) === 0 ) {
            $token = trim( substr( $_SERVER['HTTP_AUTHORIZATION'], 7 ) );
        } elseif ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) && stripos( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 'Bearer ' ) === 0 ) {
            $token = trim( substr( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'], 7 ) );
        } elseif ( isset( $_SERVER['HTTP_X_EP_TOKEN'] ) ) {
            $token = trim( wp_unslash( $_SERVER['HTTP_X_EP_TOKEN'] ) );
        } else {
            // query param fallback
            $t = $request->get_param( 'access_token' );
            if ( $t ) $token = sanitize_text_field( $t );
        }

        if ( ! empty( $token ) ) {
            $valid = $this->ep_validate_access_token( $token );
            if ( $valid && isset( $valid['user_id'] ) && $valid['user_id'] ) {
                // set current user for capability checks
                wp_set_current_user( (int) $valid['user_id'] );
                return true;
            }
        }

        return new WP_Error( 'rest_forbidden', 'You are not allowed to perform this action.', array( 'status' => 403 ) );
    }

    public function register_routes() {
        // Reduce to a single unified integration endpoint. All actions/triggers are handled
        // by handle_integration() using the `action` or `trigger` parameter. This removes
        // Debug logging removed
        $ns = 'eventprime/v1';

        // Respect global setting: only register API routes when enabled
        $global = get_option( 'em_global_settings', null );
        $enabled = false;
        if ( is_object( $global ) && isset( $global->enable_api ) && $global->enable_api ) {
            $enabled = true;
        }

        if ( ! $enabled ) {
            // Debug logging removed
            return;
        }

        register_rest_route( $ns, '/integration', array(
            array(
                'methods' => array( 'GET', 'POST' ),
                'callback' => array( $this, 'handle_integration' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            )
        ) );

        // Register more descriptive, backwards-compatible endpoints so integrators
        // can use clear resource names. These mirror existing handlers and do NOT
        // remove or change the unified /integration behaviour.
        // Examples:
        //  GET /wp-json/eventprime/v1/events            -> all events
        //  GET /wp-json/eventprime/v1/events/{id}       -> single event
        //  GET /wp-json/eventprime/v1/tickets?event_id= -> tickets list
        //  GET /wp-json/eventprime/v1/tickets/{id}     -> single ticket
        //  GET /wp-json/eventprime/v1/bookings?event_id= -> bookings list
        //  GET /wp-json/eventprime/v1/bookings/{id}    -> single booking

        // Register resource routes that proxy to the unified /integration?action=... handler
        register_rest_route( $ns, '/events', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_events' ),
                // let the handler decide token/permission behaviour
                'permission_callback' => '__return_true',
                'args' => array(
                    'page' => array(
                        'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); }
                    ),
                    'per_page' => array(
                        'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); }
                    ),
                    'search' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'after' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'before' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'status' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            ),
        ) );

        register_rest_route( $ns, '/events/(?P<id>\d+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_event' ),
                'permission_callback' => '__return_true',
                'args' => array(
                    'id' => array(
                        'validate_callback' => function( $param, $request, $key ) { return is_numeric( $param ); }
                    ),
                ),
            ),
        ) );

        register_rest_route( $ns, '/tickets', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/tickets/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/bookings', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'bookings_permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/bookings/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'bookings_permission_callback' ),
            ),
        ) );

        // Additional legacy-friendly "get_" named endpoints requested by integrators.
        // These are simple aliases that forward to the same handlers above and
        // reuse the same permission logic so existing integrations are not broken.
        register_rest_route( $ns, '/get_events', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_event/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_tickets', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_ticket/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_bookings', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'bookings_permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_booking/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'bookings_permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_performers', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_performer/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_venues', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_event_type', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns, '/get_organizers', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        // Combined extensions status for mobile clients to determine whether
        // both Tickets and Check-in extensions are available. Returns 200 when
        // both installed, otherwise 404 with a helpful message.
        register_rest_route( $ns, '/extensions/combined-status', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'extensions_combined_status' ),
                'permission_callback' => '__return_true',
            ),
        ) );

        // Also expose a v2 namespace so integrators can opt-in to the richer payload
        // and avoid potential route collisions with older block-provided endpoints.
        $ns2 = 'eventprime/v2';
        register_rest_route( $ns2, '/events', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_events' ),
                'permission_callback' => '__return_true',
                'args' => array(
                    'page' => array( 'validate_callback' => function( $param ) { return is_numeric( $param ); } ),
                    'per_page' => array( 'validate_callback' => function( $param ) { return is_numeric( $param ); } ),
                    'search' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'after' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'before' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'status' => array( 'sanitize_callback' => 'sanitize_text_field' ),
                ),
            ),
        ) );

        register_rest_route( $ns2, '/events/(?P<id>\d+)', array(
            array(
                'methods'  => WP_REST_Server::READABLE,
                'callback' => array( $this, 'get_event' ),
                'permission_callback' => '__return_true',
                'args' => array( 'id' => array( 'validate_callback' => function( $param ) { return is_numeric( $param ); } ) ),
            ),
        ) );

        // Duplicate v1 resource routes under v2 to maintain identical behaviour
        register_rest_route( $ns2, '/integration', array(
            array(
                'methods' => array( 'GET', 'POST' ),
                'callback' => array( $this, 'handle_integration' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            )
        ) );

        register_rest_route( $ns2, '/tickets', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/tickets/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/bookings', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'bookings_permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/bookings/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'bookings_permission_callback' ),
            ),
        ) );

        // legacy get_ aliases
        register_rest_route( $ns2, '/get_events', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_event/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_tickets', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_ticket/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_bookings', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'bookings_permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_booking/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_performers', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_performer/(?P<id>\d+)', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_venues', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_event_type', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/get_organizers', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'integration_proxy' ),
                'permission_callback' => array( $this, 'permission_callback' ),
            ),
        ) );

        register_rest_route( $ns2, '/extensions/combined-status', array(
            array(
                'methods' => 'GET',
                'callback' => array( $this, 'extensions_combined_status' ),
                'permission_callback' => '__return_true',
            ),
        ) );

    }

    /**
     * Backwards-compatible wrappers for route callbacks.
     * New routes registered earlier call `get_events` / `get_event`.
     * Reuse existing handlers so we don't duplicate logic.
     */
    public function get_events( WP_REST_Request $request ) {
        // Allow anonymous GET requests to receive the public events list
        if ( strtoupper( $request->get_method() ) === 'GET' ) {
            return $this->handle_events_list( $request );
        }
        // For non-GET (mutating) requests, require token/auth
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_events_list( $request );
    }
    public function get_event( WP_REST_Request $request ) {
        // Allow anonymous GET to fetch a single event
        if ( strtoupper( $request->get_method() ) === 'GET' ) {
            return $this->handle_event_get( $request );
        }
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_event_get( $request );
    }

    // Tickets wrappers
    public function get_tickets( WP_REST_Request $request ) {
        // reuse handle_tickets_list which expects 'event_id' query param
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_tickets_list( $request );
    }

    public function get_ticket( WP_REST_Request $request ) {
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_ticket_get( $request );
    }

    /**
     * Proxy a resource-style REST route to the unified integration?action=... handler.
     * This keeps all endpoints available under /integration?action=... while retaining
     * friendly resource routes that forward here.
     */
    public function integration_proxy( WP_REST_Request $request ) {
        // Build an 'action' param based on the incoming route and request
        $route = isset( $request->get_route ) ? $request->get_route() : ''; // defensive
        $params = $request->get_query_params();

        // If the caller already provided an action, prefer it
        if ( isset( $params['action'] ) && $params['action'] ) {
            $action = sanitize_text_field( $params['action'] );
        } else {
            // Derive action from route path and verb
            $path = $request->get_route();
            $method = strtoupper( $request->get_method() );
            // Map simple resource paths to integration action names
            // Examples: /events -> action=events ; /events/{id} -> action=get_event
            if ( preg_match('#/events/(?P<id>\d+)#', $path, $m) ) {
                $action = 'get_event';
                $params['id'] = isset( $m['id'] ) ? intval( $m['id'] ) : 0;
            } elseif ( strpos( $path, '/events' ) !== false ) {
                $action = 'events';
            } elseif ( preg_match('#/tickets/(?P<id>\d+)#', $path, $m) ) {
                $action = 'get_ticket';
                $params['id'] = isset( $m['id'] ) ? intval( $m['id'] ) : 0;
            } elseif ( strpos( $path, '/tickets' ) !== false ) {
                $action = 'tickets';
            } elseif ( preg_match('#/bookings/(?P<id>\d+)#', $path, $m) ) {
                $action = 'get_booking';
                $params['id'] = isset( $m['id'] ) ? intval( $m['id'] ) : 0;
            } elseif ( strpos( $path, '/bookings' ) !== false ) {
                $action = 'bookings';
            } elseif ( strpos( $path, '/get_events' ) !== false ) {
                $action = 'events';
            } elseif ( preg_match('#/get_event/(?P<id>\d+)#', $path, $m) ) {
                $action = 'get_event';
                $params['id'] = isset( $m['id'] ) ? intval( $m['id'] ) : 0;
            } else {
                // fallback to integration handler directly
                $action = '';
            }
        }

        // Merge body params into $params so integration handler sees them
        $body = $request->get_body_params();
        if ( is_array( $body ) && ! empty( $body ) ) {
            $params = array_merge( $params, $body );
        }

        // Prepare integration-style request params
        if ( $action ) $params['action'] = $action;

        // Call the unified handler
        $req = new WP_REST_Request();
        foreach ( $params as $k => $v ) {
            $req->set_param( $k, $v );
        }
        // Preserve method
        $req->set_method( $request->get_method() );

        // Forward to handle_integration which enforces token checks as needed
        return $this->handle_integration( $req );
    }

    // Bookings wrappers
    public function get_bookings( WP_REST_Request $request ) {
        // If caller provided an event_id, delegate to the integration helper which
        // supports event-scoped booking lists. This keeps the alias behavior
        // identical to the integration action=bookings?event_id=... behavior.
        $params = $request->get_query_params();
        $event_id = isset( $params['event_id'] ) ? absint( $params['event_id'] ) : 0;
            $status = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'completed';
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        if ( $event_id ) {
            $res = $this->integration_all_bookings_by_status( $status, $event_id );
            return rest_ensure_response( $res );
        }
        return $this->handle_bookings_list( $request );
    }

    public function get_booking( WP_REST_Request $request ) {
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_booking_get( $request );
    }

    /**
     * Restrict booking endpoints to authenticated users with booking management capability.
     */
    public function bookings_permission_callback( WP_REST_Request $request ) {
        // Require a valid token first (sets current user)
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        
        // Require capability to view bookings
        if (!is_user_logged_in() || !current_user_can('manage_options')) {
            return new WP_Error( 'rest_forbidden', esc_html__( 'You are not allowed to access bookings.', 'eventprime-event-calendar-management' ), array( 'status' => 403 ) );
        }
        
        return true;
        
    }

    // Performers wrappers
    public function get_performers( WP_REST_Request $request ) {
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_performers_list( $request );
    }

    public function get_performer( WP_REST_Request $request ) {
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_performer_get( $request );
    }

    // Venues & Organizers wrappers
    public function get_venues( WP_REST_Request $request ) {
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_venues_list( $request );
    }

    public function get_organizers( WP_REST_Request $request ) {
        $check = $this->ep_require_token_or_401( $request );
        if ( $check !== true ) {
            if ( $check instanceof WP_REST_Response ) {
                $data    = $check->get_data();
                $status  = $check->get_status();
                $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
            }
            return $check;
        }
        return $this->handle_organizers_list( $request );   
    }

    /* ---------- Handlers ---------- */

    public function handle_ping( WP_REST_Request $request ) {
        $out = array( 'ok' => true, 'ts' => current_time( 'mysql' ) );
        $resp = rest_ensure_response( $out );
        if ( is_object( $resp ) && method_exists( $resp, 'header' ) ) {
            $resp->header( 'X-EP-Handler', 'core-rest-ping' );
        } else {
            header( 'X-EP-Handler: core-rest-ping' );
        }
        return $resp;
    }

    public function handle_events_list( WP_REST_Request $request ) {
        if ( ! get_option( 'ep_rest_api_settings', array() ) ) {
            // ensure defaults
            get_option( 'ep_rest_api_settings', array() );
        }
        $params = $request->get_query_params();
        $page = isset( $params['page'] ) ? absint( $params['page'] ) : 1;
        // Allow caller to request all events by sending per_page='all' or per_page <= 0.
        $per_param = isset( $params['per_page'] ) ? $params['per_page'] : 'all';
        if ( is_string( $per_param ) && strtolower( $per_param ) === 'all' ) {
            $per = -1;
        } else {
            $per = intval( $per_param );
            if ( $per <= 0 ) $per = -1; // -1 => return all posts
        }
        $search = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
        $after = isset( $params['after'] ) ? sanitize_text_field( $params['after'] ) : '';
        $before = isset( $params['before'] ) ? sanitize_text_field( $params['before'] ) : '';

        $args = array(
            'post_type' => 'em_event',
            'posts_per_page' => $per,
            'paged' => $page,
            's' => $search,
        );

        // Allow caller to request other post_status values (comma-separated), default to 'publish'
        $status_param = isset( $params['status'] ) ? $params['status'] : '';
        if ( ! empty( $status_param ) ) {
            if ( strpos( $status_param, ',' ) !== false ) {
                $status_list = array_map( 'trim', explode( ',', $status_param ) );
                $status_list = array_map( 'sanitize_text_field', $status_list );
                $args['post_status'] = $status_list;
            } else {
                $args['post_status'] = sanitize_text_field( $status_param );
            }
        } else {
            $args['post_status'] = 'publish';
        }
        if ( $after ) {
            $args['date_query'][] = array( 'after' => $after );
        }
        if ( $before ) {
            $args['date_query'][] = array( 'before' => $before );
        }

        $q = new WP_Query( $args );
        $items = array();
        // Optional debug logging when caller sets ?debug=1

        if ( $q->have_posts() ) {
                while ( $q->have_posts() ) {
                    $q->the_post();
                    // use rich formatter which prefers core helper and falls back safely
                    try {
                        $items[] = $this->ep_format_event_object( get_post() );
                    } catch ( Exception $e ) {
                        // debug logging removed
                        // skip this event to avoid returning a 500 for the whole list
                        continue;
                    }
                }
                wp_reset_postdata();
           
            }

            // If no items found and caller did not explicitly request a status, try a wider fallback
            // to help surface events that may have non-standard post_status values.
            if ( empty( $items ) && empty( $status_param ) ) {
                $fallback_args = $args;
                $fallback_args['post_status'] = 'any';
                $fallback_args['posts_per_page'] = max( 20, $per );
                if ( isset( $fallback_args['date_query'] ) ) {
                    unset( $fallback_args['date_query'] );
                }
                $fallback_q = new WP_Query( $fallback_args );
                if ( $fallback_q->have_posts() ) {
                    while ( $fallback_q->have_posts() ) {
                        $fallback_q->the_post();
                        $items[] = $this->ep_format_event_object( get_post() );
                    }
                    wp_reset_postdata();
                   
                }
            }
            
            // Final fallback: try a simple get_posts call to ensure we surface any em_event posts
            if ( empty( $items ) ) {
                $gp_args = array(
                    'numberposts' => -1,
                    'post_type' => 'em_event',
                    'post_status' => 'publish',
                );
                $gp = get_posts( $gp_args );
                if ( $gp ) {
                    foreach ( $gp as $p ) {
                        $items[] = $this->ep_format_event_object( $p );
                    }
                    
                }
            }

            $out = array(
                'status' => 'success',
                'count' => count( $items ),
                'events' => $items,
            );

            // If caller requested debug, include query args and found_posts to aid troubleshooting
            if ( isset( $params['debug'] ) && $params['debug'] ) {
                $debug_info = array( 'query_args' => $args );
                if ( isset( $q ) && isset( $q->found_posts ) ) $debug_info['found_posts'] = (int) $q->found_posts;
                if ( isset( $fallback_q ) && isset( $fallback_q->found_posts ) ) $debug_info['fallback_found_posts'] = (int) $fallback_q->found_posts;
                $out['debug'] = $debug_info;
            }

            $resp = rest_ensure_response( $out );
            if ( is_object( $resp ) && method_exists( $resp, 'header' ) ) {
                $resp->header( 'X-EP-Handler', 'core-rest-events' );
            } else {
                header( 'X-EP-Handler: core-rest-events' );
            }
            return $resp;
    }

    public function handle_event_get( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_event' ) {
            return new WP_Error( 'not_found', 'Event not found.', array( 'status' => 404 ) );
        }
        $data = $this->ep_format_event_object( $post );
        return rest_ensure_response( array( 'status' => 'success', 'event' => $data ) );
    }

  public function handle_event_create( WP_REST_Request $request ) {
    // Protected: requires API key (permission callback will check)
    $body = $request->get_body_params();
    // If body empty (some clients may send query params or form-encoded), merge query params as fallback
    if ( empty( $body ) ) {
        $body = $request->get_query_params();
    }
    // Some clients (PowerShell ConvertTo-Json) produce JSON that WP may not map to body params correctly.
    // Merge raw JSON body into $body when available so nested arrays are preserved.
    $raw = $request->get_body();
    if ( ! empty( $raw ) ) {
        $decoded = json_decode( $raw, true );
        if ( is_array( $decoded ) ) {
            $body = array_merge( (array) $body, $decoded );
        }
    }

    // Normalize tickets payload early so has_tickets is computed correctly for booking enablement.
    if ( ! empty( $body['tickets'] ) && ! is_array( $body['tickets'] ) ) {
        $normalized = json_decode( wp_json_encode( $body['tickets'] ), true );
        if ( is_array( $normalized ) ) {
            $body['tickets'] = $normalized;
        }
    }

    $name = isset( $body['em_name'] ) ? sanitize_text_field( $body['em_name'] ) : '';
    // Enhanced date/time handling to support multiple input formats
    $start = '';
    $end = '';
    $start_ts = null;
    $end_ts = null;

    // PRIORITY 1: Combined date+time fields (ISO or MySQL format)
    if ( isset( $body['em_start_date_time'] ) && ! empty( $body['em_start_date_time'] ) ) {
        $start = sanitize_text_field( $body['em_start_date_time'] );
    } elseif ( isset( $body['start_date_time'] ) && ! empty( $body['start_date_time'] ) ) {
        $start = sanitize_text_field( $body['start_date_time'] );
    }
    if ( isset( $body['em_end_date_time'] ) && ! empty( $body['em_end_date_time'] ) ) {
        $end = sanitize_text_field( $body['em_end_date_time'] );
    } elseif ( isset( $body['end_date_time'] ) && ! empty( $body['end_date_time'] ) ) {
        $end = sanitize_text_field( $body['end_date_time'] );
    }

    // PRIORITY 2: Separate date + time fields
    if ( empty( $start ) && isset( $body['em_start_date'] ) && ! empty( $body['em_start_date'] ) ) {
        if ( isset( $body['em_start_time'] ) && ! empty( $body['em_start_time'] ) ) {
            $start_date = sanitize_text_field( $body['em_start_date'] );
            $start_time = sanitize_text_field( $body['em_start_time'] );
            $start = sprintf( '%s %s', $start_date, $start_time );
        } else {
            $start = sprintf( '%s 00:00:00', sanitize_text_field( $body['em_start_date'] ) );
        }
    }
    if ( empty( $end ) && isset( $body['em_end_date'] ) && ! empty( $body['em_end_date'] ) ) {
        if ( isset( $body['em_end_time'] ) && ! empty( $body['em_end_time'] ) ) {
            $end_date = sanitize_text_field( $body['em_end_date'] );
            $end_time = sanitize_text_field( $body['em_end_time'] );
            $end = sprintf( '%s %s', $end_date, $end_time );
        } else {
            $end = sprintf( '%s 23:59:59', sanitize_text_field( $body['em_end_date'] ) );
        }
    }

    // PRIORITY 3: Direct timestamp input
    if ( empty( $start ) && isset( $body['em_start_date'] ) && is_numeric( $body['em_start_date'] ) ) {
        $start_ts = (int) $body['em_start_date'];
        $start = date( 'Y-m-d H:i:s', $start_ts );
    }
    if ( empty( $end ) && isset( $body['em_end_date'] ) && is_numeric( $body['em_end_date'] ) ) {
        $end_ts = (int) $body['em_end_date'];
        $end = date( 'Y-m-d H:i:s', $end_ts );
    }

    if ( function_exists( 'wp_timezone' ) ) {
        $site_timezone = wp_timezone();
    } else {
        $tz_string = function_exists( 'wp_timezone_string' ) ? wp_timezone_string() : '';
        if ( ! empty( $tz_string ) ) {
            $site_timezone = new DateTimeZone( $tz_string );
        } else {
            $offset = get_option( 'gmt_offset', 0 );
            $offset_seconds = intval( $offset * HOUR_IN_SECONDS );
            $tz_name = timezone_name_from_abbr( '', $offset_seconds, 0 );
            $site_timezone = $tz_name ? new DateTimeZone( $tz_name ) : new DateTimeZone( 'UTC' );
        }
    }
    $parse_datetime = function( $value ) use ( $site_timezone ) {
        if ( empty( $value ) ) {
            return null;
        }
        try {
            return new DateTime( $value, $site_timezone );
        } catch ( Exception $e ) {
            try {
                $dt = new DateTime( $value );
                $dt->setTimezone( $site_timezone );
                return $dt;
            } catch ( Exception $ignored ) {
                return null;
            }
        }
    };

    $start_date_for_ep = '';
    $start_time_for_ep = '';
    $start_dt_obj      = null;
    if ( ! empty( $start ) ) {
        $start_dt_obj = $parse_datetime( $start );
    }
    if ( ! $start_dt_obj && ! empty( $start_ts ) ) {
        try {
            $start_dt_obj = new DateTime( '@' . $start_ts );
            $start_dt_obj->setTimezone( $site_timezone );
        } catch ( Exception $ignored ) {
            $start_dt_obj = null;
        }
    }
    if ( $start_dt_obj ) {
        $start_date_for_ep = $start_dt_obj->format( 'Y-m-d' );
        $start_time_for_ep = $start_dt_obj->format( 'g:i A' );
        if ( empty( $start_ts ) ) {
            $start_ts = $start_dt_obj->getTimestamp();
        }
        $start = $start_dt_obj->format( 'Y-m-d H:i:s' );
    }

    $end_date_for_ep = '';
    $end_time_for_ep = '';
    $end_dt_obj      = null;
    if ( ! empty( $end ) ) {
        $end_dt_obj = $parse_datetime( $end );
    }
    if ( ! $end_dt_obj && ! empty( $end_ts ) ) {
        try {
            $end_dt_obj = new DateTime( '@' . $end_ts );
            $end_dt_obj->setTimezone( $site_timezone );
        } catch ( Exception $ignored ) {
            $end_dt_obj = null;
        }
    }
    if ( $end_dt_obj ) {
        $end_date_for_ep = $end_dt_obj->format( 'Y-m-d' );
        $end_time_for_ep = $end_dt_obj->format( 'g:i A' );
        if ( empty( $end_ts ) ) {
            $end_ts = $end_dt_obj->getTimestamp();
        }
        $end = $end_dt_obj->format( 'Y-m-d H:i:s' );
    }

    // Description
    if ( isset( $body['em_description'] ) ) {
        $description = sanitize_textarea_field( $body['em_description'] );
    } elseif ( isset( $body['description'] ) ) {
        $description = sanitize_textarea_field( $body['description'] );
    } elseif ( isset( $body['event_description'] ) ) {
        $description = sanitize_textarea_field( $body['event_description'] );
    } else {
        $description = '';
    }

    // Prepare EP helper if available
    $ep = null;
    if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
        $ep = new Eventprime_Basic_Functions();
    }

    // Parse to timestamps
    if ( empty( $start_ts ) && ! empty( $start_date_for_ep ) ) {
    if ( $ep && method_exists( $ep, 'ep_date_to_timestamp' ) ) {
            $try = $ep->ep_date_to_timestamp( $start_date_for_ep, 'Y-m-d', 1 );
            if ( $try ) { $start_ts = (int) $try; }
        }
        if ( empty( $start_ts ) && $start_dt_obj ) {
            $start_ts = $start_dt_obj->getTimestamp();
        } elseif ( empty( $start_ts ) && ! empty( $start ) ) {
            $start_ts = strtotime( $start );
        }
    }
    if ( empty( $end_ts ) && ! empty( $end_date_for_ep ) ) {
    if ( $ep && method_exists( $ep, 'ep_date_to_timestamp' ) ) {
            $try = $ep->ep_date_to_timestamp( $end_date_for_ep, 'Y-m-d', 1 );
            if ( $try ) { $end_ts = (int) $try; }
        }
        if ( empty( $end_ts ) && $end_dt_obj ) {
            $end_ts = $end_dt_obj->getTimestamp();
        } elseif ( empty( $end_ts ) && ! empty( $end ) ) {
            $end_ts = strtotime( $end );
        }
    }
    if ( empty( $end_ts ) && ! empty( $start_ts ) ) {
        $end_ts = $start_ts + 3600;
    }

    $final_start_ts = $start_ts;
    $final_end_ts   = $end_ts;

    // Entities (unchanged)
    $organizer_id = 0; $organizer_ids = array();
    $resolve_term_by_name = function( $name, $taxonomy ) {
        $name = sanitize_text_field( $name );
        if ( empty( $name ) ) return 0;
        $term = get_term_by( 'name', $name, $taxonomy );
        if ( $term && ! is_wp_error( $term ) ) return (int) $term->term_id;
        $res = wp_insert_term( $name, $taxonomy );
        if ( ! is_wp_error( $res ) && ! empty( $res['term_id'] ) ) return (int) $res['term_id'];
        return 0;
    };
    if ( ! empty( $body['em_organizer'] ) && is_numeric( $body['em_organizer'] ) ) {
        $organizer_id = absint( $body['em_organizer'] ); $organizer_ids[] = $organizer_id;
    } elseif ( ! empty( $body['organizer_id'] ) && is_numeric( $body['organizer_id'] ) ) {
        $organizer_id = absint( $body['organizer_id'] ); $organizer_ids[] = $organizer_id;
    } elseif ( ! empty( $body['organizer'] ) ) {
        if ( is_array( $body['organizer'] ) ) {
            foreach ( $body['organizer'] as $o ) { $id = $resolve_term_by_name( $o, 'em_event_organizer' ); if ( $id ) $organizer_ids[] = $id; }
        } else { $organizer_ids[] = $resolve_term_by_name( $body['organizer'], 'em_event_organizer' ); }
    } elseif ( ! empty( $body['organizer_name'] ) ) {
        $organizer_ids[] = $resolve_term_by_name( $body['organizer_name'], 'em_event_organizer' );
    }
    if ( ! empty( $organizer_ids ) ) $organizer_id = (int) $organizer_ids[0];

    $venue_id = 0; $venue_ids = array();
    if ( ! empty( $body['em_venue'] ) && is_numeric( $body['em_venue'] ) ) { $venue_id = absint( $body['em_venue'] ); $venue_ids[] = $venue_id; }
    elseif ( ! empty( $body['venue_id'] ) && is_numeric( $body['venue_id'] ) ) { $venue_id = absint( $body['venue_id'] ); $venue_ids[] = $venue_id; }
    elseif ( ! empty( $body['venue'] ) ) {
        if ( is_array( $body['venue'] ) ) { foreach ( $body['venue'] as $v ) { $id = $resolve_term_by_name( $v, 'em_venue' ); if ( $id ) $venue_ids[] = $id; } }
        else { $venue_ids[] = $resolve_term_by_name( $body['venue'], 'em_venue' ); }
    } elseif ( ! empty( $body['venue_name'] ) ) { $venue_ids[] = $resolve_term_by_name( $body['venue_name'], 'em_venue' ); }
    if ( ! empty( $venue_ids ) ) $venue_id = (int) $venue_ids[0];

    $performer_id = 0; $performer_ids = array();
    $resolve_performer_by_name = function( $name ) {
        $name = sanitize_text_field( $name );
        if ( empty( $name ) ) return 0;
        $p = get_page_by_title( $name, OBJECT, 'em_performer' );
        if ( $p && ! is_wp_error( $p ) ) return (int) $p->ID;
        $posts = get_posts( array( 'post_type' => 'em_performer', 's' => $name, 'posts_per_page' => 1 ) );
        if ( ! empty( $posts ) ) return (int) $posts[0]->ID;
        $new = wp_insert_post( array( 'post_title' => $name, 'post_status' => 'publish', 'post_type' => 'em_performer' ) );
        if ( $new && ! is_wp_error( $new ) ) return (int) $new;
        return 0;   
    };
    if ( ! empty( $body['em_performer'] ) ) {
        if ( is_array( $body['em_performer'] ) ) foreach ( $body['em_performer'] as $pid ) if ( is_numeric( $pid ) ) $performer_ids[] = absint( $pid );
        elseif ( is_numeric( $body['em_performer'] ) ) $performer_ids[] = absint( $body['em_performer'] );
    }
    if ( ! empty( $body['performers'] ) && is_array( $body['performers'] ) ) {
        foreach ( $body['performers'] as $pn ) { $id = $resolve_performer_by_name( $pn ); if ( $id ) $performer_ids[] = $id; }
    } elseif ( ! empty( $body['performer'] ) ) {
        if ( is_array( $body['performer'] ) ) foreach ( $body['performer'] as $pn ) { $id = $resolve_performer_by_name( $pn ); if ( $id ) $performer_ids[] = $id; }
        else { $id = $resolve_performer_by_name( $body['performer'] ); if ( $id ) $performer_ids[] = $id; }
    }
    if ( ! empty( $performer_ids ) ) $performer_id = (int) $performer_ids[0];

    $event_type_ids = array();
    if ( ! empty( $body['em_event_type'] ) ) {
        if ( is_array( $body['em_event_type'] ) ) {
            foreach ( $body['em_event_type'] as $et ) {
                if ( is_numeric( $et ) ) $event_type_ids[] = absint( $et );
                else { $id = $resolve_term_by_name( $et, 'em_event_type' ); if ( $id ) $event_type_ids[] = $id; }
            }
        } else {
            if ( is_numeric( $body['em_event_type'] ) ) $event_type_ids[] = absint( $body['em_event_type'] );
            else { $id = $resolve_term_by_name( $body['em_event_type'], 'em_event_type' ); if ( $id ) $event_type_ids[] = $id; }
        }
    }
    if ( ! empty( $body['event_types'] ) && is_array( $body['event_types'] ) ) {
        foreach ( $body['event_types'] as $et ) { $id = $resolve_term_by_name( $et, 'em_event_type' ); if ( $id ) $event_type_ids[] = $id; }
    } elseif ( ! empty( $body['event_type'] ) ) {
        $etv = $body['event_type'];
        if ( is_array( $etv ) ) { foreach ( $etv as $et ) { $id = $resolve_term_by_name( $et, 'em_event_type' ); if ( $id ) $event_type_ids[] = $id; } }
        else { $id = $resolve_term_by_name( $etv, 'em_event_type' ); if ( $id ) $event_type_ids[] = $id; }
    }

    if ( empty( $name ) || ( empty( $start ) && empty( $start_ts ) ) ) {
        return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing required event data.' ), 400 );
    }

    // SIMPLE BOOKING DETECTION
    $has_tickets = ( ! empty( $body['tickets'] ) && is_array( $body['tickets'] ) ) || ! empty( $body['ticket'] );

    // Create event
    if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
        $ep = new Eventprime_Basic_Functions();
        $event_data = array( 'name' => $name, 'description' => $description );
        $event_data['em_add_slug_in_event_title'] = 0;
        if ( ! empty( $final_start_ts ) ) { $event_data['em_start_date'] = $final_start_ts; $event_data['em_start_date_time'] = $final_start_ts; }
        if ( ! empty( $final_end_ts ) )   { $event_data['em_end_date']   = $final_end_ts;   $event_data['em_end_date_time']   = $final_end_ts; }
        if ( $venue_id )      { $event_data['em_venue']     = $venue_id; }
        if ( $organizer_id )  { $event_data['em_organizer'] = $organizer_id; }
        if ( $performer_id )  { $event_data['em_performer'] = $performer_id; }
        if ( $has_tickets )   { $event_data['em_enable_booking'] = 'bookings_on'; }
        $event_id = $ep->insert_event_post_data( $event_data );
    } else {
        $post = array(
            'post_title' => $name,
            'post_content' => $description,
            'post_status' => 'publish',
            'post_type' => 'em_event',
        );
        if ( $venue_id )     $post['em_venue']     = $venue_id;
        if ( $organizer_id ) $post['em_organizer'] = $organizer_id;
        if ( $performer_id ) $post['em_performer'] = $performer_id;
        $event_id = wp_insert_post( $post );
        if ( $event_id && ! is_wp_error( $event_id ) ) {
            if ( ! empty( $final_start_ts ) ) { update_post_meta( $event_id, 'em_start_date', $final_start_ts ); update_post_meta( $event_id, 'em_start_date_time', $final_start_ts ); }
            if ( ! empty( $final_end_ts ) )   { update_post_meta( $event_id, 'em_end_date',   $final_end_ts ); update_post_meta( $event_id, 'em_end_date_time',   $final_end_ts ); }
            if ( $has_tickets )               { update_post_meta( $event_id, 'em_enable_booking', 'bookings_on' ); }
            if ( $venue_id )      update_post_meta( $event_id, 'em_venue', $venue_id );
            if ( $organizer_id )  update_post_meta( $event_id, 'em_organizer', $organizer_id );
            if ( $performer_id )  update_post_meta( $event_id, 'em_performer', $performer_id );
            update_post_meta( $event_id, 'em_add_slug_in_event_title', 0 );
        }
    }

    if ( $event_id && ! is_wp_error( $event_id ) ) {
        $original_name = $name;
        $clean_name = preg_replace( '/[\s_\-]*\d{8,14}$/', '', $original_name );
        $clean_name = trim( $clean_name );
        if ( $clean_name !== $original_name ) { $name = $clean_name; }
        wp_update_post( array( 'ID' => $event_id, 'post_title' => $name ) );
    }

    if ( ! $event_id || is_wp_error( $event_id ) ) {
        return rest_ensure_response( array( 'status' => 'error', 'message' => 'Something went wrong.' ), 500 );
    }

    // Tickets creation (unchanged)
    $created_ticket_ids = array();
    if ( ! empty( $body['tickets'] ) && ! is_array( $body['tickets'] ) ) {
        $normalized = json_decode( wp_json_encode( $body['tickets'] ), true );
        if ( is_array( $normalized ) ) { $body['tickets'] = $normalized; }
    }
    if ( ! empty( $body['tickets'] ) && is_object( $body['tickets'] ) ) {
        $body['tickets'] = json_decode( wp_json_encode( $body['tickets'] ), true );
    }
    $has_tickets = ( ! empty( $body['tickets'] ) && is_array( $body['tickets'] ) && count( $body['tickets'] ) > 0 ) || ! empty( $body['ticket'] );
    if ( $event_id && ! is_wp_error( $event_id ) ) {
        if ( $has_tickets ) update_post_meta( $event_id, 'em_enable_booking', 'bookings_on' );
        if ( ! empty( $body['tickets'] ) && is_array( $body['tickets'] ) ) {
            foreach ( $body['tickets'] as $tdata ) {
                if ( ! is_array( $tdata ) ) continue;
                $ticket_name     = isset( $tdata['name'] ) ? sanitize_text_field( $tdata['name'] ) : ( isset( $tdata['title'] ) ? sanitize_text_field( $tdata['title'] ) : 'General Admission' );
                $ticket_price    = isset( $tdata['price'] ) ? floatval( $tdata['price'] ) : 0;
                $ticket_quantity = isset( $tdata['quantity'] ) ? intval( $tdata['quantity'] ) : ( isset( $tdata['capacity'] ) ? intval( $tdata['capacity'] ) : 10 );

                $tid = null;
                $db_ticket_id = null;

                // Prefer core helper if it implements full creation
                if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
                    $ep = new Eventprime_Basic_Functions();
                    if ( method_exists( $ep, 'ep_create_ticket' ) ) {
                        $tid = $ep->ep_create_ticket( $event_id, array(
                            'name' => $ticket_name, 'price' => $ticket_price, 'capacity' => $ticket_quantity
                        ) );
                    }
                }

                // If helper didn't create ticket, use DB handler then create CPT and meta consistently
                if ( empty( $tid ) ) {
                    $db_ticket_id = null;
                    if ( class_exists( 'EP_DBhandler' ) ) {
                        try {
                            $dbhandler = new EP_DBhandler();
                            $ep_activator = class_exists( 'Eventprime_Event_Calendar_Management_Activator' ) ? new Eventprime_Event_Calendar_Management_Activator() : null;
                            $cat_table_name = 'TICKET_CATEGORIES';
                            $price_options_table = 'TICKET';
                            $cat_id = 0;

                            // create category row when provided (use same shape as admin)
                            if ( ! empty( $tdata['category_name'] ) ) {
                                $cat_data = array(
                                    'event_id'   => $event_id,
                                    'name'       => sanitize_text_field( $tdata['category_name'] ),
                                    'capacity'   => isset( $tdata['category_capacity'] ) ? intval( $tdata['category_capacity'] ) : $ticket_quantity,
                                    'priority'   => 1,
                                    'status'     => 1,
                                    'created_by' => get_current_user_id(),
                                    'created_at' => wp_date( 'Y-m-d H:i:s', time() ),
                                );
                                // Use DB handler helper to persist category rows when available
                                $cat_id = $dbhandler->insert_row( $cat_table_name, $cat_data );
                            }

                            // Build ticket row using DBhandler helpers to match admin behaviour
                            // Map integration keys to the keys expected by EP_DBhandler so
                            // offers and additional fees are persisted in the plugin table.
                            $tdata_map = is_array( $tdata ) ? $tdata : ( is_object( $tdata ) ? (array) $tdata : array() );
                            // Accept singular 'offer' from API but DB expects 'offers' (plural)
                            if ( isset( $tdata_map['offer'] ) && ! isset( $tdata_map['offers'] ) ) {
                                $tdata_map['offers'] = $tdata_map['offer'];
                            }
                            // Ensure offers is an array (DB expects an array of offer objects)
                            if ( isset( $tdata_map['offers'] ) && ! isset( $tdata_map['offers'][0] ) ) {
                                $tdata_map['offers'] = array( $tdata_map['offers'] );
                            }
                            // Normalize each offer element to include expected keys to avoid
                            // undefined-property warnings in admin helpers (they access ->uid etc.).
                            if ( isset( $tdata_map['offers'] ) && is_array( $tdata_map['offers'] ) ) {
                                foreach ( $tdata_map['offers'] as $ofk => $offer_item ) {
                                    if ( is_object( $offer_item ) ) $offer_item = (array) $offer_item;
                                    if ( ! is_array( $offer_item ) ) $offer_item = array();
                                    // Map common integration keys to admin/internal keys so API payloads
                                    // like { type, value, eligible } are converted to the shape
                                    // admin UI and helpers expect (em_ticket_offer_* fields).
                                    if ( isset( $offer_item['type'] ) && ! isset( $offer_item['em_ticket_offer_discount_type'] ) ) {
                                        $offer_item['em_ticket_offer_discount_type'] = sanitize_text_field( $offer_item['type'] );
                                    }
                                    if ( isset( $offer_item['value'] ) && ! isset( $offer_item['em_ticket_offer_discount'] ) ) {
                                        // numeric values should be preserved as-is
                                        $offer_item['em_ticket_offer_discount'] = $offer_item['value'];
                                    }
                                    if ( isset( $offer_item['eligible'] ) && ! isset( $offer_item['em_ticket_offer_user_roles'] ) ) {
                                        // keep as array to match admin multi-select shape
                                        $offer_item['em_ticket_offer_user_roles'] = is_array( $offer_item['eligible'] ) ? $offer_item['eligible'] : array( $offer_item['eligible'] );
                                    }
                                    if ( isset( $offer_item['name'] ) && ! isset( $offer_item['em_ticket_offer_name'] ) ) {
                                        $offer_item['em_ticket_offer_name'] = sanitize_text_field( $offer_item['name'] );
                                    }
                                    if ( isset( $offer_item['description'] ) && ! isset( $offer_item['em_ticket_offer_description'] ) ) {
                                        $offer_item['em_ticket_offer_description'] = sanitize_textarea_field( $offer_item['description'] );
                                    }
                                    $defaults = array(
                                        'uid' => '',
                                        'em_offer_start_booking_type' => '',
                                        'em_offer_start_booking_date' => '',
                                        'em_offer_start_booking_time' => '',
                                        'em_offer_start_booking_days' => '',
                                        'em_offer_start_booking_days_option' => '',
                                        'em_offer_start_booking_event_option' => '',
                                        'em_offer_ends_booking_type' => '',
                                        'em_offer_ends_booking_date' => '',
                                        'em_offer_ends_booking_time' => '',
                                        'em_offer_ends_booking_days' => '',
                                        'em_offer_ends_booking_days_option' => '',
                                        'em_offer_ends_booking_event_option' => '',
                                        'em_ticket_show_offer_detail' => '',
                                    );
                                    foreach ( $defaults as $dk => $dv ) {
                                        if ( ! isset( $offer_item[ $dk ] ) ) $offer_item[ $dk ] = $dv;
                                    }
                                    $tdata_map['offers'][ $ofk ] = $offer_item;
                                }
                                // DB expects offers as a JSON string (admin path stores JSON). Encode here.
                                $tdata_map['offers'] = wp_json_encode( $tdata_map['offers'] );
                            }
                            // DB handler expects ep_additional_ticket_fee_data for additional fees
                            if ( isset( $tdata_map['additional_fees'] ) && ! isset( $tdata_map['ep_additional_ticket_fee_data'] ) ) {
                                $tdata_map['ep_additional_ticket_fee_data'] = $tdata_map['additional_fees'];
                            }
                            // Also keep both keys for compatibility
                            if ( isset( $tdata_map['ep_additional_ticket_fee_data'] ) && ! isset( $tdata_map['additional_fees'] ) ) {
                                $tdata_map['additional_fees'] = $tdata_map['ep_additional_ticket_fee_data'];
                            }
                            // Normalize capacity/quantity: DB uses 'capacity' field for ticket capacity
                            if ( ! isset( $tdata_map['capacity'] ) ) {
                                if ( isset( $tdata_map['quantity'] ) ) {
                                    $tdata_map['capacity'] = intval( $tdata_map['quantity'] );
                                } else {
                                    $tdata_map['capacity'] = $ticket_quantity;
                                }
                            }
                            // Keep 'quantity' key for compatibility if missing
                            if ( ! isset( $tdata_map['quantity'] ) ) {
                                $tdata_map['quantity'] = isset( $tdata_map['capacity'] ) ? intval( $tdata_map['capacity'] ) : $ticket_quantity;
                            }
                            if ( ! empty( $cat_id ) && method_exists( $dbhandler, 'ep_add_tickets_in_category' ) ) {
                                // priority of new category tickets start at 1
                                $ticket_data = $dbhandler->ep_add_tickets_in_category( $cat_id, $event_id, (array) $tdata_map, 1 );
                            } else {
                                $ticket_data = $dbhandler->ep_add_individual_tickets( $event_id, (array) $tdata_map );
                            }

                            // If activator available, build format array expected by insert_row
                            $format = array();
                            if ( $ep_activator ) {
                                foreach ( $ticket_data as $key => $value ) {
                                    $format[] = $ep_activator->get_db_table_field_type( 'TICKET', $key );
                                }
                            }

                            // Insert into plugin table and capture id
                            $db_ticket_id = $dbhandler->insert_row( $price_options_table, $ticket_data, $format );

                            // Fire admin-equivalent action so other extensions can react
                            do_action( 'ep_update_insert_ticket_additional_data', $db_ticket_id, $tdata, $event_id );

                        } catch ( Exception $e ) {
                            // Log and continue; we'll still create the CPT below as fallback
                            error_log( 'EP API: ticket DB insert failed: ' . $e->getMessage() );
                        }
                    }

                    // Create CPT for ticket post (always ensure present)
                    $post = array( 'post_title' => $ticket_name, 'post_status' => 'publish', 'post_type' => 'em_ticket' );
                    $tid = wp_insert_post( $post );
                    if ( $tid && ! is_wp_error( $tid ) ) {
                        update_post_meta( $tid, 'em_event', $event_id );
                        update_post_meta( $tid, 'em_price', $ticket_price );
                        update_post_meta( $tid, 'em_capacity', $ticket_quantity );
                        update_post_meta( $tid, 'em_status', isset( $tdata['status_text'] ) ? sanitize_text_field( $tdata['status_text'] ) : 'active' );
                        if ( ! empty( $db_ticket_id ) ) update_post_meta( $tid, 'em_ticket_db_id', $db_ticket_id );
                    }
                }

                // Finalize CPT meta and collect id
                if ( empty( $tid ) || is_wp_error( $tid ) ) {
                    $post = array( 'post_title' => $ticket_name, 'post_status' => 'publish', 'post_type' => 'em_ticket' );
                    $tid = wp_insert_post( $post );
                }
                if ( $tid && ! is_wp_error( $tid ) ) {
                    $created_ticket_ids[] = $tid;
                    // Persist additional fields on CPT post meta for compatibility
                    if ( isset( $tdata['special_price'] ) ) update_post_meta( $tid, 'em_special_price', $tdata['special_price'] );
                    $meta_additional_fees = null;
                    if ( isset( $tdata['additional_fees'] ) ) {
                        $meta_additional_fees = $tdata['additional_fees'];
                    } elseif ( isset( $tdata_map['ep_additional_ticket_fee_data'] ) ) {
                        $meta_additional_fees = $tdata_map['ep_additional_ticket_fee_data'];
                    }
                    $normalized_meta_fees = $this->ep_normalize_additional_fees( $meta_additional_fees );
                    if ( ! empty( $normalized_meta_fees ) ) {
                        update_post_meta( $tid, 'em_additional_fees', wp_json_encode( $normalized_meta_fees ) );
                    }
                    if ( isset( $tdata['booking_starts'] ) ) update_post_meta( $tid, 'em_booking_starts', is_array( $tdata['booking_starts'] ) ? wp_json_encode( $tdata['booking_starts'] ) : sanitize_text_field( $tdata['booking_starts'] ) );
                    if ( isset( $tdata['booking_ends'] ) ) update_post_meta( $tid, 'em_booking_ends', is_array( $tdata['booking_ends'] ) ? wp_json_encode( $tdata['booking_ends'] ) : sanitize_text_field( $tdata['booking_ends'] ) );
                    if ( isset( $tdata['allow_cancellation'] ) ) update_post_meta( $tid, 'em_allow_cancellation', intval( $tdata['allow_cancellation'] ) );
                    if ( isset( $tdata['min_ticket_no'] ) ) update_post_meta( $tid, 'em_min_ticket_no', intval( $tdata['min_ticket_no'] ) );
                    if ( isset( $tdata['max_ticket_no'] ) ) update_post_meta( $tid, 'em_max_ticket_no', intval( $tdata['max_ticket_no'] ) );
                    if ( isset( $tdata['offer'] ) ) {
                        update_post_meta( $tid, 'em_offer', is_array( $tdata['offer'] ) ? wp_json_encode( $tdata['offer'] ) : sanitize_text_field( $tdata['offer'] ) );
                    } elseif ( isset( $tdata_map ) && isset( $tdata_map['offers'] ) ) {
                        update_post_meta( $tid, 'em_offer', is_array( $tdata_map['offers'] ) ? wp_json_encode( $tdata_map['offers'] ) : sanitize_text_field( $tdata_map['offers'] ) );
                    }
                    if ( isset( $tdata['category_name'] ) ) update_post_meta( $tid, 'em_ticket_category_name', sanitize_text_field( $tdata['category_name'] ) );
                    if ( isset( $tdata['category_capacity'] ) ) update_post_meta( $tid, 'em_ticket_category_capacity', intval( $tdata['category_capacity'] ) );
                    if ( ! empty( $db_ticket_id ) ) update_post_meta( $tid, 'em_ticket_db_id', $db_ticket_id );
                }
            }
        } elseif ( ! empty( $body['ticket'] ) ) {
            $tdata = $body['ticket'];
            $ticket_name     = is_array( $tdata ) && isset( $tdata['name'] ) ? sanitize_text_field( $tdata['name'] ) : ( is_array( $tdata ) && isset( $tdata['title'] ) ? sanitize_text_field( $tdata['title'] ) : 'General Admission' );
            $ticket_price    = is_array( $tdata ) && isset( $tdata['price'] ) ? floatval( $tdata['price'] ) : ( isset( $body['price'] ) ? floatval( $body['price'] ) : 0 );
            $ticket_quantity = is_array( $tdata ) && isset( $tdata['quantity'] ) ? intval( $tdata['quantity'] ) : ( isset( $body['quantity'] ) ? intval( $body['quantity'] ) : ( is_array( $tdata ) && isset( $tdata['capacity'] ) ? intval( $tdata['capacity'] ) : 10 ) );
            $tid = null;
            if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
                $ep = new Eventprime_Basic_Functions();
                if ( method_exists( $ep, 'ep_create_ticket' ) ) {
                    $tid = $ep->ep_create_ticket( $event_id, array( 'name' => $ticket_name, 'price' => $ticket_price, 'capacity' => $ticket_quantity ) );
                }
            }
            if ( empty( $tid ) ) {
                if ( class_exists( 'EP_DBhandler' ) ) {
                    try {
                        $dbhandler = new EP_DBhandler;
                        $cat_table_name = 'TICKET_CATEGORIES';
                        $price_options_table = 'TICKET';
                        $cat_id = 0;
                        if ( ! empty( $tdata['category_name'] ) ) {
                            $cat_data = array(
                                'event_id' => $event_id,
                                'name' => sanitize_text_field( $tdata['category_name'] ),
                                'capacity' => isset( $tdata['category_capacity'] ) ? intval( $tdata['category_capacity'] ) : $ticket_quantity,
                                'priority' => 1,
                                'status' => 1,
                                'created_by' => get_current_user_id(),
                                'created_at' => wp_date( 'Y-m-d H:i:s', time() ),
                            );
                            $cat_id = $dbhandler->insert_row( $cat_table_name, $cat_data );
                        }
                        // Build ticket row using DBhandler helpers to match admin behaviour
                        $ep_activator = class_exists( 'Eventprime_Event_Calendar_Management_Activator' ) ? new Eventprime_Event_Calendar_Management_Activator() : null;
                        // Map incoming integration keys to DB handler expected keys.
                        $tdata_map = is_array( $tdata ) ? $tdata : ( is_object( $tdata ) ? (array) $tdata : array() );
                        if ( isset( $tdata_map['offer'] ) && ! isset( $tdata_map['offers'] ) ) {
                            $tdata_map['offers'] = $tdata_map['offer'];
                        }
                        // Ensure offers is an array
                        if ( isset( $tdata_map['offers'] ) && ! isset( $tdata_map['offers'][0] ) ) {
                            $tdata_map['offers'] = array( $tdata_map['offers'] );
                        }
                        // Normalize offers elements and encode to JSON so admin helpers do not emit notices
                        if ( isset( $tdata_map['offers'] ) && is_array( $tdata_map['offers'] ) ) {
                            foreach ( $tdata_map['offers'] as $ofk => $offer_item ) {
                                if ( is_object( $offer_item ) ) $offer_item = (array) $offer_item;
                                if ( ! is_array( $offer_item ) ) $offer_item = array();
                                // Map common lightweight keys from integration payloads
                                if ( isset( $offer_item['type'] ) && ! isset( $offer_item['em_ticket_offer_discount_type'] ) ) {
                                    $offer_item['em_ticket_offer_discount_type'] = sanitize_text_field( $offer_item['type'] );
                                }
                                if ( isset( $offer_item['value'] ) && ! isset( $offer_item['em_ticket_offer_discount'] ) ) {
                                    $offer_item['em_ticket_offer_discount'] = $offer_item['value'];
                                }
                                if ( isset( $offer_item['eligible'] ) && ! isset( $offer_item['em_ticket_offer_user_roles'] ) ) {
                                    $offer_item['em_ticket_offer_user_roles'] = is_array( $offer_item['eligible'] ) ? $offer_item['eligible'] : array( $offer_item['eligible'] );
                                }
                                if ( isset( $offer_item['name'] ) && ! isset( $offer_item['em_ticket_offer_name'] ) ) {
                                    $offer_item['em_ticket_offer_name'] = sanitize_text_field( $offer_item['name'] );
                                }
                                if ( isset( $offer_item['description'] ) && ! isset( $offer_item['em_ticket_offer_description'] ) ) {
                                    $offer_item['em_ticket_offer_description'] = sanitize_textarea_field( $offer_item['description'] );
                                }
                                $defaults = array(
                                    'uid' => '',
                                    'em_offer_start_booking_type' => '',
                                    'em_offer_start_booking_date' => '',
                                    'em_offer_start_booking_time' => '',
                                    'em_offer_start_booking_days' => '',
                                    'em_offer_start_booking_days_option' => '',
                                    'em_offer_start_booking_event_option' => '',
                                    'em_offer_ends_booking_type' => '',
                                    'em_offer_ends_booking_date' => '',
                                    'em_offer_ends_booking_time' => '',
                                    'em_offer_ends_booking_days' => '',
                                    'em_offer_ends_booking_days_option' => '',
                                    'em_offer_ends_booking_event_option' => '',
                                    'em_ticket_show_offer_detail' => '',
                                );
                                foreach ( $defaults as $dk => $dv ) {
                                    if ( ! isset( $offer_item[ $dk ] ) ) $offer_item[ $dk ] = $dv;
                                }
                                $tdata_map['offers'][ $ofk ] = $offer_item;
                            }
                            $tdata_map['offers'] = wp_json_encode( $tdata_map['offers'] );
                        }
                            $raw_additional_fees = null;
                            if ( isset( $tdata_map['additional_fees'] ) ) {
                                $raw_additional_fees = $tdata_map['additional_fees'];
                            } elseif ( isset( $tdata_map['ep_additional_ticket_fee_data'] ) ) {
                                $raw_additional_fees = $tdata_map['ep_additional_ticket_fee_data'];
                            }
                            $normalized_additional_fees = $this->ep_normalize_additional_fees( $raw_additional_fees );
                            if ( ! empty( $normalized_additional_fees ) ) {
                                $tdata_map['ep_additional_ticket_fee_data'] = $normalized_additional_fees;
                                $tdata_map['additional_fees'] = $normalized_additional_fees;
                            } else {
                                unset( $tdata_map['ep_additional_ticket_fee_data'], $tdata_map['additional_fees'] );
                            }
                        // Normalize capacity/quantity for DB handler
                        if ( ! isset( $tdata_map['capacity'] ) ) {
                            if ( isset( $tdata_map['quantity'] ) ) {
                                $tdata_map['capacity'] = intval( $tdata_map['quantity'] );
                            } else {
                                $tdata_map['capacity'] = $ticket_quantity;
                            }
                        }
                        if ( ! isset( $tdata_map['quantity'] ) ) {
                            $tdata_map['quantity'] = isset( $tdata_map['capacity'] ) ? intval( $tdata_map['capacity'] ) : $ticket_quantity;
                        }
                        if ( ! empty( $cat_id ) && method_exists( $dbhandler, 'ep_add_tickets_in_category' ) ) {
                            $ticket_data = $dbhandler->ep_add_tickets_in_category( $cat_id, $event_id, (array) $tdata_map, 1 );
                        } else {
                            $ticket_data = $dbhandler->ep_add_individual_tickets( $event_id, (array) $tdata_map );
                        }

                        $format = array();
                        if ( $ep_activator ) {
                            foreach ( $ticket_data as $key => $value ) {
                                $format[] = $ep_activator->get_db_table_field_type( 'TICKET', $key );
                            }
                        }

                        $db_ticket_id = $dbhandler->insert_row( $price_options_table, $ticket_data, $format );
                        do_action( 'ep_update_insert_ticket_additional_data', $db_ticket_id, $tdata, $event_id );

                        $post = array( 'post_title' => $ticket_name, 'post_type' => 'em_ticket', 'post_status' => 'publish' );
                        $tid = wp_insert_post( $post );
                        if ( $tid && ! is_wp_error( $tid ) ) {
                            update_post_meta( $tid, 'em_event', $event_id );
                            update_post_meta( $tid, 'em_price', $ticket_price );
                            update_post_meta( $tid, 'em_capacity', $ticket_quantity );
                            update_post_meta( $tid, 'em_status', isset( $tdata['status_text'] ) ? sanitize_text_field( $tdata['status_text'] ) : 'active' );
                            if ( ! empty( $db_ticket_id ) ) update_post_meta( $tid, 'em_ticket_db_id', $db_ticket_id );
                            if ( isset( $tdata['special_price'] ) ) update_post_meta( $tid, 'em_special_price', $tdata['special_price'] );
                            $meta_additional_fees = null;
                            if ( isset( $tdata['additional_fees'] ) ) {
                                $meta_additional_fees = $tdata['additional_fees'];
                            } elseif ( isset( $tdata_map['ep_additional_ticket_fee_data'] ) ) {
                                $meta_additional_fees = $tdata_map['ep_additional_ticket_fee_data'];
                            }
                            $normalized_meta_fees = $this->ep_normalize_additional_fees( $meta_additional_fees );
                            if ( ! empty( $normalized_meta_fees ) ) {
                                update_post_meta( $tid, 'em_additional_fees', wp_json_encode( $normalized_meta_fees ) );
                            }
                            if ( isset( $tdata['booking_starts'] ) ) update_post_meta( $tid, 'em_booking_starts', is_array( $tdata['booking_starts'] ) ? wp_json_encode( $tdata['booking_starts'] ) : sanitize_text_field( $tdata['booking_starts'] ) );
                            if ( isset( $tdata['booking_ends'] ) ) update_post_meta( $tid, 'em_booking_ends', is_array( $tdata['booking_ends'] ) ? wp_json_encode( $tdata['booking_ends'] ) : sanitize_text_field( $tdata['booking_ends'] ) );
                            if ( isset( $tdata['allow_cancellation'] ) ) update_post_meta( $tid, 'em_allow_cancellation', intval( $tdata['allow_cancellation'] ) );
                            if ( isset( $tdata['min_ticket_no'] ) ) update_post_meta( $tid, 'em_min_ticket_no', intval( $tdata['min_ticket_no'] ) );
                            if ( isset( $tdata['max_ticket_no'] ) ) update_post_meta( $tid, 'em_max_ticket_no', intval( $tdata['max_ticket_no'] ) );
                            if ( isset( $tdata['offer'] ) ) {
                                update_post_meta( $tid, 'em_offer', is_array( $tdata['offer'] ) ? wp_json_encode( $tdata['offer'] ) : sanitize_text_field( $tdata['offer'] ) );
                            } elseif ( isset( $tdata_map ) && isset( $tdata_map['offers'] ) ) {
                                update_post_meta( $tid, 'em_offer', is_array( $tdata_map['offers'] ) ? wp_json_encode( $tdata_map['offers'] ) : sanitize_text_field( $tdata_map['offers'] ) );
                            }
                            if ( isset( $tdata['category_name'] ) ) update_post_meta( $tid, 'em_ticket_category_name', sanitize_text_field( $tdata['category_name'] ) );
                            if ( isset( $tdata['category_capacity'] ) ) update_post_meta( $tid, 'em_ticket_category_capacity', intval( $tdata['category_capacity'] ) );
                        }
                    } catch ( Exception $e ) {
                        $post = array( 'post_title' => $ticket_name, 'post_type' => 'em_ticket', 'post_status' => 'publish' );
                        $tid = wp_insert_post( $post );
                        if ( $tid && ! is_wp_error( $tid ) ) {
                            update_post_meta( $tid, 'em_event', $event_id );
                            update_post_meta( $tid, 'em_price', $ticket_price );
                            update_post_meta( $tid, 'em_capacity', $ticket_quantity );
                            update_post_meta( $tid, 'em_status', 'active' );
                        }
                    }
                } else {
                    $post = array( 'post_title' => $ticket_name, 'post_type' => 'em_ticket', 'post_status' => 'publish' );
                    $tid = wp_insert_post( $post );
                    if ( $tid && ! is_wp_error( $tid ) ) {
                        update_post_meta( $tid, 'em_event', $event_id );
                        update_post_meta( $tid, 'em_price', $ticket_price );
                        update_post_meta( $tid, 'em_capacity', $ticket_quantity );
                        update_post_meta( $tid, 'em_status', 'active' );
                    }
                }
            }
            if ( $tid && ! is_wp_error( $tid ) ) { $created_ticket_ids[] = $tid; }
        }
    }

    // Re-enforce meta/title + *** CORRECT DATETIME PERSISTENCE ***
    if ( $event_id && ! is_wp_error( $event_id ) ) {
        update_post_meta( $event_id, 'em_add_slug_in_event_title', 0 );
        wp_update_post( array( 'ID' => $event_id, 'post_title' => $name ) );

        if ( ! empty( $final_start_ts ) && ! empty( $final_end_ts ) ) {
            $ep    = class_exists('Eventprime_Basic_Functions') ? new Eventprime_Basic_Functions() : null;
            $wp_tz = wp_timezone();

            // Build strings in site timezone
            $start_local = wp_date('Y-m-d H:i:s', $final_start_ts, $wp_tz);
            $end_local   = wp_date('Y-m-d H:i:s', $final_end_ts,   $wp_tz);

            // Split into date-only and 12h strings
            $start_date_str = wp_date('Y-m-d', $final_start_ts, $wp_tz);
            $end_date_str   = wp_date('Y-m-d', $final_end_ts,   $wp_tz);
            $start_time_12h = wp_date('g:i A', $final_start_ts, $wp_tz);
            $end_time_12h   = wp_date('g:i A', $final_end_ts,   $wp_tz);

            // Timestamps the way EP expects
            if ( $ep && method_exists( $ep, 'ep_date_to_timestamp' ) && method_exists( $ep, 'ep_datetime_to_timestamp' ) ) {
                $start_date_ts = (int) $ep->ep_date_to_timestamp( $start_date_str, 'Y-m-d', 1 );
                $end_date_ts   = (int) $ep->ep_date_to_timestamp( $end_date_str, 'Y-m-d', 1 );

                $start_dt_ts = (int) $ep->ep_datetime_to_timestamp( $start_date_str . ' ' . $start_time_12h, 'Y-m-d', '', 0, 1 );
                $end_dt_ts   = (int) $ep->ep_datetime_to_timestamp( $end_date_str   . ' ' . $end_time_12h,   'Y-m-d', '', 0, 1 );
            } else {
                // Safe fallback
                $start_date_ts = strtotime( $start_date_str . ' 00:00:00' );
                $end_date_ts   = strtotime( $end_date_str   . ' 00:00:00' );
                $start_dt_ts   = strtotime( $start_local );
                $end_dt_ts     = strtotime( $end_local );
            }

            // Save meta in EP's expected shape
            update_post_meta( $event_id, 'em_start_date',      $start_date_ts );
            update_post_meta( $event_id, 'em_end_date',        $end_date_ts );
            update_post_meta( $event_id, 'em_start_time',      $start_time_12h );
            update_post_meta( $event_id, 'em_end_time',        $end_time_12h );
            update_post_meta( $event_id, 'em_start_date_time', $start_dt_ts );
            update_post_meta( $event_id, 'em_end_date_time',   $end_dt_ts );
            update_post_meta( $event_id, 'em_timezone',        wp_timezone_string() );

            // Also update EP events table so the admin UI picks them immediately
            if ( class_exists('EP_DBhandler') ) {
                try {
                    global $wpdb;
                    $table = $wpdb->prefix . 'em_events';
                    if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
                        $row = array(
                            'start_date'      => $start_date_str,                      // Y-m-d
                            'end_date'        => $end_date_str,                        // Y-m-d
                            'start_time'      => wp_date('H:i:s', $final_start_ts, $wp_tz),
                            'end_time'        => wp_date('H:i:s', $final_end_ts,   $wp_tz),
                            'start_date_time' => $start_local,                         // Y-m-d H:i:s
                            'end_date_time'   => $end_local,                           // Y-m-d H:i:s
                            'last_updated'    => current_time('mysql'),
                        );
                        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE post_id = %d", $event_id ) );
                        if ( $existing ) {
                            $wpdb->update( $table, $row, array('post_id' => $event_id), array('%s','%s','%s','%s','%s','%s','%s'), array('%d') );
                        } else {
                            $row['post_id'] = $event_id;
                            $wpdb->insert( $table, $row );
                        }
                        // timezone column if present
                        $tz_col = $wpdb->get_var( "SHOW COLUMNS FROM {$table} LIKE 'timezone'" );
                        if ( $tz_col ) {
                            $wpdb->update( $table, array('timezone' => wp_timezone_string()), array('post_id' => $event_id) );
                        }
                    }
                } catch ( Exception $e ) { /* ignore */ }
            }
        }

        if ( $has_tickets ) update_post_meta( $event_id, 'em_enable_booking', 'bookings_on' );

        // Taxonomy/meta attachments (unchanged)
        if ( ! empty( $event_type_ids ) ) {
            $et_ids = array_map( 'absint', array_values( array_unique( $event_type_ids ) ) );
            wp_set_object_terms( $event_id, $et_ids, 'em_event_type', false );
            update_post_meta( $event_id, 'em_event_type', $et_ids );
        } elseif ( ! empty( $body['em_event_type'] ) && is_numeric( $body['em_event_type'] ) ) {
            update_post_meta( $event_id, 'em_event_type', absint( $body['em_event_type'] ) );
        }
        if ( ! empty( $performer_ids ) ) {
            update_post_meta( $event_id, 'em_performer', $performer_ids );
            update_post_meta( $event_id, 'em_performer_id', $performer_id );
        }
        if ( ! empty( $organizer_ids ) ) {
            update_post_meta( $event_id, 'em_organizer', $organizer_ids );
            if ( $organizer_id ) update_post_meta( $event_id, 'em_organizer_id', $organizer_id );
            if ( $organizer_id ) wp_set_object_terms( $event_id, array( $organizer_id ), 'em_event_organizer', false );
        }
        if ( ! empty( $venue_ids ) ) {
            update_post_meta( $event_id, 'em_venue', $venue_ids );
            if ( $venue_id ) update_post_meta( $event_id, 'em_venue_id', $venue_id );
            if ( $venue_id ) wp_set_object_terms( $event_id, array( $venue_id ), 'em_venue', false );
        }
    }

    do_action( 'ep_api_event_created', $event_id, array( 'em_name' => $name ) );

    $event_out = array(
        'id'           => $event_id,
        'name'         => $name,
        'description'  => $description,
        'start_date'   => $this->convert_to_iso( ! empty( $final_start_ts ) ? $final_start_ts : ( ! empty( $start ) ? $start : '' ) ),
        'end_date'     => $this->convert_to_iso( ! empty( $final_end_ts ) ? $final_end_ts : ( ! empty( $end ) ? $end : '' ) ),
        'venue_id'     => $venue_id,
        'organizer_id' => $organizer_id,
        'performer_id' => $performer_id,
        'tickets'      => $created_ticket_ids,
    );

    return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'event' => $event_out ) );
}


    public function handle_event_tickets( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        // Provide tickets for the event. Prefer core helper if available, otherwise simple fallback.
        $tickets = array();
        if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
            $ep = new Eventprime_Basic_Functions();
            if ( method_exists( $ep, 'ep_get_tickets_by_event' ) ) {
                $tickets = $ep->ep_get_tickets_by_event( array( 'event_id' => $id ) );
                // If helper returned an error payload, translate to REST 404
                if ( is_array( $tickets ) && isset( $tickets['status'] ) && $tickets['status'] === 'error' && isset( $tickets['message'] ) && stripos( $tickets['message'], 'event not found' ) !== false ) {
                    return new WP_Error( 'not_found', $tickets['message'], array( 'status' => 404 ) );
                }
            }
        }
        // fallback: simple posts lookup
        if ( empty( $tickets ) ) {
            $q = get_posts( array( 'post_type' => 'em_ticket', 'posts_per_page' => 50, 'meta_query' => array( array( 'key' => 'em_event', 'value' => $id ) ) ) );
            foreach ( $q as $t ) {
                $tickets[] = array( 'id' => $t->ID, 'name' => sanitize_text_field( $t->post_title ), 'price' => get_post_meta( $t->ID, 'em_price', true ), 'event_id' => get_post_meta( $t->ID, 'em_event', true ) );
            }
        }
        return rest_ensure_response( array( 'status' => 'success', 'count' => count( $tickets ), 'tickets' => $tickets ) );
    }

    public function handle_venues_list( WP_REST_Request $request ) {
        $params = $request->get_query_params();
        $collection = $this->ep_fetch_venues_collection( $params );
        return rest_ensure_response(
            array(
                'status' => 'success',
                'count'  => (int) $collection['total'],
                'venues' => $collection['items'],
            )
        );
    }

    public function handle_organizers_list( WP_REST_Request $request ) {
        $params = $request->get_query_params();
        $collection = $this->ep_fetch_organizers_collection( $params );
        return rest_ensure_response(
            array(
                'status'     => 'success',
                'count'      => (int) $collection['total'],
                'organizers' => $collection['items'],
            )
        );
    }

    public function handle_performers_list( WP_REST_Request $request ) {
        $params = $request->get_query_params();
        $collection = $this->ep_fetch_performers_collection( $params );
        return rest_ensure_response(
            array(
                'status'     => 'success',
                'count'      => (int) $collection['total'],
                'performers' => $collection['items'],
            )
        );
    }

    public function handle_bookings_list( WP_REST_Request $request ) {
        $params = $request->get_query_params();
        $status = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : '';
        $event_id = isset( $params['event_id'] ) ? absint( $params['event_id'] ) : 0;
        // Prefer integration helper if available (handles custom DB storage and event-scoped queries)
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->all_bookings_data( $status, $event_id );
            if ( is_array( $res ) && isset( $res['status'] ) && $res['status'] === 'success' ) {
                // return helper's normalized payload directly
                return rest_ensure_response( $res );
            }
        }

        // Prefer core helper if available (legacy path)
        $bookings = array();
        if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
            $ep = new Eventprime_Basic_Functions();
            if ( method_exists( $ep, 'ep_get_all_bookings' ) ) {
                $bookings = $ep->ep_get_all_bookings( $status );
                if ( is_array( $bookings ) ) {
                    return rest_ensure_response( array( 'status' => 'success', 'count' => count( $bookings ), 'bookings' => $bookings ) );
                }
            }
        }
        // fallback: simple get_posts (return all bookings when helper not available)
        $args = array( 'post_type' => 'em_booking', 'posts_per_page' => -1 );
        if ( $status ) {
            $args['meta_query'] = array( array( 'key' => 'em_status', 'value' => $status ) );
        }
        // If caller filtered by event_id, include it in the meta query
        if ( $event_id ) {
            $args['meta_query'][] = array( 'key' => 'em_event', 'value' => $event_id );
        }
        $posts = get_posts( $args );
        $bookings = array();
        foreach ( $posts as $p ) {
            $bookings[] = array( 'id' => $p->ID, 'status' => get_post_meta( $p->ID, 'em_status', true ), 'event_id' => get_post_meta( $p->ID, 'em_event', true ) );
        }
        return rest_ensure_response( array( 'status' => 'success', 'count' => count( $bookings ), 'bookings' => $bookings ) );
    }

    public function handle_booking_create( WP_REST_Request $request ) {
        $body = $request->get_body_params();
        $event_id = isset( $body['event_id'] ) ? absint( $body['event_id'] ) : 0;
        $ticket_id = isset( $body['ticket_id'] ) ? absint( $body['ticket_id'] ) : 0;
        $email = isset( $body['email'] ) ? sanitize_email( $body['email'] ) : '';
        $booking_status = isset( $body['booking_status'] ) ? sanitize_text_field( $body['booking_status'] ) : 'completed';

        if ( empty( $event_id ) || empty( $ticket_id ) || empty( $email ) || ! is_email( $email ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing required booking data.' ), 400 );
        }

        // validate ticket via Eventprime_Basic_Functions if available
        $ticket_ok = true;
        if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
            $ep = new Eventprime_Basic_Functions();
            $td = $ep->ep_get_ticket_data( $ticket_id );
            if ( empty( $td ) ) {
                $ticket_ok = false;
            }
        }
        if ( ! $ticket_ok ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Invalid ticket.' ), 400 );
        }

        // find or create user
        $user = get_user_by( 'email', $email );
        if ( empty( $user ) ) {
            $username = sanitize_user( current( explode( '@', $email ) ) );
            $random_password = wp_generate_password( 12, true );
            $user_id = wp_create_user( $username, $random_password, $email );
        } else {
            $user_id = $user->ID;
        }

        // create booking post
        $post = array( 'post_title' => get_the_title( $event_id ), 'post_status' => $booking_status, 'post_type' => 'em_booking', 'post_author' => $user_id );
        $new_post_id = wp_insert_post( $post );
        if ( is_wp_error( $new_post_id ) || ! $new_post_id ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Something went wrong.' ), 500 );
        }
        update_post_meta( $new_post_id, 'em_id', $new_post_id );
        update_post_meta( $new_post_id, 'em_event', $event_id );
        update_post_meta( $new_post_id, 'em_date', current_time( 'timestamp', true ) );
        update_post_meta( $new_post_id, 'em_user', $user_id );
        update_post_meta( $new_post_id, 'em_name', get_the_title( $event_id ) );
        update_post_meta( $new_post_id, 'em_status', $booking_status );
        update_post_meta( $new_post_id, 'em_payment_method', 'none' );

        do_action( 'ep_api_booking_created', $new_post_id, array( 'event_id' => $event_id ) );

        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'booking' => array( 'id' => $new_post_id, 'event_id' => $event_id ) ) );
    }

    public function handle_perform( WP_REST_Request $request ) {
        $trigger = $request->get_param( 'trigger' );
        if ( empty( $trigger ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'No trigger specified.' ), 400 );
        }
        if ( ! in_array( $trigger, $this->supported_triggers ) ) {
            return new WP_Error( 'unknown_trigger', 'Unknown request parameters', array( 'status' => 404 ) );
        }

        switch ( $trigger ) {
            case 'create_event': case 'update_event': case 'delete_event': case 'all_events':
                $req = new WP_REST_Request();
                $req->set_param( 'per_page', 5 );
                $res = $this->handle_events_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            case 'create_venue': case 'update_venue': case 'delete_venue': case 'all_venues':
                $req = new WP_REST_Request();
                $req->set_param( 'per_page', 10 );
                $res = $this->handle_venues_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            case 'create_performer': case 'update_performer': case 'delete_performer': case 'all_performers':
                $req = new WP_REST_Request();
                $req->set_param( 'per_page', 5 );
                $res = $this->handle_performers_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            case 'confirm_booking':
                $req = new WP_REST_Request();
                $req->set_param( 'status', 'completed' );
                $res = $this->handle_bookings_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            case 'pending_booking':
                $req = new WP_REST_Request();
                $req->set_param( 'status', 'pending' );
                $res = $this->handle_bookings_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            case 'cancel_booking':
                $req = new WP_REST_Request();
                $req->set_param( 'status', 'cancelled' );
                $res = $this->handle_bookings_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            case 'refund_booking':
                $req = new WP_REST_Request();
                $req->set_param( 'status', 'refunded' );
                $res = $this->handle_bookings_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            case 'failed_booking':
                $req = new WP_REST_Request();
                $req->set_param( 'status', 'failed' );
                $res = $this->handle_bookings_list( $req );
                $data = ( is_object( $res ) && method_exists( $res, 'get_data' ) ) ? $res->get_data() : $res;
                break;
            default:
                $data = array( 'status' => 'error', 'message' => 'No such triggers available.' );
                break;
        }

        $data = apply_filters( 'ep_api_trigger_sample', $data, $trigger );

        if ( empty( $data ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'No such triggers available...' ), 404 );
        }
        return rest_ensure_response( $data );
    }

    public function handle_subscribe( WP_REST_Request $request ) {
        $body = $request->get_body_params();
    // Accept a generic subscriberId (preferred).
    $subscriberId = isset( $body['subscriberId'] ) ? sanitize_text_field( $body['subscriberId'] ) : '';
        $trigger = isset( $body['trigger'] ) ? sanitize_text_field( $body['trigger'] ) : '';
        $hookUrl = isset( $body['hookUrl'] ) ? esc_url_raw( $body['hookUrl'] ) : '';

        if ( empty( $subscriberId ) || empty( $trigger ) || empty( $hookUrl ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing subscription data.' ), 400 );
        }
        if ( ! in_array( $trigger, $this->supported_triggers ) ) {
            return new WP_Error( 'unknown_trigger', 'Unknown request parameters', array( 'status' => 404 ) );
        }

    $db = new Eventprime_Api_Db();
    $id = $db->store_webhook( $subscriberId, $trigger, $hookUrl );

    // Send a test payload (best-effort)
    $sample = $this->generate_sample_for_trigger( $trigger );
    $this->send_webhook( $hookUrl, $sample );

    $resp = array( 'status' => 'success', 'count' => 1, 'subscription' => array( 'id' => $id, 'subscriberId' => $subscriberId, 'type' => $trigger, 'webhook_url' => $hookUrl ) );
    return rest_ensure_response( $resp );
    }

    public function handle_unsubscribe( WP_REST_Request $request ) {
        $body = $request->get_body_params();
    // Accept subscriberId (preferred)
    $subscriberId = isset( $body['subscriberId'] ) ? sanitize_text_field( $body['subscriberId'] ) : '';
        if ( empty( $subscriberId ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing subscriberId.' ), 400 );
        }
    $db = new Eventprime_Api_Db();
    $db->delete_webhook_by_subscriber( $subscriberId );
    $resp = array( 'status' => 'success', 'count' => 1, 'unsubscribed' => array( 'subscriberId' => $subscriberId, 'trigger' => isset( $body['trigger'] ) ? sanitize_text_field( $body['trigger'] ) : '', 'webhook_url' => isset( $body['hookUrl'] ) ? esc_url_raw( $body['hookUrl'] ) : '' ) );
    return rest_ensure_response( $resp );
    }

    public function handle_webhook_test( WP_REST_Request $request ) {
        $body = $request->get_body_params();
        $id = isset( $body['id'] ) ? absint( $body['id'] ) : 0;
    // Accept subscriberId (preferred)
    $subscriberId = isset( $body['subscriberId'] ) ? sanitize_text_field( $body['subscriberId'] ) : '';
        $trigger = isset( $body['trigger'] ) ? sanitize_text_field( $body['trigger'] ) : '';
        $hookUrl = isset( $body['hookUrl'] ) ? esc_url_raw( $body['hookUrl'] ) : '';

        $db = new Eventprime_Api_Db();
        $row = null;
        if ( $id ) {
            $row = $db->get_webhook_by_id( $id );
        } elseif ( $subscriberId ) {
            // find matching by subscriber_id
            $all = $db->get_all_webhooks();
            foreach ( $all as $r ) {
                if ( isset( $r->subscriber_id ) && $r->subscriber_id === $subscriberId ) { $row = $r; break; }
            }
        }
        if ( empty( $row ) && $hookUrl ) {
            // use provided
            $payload = array( 'status' => 'success', 'message' => 'test' );
            $this->send_webhook( $hookUrl, $payload );
            return rest_ensure_response( array( 'status' => 'success', 'sent' => true ) );
        }
        if ( empty( $row ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Webhook not found.' ), 404 );
        }
        $sample = $this->generate_sample_for_trigger( $row->trigger_key );
        $this->send_webhook( $row->webhook_url, $sample );
        return rest_ensure_response( array( 'status' => 'success', 'sent' => true ) );
    }

    /**
     * Expects query params or JSON body with at least `action` or `trigger`.
     * action values: subscribe, unsubscribe, perform, init_action, create_event, create_booking, etc.
     */
    public function handle_integration( WP_REST_Request $request ) {
        $params = array_merge( $request->get_query_params(), $request->get_body_params() );

        $action = isset( $params['action'] ) ? sanitize_text_field( $params['action'] ) : '';
        $trigger = isset( $params['trigger'] ) ? sanitize_text_field( $params['trigger'] ) : '';

        // Fallback: if parsed params are missing (some clients), try raw JSON body and merge it
        $raw = $request->get_body();
        if ( ! empty( $raw ) ) {
            $decoded_raw = json_decode( $raw, true );
            if ( is_array( $decoded_raw ) ) {
                // Merge raw JSON into params so handlers see all fields; JSON body should take precedence
                $params = array_merge( $params, $decoded_raw );
                if ( empty( $action ) && isset( $decoded_raw['action'] ) ) {
                    $action = sanitize_text_field( $decoded_raw['action'] );
                }
                if ( empty( $trigger ) && isset( $decoded_raw['trigger'] ) ) {
                    $trigger = sanitize_text_field( $decoded_raw['trigger'] );
                }
            }
        }
        $method = strtoupper( $request->get_method() );

        // Support legacy "get_" aliases so callers can use action=get_events, get_event, etc.
        if ( $action === 'get_events' ) {
            $action = 'events';
        } elseif ( $action === 'get_tickets' ) {
            $action = 'tickets';
        } elseif ( $action === 'get_bookings' ) {
            $action = 'bookings';
        } elseif ( $action === 'get_performers' ) {
            $action = 'performers';
        } elseif ( $action === 'get_venues' ) {
            $action = 'venues';
        } elseif ( $action === 'event_types' ) {
            $action = 'get_event_type';
        } elseif ( $action === 'get_event_type' || $action === 'get_event_types' ) {
            $action = 'get_event_type';
        } elseif ( $action === 'get_organizers' ) {
            $action = 'organizers';
        } elseif ( $action === 'get_event' ) {
            // single event fetch
            return call_user_func_array( array( $this, 'handle_event_get' ), array( $request ) );
        } elseif ( $action === 'get_ticket' ) {
            return call_user_func_array( array( $this, 'handle_ticket_get' ), array( $request ) );
        } elseif ( $action === 'get_booking' ) {
            $check = $this->ep_require_token_or_401( $request );
            if ( $check !== true ) {
                if ( $check instanceof WP_REST_Response ) {
                    $data    = $check->get_data();
                    $status  = $check->get_status();
                    $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                    return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
                }
                return $check;
            }
            return call_user_func_array( array( $this, 'handle_booking_get' ), array( $request ) );
        } elseif ( $action === 'get_performer' ) {
            return call_user_func_array( array( $this, 'handle_performer_get' ), array( $request ) );
        } elseif ( $action === 'get_venue' ) {
            return call_user_func_array( array( $this, 'handle_venues_list' ), array( $request ) );
        } elseif ( $action === 'get_organizer' ) {
            return call_user_func_array( array( $this, 'get_organizers' ), array( $request ) );
        }

        // Enforce token requirement for all integration actions except token issuance
        // Allow anonymous GETs for safe, read-only integration actions so callers
        // using the /integration?action=... shortcut can fetch public lists without a token.
        if ( $action !== 'get_access_token' ) {
            $public_read_actions = array( 'events', 'performers', 'organizers', 'venues', 'tickets', 'bookings', 'event_types', 'get_events', 'get_tickets', 'get_bookings', 'get_performers', 'get_venues', 'get_organizers', 'get_event_type' );
            if ( strtoupper( $request->get_method() ) === 'GET' && in_array( $action, $public_read_actions, true ) ) {
                // allow anonymous read
            } else {
                $check = $this->ep_require_token_or_401( $request );
                if ( $check !== true ) {
                    if ( $check instanceof WP_REST_Response ) {
                        $data    = $check->get_data();
                        $status  = $check->get_status();
                        $message = isset( $data['message'] ) ? $data['message'] : esc_html__( 'Invalid or missing access token.', 'eventprime-event-calendar-management' );
                        return new WP_Error( 'rest_forbidden', $message, array( 'status' => $status ? $status : 401 ) );
                    }
                    return $check;
                }
            }
        }

        // Resource-style shortcuts (action='events','performers','organizers','venues','tickets','bookings')
    if ( in_array( $action, array( 'events', 'performers', 'organizers', 'venues', 'tickets', 'bookings', 'event_types', 'get_event_type' ), true ) ) {
            switch ( $action ) {
                case 'events':
                    // Return the same REST list payload as handle_events_list() so the
                    // integration endpoint returns { status, count, events } with full
                    // formatted event objects (matches your GET sample).
                    $req = new WP_REST_Request();
                    // forward common list params
                    $forward = array( 'page', 'per_page', 'per-page', 'per_page', 'per-page', 'per-page', 'per-page', 'per_page' );
                    if ( isset( $params['page'] ) ) $req->set_param( 'page', absint( $params['page'] ) );
                    if ( isset( $params['per_page'] ) ) $req->set_param( 'per_page', absint( $params['per_page'] ) );
                    if ( isset( $params['per-page'] ) ) $req->set_param( 'per_page', absint( $params['per-page'] ) );
                    if ( isset( $params['status'] ) ) $req->set_param( 'status', sanitize_text_field( $params['status'] ) );
                    if ( isset( $params['search'] ) ) $req->set_param( 'search', sanitize_text_field( $params['search'] ) );
                    if ( isset( $params['after'] ) ) $req->set_param( 'after', sanitize_text_field( $params['after'] ) );
                    if ( isset( $params['before'] ) ) $req->set_param( 'before', sanitize_text_field( $params['before'] ) );

                    $res = $this->handle_events_list( $req );
                    // If the handler returned a WP_REST_Response, return it directly
                    if ( is_object( $res ) && method_exists( $res, 'get_data' ) ) {
                        return $res;
                    }
                    return rest_ensure_response( $res );
                case 'performers':
                    return rest_ensure_response( $this->integration_all_posts( 'em_performer' ) );
                case 'organizers':
                    return rest_ensure_response( $this->integration_all_terms( 'em_event_organizer', $params ) );
                case 'venues':
                    return rest_ensure_response( $this->integration_all_terms( 'em_venue', $params ) );
                case 'event_types':
                    return rest_ensure_response( $this->integration_all_terms( 'em_event_type', $params ) );
                case 'get_event_type':
                    return rest_ensure_response( $this->integration_all_terms( 'em_event_type', $params ) );
                case 'tickets':
                    $res = $this->integration_get_tickets_by_event( $params );
                    // If helper returned an error payload for missing event, convert to WP_Error (404)
                    if ( is_array( $res ) && isset( $res['status'] ) && $res['status'] === 'error' && isset( $res['message'] ) && stripos( $res['message'], 'event not found' ) !== false ) {
                        return new WP_Error( 'not_found', $res['message'], array( 'status' => 404 ) );
                    }
                    return rest_ensure_response( $res );
                case 'bookings':
                    $status = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'completed';
                    $event_id = isset( $params['event_id'] ) ? absint( $params['event_id'] ) : 0;
                    return rest_ensure_response( $this->integration_all_bookings_by_status( $status, $event_id ) );
            }
        }

        switch ( $action ) {
            case 'subscribe':
                // reuse existing subscribe handler if available
                return call_user_func_array( array( $this, 'handle_subscribe' ), array( $request ) );

            case 'unsubscribe':
                return call_user_func_array( array( $this, 'handle_unsubscribe' ), array( $request ) );

            case 'perform':
                return $this->handle_perform( $request );

            case 'init_action':
                $key = isset( $params['action_key'] ) ? sanitize_text_field( $params['action_key'] ) : '';
                return rest_ensure_response( apply_filters( 'eventprime_zapier_api_action_payload', false, $key ) );

            case 'create_event':
                // If caller is performing a mutating request, call the real create handler.
                if ( $method === 'POST' ) {
                    // Ensure merged params are available to the create handler
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_event_create' ), array( $request ) );
                }
                // Otherwise return sample data for init flows (GET)
                return rest_ensure_response( $this->integration_get_event_sample( $action ) );

            case 'update_event':
                if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_event_update' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_get_event_sample( $action ) );

            case 'delete_event':
                if ( in_array( $method, array( 'POST', 'DELETE' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_event_delete' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_get_event_sample( $action ) );

            case 'create_booking':
                if ( $method === 'POST' ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_booking_create' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_get_booking_sample( $params ) );

            case 'update_booking':
                if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_booking_update' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_get_booking_sample( $params ) );

            case 'delete_booking':
                if ( in_array( $method, array( 'POST', 'DELETE' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_booking_delete' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_get_booking_sample( $params ) );

            case 'create_ticket':
                if ( $method === 'POST' ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_ticket_create' ), array( $request ) );
                }
                return rest_ensure_response( array( 'status' => 'error', 'message' => 'Use POST to create ticket.' ) );

            case 'update_ticket':
                if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_ticket_update' ), array( $request ) );
                }
                return rest_ensure_response( array( 'status' => 'error', 'message' => 'Use PUT/PATCH to update ticket.' ) );

            case 'delete_ticket':
                if ( in_array( $method, array( 'POST', 'DELETE' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_ticket_delete' ), array( $request ) );
                }
                return rest_ensure_response( array( 'status' => 'error', 'message' => 'Use DELETE to remove ticket.' ) );

            case 'get_organizers_data':
                $organizers = $this->ep_fetch_organizers_collection( $params );
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $organizers['total'], 'organizers' => $organizers['items'] ) );

            case 'get_organizers_count':
                if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
                    $ep       = new Eventprime_Basic_Functions();
                    $search   = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
                    $featured = $this->ep_param_truthy( $params, 'featured' );
                    $popular  = $this->ep_param_truthy( $params, 'popular' );
                    $count    = $ep->get_organizers_count( array( 'hide_empty' => false ), $search, $featured, $popular );
                } else {
                    $count = 0;
                }
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $count ) );

            case 'get_venues_data':
                $venues = $this->ep_fetch_venues_collection( $params );
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $venues['total'], 'venues' => $venues['items'] ) );

            case 'get_venues_count':
                if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
                    $ep    = new Eventprime_Basic_Functions();
                    $args  = isset( $params['search'] ) ? array( 'hide_empty' => false, 'search' => sanitize_text_field( $params['search'] ) ) : array( 'hide_empty' => false );
                    $count = $ep->get_venues_count( $args );
                } else {
                    $count = 0;
                }
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $count ) );

            case 'get_event_types_data':
                $event_types = $this->ep_fetch_event_types_collection( $params );
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $event_types['total'], 'event_types' => $event_types['items'] ) );

            case 'get_event_types_count':
                if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
                    $ep       = new Eventprime_Basic_Functions();
                    $search   = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
                    $featured = $this->ep_param_truthy( $params, 'featured' );
                    $popular  = $this->ep_param_truthy( $params, 'popular' );
                    $count    = $ep->get_event_types_count( array( 'hide_empty' => false ), $search, $featured, $popular );
                } else {
                    $count = 0;
                }
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $count ) );

            case 'get_performers_data':
                $performers = $this->ep_fetch_performers_collection( $params );
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $performers['total'], 'performers' => $performers['items'] ) );

            case 'get_performers_count':
                $performers = $this->ep_fetch_performers_collection( array_merge( $params, array( 'per_page' => 1 ) ) );
                return rest_ensure_response( array( 'status' => 'success', 'count' => (int) $performers['total'] ) );

            case 'create_venue':
                if ( $method === 'POST' ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_venue_create' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_terms( 'em_venue', $params ) );

            case 'create_organizer':
                if ( $method === 'POST' ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_organizer_create' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_terms( 'em_event_organizer', $params ) );

            case 'create_event_type':
                if ( $method === 'POST' ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_event_type_create' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_terms( 'em_event_type', $params ) );

            case 'update_venue':
                if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_venue_update' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_terms( 'em_venue', $params ) );

            case 'delete_venue':
                if ( in_array( $method, array( 'POST', 'DELETE' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_venue_delete' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_terms( 'em_venue', $params ) );

            case 'create_performer':
                if ( $method === 'POST' ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_performer_create' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_posts( 'em_performer' ) );

            case 'update_performer':
                if ( in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_performer_update' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_posts( 'em_performer' ) );

            case 'delete_performer':
                if ( in_array( $method, array( 'POST', 'DELETE' ), true ) ) {
                    if ( method_exists( $request, 'set_body_params' ) ) {
                        $request->set_body_params( $params );
                    }
                    return call_user_func_array( array( $this, 'handle_performer_delete' ), array( $request ) );
                }
                return rest_ensure_response( $this->integration_all_posts( 'em_performer' ) );

            case 'get_access_token':
                if ( $method === 'POST' ) {
                    return call_user_func_array( array( $this, 'handle_get_access_token' ), array( $request ) );
                }
                return rest_ensure_response( array( 'status' => 'error', 'message' => 'Use POST to obtain access token.' ) );

            default:
                if ( ! empty( $trigger ) ) {
                    return rest_ensure_response( $this->integration_handle_trigger( $trigger, $params ) );
                }

                return rest_ensure_response( array( 'status' => 'error', 'message' => 'Unknown integration action/trigger' ), 404 );
        }
    }

    /**
     * Issue a short-lived access token for a valid WP user.
     * Usage: POST /wp-json/eventprime/v1/integration?action=get_access_token with JSON body {"username":"...","password":"..."}
     * Returns: { status: 'success', access_token: '...', expires_in: 300 }
     */
    protected function handle_get_access_token( WP_REST_Request $request ) {
        // Accept JSON body, form-encoded body, or query params
        $body = $request->get_body_params();
        // If body params empty, try raw JSON
        if ( empty( $body ) ) {
            $raw = $request->get_body();
            if ( ! empty( $raw ) ) {
                $decoded = json_decode( $raw, true );
                if ( is_array( $decoded ) ) $body = $decoded;
            }
        }
        // Fallback to query params
        if ( empty( $body ) ) {
            $body = $request->get_query_params();
        }

        $username = '';
        $password = '';
        if ( isset( $body['username'] ) ) $username = sanitize_user( $body['username'] );
        elseif ( isset( $body['user'] ) ) $username = sanitize_user( $body['user'] );
        if ( isset( $body['password'] ) ) $password = $body['password'];
        elseif ( isset( $body['pass'] ) ) $password = $body['pass'];
        if ( empty( $username ) || empty( $password ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing credentials.' ), 400 );
        }
        // Determine whether application-password-only mode is enabled.
        // Priority: constant EP_APP_PASSWORD_ONLY -> option 'ep_rest_api_app_password_only'
        // If the option is not present, default to enforcing application-password-only mode immediately
        // (per administrator request). To revert, define EP_APP_PASSWORD_ONLY=false in wp-config.php
        // or set the option 'ep_rest_api_app_password_only' to false.
        $require_app_only = false;
        if ( defined( 'EP_APP_PASSWORD_ONLY' ) ) {
            $require_app_only = (bool) EP_APP_PASSWORD_ONLY;
        } else {
            $opt = get_option( 'ep_rest_api_app_password_only', null );
            if ( $opt === null ) {
                // Option not set: default to enforcing application-password-only
                $require_app_only = true;
            } else {
                $require_app_only = (bool) $opt;
            }
        }

        $user = null;

        if ( $require_app_only ) {
            // When enabled, only accept Application Passwords for token issuance.
            if ( ! function_exists( 'wp_authenticate_application_password' ) ) {
                return rest_ensure_response( array( 'status' => 'error', 'message' => 'Application Passwords are not supported on this WordPress installation.' ), 500 );
            }
            $app_user = wp_authenticate_application_password( null, $username, $password );
            if ( $app_user instanceof WP_User ) {
                $user = $app_user;
            } else {
                // Record failed auth attempt by client IP so we can temporarily block
                // subsequent requests that might attempt to probe the API with bad creds.
                $client_ip = '';
                if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                    $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
                    $client_ip = trim( $ips[0] );
                } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
                    $client_ip = trim( $_SERVER['REMOTE_ADDR'] );
                }
                if ( ! empty( $client_ip ) && function_exists( 'set_transient' ) ) {
                    $flag_key = 'ep_failed_auth_' . md5( $client_ip );
                    // block for 5 minutes
                    set_transient( $flag_key, 1, 300 );
                }
                return rest_ensure_response( array( 'status' => 'error', 'message' => 'Invalid credentials.' ), 401 );
            }
        } else {
            // Default behaviour: try regular password first, fall back to Application Passwords
            $user = wp_authenticate( $username, $password );

            // If standard password auth failed, try Application Passwords (WP 5.6+)
            if ( is_wp_error( $user ) || empty( $user->ID ) ) {
                if ( function_exists( 'wp_authenticate_application_password' ) ) {
                    $app_user = wp_authenticate_application_password( null, $username, $password );
                    if ( $app_user instanceof WP_User ) {
                        $user = $app_user;
                    }
                }
            }
        }

        if ( is_wp_error( $user ) || empty( $user->ID ) ) {
            // Record failed auth attempt by client IP so we can temporarily block
            // subsequent requests that might attempt to probe the API with bad creds.
            $client_ip = '';
            if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
                $ips = explode( ',', $_SERVER['HTTP_X_FORWARDED_FOR'] );
                $client_ip = trim( $ips[0] );
            } elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
                $client_ip = trim( $_SERVER['REMOTE_ADDR'] );
            }
            if ( ! empty( $client_ip ) && function_exists( 'set_transient' ) ) {
                $flag_key = 'ep_failed_auth_' . md5( $client_ip );
                // block for 5 minutes
                set_transient( $flag_key, 1, 300 );
            }
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Invalid credentials.' ), 401 );
        }
        // Only allow users who can edit posts to receive tokens
        if ( ! user_can( $user, 'edit_posts' ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Insufficient permissions.' ), 403 );
        }
        $token = $this->ep_generate_access_token( $user->ID );
        if ( ! $token ) return rest_ensure_response( array( 'status' => 'error', 'message' => 'Failed to generate token.' ), 500 );
        return rest_ensure_response( array( 'status' => 'success', 'access_token' => $token['token'], 'expires_in' => $token['expires_in'] ) );
    }

    /**
     * Generate a short-lived access token and store it in user meta.
     * Returns array('token'=>..., 'expires_in'=>int)
     */
    protected function ep_generate_access_token( $user_id ) {
        $expires_in = 300; // 5 minutes
        $expires_at = time() + $expires_in;
        // Payload: user_id|expires_at|random
        $random = wp_generate_password( 16, false, false );
        $payload = $user_id . '|' . $expires_at . '|' . $random;
        // Sign payload with site-specific salt
        $signature = hash_hmac( 'sha256', $payload, wp_salt() );
        $token_raw = $payload . '|' . $signature;
        // URL-safe base64
        $token = rtrim( strtr( base64_encode( $token_raw ), '+/', '-_' ), '=' );
        // Stateless token — no need to persist on server for validation. Return token and TTL.
        return array( 'token' => $token, 'expires_in' => $expires_in );
    }

    /**
     * Validate an access token string. Returns array('user_id'=>int) on success, false on failure.
     */
    protected function ep_validate_access_token( $token ) {
        if ( empty( $token ) ) return false;
        // Decode URL-safe base64
        $decoded = base64_decode( strtr( $token, '-_', '+/' ) );
        if ( $decoded === false ) return false;
        $parts = explode( '|', $decoded );
        // expected parts: user_id | expires_at | random | signature
        if ( count( $parts ) !== 4 ) return false;
        list( $user_id, $expires_at, $random, $signature ) = $parts;
        $user_id = intval( $user_id );
        $expires_at = intval( $expires_at );
        if ( $user_id <= 0 || $expires_at <= 0 ) return false;
        if ( time() > $expires_at ) return false; // expired
        // Recompute signature and compare in timing-safe way
        $payload = $user_id . '|' . $expires_at . '|' . $random;
        $expected_sig = hash_hmac( 'sha256', $payload, wp_salt() );
        if ( ! hash_equals( $expected_sig, $signature ) ) return false;
        // Optionally, confirm user still exists and can edit posts
        $user = get_user_by( 'id', $user_id );
        if ( empty( $user ) ) return false;
        if ( ! user_can( $user, 'edit_posts' ) ) return false;
        return array( 'user_id' => $user_id );
    }

    protected function integration_handle_trigger( $trigger, $params = array() ) {
        // Delegate trigger payload generation to the centralized helpers when available.
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            switch ( $trigger ) {
                case 'create_event':
                case 'update_event':
                case 'delete_event':
                    return $helpers->all_events_data( $trigger );
                case 'all_events':
                    return $helpers->all_events_list();
                case 'get_tickets_by_event':
                    return $helpers->ep_get_tickets_by_event( $params );
                case 'create_venue':
                case 'update_venue':
                case 'delete_venue':
                    return $helpers->all_venues_data();
                case 'create_organizer':
                case 'update_organizer':
                case 'delete_organizer':
                    return $helpers->all_organizers_data();
                case 'create_performer':
                case 'update_performer':
                case 'delete_performer':
                    return $helpers->all_performers_data( $trigger );
                case 'confirm_booking':
                    return $helpers->all_bookings_data( 'completed' );
                case 'pending_booking':
                    return $helpers->all_bookings_data( 'pending' );
                case 'cancel_booking':
                    return $helpers->all_bookings_data( 'cancelled' );
                case 'refund_booking':
                    return $helpers->all_bookings_data( 'refunded' );
                case 'failed_booking':
                    return $helpers->all_bookings_data( 'failed' );
                default:
                    return array( 'status' => 'error', 'message' => esc_html__( 'Unknown trigger', 'eventprime-event-calendar-management' ) );
            }
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers are not available.', 'eventprime-event-calendar-management' ) );
    }

    /**
     * Require a valid access token for API access. Returns true on success or
     * a WP_REST_Response (401) formatted as { success:false, message:'...', code:401 } on failure.
     * The get_access_token action is always allowed to proceed.
     */
    protected function ep_require_token_or_401( WP_REST_Request $request ) {
        // Allow token issuance without an existing token
        $action = $request->get_param( 'action' );
        if ( $action === 'get_access_token' ) return true;

        // Extract token from Authorization header, X-EP-Token header, or access_token query param
        $auth = '';
        $hdr = $request->get_header( 'authorization' );
        if ( ! empty( $hdr ) && stripos( $hdr, 'Bearer ' ) === 0 ) {
            $auth = trim( substr( $hdr, 7 ) );
        }
        if ( empty( $auth ) ) {
            $hdr2 = $request->get_header( 'x-ep-token' );
            if ( ! empty( $hdr2 ) ) $auth = trim( wp_unslash( $hdr2 ) );
        }
        if ( empty( $auth ) ) {
            $qs = $request->get_param( 'access_token' );
            if ( ! empty( $qs ) ) $auth = sanitize_text_field( $qs );
        }

        if ( empty( $auth ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid or missing access token.', 'code' => 401 ), 401 );
        }

        
        $valid = $this->ep_validate_access_token( $auth );
        if ( ! $valid || empty( $valid['user_id'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Invalid or missing access token.', 'code' => 401 ), 401 );
        }

        // set current WP user for capability checks later
        wp_set_current_user( (int) $valid['user_id'] );
        return true;
    }

    /**
     * Returns an array of booking details or a helpful message when none found.
     */
    protected function integration_all_bookings_data( $status ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            return $helpers->all_bookings_data( $status );
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    /**
     */
    protected function integration_all_performers_data( $status ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->all_performers_data( $status );
            if ( isset( $res['status'] ) && $res['status'] === 'success' ) {
                $performer = isset( $res['performer'] ) ? $res['performer'] : $res;
                return array( 'status' => 'success', 'count' => 1, 'performers' => array( $performer ) );
            }
            return array( 'status' => 'error', 'message' => isset( $res['message'] ) ? $res['message'] : esc_html__( 'No performers found.', 'eventprime-event-calendar-management' ) );
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    /**
     */
    protected function integration_all_organizers_data() {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->all_organizers_data();
            if ( isset( $res['status'] ) && $res['status'] === 'success' ) {
                $organizer = isset( $res['organizer'] ) ? $res['organizer'] : $res;
                return array( 'status' => 'success', 'count' => 1, 'organizers' => array( $organizer ) );
            }
            return array( 'status' => 'error', 'message' => isset( $res['message'] ) ? $res['message'] : esc_html__( 'No organizers found.', 'eventprime-event-calendar-management' ) );
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    /**
     */
    protected function integration_all_venues_data() {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->all_venues_data();
            if ( isset( $res['status'] ) && $res['status'] === 'success' ) {
                $venue = isset( $res['venue'] ) ? $res['venue'] : $res;
                return array( 'status' => 'success', 'count' => 1, 'venues' => array( $venue ) );
            }
            return array( 'status' => 'error', 'message' => isset( $res['message'] ) ? $res['message'] : esc_html__( 'No venues found.', 'eventprime-event-calendar-management' ) );
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    /**
     * Accepts array $params with 'event_id'.
     */
    protected function integration_ep_get_tickets_by_event( $params ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->ep_get_tickets_by_event( $params );
            if ( is_array( $res ) && isset( $res['status'] ) && $res['status'] === 'error' && isset( $res['message'] ) && stripos( $res['message'], 'event not found' ) !== false ) {
                return new WP_Error( 'not_found', $res['message'], array( 'status' => 404 ) );
            }
            return $res;
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    /**
     * Fetch a single recent event and return a validated event payload suitable
     *
     * @param string $status Trigger type (create_event|update_event|delete_event)
     * @return array|object
     */
    protected function integration_all_events_data( $status ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->all_events_data( $status );
            if ( isset( $res['status'] ) && $res['status'] === 'success' ) {
                if ( isset( $res['event'] ) ) {
                    return array( 'status' => 'success', 'count' => 1, 'events' => array( $res['event'] ) );
                }
                if ( isset( $res['events'] ) ) { 
                    return array( 'status' => 'success', 'count' => count( $res['events'] ), 'events' => $res['events'] );
                }
            }
            return array( 'status' => 'error', 'message' => isset( $res['message'] ) ? $res['message'] : esc_html__( 'No events found.', 'eventprime-event-calendar-management' ) );
        }
        return array( 'status' => 'error', 'message'=> esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    /**
     * Validate/normalize an event data object for integration payloads.
     *
     * @param object $event_data
     * @return object
     */
    protected function integration_validate_event_data_fields( $event_data ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $validated = $helpers->validate_event_data_fields( $event_data );
            if ( is_object( $validated ) || is_array( $validated ) ) {
                return array( 'status' => 'success', 'count' => 1, 'event' => $validated );
            }
            return array( 'status' => 'error', 'message' => esc_html__( 'Event validation failed.', 'eventprime-event-calendar-management' ) );
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    protected function integration_get_event_sample( $action ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->get_event_sample( $action );
            if ( isset( $res['status'] ) && $res['status'] === 'success' ) {
                if ( isset( $res['event'] ) ) {
                    return array( 'status' => 'success', 'count' => 1, 'events' => array( $res['event'] ) );
                }
            }
            if ( isset( $res['event_id'] ) ) {
                return array( 'status' => 'success', 'count' => 1, 'events' => array( array( 'event_id' => $res['event_id'], 'event_name' => isset( $res['event_name'] ) ? $res['event_name'] : '' ) ) );
            }
            return array( 'status' => 'error', 'message' => isset( $res['message'] ) ? $res['message'] : esc_html__( 'No events found for sample data.', 'eventprime-event-calendar-management' ) );
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    protected function integration_all_events_list() {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->all_events_list();
            if ( ! empty( $res ) ) {
                return array( 'status' => 'success', 'count' => count( $res ), 'events' => $res );
            }
            return array( 'status' => 'error', 'message' => esc_html__( 'No events found.', 'eventprime-event-calendar-management' ) );
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    protected function integration_get_tickets_by_event( $params ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->ep_get_tickets_by_event( $params );
            if ( is_array( $res ) && isset( $res['status'] ) && $res['status'] === 'error' && isset( $res['message'] ) && stripos( $res['message'], 'event not found' ) !== false ) {
                return new WP_Error( 'not_found', $res['message'], array( 'status' => 404 ) );
            }
            return $res;
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    protected function integration_all_terms( $taxonomy, $params = array() ) {
        switch ( $taxonomy ) {
            case 'em_event_organizer':
                $collection = $this->ep_fetch_organizers_collection( $params );
                if ( empty( $collection['items'] ) ) {
                    return array( 'status' => 'error', 'message' => esc_html__( 'No terms found.', 'eventprime-event-calendar-management' ) );
                }
                return array(
                    'status'     => 'success',
                    'count'      => (int) $collection['total'],
                    'organizers' => $collection['items'],
                );
            case 'em_venue':
                $collection = $this->ep_fetch_venues_collection( $params );
                if ( empty( $collection['items'] ) ) {
                    return array( 'status' => 'error', 'message' => esc_html__( 'No terms found.', 'eventprime-event-calendar-management' ) );
                }
                return array(
                    'status' => 'success',
                    'count'  => (int) $collection['total'],
                    'venues' => $collection['items'],
                );
            case 'em_event_type':
                $collection = $this->ep_fetch_event_types_collection( $params );
                if ( empty( $collection['items'] ) ) {
                    return array( 'status' => 'error', 'message' => esc_html__( 'No terms found.', 'eventprime-event-calendar-management' ) );
                }
                return array(
                    'status'      => 'success',
                    'count'       => (int) $collection['total'],
                    'event_types' => $collection['items'],
                );
        }

        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'orderby' => 'id', 'order' => 'DESC' ) );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array( 'status' => 'error', 'message' => esc_html__( 'No terms found.', 'eventprime-event-calendar-management' ) );
        }
        $out = array();
        foreach ( $terms as $t ) {
            $out[] = (array) $t;
        }
        $key = $taxonomy;
        if ( $taxonomy === 'em_venue' ) {
            $key = 'venues';
        } elseif ( $taxonomy === 'em_event_organizer' ) {
            $key = 'organizers';
        } elseif ( $taxonomy === 'em_event_type' ) {
            $key = 'event_types';
        }
        return array( 'status' => 'success', 'count' => count( $out ), $key => $out );
    }

    protected function integration_all_posts( $post_type ) {
        $args = array( 'post_type' => $post_type, 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' );
        $posts = get_posts( $args );
        if ( empty( $posts ) ) return array( 'status' => 'error', 'message' => esc_html__( 'No posts found.', 'eventprime-event-calendar-management' ) );
        $out = array();
        $epf = new Eventprime_Basic_Functions();
        foreach ( $posts as $p ) {
            if ( $post_type === 'em_performer' && method_exists( $epf, 'get_single_performer' ) ) {
                $out[] = $epf->get_single_performer( $p->ID );
            } else {
                $out[] = array( 'id' => $p->ID, 'title' => $p->post_title );
            }
        }
        $key = $post_type;
        if ( $post_type === 'em_performer' ) $key = 'performers';
        return array( 'status' => 'success', 'count' => count( $out ), $key => $out );
    }

    protected function integration_all_bookings_by_status( $status, $event_id = 0 ) {
        $helpers = $this->get_integration_helpers();
        if ( $helpers ) {
            $res = $helpers->all_bookings_data( $status, $event_id );
            return $res;
        }
        return array( 'status' => 'error', 'message' => esc_html__( 'Integration helpers not available.', 'eventprime-event-calendar-management' ) );
    }

    protected function integration_get_booking_sample( $params ) {
        $status = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'completed';
        return $this->integration_all_bookings_by_status( $status );
    }

    /**
     * Normalize request body data by merging JSON and form params.
     */
    protected function ep_normalize_body_params( WP_REST_Request $request ) {
        $body = $request->get_body_params();
        if ( ! is_array( $body ) ) {
            $body = array();
        }
        $json = $request->get_json_params();
        if ( is_array( $json ) ) {
            $body = array_merge( $body, $json );
        }
        return $body;
    }

    /**
     * Convert a mixed value into a 1/0 flag.
     */
    protected function ep_param_truthy( $source, $key ) {
        $value = null;
        if ( is_array( $source ) && array_key_exists( $key, $source ) ) {
            $value = $source[ $key ];
        } elseif ( is_object( $source ) && isset( $source->$key ) ) {
            $value = $source->$key;
        }
        if ( null === $value ) {
            return 0;
        }
        if ( is_bool( $value ) ) {
            return $value ? 1 : 0;
        }
        if ( is_numeric( $value ) ) {
            return absint( $value ) ? 1 : 0;
        }
        $normalized = strtolower( trim( (string) $value ) );
        return in_array( $normalized, array( '1', 'true', 'yes', 'on' ), true ) ? 1 : 0;
    }

    /**
     * Sanitize a list (or scalar) into an array of clean strings.
     *
     * @param mixed  $value    Scalar or array payload.
     * @param string $callback Sanitizer callable.
     *
     * @return array
     */
    protected function ep_sanitize_string_list( $value, $callback = 'sanitize_text_field' ) {
        if ( empty( $value ) && '0' !== $value ) {
            return array();
        }
        if ( ! is_array( $value ) ) {
            $value = array( $value );
        }
        $clean = array();
        foreach ( $value as $item ) {
            if ( $item === null || $item === '' ) {
                continue;
            }
            $sanitized = call_user_func( $callback, is_scalar( $item ) ? $item : wp_json_encode( $item ) );
            if ( $sanitized === '' ) {
                continue;
            }
            $clean[] = $sanitized;
        }
        return $clean;
    }

    /**
     * Sanitize associative array of URLs (used for social links).
     */
    protected function ep_sanitize_assoc_urls( $value ) {
        if ( ! is_array( $value ) ) {
            return array();
        }
        $clean = array();
        foreach ( $value as $key => $url ) {
            if ( empty( $url ) && '0' !== $url ) {
                continue;
            }
            $clean[ $key ] = esc_url_raw( $url );
        }
        return $clean;
    }

    /**
     * Normalize additional fee payloads to EventPrime's expected structure.
     *
     * @param mixed $fees Array/object/string payload from API consumers.
     * @return array Normalized list of arrays with at least 'label' and 'price'.
     */
    protected function ep_normalize_additional_fees( $fees ) {
        if ( empty( $fees ) && ! is_numeric( $fees ) ) {
            return array();
        }
        if ( is_string( $fees ) ) {
            $decoded = json_decode( $fees, true );
            if ( json_last_error() === JSON_ERROR_NONE ) {
                $fees = $decoded;
            } else {
                $fees = array( array( 'label' => $fees, 'price' => 0 ) );
            }
        }
        if ( ! is_array( $fees ) ) {
            $fees = array( $fees );
        }
        $out = array();
        foreach ( $fees as $fee ) {
            if ( is_null( $fee ) || $fee === '' ) {
                continue;
            }
            if ( is_object( $fee ) ) {
                $fee = (array) $fee;
            }
            if ( ! is_array( $fee ) ) {
                $fee = array( 'label' => $fee );
            }
            $label = '';
            foreach ( array( 'label', 'name', 'title' ) as $candidate ) {
                if ( isset( $fee[ $candidate ] ) && $fee[ $candidate ] !== '' ) {
                    $label = sanitize_text_field( $fee[ $candidate ] );
                    break;
                }
            }
            if ( $label === '' ) {
                $label = esc_html__( 'Additional Fee', 'eventprime-event-calendar-management' );
            }
            $price = 0;
            if ( isset( $fee['price'] ) && $fee['price'] !== '' ) {
                $price = floatval( $fee['price'] );
            } elseif ( isset( $fee['value'] ) && $fee['value'] !== '' ) {
                $price = floatval( $fee['value'] );
            }
            $entry = array(
                'label' => $label,
                'price' => $price,
            );
            if ( isset( $fee['type'] ) && $fee['type'] !== '' ) {
                $entry['type'] = sanitize_text_field( $fee['type'] );
            }
            if ( isset( $fee['description'] ) && $fee['description'] !== '' ) {
                $entry['description'] = sanitize_textarea_field( $fee['description'] );
            }
            $out[] = $entry;
        }
        return $out;
    }

    /**
     * Calculate pagination information from request params.
     */
    protected function ep_calculate_pagination( $params, $default_per = 20 ) {
        $per_raw  = isset( $params['per_page'] ) ? $params['per_page'] : $default_per;
        $fetch_all = false;
        if ( is_string( $per_raw ) && 'all' === strtolower( $per_raw ) ) {
            $fetch_all = true;
        }
        $per_page = absint( $per_raw );
        if ( $per_page <= 0 && ! $fetch_all ) {
            $per_page = $default_per;
        }
        $page   = isset( $params['page'] ) ? max( 1, absint( $params['page'] ) ) : 1;
        $offset = isset( $params['offset'] ) ? max( 0, absint( $params['offset'] ) ) : ( $fetch_all ? 0 : ( $page - 1 ) * $per_page );

        return array(
            'per_page' => $fetch_all ? 0 : $per_page,
            'offset'   => $fetch_all ? 0 : $offset,
            'page'     => $page,
            'fetch_all'=> $fetch_all,
        );
    }

    /**
     * Fetch organizers (terms + metadata) using EventPrime helpers.
     */
    protected function ep_fetch_organizers_collection( $params = array() ) {
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return array( 'items' => array(), 'total' => 0 );
        }
        $pagination = $this->ep_calculate_pagination( $params, 20 );
        $search     = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
        $featured   = $this->ep_param_truthy( $params, 'featured' );
        $popular    = $this->ep_param_truthy( $params, 'popular' );

        $args = array( 'hide_empty' => false );
        if ( ! empty( $params['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( $params['orderby'] );
        }
        if ( ! empty( $params['order'] ) ) {
            $args['order'] = sanitize_text_field( $params['order'] );
        }
        if ( $pagination['per_page'] > 0 ) {
            $args['number'] = $pagination['per_page'];
            $args['offset'] = $pagination['offset'];
        }
        if ( $search ) {
            $args['search'] = $search;
        }

        $ep  = new Eventprime_Basic_Functions();
        $res = $ep->get_organizers_data( $args );
        $items = ( $res instanceof WP_Term_Query ) ? $res->terms : (array) $res;

        $count_args = $args;
        unset( $count_args['number'], $count_args['offset'] );
        $total = $ep->get_organizers_count( $count_args, $search, $featured, $popular );

        return array(
            'items' => array_values( $items ),
            'total' => (int) $total,
        );
    }

    /**
     * Fetch venues collection.
     */
    protected function ep_fetch_venues_collection( $params = array() ) {
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return array( 'items' => array(), 'total' => 0 );
        }
        $pagination = $this->ep_calculate_pagination( $params, 10 );
        $search     = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';

        $args = array( 'hide_empty' => false );
        if ( $pagination['per_page'] > 0 ) {
            $args['number'] = $pagination['per_page'];
            $args['offset'] = $pagination['offset'];
        }
        if ( ! empty( $params['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( $params['orderby'] );
        }
        if ( ! empty( $params['order'] ) ) {
            $args['order'] = sanitize_text_field( $params['order'] );
        }
        if ( $search ) {
            $args['search'] = $search;
        }

        $ep    = new Eventprime_Basic_Functions();
        $query = $ep->get_venues_data( $args );
        $items = ( $query instanceof WP_Term_Query ) ? $query->terms : (array) $query;

        $count_args = $args;
        unset( $count_args['number'], $count_args['offset'] );
        $total = $ep->get_venues_count( $count_args );

        return array(
            'items' => array_values( $items ),
            'total' => (int) $total,
        );
    }

    /**
     * Fetch event types with metadata.
     */
    protected function ep_fetch_event_types_collection( $params = array() ) {
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return array( 'items' => array(), 'total' => 0 );
        }
        $pagination = $this->ep_calculate_pagination( $params, 20 );
        $search     = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
        $featured   = $this->ep_param_truthy( $params, 'featured' );
        $popular    = $this->ep_param_truthy( $params, 'popular' );

        $args = array( 'hide_empty' => false );
        if ( $pagination['per_page'] > 0 ) {
            $args['number'] = $pagination['per_page'];
            $args['offset'] = $pagination['offset'];
        }
        if ( ! empty( $params['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( $params['orderby'] );
        }
        if ( ! empty( $params['order'] ) ) {
            $args['order'] = sanitize_text_field( $params['order'] );
        }
        if ( $search ) {
            $args['name__like'] = $search;
        }

        $ep    = new Eventprime_Basic_Functions();
        $query = $ep->get_event_types_data( $args );
        $items = ( $query instanceof WP_Term_Query ) ? $query->terms : (array) $query;

        $count_args = $args;
        unset( $count_args['number'], $count_args['offset'] );
        $total = $ep->get_event_types_count( $count_args, $search, $featured, $popular );

        return array(
            'items' => array_values( $items ),
            'total' => (int) $total,
        );
    }

    /**
     * Fetch performers via helper so meta data is preserved.
     */
    protected function ep_fetch_performers_collection( $params = array() ) {
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return array( 'items' => array(), 'total' => 0 );
        }
        $pagination = $this->ep_calculate_pagination( $params, 10 );
        $search     = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
        $status     = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'publish';

        $args = array(
            'posts_per_page' => $pagination['fetch_all'] ? -1 : $pagination['per_page'],
            'numberposts'    => $pagination['fetch_all'] ? -1 : $pagination['per_page'],
            'paged'          => $pagination['page'],
            'post_type'      => 'em_performer',
            'post_status'    => $status,
            'no_found_rows'  => false,
        );
        if ( $search ) {
            $args['s'] = $search;
        }
        if ( ! empty( $params['orderby'] ) ) {
            $args['orderby'] = sanitize_text_field( $params['orderby'] );
        }
        if ( ! empty( $params['order'] ) ) {
            $args['order'] = sanitize_text_field( $params['order'] );
        }

        $ep    = new Eventprime_Basic_Functions();
        $query = $ep->get_performers_post_data( $args );
        $items = array();
        $total = 0;
        if ( $query instanceof WP_Query ) {
            $items = $query->posts;
            $total = (int) $query->found_posts;
        } elseif ( is_array( $query ) ) {
            $items = $query;
            $total = count( $items );
        }

        return array(
            'items' => array_values( $items ),
            'total' => $total,
        );
    }

    protected function generate_sample_for_trigger( $trigger ) {
        // reuse perform logic
        $req = new WP_REST_Request();
        $req->set_param( 'trigger', $trigger );
        $res = $this->handle_perform( $req );
        return $res->get_data();
    }


    protected function send_webhook( $hookUrl, $payload ) {
        // If the integration helpers provide a normalizer, use it to decode
        // any nested serialized/JSON-in-string values so consumers receive
        $helpers = $this->get_integration_helpers();
        if ( $helpers && method_exists( $helpers, 'normalize_response' ) ) {
            $payload = $helpers->normalize_response( $payload );
        }

        $args = array(
            'headers' => array( 'Content-Type' => 'application/json; charset=utf-8' ),
            'body' => wp_json_encode( $payload ),
            'timeout' => 20,
            'sslverify' => true,
        );
        $result = wp_remote_post( $hookUrl, $args );
        if ( is_wp_error( $result ) ) {
        }
        return $result;
    }

    /* ---------- Normalization helpers ---------- */

    protected function normalize_event( $post ) {
        // try to pull standard meta keys, fallback to available
        $id = $post->ID;
        $title = sanitize_text_field( $post->post_title );
        $permalink = get_permalink( $id );
        $status = $post->post_status;
        $content = wp_kses_post( $post->post_content );
        $thumb = get_the_post_thumbnail_url( $id );
        if ( empty( $thumb ) && class_exists( 'Eventprime_Basic_Functions' ) ) {
            $epf = new Eventprime_Basic_Functions();
            if ( method_exists( $epf, 'get_event_image_url' ) ) {
                $ep_img = $epf->get_event_image_url( $id );
                if ( ! empty( $ep_img ) ) {
                    $thumb = esc_url_raw( $ep_img );
                }
            }
        }
        // prefer the timestamp meta keys used elsewhere in the plugin (em_start_date_time)
        $start = get_post_meta( $id, 'em_start_date', true );
        $end = get_post_meta( $id, 'em_end_date', true );
        $start_ts = get_post_meta( $id, 'em_start_date_time', true );
        $end_ts = get_post_meta( $id, 'em_end_date_time', true );
        if ( ! empty( $start_ts ) ) {
            $start_iso = $this->convert_to_iso( $start_ts );
        } else {
            $start_iso = $this->convert_to_iso( $start );
        }
        if ( ! empty( $end_ts ) ) {
            $end_iso = $this->convert_to_iso( $end_ts );
        } else {
            $end_iso = $this->convert_to_iso( $end );
        }
        $venue = get_post_meta( $id, 'em_venue', true );
        // Normalize stored venue meta: some installs store a single id, an
        // array of ids, a JSON string, or a comma-separated list. Ensure we
        // return a single integer id (prefer first) so downstream code can
        // reliably use it.
        if ( is_array( $venue ) ) {
            $venue_id_val = count( $venue ) ? $venue[0] : 0;
        } elseif ( is_string( $venue ) ) {
            $maybe = json_decode( $venue, true );
            if ( is_array( $maybe ) && count( $maybe ) ) {
                $venue_id_val = $maybe[0];
            } elseif ( strpos( $venue, ',' ) !== false ) {
                $parts = array_map( 'trim', explode( ',', $venue ) );
                $venue_id_val = isset( $parts[0] ) ? $parts[0] : 0;
            } else {
                $venue_id_val = $venue;
            }
        } else {
            $venue_id_val = $venue;
        }
        // If meta did not contain a usable venue id, try the taxonomy relationship
        // (some installs store the relation as a term only, not in post meta).
        if ( empty( $venue_id_val ) ) {
            $term_ids = wp_get_object_terms( $id, 'em_venue', array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $term_ids ) && ! empty( $term_ids ) ) {
                $venue_id_val = $term_ids[0];
            }
        }
        $organizer = get_post_meta( $id, 'em_organizer', true );

        return array(
            'id' => $id,
            'title' => $title,
            'permalink' => esc_url_raw( $permalink ),
            'status' => $status,
            'content' => $content,
            'thumbnail' => $thumb ? esc_url_raw( $thumb ) : null,
            'start_date' => $start_iso,
            'end_date' => $end_iso,
            'venue_id' => ! empty( $venue_id_val ) ? absint( $venue_id_val ) : null,
            'organizer_id' => $organizer ? absint( $organizer ) : null,
        );
    }

    
    public function handle_events_counts( WP_REST_Request $request ) {
        global $wpdb;
        $statuses = $wpdb->get_col( "SELECT DISTINCT post_status FROM {$wpdb->posts} WHERE post_type = 'em_event'" );
        $out = array();
        if ( empty( $statuses ) ) return rest_ensure_response( array( 'total' => 0, 'by_status' => (object) array() ) );
        $total = 0;
        foreach ( $statuses as $st ) {
            $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s", 'em_event', $st ) );
            $out[ $st ] = $count;
            $total += $count;
        }
        $resp = rest_ensure_response( array( 'total' => $total, 'by_status' => $out ) );
        if ( is_object( $resp ) && method_exists( $resp, 'header' ) ) {
            $resp->header( 'X-EP-Handler', 'core-rest-counts' );
        } else {
            header( 'X-EP-Handler: core-rest-counts' );
        }
        return $resp;
    }

    protected function convert_to_iso( $value ) {
        if ( empty( $value ) ) return null;
        // if numeric timestamp
        if ( is_numeric( $value ) ) {
            $ts = (int) $value;
        } else {
            $ts = strtotime( $value );
            if ( $ts === false ) return sanitize_text_field( $value );
        }

        // Use site's timezone to present ISO datetimes with correct offset
        if ( function_exists( 'wp_timezone' ) ) {
            $wp_tz = wp_timezone();
        } else {
            $tz_str = get_option( 'timezone_string', '' );
            if ( ! empty( $tz_str ) ) {
                try { $wp_tz = new DateTimeZone( $tz_str ); } catch ( Exception $e ) { $wp_tz = new DateTimeZone( 'UTC' ); }
            } else {
                $offset = get_option( 'gmt_offset', 0 );
                $offset_seconds = intval( $offset * 3600 );
                $tz_name = timezone_name_from_abbr( '', $offset_seconds, 0 );
                if ( $tz_name ) {
                    $wp_tz = new DateTimeZone( $tz_name );
                } else {
                    $wp_tz = new DateTimeZone( 'UTC' );
                }
            }
        }

        try {
            $dt = new DateTime( '@' . $ts );
            $dt->setTimezone( $wp_tz );
            return $dt->format( DateTime::ATOM );
        } catch ( Exception $e ) {
            return date_i18n( 'c', $ts );
        }
    }

    /**
     * Format an event post or Eventprime_Basic_Functions event object into a stable API schema.
     * Uses Eventprime_Basic_Functions->get_single_event_detail when available for rich data.
     * Returns array with: id, title, slug, content, start_date, end_date, timezone, status,
     * venue (object|null), organizer (object|null), tickets (array), thumbnail, permalink, created_at, updated_at
     */
    protected function ep_format_event_object( $post_or_id ) {
        // If passed an ID, load post
        if ( is_numeric( $post_or_id ) ) {
            $post = get_post( absint( $post_or_id ) );
        } elseif ( is_object( $post_or_id ) && isset( $post_or_id->ID ) ) {
            $post = $post_or_id;
        } else {
            $post = null;
        }
        if ( empty( $post ) || $post->post_type !== 'em_event' ) return null;

        // Prefer core helper if available
        if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
            $ep = new Eventprime_Basic_Functions();
            if ( method_exists( $ep, 'get_single_event_detail' ) ) {
                // Call helper defensively: convert PHP warnings/notices to exceptions while calling.
                $ev = null;
                $prev_handler = set_error_handler(function($severity, $message, $file, $line) {
                    throw new ErrorException($message, 0, $severity, $file, $line);
                });
                try {
                    $ev = $ep->get_single_event_detail( $post->ID, $post );
                } catch ( Exception $e ) {
                    $ev = null;
                }
                // restore previous error handler
                restore_error_handler();

                // Normalize arrays to objects so property access below is safe
                if ( is_array( $ev ) ) $ev = (object) $ev;

                // normalize some common fields to a compact API-friendly shape
                // prefer image provided by core helper (which itself uses plugin meta/fallbacks)
                $thumbnail = null;
                if ( isset( $ev->image_url ) && ! empty( $ev->image_url ) ) {
                    $thumbnail = esc_url_raw( $ev->image_url );
                } else {
                    $thumbnail = get_the_post_thumbnail_url( $post->ID ) ?: null;
                }

                $out = array(
                    'id' => isset( $ev->ID ) && $ev->ID ? intval( $ev->ID ) : intval( $post->ID ),
                    'title' => isset( $ev->em_name ) ? sanitize_text_field( $ev->em_name ) : sanitize_text_field( $post->post_title ),
                    'slug' => isset( $ev->slug ) ? sanitize_text_field( $ev->slug ) : sanitize_title( $post->post_name ),
                    'content' => isset( $ev->description ) ? wp_kses_post( $ev->description ) : wp_kses_post( $post->post_content ),
                    'status' => $post->post_status,
                    'permalink' => esc_url_raw( get_permalink( $post->ID ) ),
                    'thumbnail' => $thumbnail,
                    'image_url' => isset( $ev->image_url ) ? esc_url_raw( $ev->image_url ) : $thumbnail,
                    'start_date' => isset( $ev->em_start_date_time ) ? date_i18n( 'c', (int) $ev->em_start_date_time ) : ( isset( $ev->em_start_date ) ? date_i18n( 'c', (int) $ev->em_start_date ) : null ),
                    'end_date' => isset( $ev->em_end_date_time ) ? date_i18n( 'c', (int) $ev->em_end_date_time ) : ( isset( $ev->em_end_date ) ? date_i18n( 'c', (int) $ev->em_end_date ) : null ),
                    'timezone' => $this->convert_to_iso( '' ),
                    'venue' => isset( $ev->venue_details ) ? $ev->venue_details : null,
                    'organizer' => isset( $ev->organizer_details ) ? $ev->organizer_details : null,
                    // Prefer helper-provided performers when available; normalize common shapes
                    'performers' => ( function() use ( $ev ) {
                        $outp = array();
                        if ( isset( $ev->performers ) && ! empty( $ev->performers ) ) {
                            $vals = $ev->performers;
                        } elseif ( isset( $ev->em_performer ) && ! empty( $ev->em_performer ) ) {
                            $vals = $ev->em_performer;
                        } elseif ( isset( $ev->em_performer_id ) && ! empty( $ev->em_performer_id ) ) {
                            $vals = $ev->em_performer_id;
                        } else {
                            $vals = array();
                        }
                        // normalize via integration helper if available
                        $helpers = $this->get_integration_helpers();
                        if ( $helpers && method_exists( $helpers, 'normalize_response' ) ) {
                            $vals = $helpers->normalize_response( $vals );
                        }
                        if ( is_numeric( $vals ) ) $vals = array( $vals );
                        if ( is_string( $vals ) ) {
                            $maybe = json_decode( $vals, true );
                            if ( is_array( $maybe ) ) $vals = $maybe; else $vals = array_map( 'trim', explode( ',', $vals ) );
                        }
                        if ( is_array( $vals ) ) {
                            foreach ( $vals as $it ) {
                                if ( is_numeric( $it ) ) {
                                    $p = get_post( absint( $it ) );
                                    if ( $p && $p->post_type === 'em_performer' ) {
                                        $outp[] = array( 'id' => $p->ID, 'name' => sanitize_text_field( $p->post_title ), 'thumbnail' => get_the_post_thumbnail_url( $p->ID ) ?: null );
                                    }
                                } elseif ( is_array( $it ) || is_object( $it ) ) {
                                    $id = isset( $it['id'] ) ? $it['id'] : ( isset( $it->id ) ? $it->id : ( isset( $it->ID ) ? $it->ID : 0 ) );
                                    $name = isset( $it['name'] ) ? $it['name'] : ( isset( $it->post_title ) ? $it->post_title : ( isset( $it->title ) ? $it->title : null ) );
                                    if ( $id ) {
                                        $p = get_post( absint( $id ) );
                                        if ( $p && $p->post_type === 'em_performer' ) {
                                            $outp[] = array( 'id' => $p->ID, 'name' => sanitize_text_field( $p->post_title ), 'thumbnail' => get_the_post_thumbnail_url( $p->ID ) ?: null );
                                        }
                                    } elseif ( $name ) {
                                        $pp = get_page_by_title( sanitize_text_field( $name ), OBJECT, 'em_performer' );
                                        if ( $pp ) $outp[] = array( 'id' => $pp->ID, 'name' => sanitize_text_field( $pp->post_title ), 'thumbnail' => get_the_post_thumbnail_url( $pp->ID ) ?: null );
                                    }
                                } elseif ( is_string( $it ) && trim( $it ) !== '' ) {
                                    $pp = get_page_by_title( sanitize_text_field( $it ), OBJECT, 'em_performer' );
                                    if ( $pp ) $outp[] = array( 'id' => $pp->ID, 'name' => sanitize_text_field( $pp->post_title ), 'thumbnail' => get_the_post_thumbnail_url( $pp->ID ) ?: null );
                                }
                            }
                        }
                        return $outp;
                    } )(),
                    // Prefer helper-provided event types, otherwise normalize em_event_type
                    'event_types' => ( function() use ( $ev ) {
                        $outt = array();
                        if ( isset( $ev->event_types ) && ! empty( $ev->event_types ) ) {
                            $ets = $ev->event_types;
                        } elseif ( isset( $ev->em_event_type ) && ! empty( $ev->em_event_type ) ) {
                            $ets = $ev->em_event_type;
                        } else {
                            $ets = array();
                        }
                        $helpers = $this->get_integration_helpers();
                        if ( $helpers && method_exists( $helpers, 'normalize_response' ) ) {
                            $ets = $helpers->normalize_response( $ets );
                        }
                        if ( is_numeric( $ets ) ) $ets = array( $ets );
                        if ( is_string( $ets ) ) {
                            $maybe = json_decode( $ets, true );
                            if ( is_array( $maybe ) ) $ets = $maybe; else $ets = array_map( 'trim', explode( ',', $ets ) );
                        }
                        if ( is_array( $ets ) ) {
                            foreach ( $ets as $e ) {
                                if ( is_numeric( $e ) ) {
                                    $term = get_term( absint( $e ), 'em_event_type' );
                                    if ( $term && ! is_wp_error( $term ) ) $outt[] = array( 'id' => $term->term_id, 'name' => sanitize_text_field( $term->name ) );
                                } elseif ( is_array( $e ) || is_object( $e ) ) {
                                    $tid = isset( $e['term_id'] ) ? $e['term_id'] : ( isset( $e->term_id ) ? $e->term_id : 0 );
                                    $tname = isset( $e['name'] ) ? $e['name'] : ( isset( $e->name ) ? $e->name : null );
                                    if ( $tid ) {
                                        $term = get_term( absint( $tid ), 'em_event_type' );
                                        if ( $term && ! is_wp_error( $term ) ) $outt[] = array( 'id' => $term->term_id, 'name' => sanitize_text_field( $term->name ) );
                                    } elseif ( $tname ) {
                                        $tt = get_term_by( 'name', sanitize_text_field( $tname ), 'em_event_type' );
                                        if ( $tt && ! is_wp_error( $tt ) ) $outt[] = array( 'id' => $tt->term_id, 'name' => sanitize_text_field( $tt->name ) );
                                    }
                                } elseif ( is_string( $e ) && trim( $e ) !== '' ) {
                                    $tt = get_term_by( 'name', sanitize_text_field( $e ), 'em_event_type' );
                                    if ( $tt && ! is_wp_error( $tt ) ) $outt[] = array( 'id' => $tt->term_id, 'name' => sanitize_text_field( $tt->name ) );
                                }
                            }
                        }
                        return $outt;
                    } )(),
                    'tickets' => ( function() use ( $ev, $post ) {
                        $outt = array();
                        $vals = array();
                        $helpers = $this->get_integration_helpers();

                        // Try multiple possible properties the core helper may use
                        $candidates = array( 'tickets', 'all_tickets_data', 'all_tickets', 'all_ticket_data', 'ticket_list' );
                        foreach ( $candidates as $k ) {
                            if ( isset( $ev->{$k} ) && ! empty( $ev->{$k} ) ) {
                                $vals = $ev->{$k};
                                break;
                            }
                        }

                        // If still empty, attempt to fetch via integration helper's ticket function
                        if ( empty( $vals ) && $helpers && method_exists( $helpers, 'ep_get_tickets_by_event' ) ) {
                            $res = $helpers->ep_get_tickets_by_event( array( 'event_id' => $post->ID ) );
                            if ( is_array( $res ) && isset( $res['status'] ) && $res['status'] === 'success' && isset( $res['tickets'] ) ) {
                                $vals = $res['tickets'];
                            }
                        }

                        if ( ! empty( $vals ) ) {
                            if ( $helpers && method_exists( $helpers, 'normalize_response' ) ) {
                                $vals = $helpers->normalize_response( $vals );
                            }
                            if ( is_object( $vals ) ) $vals = (array) $vals;
                            if ( ! is_array( $vals ) ) $vals = array( $vals );
                            foreach ( $vals as $t ) {
                                $tid = null;
                                $tname = '';
                                $tprice = null;
                                $tcap = null;
                                if ( is_array( $t ) ) {
                                    $tid = isset( $t['ID'] ) ? $t['ID'] : ( isset( $t['id'] ) ? $t['id'] : ( isset( $t['ticket_id'] ) ? $t['ticket_id'] : null ) );
                                    $tname = isset( $t['name'] ) ? $t['name'] : ( isset( $t['title'] ) ? $t['title'] : ( isset( $t['post_title'] ) ? $t['post_title'] : '' ) );
                                    $tprice = isset( $t['price'] ) ? $t['price'] : ( isset( $t['em_price'] ) ? $t['em_price'] : null );
                                    $tcap = isset( $t['capacity'] ) ? $t['capacity'] : ( isset( $t['em_capacity'] ) ? $t['em_capacity'] : null );
                                } elseif ( is_object( $t ) ) {
                                    $tid = isset( $t->ID ) ? $t->ID : ( isset( $t->id ) ? $t->id : ( isset( $t->ticket_id ) ? $t->ticket_id : null ) );
                                    $tname = isset( $t->name ) ? $t->name : ( isset( $t->title ) ? $t->title : ( isset( $t->post_title ) ? $t->post_title : '' ) );
                                    $tprice = isset( $t->price ) ? $t->price : ( isset( $t->em_price ) ? $t->em_price : null );
                                    $tcap = isset( $t->capacity ) ? $t->capacity : ( isset( $t->em_capacity ) ? $t->em_capacity : null );
                                } elseif ( is_numeric( $t ) ) {
                                    $tid = $t;
                                    $tp = get_post( absint( $tid ) );
                                    if ( $tp ) $tname = $tp->post_title;
                                    $tprice = $tp ? get_post_meta( $tp->ID, 'em_price', true ) : null;
                                    $tcap = $tp ? get_post_meta( $tp->ID, 'em_capacity', true ) : null;
                                }
                                // sanitize and normalize
                                $entry = array();
                                $entry['id'] = $tid ? intval( $tid ) : null;
                                $entry['name'] = $tname ? sanitize_text_field( $tname ) : '';
                                $entry['price'] = $tprice !== null && $tprice !== '' ? floatval( $tprice ) : 0;
                                $entry['capacity'] = $tcap !== null && $tcap !== '' ? intval( $tcap ) : null;
                                $outt[] = $entry;
                            }
                        }
                        return $outt;
                    } )(),
                    // preserve raw helper response for clients that want the full original structure
                    'all_tickets_data' => isset( $ev->all_tickets_data ) ? $ev->all_tickets_data : ( isset( $ev->all_tickets ) ? $ev->all_tickets : array() ),
                    'raw' => is_object( $ev ) ? (array) $ev : $ev,
                    // include post meta (raw) so external integrators can access any extra fields
                    'meta' => get_post_meta( $post->ID ),
                    // Include ticket categories (only id/name/capacity — do NOT include nested tickets)
                    'ticket_categories' => ( function() use ( $ev, $post, $ep ) {
                        try {
                            $cats_out = array();
                            $cats = array();
                            if ( isset( $ev->ticket_categories ) && ! empty( $ev->ticket_categories ) ) {
                                $cats = $ev->ticket_categories;
                            } else {
                                // Try core functions to fetch categories from DB
                                if ( isset( $ep ) && method_exists( $ep, 'get_multiple_events_ticket_category' ) ) {
                                    $res = $ep->get_multiple_events_ticket_category( array( $post->ID ) );
                                    if ( ! empty( $res ) ) $cats = $res;
                                }
                            }

                            if ( ! empty( $cats ) ) {
                                // normalize to array (may be array or object)
                                if ( is_object( $cats ) ) {
                                    $cats = array( $cats );
                                }
                                foreach ( $cats as $cat ) {
                                    // normalize each category to array
                                    if ( is_object( $cat ) ) {
                                        $cat = (array) $cat;
                                    }
                                    $cat_id = isset( $cat['id'] ) ? intval( $cat['id'] ) : 0;
                                    $cat_name = isset( $cat['name'] ) ? sanitize_text_field( $cat['name'] ) : '';
                                    $cat_capacity = isset( $cat['capacity'] ) && $cat['capacity'] !== '' ? intval( $cat['capacity'] ) : null;
                                    $cats_out[] = array(
                                        'id' => $cat_id,
                                        'event_id' => intval( $post->ID ),
                                        'name' => $cat_name,
                                        'capacity' => $cat_capacity,
                                    );
                                }
                            }
                            return $cats_out;
                        } catch ( Exception $e ) {
                            return array();
                        }
                    } )(),
                        'created_at' => isset( $post->post_date ) ? $post->post_date : null,
                        'updated_at' => isset( $post->post_modified ) ? $post->post_modified : null,
                    );

                    // If helper returned no venue details, attempt a robust fallback using
                    // the event's em_venue field (which may be numeric, array, JSON, or
                    // assigned as a taxonomy term only). This ensures events have a
                    // venue when possible even if the core helper did not populate it.
                    if ( empty( $out['venue'] ) ) {
                        $venue_meta = isset( $ev->em_venue ) ? $ev->em_venue : get_post_meta( $post->ID, 'em_venue', true );
                        $venue_candidate = null;
                        if ( is_numeric( $venue_meta ) ) {
                            $venue_candidate = absint( $venue_meta );
                        } elseif ( is_array( $venue_meta ) && count( $venue_meta ) ) {
                            $venue_candidate = absint( $venue_meta[0] );
                        } elseif ( is_string( $venue_meta ) ) {
                            $maybe = json_decode( $venue_meta, true );
                            if ( is_array( $maybe ) && count( $maybe ) ) {
                                $venue_candidate = absint( $maybe[0] );
                            } elseif ( strpos( $venue_meta, ',' ) !== false ) {
                                $parts = array_map( 'trim', explode( ',', $venue_meta ) );
                                $venue_candidate = isset( $parts[0] ) ? absint( $parts[0] ) : null;
                            } else {
                                $venue_candidate = absint( $venue_meta );
                            }
                        }
                        if ( empty( $venue_candidate ) ) {
                            $tids = wp_get_object_terms( $post->ID, 'em_venue', array( 'fields' => 'ids' ) );
                            if ( ! is_wp_error( $tids ) && ! empty( $tids ) ) {
                                $venue_candidate = absint( $tids[0] );
                            }
                        }
                        if ( ! empty( $venue_candidate ) ) {
                            if ( method_exists( $ep, 'ep_get_venue_by_id' ) ) {
                                $vd = $ep->ep_get_venue_by_id( $venue_candidate );
                                if ( ! empty( $vd ) ) {
                                    $out['venue'] = $vd;
                                } else {
                                    $term = get_term( $venue_candidate, 'em_venue' );
                                    if ( $term && ! is_wp_error( $term ) ) $out['venue'] = array( 'id' => $term->term_id, 'name' => sanitize_text_field( $term->name ) );
                                }
                            } else {
                                $term = get_term( $venue_candidate, 'em_venue' );
                                if ( $term && ! is_wp_error( $term ) ) $out['venue'] = array( 'id' => $term->term_id, 'name' => sanitize_text_field( $term->name ) );
                            }
                        }
                    }

                    return $out;
            }
        }

        // Fallback: use normalize_event + basic ticket lookup
        $base = $this->normalize_event( $post );
        $out = array(
            'id' => $base['id'],
            'title' => $base['title'],
            'slug' => sanitize_title( $post->post_name ),
            'content' => $base['content'],
            'status' => $base['status'],
            'permalink' => $base['permalink'],
            'thumbnail' => $base['thumbnail'],
            'start_date' => $base['start_date'],
            'end_date' => $base['end_date'],
            'venue' => null,
            'organizer' => null,
            'tickets' => array(),
            'ticket_categories' => array(),
            'created_at' => $post->post_date,
            'updated_at' => $post->post_modified,
        );
        $helpers = $this->get_integration_helpers();
        // attach venue/organizer names or richer details if available
        if ( ! empty( $base['venue_id'] ) ) {
            $v = get_term( $base['venue_id'], 'em_venue' );
            if ( $v && ! is_wp_error( $v ) ) {
                // prefer core helper for venue details when available
                if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
                    $ep = isset( $ep ) && $ep instanceof Eventprime_Basic_Functions ? $ep : new Eventprime_Basic_Functions();
                    if ( method_exists( $ep, 'ep_get_venue_by_id' ) ) {
                        $venue_details = $ep->ep_get_venue_by_id( $v->term_id );
                        if ( ! empty( $venue_details ) ) {
                            $out['venue'] = $venue_details;
                        } else {
                            $out['venue'] = array( 'id' => $v->term_id, 'name' => sanitize_text_field( $v->name ) );
                        }
                    } else {
                        $out['venue'] = array( 'id' => $v->term_id, 'name' => sanitize_text_field( $v->name ) );
                    }
                } else {
                    $out['venue'] = array( 'id' => $v->term_id, 'name' => sanitize_text_field( $v->name ) );
                }
            }
        }

        if ( ! empty( $base['organizer_id'] ) ) {
            // use the em_event_organizer taxonomy which the rest of the plugin uses
            $o = get_term( $base['organizer_id'], 'em_event_organizer' );
            if ( $o && ! is_wp_error( $o ) ) {
                if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
                    $ep = isset( $ep ) && $ep instanceof Eventprime_Basic_Functions ? $ep : new Eventprime_Basic_Functions();
                    if ( method_exists( $ep, 'get_single_organizer' ) ) {
                        $org = $ep->get_single_organizer( $o->term_id, $o );
                        if ( $org ) {
                            // ensure array shape
                            $out['organizer'] = is_object( $org ) ? (array) $org : $org;
                        } else {
                            $out['organizer'] = array( 'id' => $o->term_id, 'name' => sanitize_text_field( $o->name ) );
                        }
                    } else {
                        $out['organizer'] = array( 'id' => $o->term_id, 'name' => sanitize_text_field( $o->name ) );
                    }
                } else {
                    $out['organizer'] = array( 'id' => $o->term_id, 'name' => sanitize_text_field( $o->name ) );
                }
            }
        }

        // attach performers (may be stored as em_performer meta in various shapes)
        $performers = array();
        $raw_perf = get_post_meta( $post->ID, 'em_performer', true );
        if ( empty( $raw_perf ) ) {
            // some installs use em_performer_id meta
            $raw_perf = get_post_meta( $post->ID, 'em_performer_id', true );
        }
        // Use integration normalizer if available to decode serialized/JSON strings
        if ( $helpers && method_exists( $helpers, 'normalize_response' ) ) {
            $raw_perf = $helpers->normalize_response( $raw_perf );
        }
        // Normalize into an array of candidate ids/names
        $perf_ids = array();
        if ( is_numeric( $raw_perf ) ) {
            $perf_ids[] = absint( $raw_perf );
        } elseif ( is_string( $raw_perf ) ) {
            // maybe a JSON array or comma-separated list
            $maybe = json_decode( $raw_perf, true );
            if ( is_array( $maybe ) ) {
                $raw_perf = $maybe;
            } else {
                $parts = preg_split( '/\s*,\s*/', trim( $raw_perf ) );
                if ( is_array( $parts ) && count( $parts ) > 1 ) {
                    $raw_perf = $parts;
                }
            }
        }
        if ( is_array( $raw_perf ) ) {
            foreach ( $raw_perf as $item ) {
                if ( is_numeric( $item ) ) {
                    $perf_ids[] = absint( $item );
                } elseif ( is_array( $item ) && isset( $item['id'] ) ) {
                    $perf_ids[] = absint( $item['id'] );
                } elseif ( is_object( $item ) && ( isset( $item->ID ) || isset( $item->id ) ) ) {
                    $perf_ids[] = absint( isset( $item->ID ) ? $item->ID : $item->id );
                } elseif ( is_string( $item ) && trim( $item ) !== '' ) {
                    // try to resolve by performer title
                    $p = get_page_by_title( sanitize_text_field( $item ), OBJECT, 'em_performer' );
                    if ( $p && ! is_wp_error( $p ) ) $perf_ids[] = $p->ID;
                }
            }
        }
        $perf_ids = array_values( array_unique( array_filter( $perf_ids ) ) );
        if ( ! empty( $perf_ids ) ) {
            foreach ( $perf_ids as $pid ) {
                $p = get_post( $pid );
                if ( $p && $p->post_type === 'em_performer' ) {
                    $performers[] = array( 'id' => $p->ID, 'name' => sanitize_text_field( $p->post_title ), 'thumbnail' => get_the_post_thumbnail_url( $p->ID ) ?: null );
                }
            }
        }
        $out['performers'] = $performers;

        // attach event types (taxonomy em_event_type) — try taxonomy first, then post meta
        $event_types = array();
        $ets = wp_get_object_terms( $post->ID, 'em_event_type', array( 'fields' => 'all' ) );
        if ( is_wp_error( $ets ) || empty( $ets ) ) {
            $et_meta = get_post_meta( $post->ID, 'em_event_type', true );
            if ( $helpers && method_exists( $helpers, 'normalize_response' ) ) {
                $et_meta = $helpers->normalize_response( $et_meta );
            }
            // et_meta may be single id, array of ids, or array of term objects
            if ( is_numeric( $et_meta ) ) {
                $term = get_term( absint( $et_meta ), 'em_event_type' );
                if ( $term && ! is_wp_error( $term ) ) $event_types[] = array( 'id' => $term->term_id, 'name' => sanitize_text_field( $term->name ) );
            } elseif ( is_array( $et_meta ) ) {
                foreach ( $et_meta as $eitem ) {
                    if ( is_numeric( $eitem ) ) {
                        $term = get_term( absint( $eitem ), 'em_event_type' );
                        if ( $term && ! is_wp_error( $term ) ) $event_types[] = array( 'id' => $term->term_id, 'name' => sanitize_text_field( $term->name ) );
                    } elseif ( is_string( $eitem ) && trim( $eitem ) !== '' ) {
                        // try find by name
                        $t = get_term_by( 'name', sanitize_text_field( $eitem ), 'em_event_type' );
                        if ( $t && ! is_wp_error( $t ) ) $event_types[] = array( 'id' => $t->term_id, 'name' => sanitize_text_field( $t->name ) );
                    } elseif ( is_object( $eitem ) && isset( $eitem->term_id ) ) {
                        $event_types[] = array( 'id' => intval( $eitem->term_id ), 'name' => sanitize_text_field( $eitem->name ) );
                    }
                }
            }
        } else {
            foreach ( $ets as $et ) {
                $event_types[] = array( 'id' => intval( $et->term_id ), 'name' => sanitize_text_field( $et->name ) );
            }
        }
        $out['event_types'] = $event_types;

        // attach tickets from post type em_ticket if present
        $tickets = array();
        $tq = get_posts( array( 'post_type' => 'em_ticket', 'posts_per_page' => 50, 'meta_query' => array( array( 'key' => 'em_event', 'value' => $post->ID ) ) ) );
        if ( $tq ) {
            foreach ( $tq as $t ) {
                $tickets[] = array( 'id' => $t->ID, 'name' => sanitize_text_field( $t->post_title ), 'price' => get_post_meta( $t->ID, 'em_price', true ), 'capacity' => get_post_meta( $t->ID, 'em_capacity', true ) );
            }
        }
        $out['tickets'] = $tickets;
        return $out;
    }

    /* ---------- Additional handlers (CRUD) ---------- */

    public function handle_event_update( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_event' ) {
            return new WP_Error( 'not_found', 'Event not found.', array( 'status' => 404 ) );
        }
        $body = $request->get_body_params();
        $update = array( 'ID' => $id );
        if ( isset( $body['em_name'] ) ) $update['post_title'] = sanitize_text_field( $body['em_name'] );
        if ( isset( $body['description'] ) ) $update['post_content'] = sanitize_textarea_field( $body['description'] );
        if ( ! empty( $update ) ) {
            wp_update_post( $update );
        }
        if ( isset( $body['em_start_date'] ) ) update_post_meta( $id, 'em_start_date', sanitize_text_field( $body['em_start_date'] ) );
        if ( isset( $body['em_end_date'] ) ) update_post_meta( $id, 'em_end_date', sanitize_text_field( $body['em_end_date'] ) );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'event' => array( 'id' => $id ) ) );
    }

    public function handle_event_delete( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_event' ) {
            return new WP_Error( 'not_found', 'Event not found.', array( 'status' => 404 ) );
        }
        wp_delete_post( $id, true );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 0, 'message' => 'Event deleted.' ) );
    }

    public function handle_event_ticket_create( WP_REST_Request $request ) {
        $event_id = absint( $request->get_param( 'id' ) );
        $body = $request->get_body_params();
        $name = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
        $price = isset( $body['price'] ) ? floatval( $body['price'] ) : 0;
        if ( empty( $event_id ) || empty( $name ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing ticket data.' ), 400 );
        }
        // Try to use Eventprime_Basic_Functions to create ticket
        if ( class_exists( 'Eventprime_Basic_Functions' ) ) {
            $ep = new Eventprime_Basic_Functions();
            if ( method_exists( $ep, 'ep_create_ticket' ) ) {
                $tid = $ep->ep_create_ticket( $event_id, array( 'name' => $name, 'price' => $price ) );
                if ( $tid ) return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'ticket' => array( 'id' => $tid ) ) );
            }
        }
        // fallback: create a custom post type 'em_ticket' if available
        $post = array( 'post_title' => $name, 'post_status' => 'publish', 'post_type' => 'em_ticket' );
        $tid = wp_insert_post( $post );
        if ( $tid && ! is_wp_error( $tid ) ) {
            update_post_meta( $tid, 'em_event', $event_id );
            update_post_meta( $tid, 'em_price', $price );
            return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'ticket' => array( 'id' => $tid ) ) );
        }
        return rest_ensure_response( array( 'status' => 'error', 'message' => 'Failed to create ticket.' ), 500 );
    }

    public function handle_tickets_list( WP_REST_Request $request ) {
        $params = $request->get_query_params();
        $event_id = isset( $params['event_id'] ) ? absint( $params['event_id'] ) : 0;
        $args = array( 'post_type' => 'em_ticket', 'posts_per_page' => 20 );
        if ( $event_id ) $args['meta_query'] = array( array( 'key' => 'em_event', 'value' => $event_id ) );
        $q = get_posts( $args );
        $out = array();
        $epf = new Eventprime_Basic_Functions();
        $currency = $epf->ep_get_global_settings('currency');
        foreach ( $q as $t ) {
            $out[] = array(
                'ticket_id' => $t->ID,
                'event_id' => intval( get_post_meta( $t->ID, 'em_event', true ) ),
                'type' => sanitize_text_field( $t->post_title ),
                'price' => get_post_meta( $t->ID, 'em_price', true ) !== '' ? floatval( get_post_meta( $t->ID, 'em_price', true ) ) : 0,
                'currency' => $currency ? $currency : 'USD',
                'available' => get_post_meta( $t->ID, 'em_capacity', true ) !== '' ? intval( get_post_meta( $t->ID, 'em_capacity', true ) ) : null,
                'created_at' => isset( $t->post_date ) ? $t->post_date : null,
            );
        }

        $resp = rest_ensure_response( array( 'status' => 'success', 'count' => count( $out ), 'tickets' => $out ) );
        return $resp;
    }

        

    public function handle_ticket_create( WP_REST_Request $request ) {
        // Support both form and JSON requests. Note: integration dispatcher may set
        // body params from query params which can hide a raw JSON payload. To be
        // robust, always prefer explicit JSON body values when present by merging
        // any JSON params into the parsed body params so fields like 'name' are
        // not lost when client posts JSON with action in query string.
        $body = $request->get_body_params();
        if ( empty( $body ) || ! is_array( $body ) ) {
            $body = $request->get_json_params();
        } else {
            // Merge JSON body params (if any) so they override query-derived body params
            $json_body = $request->get_json_params();
            if ( ! empty( $json_body ) && is_array( $json_body ) ) {
                $body = array_merge( $body, $json_body );
            }
        }
        $event_id = isset( $body['event_id'] ) ? absint( $body['event_id'] ) : 0;
        $name = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
        $price = isset( $body['price'] ) ? floatval( $body['price'] ) : 0;
        $quantity = isset( $body['quantity'] ) ? intval( $body['quantity'] ) : ( isset( $body['capacity'] ) ? intval( $body['capacity'] ) : 0 );
        $category_name = isset( $body['category_name'] ) ? sanitize_text_field( $body['category_name'] ) : '';
        $category_capacity = isset( $body['category_capacity'] ) ? intval( $body['category_capacity'] ) : ( $quantity > 0 ? $quantity : 0 );
        if ( empty( $name ) ) return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing name.' ), 400 );
        // Try core helper first (preferred)
        if ( $event_id && class_exists( 'Eventprime_Basic_Functions' ) ) {
            $ep = new Eventprime_Basic_Functions();
            if ( method_exists( $ep, 'ep_create_ticket' ) ) {
                $tid = $ep->ep_create_ticket( $event_id, array( 'name' => $name, 'price' => $price, 'capacity' => $quantity ) );
                if ( $tid ) return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'ticket' => array( 'id' => $tid ) ) );
            }
        }

        // If EventPrime DB handler is available and event_id provided, create DB category (if provided)
        $db_ticket_id = 0;
        if ( $event_id && class_exists( 'EP_DBhandler' ) ) {
            try {
                $dbhandler = new EP_DBhandler();
                $cat_id = 0;
                if ( ! empty( $category_name ) ) {
                    $cat_table_name = 'TICKET_CATEGORIES';
                    $cat_data = array(
                        'event_id' => $event_id,
                        'name' => $category_name,
                        'capacity' => $category_capacity,
                        'priority' => 1,
                        'status' => 1,
                        'created_by' => get_current_user_id(),
                        'created_at' => wp_date( "Y-m-d H:i:s", time() ),
                    );
                    $cat_id = $dbhandler->insert_row( $cat_table_name, $cat_data );
                }

                // Create ticket row in TICKET table for better integration with plugin
                $price_options_table = 'TICKET';
                // Prefer DBhandler helpers which build the expected field shape
                $ep_activator = class_exists( 'Eventprime_Event_Calendar_Management_Activator' ) ? new Eventprime_Event_Calendar_Management_Activator() : null;
                // Normalize body keys so DB handler receives expected fields (offers, ep_additional_ticket_fee_data)
                $body_map = is_array( $body ) ? $body : ( is_object( $body ) ? (array) $body : array() );
                if ( isset( $body_map['offer'] ) && ! isset( $body_map['offers'] ) ) {
                    $body_map['offers'] = $body_map['offer'];
                }
                // Ensure offers is an array
                if ( isset( $body_map['offers'] ) && ! isset( $body_map['offers'][0] ) ) {
                    $body_map['offers'] = array( $body_map['offers'] );
                }
                // Normalize offers elements to include expected keys and encode to JSON
                if ( isset( $body_map['offers'] ) && is_array( $body_map['offers'] ) ) {
                    foreach ( $body_map['offers'] as $ofk => $offer_item ) {
                        if ( is_object( $offer_item ) ) $offer_item = (array) $offer_item;
                        if ( ! is_array( $offer_item ) ) $offer_item = array();
                        // Map lightweight integration keys to admin fields
                        if ( isset( $offer_item['type'] ) && ! isset( $offer_item['em_ticket_offer_discount_type'] ) ) {
                            $offer_item['em_ticket_offer_discount_type'] = sanitize_text_field( $offer_item['type'] );
                        }
                        if ( isset( $offer_item['value'] ) && ! isset( $offer_item['em_ticket_offer_discount'] ) ) {
                            $offer_item['em_ticket_offer_discount'] = $offer_item['value'];
                        }
                        if ( isset( $offer_item['eligible'] ) && ! isset( $offer_item['em_ticket_offer_user_roles'] ) ) {
                            $offer_item['em_ticket_offer_user_roles'] = is_array( $offer_item['eligible'] ) ? $offer_item['eligible'] : array( $offer_item['eligible'] );
                        }
                        if ( isset( $offer_item['name'] ) && ! isset( $offer_item['em_ticket_offer_name'] ) ) {
                            $offer_item['em_ticket_offer_name'] = sanitize_text_field( $offer_item['name'] );
                        }
                        if ( isset( $offer_item['description'] ) && ! isset( $offer_item['em_ticket_offer_description'] ) ) {
                            $offer_item['em_ticket_offer_description'] = sanitize_textarea_field( $offer_item['description'] );
                        }
                        $defaults = array(
                            'uid' => '',
                            'em_offer_start_booking_type' => '',
                            'em_offer_start_booking_date' => '',
                            'em_offer_start_booking_time' => '',
                            'em_offer_start_booking_days' => '',
                            'em_offer_start_booking_days_option' => '',
                            'em_offer_start_booking_event_option' => '',
                            'em_offer_ends_booking_type' => '',
                            'em_offer_ends_booking_date' => '',
                            'em_offer_ends_booking_time' => '',
                            'em_offer_ends_booking_days' => '',
                            'em_offer_ends_booking_days_option' => '',
                            'em_offer_ends_booking_event_option' => '',
                            'em_ticket_show_offer_detail' => '',
                        );
                        foreach ( $defaults as $dk => $dv ) {
                            if ( ! isset( $offer_item[ $dk ] ) ) $offer_item[ $dk ] = $dv;
                        }
                        $body_map['offers'][ $ofk ] = $offer_item;
                    }
                    $body_map['offers'] = wp_json_encode( $body_map['offers'] );
                }
                $raw_additional_fees = null;
                if ( isset( $body_map['additional_fees'] ) ) {
                    $raw_additional_fees = $body_map['additional_fees'];
                } elseif ( isset( $body_map['ep_additional_ticket_fee_data'] ) ) {
                    $raw_additional_fees = $body_map['ep_additional_ticket_fee_data'];
                }
                $normalized_additional_fees = $this->ep_normalize_additional_fees( $raw_additional_fees );
                if ( ! empty( $normalized_additional_fees ) ) {
                    $body_map['ep_additional_ticket_fee_data'] = $normalized_additional_fees;
                    $body_map['additional_fees'] = $normalized_additional_fees;
                } else {
                    unset( $body_map['ep_additional_ticket_fee_data'], $body_map['additional_fees'] );
                }
                // Normalize capacity/quantity for DB handler
                if ( ! isset( $body_map['capacity'] ) ) {
                    if ( isset( $body_map['quantity'] ) ) {
                        $body_map['capacity'] = intval( $body_map['quantity'] );
                    } else {
                        $body_map['capacity'] = $quantity;
                    }
                }
                if ( ! isset( $body_map['quantity'] ) ) {
                    $body_map['quantity'] = isset( $body_map['capacity'] ) ? intval( $body_map['capacity'] ) : $quantity;
                }
                if ( ! empty( $cat_id ) && method_exists( $dbhandler, 'ep_add_tickets_in_category' ) ) {
                    $ticket_data = $dbhandler->ep_add_tickets_in_category( $cat_id, $event_id, (array) $body_map, 1 );
                } else {
                    $ticket_data = $dbhandler->ep_add_individual_tickets( $event_id, (array) $body_map );
                }
                $format = array();
                if ( $ep_activator ) {
                    foreach ( $ticket_data as $key => $value ) {
                        $format[] = $ep_activator->get_db_table_field_type( 'TICKET', $key );
                    }
                }
                $db_ticket_id = $dbhandler->insert_row( $price_options_table, $ticket_data, $format );
                do_action( 'ep_update_insert_ticket_additional_data', $db_ticket_id, $body, $event_id );
            } catch ( Exception $e ) {
                // ignore DB errors and fallback to CPT creation below
                $db_ticket_id = 0;
            }
        }

        // Create fallback CPT 'em_ticket' for compatibility / UI visibility
        $post = array( 'post_title' => $name, 'post_status' => 'publish', 'post_type' => 'em_ticket' );
        $tid = wp_insert_post( $post );
        if ( $tid && ! is_wp_error( $tid ) ) {
            if ( $event_id ) update_post_meta( $tid, 'em_event', $event_id );
            update_post_meta( $tid, 'em_price', $price );
            if ( $quantity > 0 ) update_post_meta( $tid, 'em_capacity', $quantity );
            if ( $db_ticket_id ) update_post_meta( $tid, 'em_ticket_db_id', $db_ticket_id );
            // store category info on CPT for clients that read CPT metadata
            if ( ! empty( $category_name ) ) {
                update_post_meta( $tid, 'em_ticket_category_name', $category_name );
                update_post_meta( $tid, 'em_ticket_category_capacity', $category_capacity );
            }
            // Persist extras
            if ( isset( $body['special_price'] ) ) update_post_meta( $tid, 'em_special_price', $body['special_price'] );
            $normalized_meta_fees = $this->ep_normalize_additional_fees( isset( $body['additional_fees'] ) ? $body['additional_fees'] : array() );
            if ( ! empty( $normalized_meta_fees ) ) {
                update_post_meta( $tid, 'em_additional_fees', wp_json_encode( $normalized_meta_fees ) );
            }
            if ( isset( $body['booking_starts'] ) ) update_post_meta( $tid, 'em_booking_starts', is_array( $body['booking_starts'] ) ? wp_json_encode( $body['booking_starts'] ) : sanitize_text_field( $body['booking_starts'] ) );
            if ( isset( $body['booking_ends'] ) ) update_post_meta( $tid, 'em_booking_ends', is_array( $body['booking_ends'] ) ? wp_json_encode( $body['booking_ends'] ) : sanitize_text_field( $body['booking_ends'] ) );
            if ( isset( $body['allow_cancellation'] ) ) update_post_meta( $tid, 'em_allow_cancellation', intval( $body['allow_cancellation'] ) );
            if ( isset( $body['min_ticket_no'] ) ) update_post_meta( $tid, 'em_min_ticket_no', intval( $body['min_ticket_no'] ) );
            if ( isset( $body['max_ticket_no'] ) ) update_post_meta( $tid, 'em_max_ticket_no', intval( $body['max_ticket_no'] ) );
            if ( isset( $body['offer'] ) ) {
                update_post_meta( $tid, 'em_offer', is_array( $body['offer'] ) ? wp_json_encode( $body['offer'] ) : sanitize_text_field( $body['offer'] ) );
            } elseif ( isset( $body['offers'] ) ) {
                update_post_meta( $tid, 'em_offer', is_array( $body['offers'] ) ? wp_json_encode( $body['offers'] ) : sanitize_text_field( $body['offers'] ) );
            }
            return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'ticket' => array( 'id' => $tid, 'db_id' => $db_ticket_id ) ) );
        }
        return rest_ensure_response( array( 'status' => 'error', 'message' => 'Failed to create ticket.' ), 500 );
    }

    public function handle_ticket_get( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_ticket' ) return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'ticket' => array( 'id' => $post->ID, 'name' => sanitize_text_field( $post->post_title ), 'price' => get_post_meta( $post->ID, 'em_price', true ), 'event_id' => get_post_meta( $post->ID, 'em_event', true ) ) ) );
    }

    public function handle_ticket_update( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_ticket' ) return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        $body = $request->get_body_params();
        $update = array( 'ID' => $id );
        if ( isset( $body['name'] ) ) $update['post_title'] = sanitize_text_field( $body['name'] );
        if ( ! empty( $update ) ) wp_update_post( $update );
        if ( isset( $body['price'] ) ) update_post_meta( $id, 'em_price', floatval( $body['price'] ) );
        if ( isset( $body['event_id'] ) ) update_post_meta( $id, 'em_event', absint( $body['event_id'] ) );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'ticket' => array( 'id' => $id ) ) );
    }

    public function handle_ticket_delete( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_ticket' ) return new WP_Error( 'not_found', 'Ticket not found.', array( 'status' => 404 ) );
        wp_delete_post( $id, true );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 0, 'message' => 'Ticket deleted.' ) );
    }

    public function handle_booking_get( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_booking' ) return new WP_Error( 'not_found', 'Booking not found.', array( 'status' => 404 ) );
        $data = array( 'id' => $post->ID, 'status' => get_post_meta( $post->ID, 'em_status', true ), 'event_id' => get_post_meta( $post->ID, 'em_event', true ), 'user' => get_post_meta( $post->ID, 'em_user', true ) );
        return rest_ensure_response( array( 'status' => 'success', 'booking' => $data ) );
    }

    public function handle_booking_update( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_booking' ) return new WP_Error( 'not_found', 'Booking not found.', array( 'status' => 404 ) );
        $body = $request->get_body_params();
        if ( isset( $body['status'] ) ) update_post_meta( $id, 'em_status', sanitize_text_field( $body['status'] ) );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'booking' => array( 'id' => $id ) ) );
    }

    public function handle_booking_delete( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $post = get_post( $id );
        if ( empty( $post ) || $post->post_type !== 'em_booking' ) return new WP_Error( 'not_found', 'Booking not found.', array( 'status' => 404 ) );
        wp_delete_post( $id, true );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 0, 'message' => 'Booking deleted.' ) );
    }

    public function handle_venue_create( WP_REST_Request $request ) {
        $body = $this->ep_normalize_body_params( $request );
        if ( empty( $body['name'] ) && $request->get_param( 'name' ) ) {
            $body['name'] = $request->get_param( 'name' );
        }
        if ( empty( $body['description'] ) && $request->get_param( 'description' ) ) {
            $body['description'] = $request->get_param( 'description' );
        }
        $name = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
        if ( empty( $name ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing name.' ), 400 );
        }
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Core functions unavailable.' ), 500 );
        }

        $payload = array( 'name' => $name );
        $text_fields = array(
            'description'                    => 'sanitize_textarea_field',
            'em_address'                     => 'sanitize_text_field',
            'em_lat'                         => 'sanitize_text_field',
            'em_lng'                         => 'sanitize_text_field',
            'em_locality'                    => 'sanitize_text_field',
            'em_state'                       => 'sanitize_text_field',
            'em_country'                     => 'sanitize_text_field',
            'em_postal_code'                 => 'sanitize_text_field',
            'em_zoom_level'                  => 'sanitize_text_field',
            'em_established'                 => 'sanitize_text_field',
            'em_seating_organizer'           => 'sanitize_text_field',
            'em_type'                        => 'sanitize_text_field',
            'em_facebook_page'               => 'esc_url_raw',
            'em_instagram_page'              => 'esc_url_raw',
        );
        foreach ( $text_fields as $field => $callback ) {
            if ( isset( $body[ $field ] ) ) {
                $payload[ $field ] = call_user_func( $callback, $body[ $field ] );
            }
        }
        if ( isset( $body['em_image_id'] ) ) {
            if ( is_array( $body['em_image_id'] ) ) {
                $first = reset( $body['em_image_id'] );
                $payload['em_image_id'] = absint( $first );
            } else {
                $payload['em_image_id'] = absint( $body['em_image_id'] );
            }
        }
        $payload['em_display_address_on_frontend'] = $this->ep_param_truthy( $body, 'em_display_address_on_frontend' );
        if ( isset( $body['em_is_featured'] ) ) {
            $payload['em_is_featured'] = $this->ep_param_truthy( $body, 'em_is_featured' );
        }
        if ( isset( $body['em_status'] ) ) {
            $payload['em_status'] = $this->ep_param_truthy( $body, 'em_status' );
        }

        $ep      = new Eventprime_Basic_Functions();
        $term_id = 0;
        $create_error = '';
        try {
            $term_id = $ep->create_venue( $payload );
        } catch ( Exception $e ) {
            $create_error = $e->getMessage();
        } catch ( Error $e ) {
            $create_error = $e->getMessage();
        }

        if ( is_wp_error( $term_id ) ) {
            $create_error = $term_id->get_error_message();
            $term_id = 0;
        }

        if ( empty( $term_id ) ) {
            $existing = term_exists( $name, 'em_venue' );
            if ( ! $existing ) {
                $existing = term_exists( sanitize_title( $name ), 'em_venue' );
            }
            if ( $existing && ! is_wp_error( $existing ) ) {
                $term_id = is_array( $existing ) && isset( $existing['term_id'] ) ? (int) $existing['term_id'] : (int) $existing;
            }
        }

        if ( empty( $term_id ) ) {
            $message = 'Failed to create venue.';
            if ( ! empty( $create_error ) ) {
                $message .= ' ' . $create_error;
            }
            return rest_ensure_response( array( 'status' => 'error', 'message' => $message ), 500 );
        }

        $venue = method_exists( $ep, 'ep_get_venue_by_id' ) ? $ep->ep_get_venue_by_id( $term_id ) : array( 'id' => $term_id, 'name' => $name );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'venue' => $venue ) );
    }

    public function handle_venue_update( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $body = $request->get_body_params();
        $name = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
        if ( empty( $name ) ) return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing name.' ), 400 );
        $res = wp_update_term( $id, 'em_venue', array( 'name' => $name ) );
        if ( is_wp_error( $res ) ) return rest_ensure_response( array( 'status' => 'error', 'message' => 'Failed to update venue.' ), 500 );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'venue' => array( 'id' => $id ) ) );
    }

    public function handle_venue_delete( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $res = wp_delete_term( $id, 'em_venue' );
        if ( is_wp_error( $res ) ) return rest_ensure_response( array( 'status' => 'error', 'message' => 'Failed to delete venue.' ), 500 );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 0, 'message' => 'Venue deleted.' ) );
    }

    public function handle_performer_create( WP_REST_Request $request ) {
        $body = $this->ep_normalize_body_params( $request );
        if ( empty( $body['name'] ) && $request->get_param( 'name' ) ) {
            $body['name'] = $request->get_param( 'name' );
        }
        if ( empty( $body['description'] ) && $request->get_param( 'description' ) ) {
            $body['description'] = $request->get_param( 'description' );
        }
        $name = isset( $body['name'] ) ? sanitize_text_field( $body['name'] ) : '';
        if ( empty( $name ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing name.' ), 400 );
        }
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Core functions unavailable.' ), 500 );
        }

        $payload = array( 'name' => $name );
        if ( isset( $body['description'] ) ) {
            $payload['description'] = wp_kses_post( $body['description'] );
        }
        if ( isset( $body['status'] ) ) {
            $payload['status'] = sanitize_text_field( $body['status'] );
        }
        $text_fields = array(
            'em_type'      => 'sanitize_text_field',
            'em_role'      => 'sanitize_text_field',
        );
        foreach ( $text_fields as $field => $callback ) {
            if ( isset( $body[ $field ] ) ) {
                $payload[ $field ] = call_user_func( $callback, $body[ $field ] );
            }
        }
        if ( isset( $body['thumbnail'] ) ) {
            $payload['thumbnail'] = absint( $body['thumbnail'] );
        }
        if ( array_key_exists( 'em_display_front', $body ) ) {
            $payload['em_display_front'] = $this->ep_param_truthy( $body, 'em_display_front' );
        }
        if ( array_key_exists( 'em_is_featured', $body ) ) {
            $payload['em_is_featured'] = $this->ep_param_truthy( $body, 'em_is_featured' );
        }
        $payload['em_social_links'] = array();
        if ( isset( $body['em_social_links'] ) ) {
            $payload['em_social_links'] = $this->ep_sanitize_assoc_urls( (array) $body['em_social_links'] );
        } elseif ( isset( $body['social_links'] ) ) {
            $payload['em_social_links'] = $this->ep_sanitize_assoc_urls( (array) $body['social_links'] );
        }
        if ( isset( $body['em_performer_phones'] ) || isset( $body['phones'] ) ) {
            $payload['em_performer_phones'] = $this->ep_sanitize_string_list( isset( $body['em_performer_phones'] ) ? $body['em_performer_phones'] : $body['phones'] );
        }
        if ( isset( $body['em_performer_emails'] ) || isset( $body['emails'] ) ) {
            $emails = isset( $body['em_performer_emails'] ) ? $body['em_performer_emails'] : $body['emails'];
            $payload['em_performer_emails'] = $this->ep_sanitize_string_list( $emails, 'sanitize_email' );
        }
        if ( isset( $body['em_performer_websites'] ) || isset( $body['websites'] ) ) {
            $websites = isset( $body['em_performer_websites'] ) ? $body['em_performer_websites'] : $body['websites'];
            $payload['em_performer_websites'] = $this->ep_sanitize_string_list( $websites, 'esc_url_raw' );
        }
        if ( isset( $body['em_performer_gallery'] ) ) {
            $gallery = is_array( $body['em_performer_gallery'] ) ? array_map( 'absint', $body['em_performer_gallery'] ) : array( absint( $body['em_performer_gallery'] ) );
            $payload['em_performer_gallery'] = array_filter( $gallery );
        }

        $ep  = new Eventprime_Basic_Functions();
        $pid = $ep->insert_performer_post_data( $payload );
        if ( empty( $pid ) || is_wp_error( $pid ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Failed to create performer.' ), 500 );
        }
        $performer = method_exists( $ep, 'get_single_performer' ) ? $ep->get_single_performer( $pid ) : array( 'id' => $pid, 'name' => $name );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'performer' => $performer ) );
    }

    public function handle_performer_get( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $p = get_post( $id );
        if ( empty( $p ) || $p->post_type !== 'em_performer' ) return new WP_Error( 'not_found', 'Performer not found.', array( 'status' => 404 ) );
        if ( class_exists( 'Eventprime_Basic_Functions' ) && method_exists( 'Eventprime_Basic_Functions', 'get_single_performer' ) ) {
            $ep = new Eventprime_Basic_Functions();
            $performer = $ep->get_single_performer( $p->ID, $p );
        } else {
            $performer = array(
                'id'      => $p->ID,
                'name'    => sanitize_text_field( $p->post_title ),
                'content' => wp_kses_post( $p->post_content ),
            );
        }
        return rest_ensure_response( $performer );
    }

    public function handle_performer_update( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $p = get_post( $id );
        if ( empty( $p ) || $p->post_type !== 'em_performer' ) return new WP_Error( 'not_found', 'Performer not found.', array( 'status' => 404 ) );
        $body = $request->get_body_params();
        $update = array( 'ID' => $id );
        if ( isset( $body['name'] ) ) $update['post_title'] = sanitize_text_field( $body['name'] );
        if ( isset( $body['content'] ) ) $update['post_content'] = sanitize_textarea_field( $body['content'] );
        wp_update_post( $update );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'performer' => array( 'id' => $id ) ) );
    }

    public function handle_performer_delete( WP_REST_Request $request ) {
        $id = absint( $request->get_param( 'id' ) );
        $p = get_post( $id );
        if ( empty( $p ) || $p->post_type !== 'em_performer' ) return new WP_Error( 'not_found', 'Performer not found.', array( 'status' => 404 ) );
        wp_delete_post( $id, true );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 0, 'message' => 'Performer deleted.' ) );
    }

    /**
     * Create an organizer term (taxonomy: em_event_organizer).
     * Optional fields: email, phone, website, event_id to attach term to event.
     */
    public function handle_organizer_create( WP_REST_Request $request ) {
        $body = $this->ep_normalize_body_params( $request );
        if ( empty( $body['name'] ) && $request->get_param( 'name' ) ) {
            $body['name'] = $request->get_param( 'name' );
        }
        if ( empty( $body['organizer_name'] ) && $request->get_param( 'organizer_name' ) ) {
            $body['organizer_name'] = $request->get_param( 'organizer_name' );
        }
        $name = '';
        if ( isset( $body['name'] ) ) {
            $name = sanitize_text_field( $body['name'] );
        } elseif ( isset( $body['organizer_name'] ) ) {
            $name = sanitize_text_field( $body['organizer_name'] );
        }
        if ( empty( $name ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing name.' ), 400 );
        }
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Core functions unavailable.' ), 500 );
        }

        $payload = array( 'name' => $name );
        if ( isset( $body['description'] ) ) {
            $payload['description'] = wp_kses_post( $body['description'] );
        }
        $payload['em_organizer_phones'] = array();
        if ( isset( $body['em_organizer_phones'] ) || isset( $body['phone'] ) ) {
            $payload['em_organizer_phones'] = $this->ep_sanitize_string_list( isset( $body['em_organizer_phones'] ) ? $body['em_organizer_phones'] : $body['phone'] );
        }
        $payload['em_organizer_emails'] = array();
        if ( isset( $body['em_organizer_emails'] ) || isset( $body['email'] ) ) {
            $emails = isset( $body['em_organizer_emails'] ) ? $body['em_organizer_emails'] : $body['email'];
            $payload['em_organizer_emails'] = $this->ep_sanitize_string_list( $emails, 'sanitize_email' );
        }
        $payload['em_organizer_websites'] = array();
        if ( isset( $body['em_organizer_websites'] ) || isset( $body['website'] ) ) {
            $websites = isset( $body['em_organizer_websites'] ) ? $body['em_organizer_websites'] : $body['website'];
            $payload['em_organizer_websites'] = $this->ep_sanitize_string_list( $websites, 'esc_url_raw' );
        }
        if ( isset( $body['em_image_id'] ) ) {
            $payload['em_image_id'] = absint( is_array( $body['em_image_id'] ) ? reset( $body['em_image_id'] ) : $body['em_image_id'] );
        }
        if ( isset( $body['em_is_featured'] ) ) {
            $payload['em_is_featured'] = $this->ep_param_truthy( $body, 'em_is_featured' );
        }
        if ( isset( $body['em_social_links'] ) || isset( $body['social_links'] ) ) {
            $links = isset( $body['em_social_links'] ) ? $body['em_social_links'] : $body['social_links'];
            $payload['em_social_links'] = $this->ep_sanitize_assoc_urls( (array) $links );
        }
        if ( isset( $body['em_status'] ) ) {
            $payload['em_status'] = $this->ep_param_truthy( $body, 'em_status' );
        }

        $ep      = new Eventprime_Basic_Functions();
        $term_id = $ep->create_organizer( $payload );
        if ( empty( $term_id ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Failed to create organizer.' ), 500 );
        }

        if ( isset( $body['event_id'] ) && is_numeric( $body['event_id'] ) ) {
            $event_id = absint( $body['event_id'] );
            wp_set_object_terms( $event_id, array( $term_id ), 'em_event_organizer', false );
            update_post_meta( $event_id, 'em_organizer', array( $term_id ) );
            update_post_meta( $event_id, 'em_organizer_id', $term_id );
        }

        $organizer = method_exists( $ep, 'get_single_organizer' ) ? $ep->get_single_organizer( $term_id ) : array( 'id' => $term_id, 'name' => $name );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'organizer' => $organizer ) );
    }

    /**
     * Create an event type term (taxonomy: em_event_type).
     * Optional: event_id to attach created type to an event.
     */
    public function handle_event_type_create( WP_REST_Request $request ) {
        $body = $this->ep_normalize_body_params( $request );
        if ( empty( $body['name'] ) && $request->get_param( 'name' ) ) {
            $body['name'] = $request->get_param( 'name' );
        }
        if ( empty( $body['event_type_name'] ) && $request->get_param( 'event_type_name' ) ) {
            $body['event_type_name'] = $request->get_param( 'event_type_name' );
        }
        if ( empty( $body['event_id'] ) && $request->get_param( 'event_id' ) ) {
            $body['event_id'] = $request->get_param( 'event_id' );
        }
        $name = '';
        if ( isset( $body['name'] ) ) {
            $name = sanitize_text_field( $body['name'] );
        } elseif ( isset( $body['event_type_name'] ) ) {
            $name = sanitize_text_field( $body['event_type_name'] );
        }
        if ( empty( $name ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Missing name.' ), 400 );
        }
        if ( ! class_exists( 'Eventprime_Basic_Functions' ) ) {
            return rest_ensure_response( array( 'status' => 'error', 'message' => 'Core functions unavailable.' ), 500 );
        }

        $payload = array( 'name' => $name );
        if ( isset( $body['description'] ) ) {
            $payload['description'] = wp_kses_post( $body['description'] );
        }
        $map = array(
            'em_color'           => 'sanitize_text_field',
            'em_type_text_color' => 'sanitize_text_field',
            'em_age_group'       => 'sanitize_text_field',
        );
        foreach ( $map as $field => $callback ) {
            if ( isset( $body[ $field ] ) ) {
                $payload[ $field ] = call_user_func( $callback, $body[ $field ] );
            }
        }
        if ( isset( $body['em_image_id'] ) ) {
            $payload['em_image_id'] = absint( is_array( $body['em_image_id'] ) ? reset( $body['em_image_id'] ) : $body['em_image_id'] );
        }
        if ( isset( $body['em_is_featured'] ) ) {
            $payload['em_is_featured'] = $this->ep_param_truthy( $body, 'em_is_featured' );
        }
        if ( isset( $body['em_status'] ) ) {
            $payload['em_status'] = $this->ep_param_truthy( $body, 'em_status' );
        }

        $ep      = new Eventprime_Basic_Functions();
        $term_id = 0;
        $create_error = '';

        // Avoid duplicate inserts that make the helper crash.
        $existing = term_exists( $name, 'em_event_type' );
        if ( ! $existing ) {
            $existing = term_exists( sanitize_title( $name ), 'em_event_type' );
        }
        if ( $existing && ! is_wp_error( $existing ) ) {
            $term_id = is_array( $existing ) && isset( $existing['term_id'] ) ? (int) $existing['term_id'] : (int) $existing;
        }

        if ( empty( $term_id ) ) {
            try {
                $term_id = $ep->create_event_types( $payload );
            } catch ( Throwable $e ) {
                $create_error = $e->getMessage();
                $term_id = 0;
            }
            if ( is_wp_error( $term_id ) ) {
                $create_error = $term_id->get_error_message();
                $term_id = 0;
            }
            if ( empty( $term_id ) ) {
                $existing = term_exists( $name, 'em_event_type' );
                if ( ! $existing ) {
                    $existing = term_exists( sanitize_title( $name ), 'em_event_type' );
                }
                if ( $existing && ! is_wp_error( $existing ) ) {
                    $term_id = is_array( $existing ) && isset( $existing['term_id'] ) ? (int) $existing['term_id'] : (int) $existing;
                }
            }
        }

        if ( empty( $term_id ) ) {
            $message = 'Failed to create event type.';
            if ( ! empty( $create_error ) ) {
                $message .= ' ' . $create_error;
            }
            return rest_ensure_response( array( 'status' => 'error', 'message' => $message ), 500 );
        }

        if ( isset( $body['event_id'] ) && is_numeric( $body['event_id'] ) ) {
            $event_id = absint( $body['event_id'] );
            wp_set_object_terms( $event_id, array( $term_id ), 'em_event_type', false );
            update_post_meta( $event_id, 'em_event_type', array( $term_id ) );
        }

        $event_type = method_exists( $ep, 'get_single_event_type' ) ? $ep->get_single_event_type( $term_id ) : array( 'id' => $term_id, 'name' => $name );
        return rest_ensure_response( array( 'status' => 'success', 'count' => 1, 'event_type' => $event_type ) );
    }

}
