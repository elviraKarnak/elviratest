<?php
/**
 * Integration helpers extracted from previous inline code.
 * Provides data generators for integration triggers and samples.
 */
class Eventprime_API_Integration_Helpers {

    /**
     * Return bookings matching a status, optionally filtered to a specific event_id.
     * @param string $status
     * @param int $event_id optional event id to filter bookings
     * @return array
     */
    public function all_bookings_data( $status, $event_id = 0 ) {
        $bookings = new Eventprime_Bookings();
        $args = array(
            'numberposts' => -1,
            'post_status' => 'any',
            'post_type'   => 'em_booking',
            'orderby'     => 'date',
            'order'       => 'DESC',
        );

        // If caller requested an event-specific filter, add a meta query.
        if ( $event_id ) {
            $args['meta_query'] = array( array( 'key' => 'em_event', 'value' => $event_id ) );
        }

        $all_bookings = get_posts( $args );

        if ( empty( $all_bookings ) ) {
            return array( 'status' => 'error', 'message' => __( 'No bookings found. Please create a demo booking to generate sample data.', 'eventprime-event-calendar-management' ) );
        }

        $out = array();
        foreach ( $all_bookings as $booking_post ) {
            if ( empty( $booking_post->ID ) ) continue;
            $booking_detail = $bookings->load_booking_detail( $booking_post->ID );
            // If a status filter is provided, respect it
            if ( isset( $booking_detail->em_status ) && $booking_detail->em_status === $status ) {
                // If event_id filter provided, double-check booking's event association
                if ( $event_id ) {
                    $b_event = isset( $booking_detail->em_event ) ? intval( $booking_detail->em_event ) : ( get_post_meta( $booking_post->ID, 'em_event', true ) ? intval( get_post_meta( $booking_post->ID, 'em_event', true ) ) : 0 );
                    if ( $b_event && $b_event !== intval( $event_id ) ) {
                        continue;
                    }
                }
                $out[] = $booking_detail;
            }
        }

        if ( empty( $out ) ) {
            return array( 'status' => 'error', 'message' => sprintf( __( 'No bookings found with the status "%s". Please create a booking with this status to proceed.', 'eventprime-event-calendar-management' ), esc_html( $status ) ) );
        }

        return array( 'status' => 'success', 'count' => count( $out ), 'bookings' => $out );
    }

    public function all_performers_data( $status ) {
        $ep_functions = new Eventprime_Basic_Functions();
        $args = array( 'post_type' => 'em_performer', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC' );
        if ( $status === 'delete_performer' ) $args['post_status'] = 'trash';
        $performers = get_posts( $args );
        if ( empty( $performers ) ) return array( 'status' => 'error', 'message' => __( 'No performers found. Please create at least one performer to get sample data.', 'eventprime-event-calendar-management' ) );
        $performer_id = $performers[0]->ID;
        if ( method_exists( $ep_functions, 'get_single_performer' ) ) return array( 'status' => 'success', 'performer' => $ep_functions->get_single_performer( $performer_id ) );
        return array( 'status' => 'success', 'performer' => array( 'id' => $performer_id, 'title' => $performers[0]->post_title ) );
    }

    public function all_organizers_data() {
        $ep_functions = new Eventprime_Basic_Functions();
        $organizers = get_terms( array( 'taxonomy' => 'em_event_organizer', 'hide_empty' => false, 'orderby' => 'id', 'order' => 'DESC', 'number' => 1 ) );
        if ( is_wp_error( $organizers ) || empty( $organizers ) ) return array( 'status' => 'error', 'message' => __( 'No organizers found. Please create at least one organizer to get sample data.', 'eventprime-event-calendar-management' ) );
        $organizer_id = $organizers[0]->term_id;
        if ( method_exists( $ep_functions, 'get_single_organizer' ) ) return array( 'status' => 'success', 'organizer' => $ep_functions->get_single_organizer( $organizer_id ) );
        return array( 'status' => 'success', 'organizer' => (array) $organizers[0] );
    }

    public function all_venues_data() {
        $ep_functions = new Eventprime_Basic_Functions();
        $venues = get_terms( array( 'taxonomy' => 'em_venue', 'hide_empty' => false, 'orderby' => 'id', 'order' => 'DESC', 'number' => 1 ) );
        if ( is_wp_error( $venues ) || empty( $venues ) ) return array( 'status' => 'error', 'message' => __( 'No venues found. Please create at least one venue to get sample data.', 'eventprime-event-calendar-management' ) );
        $venue_id = $venues[0]->term_id;
        if ( method_exists( $ep_functions, 'ep_get_venue_by_id' ) ) return array( 'status' => 'success', 'venue' => $ep_functions->ep_get_venue_by_id( $venue_id ) );
        return array( 'status' => 'success', 'venue' => (array) $venues[0] );
    }

    public function ep_get_tickets_by_event( $request ) {
        $event_id = isset( $request['event_id'] ) ? absint( $request['event_id'] ) : 0;
        $ticket_data = array();

        // If an event_id was provided, ensure the event exists and is of type 'em_event'.
        if ( $event_id ) {
            $post = get_post( $event_id );
            if ( empty( $post ) || $post->post_type !== 'em_event' ) {
                return array( 'status' => 'error', 'message' => __( 'Event not found.', 'eventprime-event-calendar-management' ), 'count' => 0, 'tickets' => array() );
            }

            $epf = new Eventprime_Basic_Functions();
            if ( method_exists( $epf, 'get_single_event_detail' ) ) {
                $detail = $epf->get_single_event_detail( $event_id );
                if ( ! empty( $detail->all_tickets_data ) && is_array( $detail->all_tickets_data ) ) {
                    foreach ( $detail->all_tickets_data as $t ) {
                        // Normalize whatever structure core returned so nested JSON/serialized strings
                        // are expanded for downstream consumers.
                        $norm = $this->normalize_response( $t );
                        // Try to produce a consistent ticket shape
                        $ticket = array();
                        if ( is_array( $norm ) ) {
                            // Common keys used by core helper
                            $ticket['ticket_id'] = isset( $norm['id'] ) ? intval( $norm['id'] ) : ( isset( $norm['ticket_id'] ) ? intval( $norm['ticket_id'] ) : null );
                            $ticket['ticket_name'] = isset( $norm['name'] ) ? sanitize_text_field( $norm['name'] ) : ( isset( $norm['ticket_name'] ) ? sanitize_text_field( $norm['ticket_name'] ) : '' );
                            $ticket['price'] = isset( $norm['price'] ) ? floatval( $norm['price'] ) : ( isset( $norm['em_price'] ) ? floatval( $norm['em_price'] ) : 0 );
                            $ticket['capacity'] = isset( $norm['capacity'] ) ? intval( $norm['capacity'] ) : ( isset( $norm['em_capacity'] ) ? intval( $norm['em_capacity'] ) : null );
                            $ticket['event_id'] = isset( $norm['event_id'] ) ? intval( $norm['event_id'] ) : $event_id;
                            // attach any remaining normalized payload for completeness
                            $ticket['meta'] = $norm;
                        } else {
                            // If object or scalar, cast to array and include
                            $ticket['ticket_id'] = isset( $t->id ) ? intval( $t->id ) : ( isset( $t->ticket_id ) ? intval( $t->ticket_id ) : null );
                            $ticket['ticket_name'] = isset( $t->name ) ? sanitize_text_field( $t->name ) : ( isset( $t->ticket_name ) ? sanitize_text_field( $t->ticket_name ) : '' );
                            $ticket['price'] = isset( $t->price ) ? floatval( $t->price ) : 0;
                            $ticket['capacity'] = isset( $t->capacity ) ? intval( $t->capacity ) : null;
                            $ticket['event_id'] = $event_id;
                            $ticket['meta'] = ( is_object( $t ) ) ? (array) $t : array( 'value' => $t );
                        }
                        $ticket_data[] = $ticket;
                    }
                }
            }
        }
        // Fallback: query ticket posts directly if core helper returned no results
        if ( empty( $ticket_data ) && $event_id ) {
            // Look for tickets where meta 'em_event' stores the event id. Some installs store as plain int, others
            // may store serialized arrays; use several meta_query patterns to match both.
            $meta_queries = array(
                array( 'key' => 'em_event', 'value' => $event_id, 'compare' => '=' ),
                array( 'key' => 'em_event', 'value' => '"' . $event_id . '"', 'compare' => 'LIKE' ),
                array( 'key' => 'em_event', 'value' => ':' . $event_id . ';', 'compare' => 'LIKE' ),
            );

            $args = array(
                'post_type' => 'em_ticket',
                'posts_per_page' => 200,
                'post_status' => 'publish',
                'meta_query' => array( 'relation' => 'OR' )
            );
            foreach ( $meta_queries as $mq ) $args['meta_query'][] = $mq;

            $tickets = get_posts( $args );
            if ( ! empty( $tickets ) ) {
                foreach ( $tickets as $t ) {
                    $raw_event = get_post_meta( $t->ID, 'em_event', true );
                    $resolved_event_id = null;
                    if ( is_array( $raw_event ) && ! empty( $raw_event ) ) {
                        // sometimes stored as array
                        $vals = array_values( $raw_event );
                        $resolved_event_id = isset( $vals[0] ) ? absint( $vals[0] ) : null;
                    } elseif ( is_numeric( $raw_event ) ) {
                        $resolved_event_id = absint( $raw_event );
                    } elseif ( is_string( $raw_event ) ) {
                        // try to extract a number from serialized/string
                        if ( preg_match( '/(\d+)/', $raw_event, $m ) ) {
                            $resolved_event_id = absint( $m[1] );
                        }
                    }

                    // only include tickets that match the requested event id when possible
                    if ( $resolved_event_id && $resolved_event_id !== $event_id ) continue;

                    // Prefer core ticket helper if available for rich data
                    $epf = new Eventprime_Basic_Functions();
                    $ticket = array();
                    if ( method_exists( $epf, 'ep_get_ticket_data' ) ) {
                        $td = $epf->ep_get_ticket_data( $t->ID );
                        if ( ! empty( $td ) ) {
                            $tdn = $this->normalize_response( $td );
                            // try to standardize keys
                            $ticket['ticket_id'] = isset( $tdn['id'] ) ? intval( $tdn['id'] ) : $t->ID;
                            $ticket['ticket_name'] = isset( $tdn['name'] ) ? sanitize_text_field( $tdn['name'] ) : sanitize_text_field( $t->post_title );
                            $ticket['price'] = isset( $tdn['price'] ) ? floatval( $tdn['price'] ) : ( isset( $tdn['em_price'] ) ? floatval( $tdn['em_price'] ) : 0 );
                            $ticket['capacity'] = isset( $tdn['capacity'] ) ? intval( $tdn['capacity'] ) : ( isset( $tdn['em_capacity'] ) ? intval( $tdn['em_capacity'] ) : null );
                            $ticket['event_id'] = isset( $tdn['event_id'] ) ? intval( $tdn['event_id'] ) : ( $resolved_event_id ? $resolved_event_id : $event_id );
                            $ticket['meta'] = $tdn;
                            $ticket_data[] = $ticket;
                            continue;
                        }
                    }

                    // Fallback: build from raw post meta and post object
                    $raw_meta = get_post_meta( $t->ID );
                    $norm_meta = $this->normalize_response( $raw_meta );

                    // helper to pick first available meta key
                    $pick = function( $keys ) use ( $norm_meta ) {
                        foreach ( $keys as $k ) {
                            if ( isset( $norm_meta[ $k ] ) ) {
                                $v = $norm_meta[ $k ];
                                if ( is_array( $v ) ) {
                                    return isset( $v[0] ) ? $v[0] : $v;
                                }
                                return $v;
                            }
                        }
                        return null;
                    };

                    $price_val = $pick( array( 'em_price', 'price', 'amount' ) );
                    $capacity_val = $pick( array( 'em_capacity', 'capacity', 'available' ) );
                    $start_date_val = $pick( array( 'em_start_date', 'start_date' ) );
                    $end_date_val = $pick( array( 'em_end_date', 'end_date' ) );

                    // normalize numeric values
                    $ticket['ticket_id'] = $t->ID;
                    $ticket['ticket_name'] = sanitize_text_field( $t->post_title );
                    $ticket['price'] = $price_val !== null ? floatval( $price_val ) : 0;
                    $ticket['capacity'] = $capacity_val !== null ? intval( $capacity_val ) : ( isset( $norm_meta['em_capacity'] ) ? intval( $norm_meta['em_capacity'] ) : null );
                    $ticket['event_id'] = $resolved_event_id ? $resolved_event_id : $event_id;
                    // convert dates if helper available
                    if ( method_exists( $epf, 'ep_timestamp_to_date' ) ) {
                        $ticket['start_date'] = $start_date_val !== null ? $epf->ep_timestamp_to_date( $start_date_val ) : null;
                        $ticket['end_date'] = $end_date_val !== null ? $epf->ep_timestamp_to_date( $end_date_val ) : null;
                    } else {
                        $ticket['start_date'] = $start_date_val ? ( is_numeric( $start_date_val ) ? date_i18n( 'c', (int) $start_date_val ) : sanitize_text_field( $start_date_val ) ) : null;
                        $ticket['end_date'] = $end_date_val ? ( is_numeric( $end_date_val ) ? date_i18n( 'c', (int) $end_date_val ) : sanitize_text_field( $end_date_val ) ) : null;
                    }

                    // include all normalized meta for completeness (offers, additional_fees etc.)
                    $ticket['meta'] = $norm_meta;

                    $ticket_data[] = $ticket;
                }
            }
        }

        if ( empty( $ticket_data ) ) {
            return array( 'status' => 'error', 'message' => __( 'No tickets found for the given event.', 'eventprime-event-calendar-management' ) );
        }

        // Final normalization pass to decode any remaining nested serialized/JSON values.
        $ticket_data = $this->normalize_response( $ticket_data );

        return array( 'status' => 'success', 'count' => count( $ticket_data ), 'tickets' => $ticket_data );
    }

    public function all_events_data( $status ) {
        $ep_functions = new Eventprime_Basic_Functions();

        $args = array(
            'post_type'      => 'em_event',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        if ( $status === 'delete_event' ) {
            $args['post_status'] = 'trash';
        }

        $events = get_posts( $args );

        if ( empty( $events ) ) {
            return array( 'status' => 'error', 'message' => __( 'No events found. Please create a demo event to generate sample data.', 'eventprime-event-calendar-management' ) );
        }

        $event_id = $events[0]->ID;
        if ( method_exists( $ep_functions, 'get_single_event_detail' ) ) {
            $event_data = $ep_functions->get_single_event_detail( $event_id );
            $event_data = $this->validate_event_data_fields( $event_data );
            return array( 'status' => 'success', 'event' => $event_data );
        }

        return array( 'status' => 'success', 'event' => array( 'event_id' => $event_id, 'event_name' => $events[0]->post_title ) );
    }

    public function validate_event_data_fields( $event_data ) {
        $ep_functions = new Eventprime_Basic_Functions();

        if ( ! empty( $event_data->em_start_date ) ) {
            $event_data->em_start_date = $ep_functions->ep_timestamp_to_date( $event_data->em_start_date );
        }
        if ( ! empty( $event_data->em_end_date ) ) {
            $event_data->em_end_date = $ep_functions->ep_timestamp_to_date( $event_data->em_end_date );
        }
        $event_data->venue_details = ! empty( $event_data->em_venue ) ? $ep_functions->ep_get_venue_by_id( $event_data->em_venue ) : array();
        if ( isset( $event_data->fstart_date ) ) unset( $event_data->fstart_date );
        if ( isset( $event_data->fend_date ) ) unset( $event_data->fend_date );
        return $event_data;
    }

    public function get_event_sample( $action ) {
        $epf = new Eventprime_Basic_Functions();
        $args = array( 'post_type' => 'em_event', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC' );
        if ( $action === 'delete_event' ) $args['post_status'] = 'trash';
        $posts = get_posts( $args );
        if ( empty( $posts ) ) {
            return array( 'status' => 'error', 'message' => 'No events found for sample data.' );
        }
        $id = $posts[0]->ID;
        if ( method_exists( $epf, 'get_single_event_detail' ) ) {
            return array( 'status' => 'success', 'event' => $epf->get_single_event_detail( $id ) );
        }
        return array( 'status' => 'success', 'event' => array( 'event_id' => $id, 'event_name' => $posts[0]->post_title ) );
    }

    public function all_events_list() {
        $args = array( 'post_type' => 'em_event', 'posts_per_page' => -1, 'post_status' => 'publish' );
        $events = get_posts( $args );
        $out = array();
        if ( ! empty( $events ) ) {
            foreach ( $events as $e ) {
                $out[] = array( 'event_id' => $e->ID, 'event_name' => $e->post_title );
            }
        }
        return array( 'status' => 'success', 'count' => count( $out ), 'events' => $out );
    }

    /**
     * Normalize a payload value recursively, decoding serialized/meta JSON strings
     * into arrays/objects where appropriate. This mirrors behavior used by the
     * data instead of JSON-in-string or serialized stored values.
     *
     * @param mixed $data
     * @return mixed normalized data
     */
    public function normalize_response( $data ) {
        // If object -> convert to array
        if ( is_object( $data ) ) {
            $data = (array) $data;
        }

        // If array -> recurse
        if ( is_array( $data ) ) {
            foreach ( $data as $k => $v ) {
                $data[ $k ] = $this->normalize_response( $v );
            }
            return $data;
        }

        // Non-string scalars passthrough
        if ( ! is_string( $data ) ) {
            return $data;
        }

        $original = $data;

        // 1) maybe unserialize
        if ( function_exists( 'maybe_unserialize' ) ) {
            $maybe = maybe_unserialize( $data );
            if ( $maybe !== $data ) {
                return $this->normalize_response( $maybe );
            }
        }

        // helper: try json decode tolerant of invalid utf8
        $try_json = static function ( $s ) {
            $v = json_decode( $s, true, 512, defined('JSON_INVALID_UTF8_SUBSTITUTE') ? JSON_INVALID_UTF8_SUBSTITUTE : 0 );
            return ( json_last_error() === JSON_ERROR_NONE && ( is_array( $v ) || is_object( $v ) ) ) ? $v : null;
        };

        // 2) direct json
        if ( ( $decoded = $try_json( $data ) ) !== null ) {
            return $this->normalize_response( $decoded );
        }

        // 3) unslash then json
        $unslashed = function_exists( 'wp_unslash' ) ? wp_unslash( $data ) : stripcslashes( $data );
        if ( $unslashed !== $data ) {
            if ( ( $decoded = $try_json( $unslashed ) ) !== null ) {
                return $this->normalize_response( $decoded );
            }
        }

        // 4) quoted JSON "{...}" or '"[...]"'
        $trim = trim( $data );
        if ( $trim !== '' && $trim[0] === '"' && substr( $trim, -1 ) === '"' ) {
            $inner = substr( $trim, 1, -1 );
            if ( ( $decoded = $try_json( $inner ) ) !== null ) {
                return $this->normalize_response( $decoded );
            }
            $inner2 = function_exists( 'wp_unslash' ) ? wp_unslash( $inner ) : stripcslashes( $inner );
            if ( $inner2 !== $inner && ( $decoded = $try_json( $inner2 ) ) !== null ) {
                return $this->normalize_response( $decoded );
            }
        }

        return $original;
    }

}
