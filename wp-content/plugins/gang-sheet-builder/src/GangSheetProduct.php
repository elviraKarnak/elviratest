<?php

namespace GangSheetBuilder;

class GangSheetProduct
{
    public $options;

    public function __construct()
    {
        $this->options = get_gs_options();

        add_action('wp_enqueue_scripts', [$this, 'assets'], PHP_INT_MAX, 1);

        add_action('woocommerce_after_add_to_cart_button', [$this, 'add_gang_sheet_builder_button']);

        add_filter('woocommerce_cart_item_thumbnail', [$this, 'get_gang_sheet_preview'], 10, 3);
    }

    public function assets()
    {
        wp_enqueue_style('gang-sheet-product', gs_asset('css/gang-sheet-product.css'), false);
        if (is_product()) {
            $product_id = get_the_ID();
            $product = get_gang_sheet_product($product_id);
            if (empty($product['error']) && $product['artBoardType'] == 7) {
                wp_enqueue_style('gang-size', gs_asset('css/gang-size.css'), false);
            }

            if (empty($product['error']) && $product['artBoardType'] == 8) {
                wp_enqueue_style('gang-upload', gs_asset('css/gang-upload.css'), false);
            }
        }

        if (is_user_logged_in()) {
            $customer = wp_get_current_user();
        }
        $customer_id = isset($customer) ? $customer->ID : 'undefined';
        $customer_email = isset($customer) && !empty($customer->user_email) ? json_encode($customer->user_email) : 'undefined';

        if (function_exists('is_product') && is_product()) {
            $product_id = get_the_ID();
            $product = get_gang_sheet_product($product_id);
            if (empty($product['error']) && (!empty($product['variants']) || $product['artBoardType'] == 8)) {
                wp_enqueue_script('gang-sheet-product', gs_asset('scripts/gang-sheet-product.js'), false);
            }
        } else {
            ?>
            <script>
                window.appEnv = "<?php echo gs_env(); ?>"
                window.GangSheetOptions = {
                    shop_slug: "<?php echo get_gs_shop_slug(); ?>",
                    gs_version: "<?php echo(defined('GSB_VERSION') ? GSB_VERSION : 'undefined'); ?>",
                    customer: {
                        'id': <?php echo $customer_id; ?>,
                        'email': <?php echo $customer_email; ?>
                    }
                }
            </script>
            <?php
            wp_enqueue_script('gang-sheet-edit', gs_asset('scripts/gang-sheet-edit.js'), false);
        }

        if (is_cart()) {
            wp_enqueue_script('gang-sheet-cart', gs_asset('scripts/gang-sheet-cart.js'), false);
        }

        wp_enqueue_script('gang-sheet-login', gs_asset('scripts/gang-sheet-login.js'), false);
    }

    public function add_gang_sheet_builder_button()
    {
        $product_id = get_the_ID();
        $product = get_gang_sheet_product($product_id);

        if (empty($product['error'])) {
            remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);
            remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30);

            if (is_user_logged_in()) {
                $customer = wp_get_current_user();
            }
            $customer_id = isset($customer) ? $customer->ID : 'undefined';
            $customer_email = isset($customer) && !empty($customer->user_email) ? json_encode($customer->user_email) : 'undefined';

            $btn_label = get_post_meta($product_id, 'btn_label', true);
            $product_type = $product['artBoardType'];

            if ($product_type == 8 || !empty($product['variants'])) {
                ?>
                <script>
                    window.appEnv = "<?php echo gs_env(); ?>"
                    window.GangSheetOptions = {
                        'shop_uuid': "<?php echo get_gs_shop_uuid(); ?>",
                        'shop_slug': "<?php echo get_gs_shop_slug(); ?>",
                        'gs_version': "<?php echo(defined('GSB_VERSION') ? GSB_VERSION : 'undefined'); ?>",
                        'product_id': "<?php echo $product_id; ?>",
                        'product_type': "<?php echo $product_type; ?>",
                        'variants': <?php echo json_encode($product['variants'] ?? []); ?>,
                        'cart_url': "<?php echo wc_get_cart_url(); ?>",
                        'customer': {
                            'id': <?php echo $customer_id; ?>,
                            'email': <?php echo $customer_email; ?>
                        },
                        'btn_label': "<?php echo !empty($btn_label) ? $btn_label : $this->options['btn_text']; ?>",
                        'btn_bg_color': "<?php echo $this->options['btn_bg_color'] ?? 'undefined'; ?>",
                        'btn_fg_color': "<?php echo $this->options['btn_fg_color'] ?? 'undefined'; ?>"
                    }
                </script>
                <button id="gang-sheet-builder-button" type="button" style="display: none"></button>
                <?php
            } else {
                ?>
                <span style="color: red"> No available gang sheet sizes. </span>
                <?php
            }
        }
    }

    public function get_gang_sheet_preview($product_image, $cart_item, $cart_item_key)
    {
        if ($cart_item_key === 'gs_design_id' && !empty($cart_item['gs_design_id'])) {
            return '<img src="' . gs_get_thumbnail_url($cart_item['gs_design_id']) . '" />';
        }

        return $product_image;
    }
}
