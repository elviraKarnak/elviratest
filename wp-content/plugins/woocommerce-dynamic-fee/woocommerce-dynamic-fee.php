<?php
/**
 * Plugin Name: WooCommerce Dynamic Additional Fee (Improved)
 * Description: Adds a configurable dynamic additional fee to the WooCommerce cart. More robust postcode handling and duplicate prevention.
 * Version: 1.1.0
 * Author: ChatGPT (updated)
 * Text Domain: wc-dynamic-fee
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WC_Dynamic_Additional_Fee_Improved {

    const OPTION_KEY = 'wc_ddf_options';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        // Use default priority; runs in AJAX and normal requests.
        add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_dynamic_fee' ), 10, 1 );
    }

    public function admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Dynamic Additional Fee',
            'Dynamic Additional Fee',
            'manage_options',
            'wc-dynamic-fee',
            array( $this, 'settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'wc_ddf_group', self::OPTION_KEY, array( $this, 'sanitize_options' ) );
    }

    public function sanitize_options( $input ) {
        $out = array();
        $out['enabled'] = ( isset( $input['enabled'] ) && $input['enabled'] === 'yes' ) ? 'yes' : 'no';
        $out['fee_type'] = ( isset( $input['fee_type'] ) && $input['fee_type'] === 'percent' ) ? 'percent' : 'fixed';
        $out['amount'] = isset( $input['amount'] ) ? floatval( $input['amount'] ) : 0;
        $out['label'] = isset( $input['label'] ) ? sanitize_text_field( $input['label'] ) : 'Additional Fee';
        $out['apply_zip'] = ( isset( $input['apply_zip'] ) && $input['apply_zip'] === 'yes' ) ? 'yes' : 'no';
        $out['zip_codes'] = isset( $input['zip_codes'] ) ? sanitize_text_field( $input['zip_codes'] ) : '';
        $out['taxable'] = ( isset( $input['taxable'] ) && $input['taxable'] === 'yes' ) ? 'yes' : 'no';
        return $out;
    }

    public function settings_page() {
        $opts = get_option( self::OPTION_KEY, array(
            'enabled' => 'no',
            'fee_type' => 'fixed',
            'amount' => 0,
            'label' => 'Additional Fee',
            'apply_zip' => 'no',
            'zip_codes' => '',
            'taxable' => 'no'
        ) );
        ?>
        <div class="wrap">
            <h1>WooCommerce Dynamic Additional Fee</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'wc_ddf_group' ); ?>
                <?php do_settings_sections( 'wc_ddf_group' ); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="wc_ddf_enabled">Enable</label></th>
                        <td>
                            <label><input type="checkbox" name="wc_ddf_options[enabled]" value="yes" <?php checked( $opts['enabled'], 'yes' ); ?>> Enable dynamic fee</label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ddf_fee_type">Fee type</label></th>
                        <td>
                            <select name="wc_ddf_options[fee_type]">
                                <option value="fixed" <?php selected( $opts['fee_type'], 'fixed' ); ?>>Fixed amount</option>
                                <option value="percent" <?php selected( $opts['fee_type'], 'percent' ); ?>>Percent of cart subtotal</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ddf_amount">Amount</label></th>
                        <td>
                            <input name="wc_ddf_options[amount]" type="number" step="0.01" min="0" value="<?php echo esc_attr( $opts['amount'] ); ?>"> (If percent, enter e.g. 5 for 5%)
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ddf_label">Fee label</label></th>
                        <td>
                            <input name="wc_ddf_options[label]" type="text" value="<?php echo esc_attr( $opts['label'] ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ddf_apply_zip">Apply only for ZIP/Postcodes</label></th>
                        <td>
                            <label><input type="checkbox" name="wc_ddf_options[apply_zip]" value="yes" <?php checked( $opts['apply_zip'], 'yes' ); ?>> Enable ZIP/postcode filtering</label><br>
                            <small>If enabled, enter comma-separated ZIP/postcodes below. Example: 10001,10002,SW1A 1AA</small>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ddf_zip_codes">ZIP/Postcodes list</label></th>
                        <td>
                            <input name="wc_ddf_options[zip_codes]" type="text" size="60" value="<?php echo esc_attr( $opts['zip_codes'] ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="wc_ddf_taxable">Taxable</label></th>
                        <td>
                            <label><input type="checkbox" name="wc_ddf_options[taxable]" value="yes" <?php checked( $opts['taxable'], 'yes' ); ?>> Fee is taxable</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Normalizes postcode (strip spaces, uppercase).
     */
    protected function normalize_postcode( $postcode ) {
        if ( empty( $postcode ) ) {
            return '';
        }
        return trim( strtoupper( str_replace( ' ', '', $postcode ) ) );
    }

    /**
     * Get a reliable postcode: shipping -> billing -> session stored values.
     */
    protected function get_customer_postcode() {
        $postcode = '';

        if ( WC()->customer ) {
            // Shipping first
            $postcode = WC()->customer->get_shipping_postcode();
            if ( empty( $postcode ) ) {
                $postcode = WC()->customer->get_billing_postcode();
            }
        }

        // Fallback to posted data in AJAX/fragment updates
        if ( empty( $postcode ) && isset( $_POST['ship_to_different_address'] ) ) {
            if ( ! empty( $_POST['shipping_postcode'] ) ) {
                $postcode = sanitize_text_field( wp_unslash( $_POST['shipping_postcode'] ) );
            } elseif ( ! empty( $_POST['billing_postcode'] ) ) {
                $postcode = sanitize_text_field( wp_unslash( $_POST['billing_postcode'] ) );
            }
        }

        // Last fallback: session values
        if ( empty( $postcode ) && isset( WC()->session ) ) {
            $s_ship_pp = WC()->session->get( 'shipping_postcode' );
            $s_bill_pp = WC()->session->get( 'billing_postcode' );
            if ( ! empty( $s_ship_pp ) ) {
                $postcode = $s_ship_pp;
            } elseif ( ! empty( $s_bill_pp ) ) {
                $postcode = $s_bill_pp;
            }
        }

        return $this->normalize_postcode( $postcode );
    }

    /**
     * Main fee logic. Runs on cart calculate fees.
     */
    public function add_dynamic_fee( $cart ) {
        if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
            return;
        }

        if ( ! $cart || ! is_object( $cart ) ) {
            return;
        }

        $opts = get_option( self::OPTION_KEY, array() );
        $enabled = isset( $opts['enabled'] ) && $opts['enabled'] === 'yes';
        if ( ! $enabled ) {
            return;
        }

        $fee_type = isset( $opts['fee_type'] ) ? $opts['fee_type'] : 'fixed';
        $amount = isset( $opts['amount'] ) ? floatval( $opts['amount'] ) : 0;
        $label = isset( $opts['label'] ) ? $opts['label'] : 'Additional Fee';
        $apply_zip = isset( $opts['apply_zip'] ) && $opts['apply_zip'] === 'yes';
        $zip_codes = isset( $opts['zip_codes'] ) ? $opts['zip_codes'] : '';
        $taxable = isset( $opts['taxable'] ) && $opts['taxable'] === 'yes' ? true : false;

        if ( $amount <= 0 ) {
            return;
        }

        // ZIP/postcode filtering
        if ( $apply_zip ) {
            $postcode = $this->get_customer_postcode();
            if ( empty( $postcode ) ) {
                // If no postcode found yet, skip applying fee this pass. WooCommerce will recalc after address is entered.
                return;
            }
            $allowed = array();
            if ( ! empty( $zip_codes ) ) {
                $parts = explode( ',', $zip_codes );
                foreach ( $parts as $p ) {
                    $p = $this->normalize_postcode( $p );
                    if ( $p !== '' ) {
                        $allowed[] = $p;
                    }
                }
            }
            if ( ! in_array( $postcode, $allowed, true ) ) {
                return;
            }
        }

        // Use cart contents total for percentage calculation (excludes shipping)
        $cart_total_for_calc = floatval( $cart->get_cart_contents_total() );
        if ( $fee_type === 'percent' ) {
            $fee_amount = ( $cart_total_for_calc * $amount ) / 100;
        } else {
            $fee_amount = $amount;
        }

        // Round to 2 decimals to avoid tiny float differences
        $fee_amount = round( (float) $fee_amount, 2 );

        // Check existing fees on cart
        $existing_fee = null;
        $existing_index = null;
        $fees = $cart->get_fees(); // array of WC_Cart_Fee objects
        if ( is_array( $fees ) ) {
            foreach ( $fees as $index => $fee_obj ) {
                if ( isset( $fee_obj->name ) && $fee_obj->name === $label ) {
                    $existing_fee = round( (float) $fee_obj->amount, 2 );
                    $existing_index = $index;
                    break;
                }
            }
        }

        // If existing fee equals new amount, do nothing.
        if ( $existing_fee !== null && abs( $existing_fee - $fee_amount ) < 0.01 ) {
            return;
        }

        // If existing fee exists but with a different amount, add a compensating negative fee to remove it,
        // then add the correct fee. This guarantees the final shown fee equals $fee_amount and prevents duplicates.
        if ( $existing_fee !== null && abs( $existing_fee - $fee_amount ) >= 0.01 ) {
            $comp_label = $label . ' (adjustment)';
            // Add a negative fee to cancel existing fee
            $cart->add_fee( $comp_label, -1 * $existing_fee, false );
        }

        // Finally add the up-to-date fee (taxable depends on settings)
        $cart->add_fee( $label, $fee_amount, $taxable );
    }
}

new WC_Dynamic_Additional_Fee_Improved();
