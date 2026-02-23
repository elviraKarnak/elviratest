<?php

namespace GangSheetBuilder;

class GangSheetWebhook
{

    private $orderProcessed = false;

    public function __construct()
    {
        if (defined('WC_VERSION')) {
            if (version_compare(WC_VERSION, '7.2.0', '<')) {
                add_action('woocommerce_blocks_checkout_order_processed', [$this, 'handle_checkout_order'], PHP_INT_MAX, 1);
            } else {
                add_action('woocommerce_store_api_checkout_order_processed', [$this, 'handle_checkout_order'], PHP_INT_MAX, 1);
            }
        }

        add_action('woocommerce_checkout_order_processed', [$this, 'handle_checkout_order_processed'], PHP_INT_MAX, 3);

        add_action('woocommerce_payment_complete', [$this, 'handle_payment_complete'], PHP_INT_MAX, 2);

        add_action('woocommerce_order_status_changed', [$this, 'handle_order_status_changed'], PHP_INT_MAX, 3);
    }

    public function handle_checkout_order($order)
    {
        if (!$this->orderProcessed) {
            $this->orderProcessed = true;
            $this->post_order($order);
        }
    }

    public function handle_payment_complete($order_id, $transaction_id)
    {
        if (!$this->orderProcessed) {
            $this->orderProcessed = true;
            $order = wc_get_order($order_id);
            $this->post_order($order);
        }
    }

    public function handle_order_status_changed($order_id, $old_status, $new_status)
    {
        $this->post_order_status($order_id, $new_status);
    }

    public function handle_checkout_order_processed($order_id, $posted_data, $order)
    {
        if (!$this->orderProcessed) {
            $this->orderProcessed = true;
            $order = wc_get_order($order_id);
            $this->post_order($order);
        }
    }

    public function post_order($order)
    {
        try {
            $paymentMethod = $order->get_payment_method();
            if ($paymentMethod === 'paypal' or $paymentMethod === 'ppcp-gateway') {
                $cart_items = WC()->session->get('cart');

                foreach ($order->get_items() as $item_id => $item) {
                    if (!$item->get_meta('gs_design_id', true)) {
                        $cart_item_key = $item->get_meta('_bundle_cart_key');

                        if (!$cart_item_key || !isset($cart_items[$cart_item_key])) continue;

                        $original_cart_item = $cart_items[$cart_item_key];

                        $design_id = $original_cart_item['gs_design_id'] ?? null;

                        if ($design_id) {
                            wc_add_order_item_meta($item_id, 'gs_design_id', $design_id);
                            $item->add_meta_data('gs_design_id', $design_id, true);
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            gs_report($exception->getMessage());
        }

        try {
            $order = get_gang_sheet_order($order);

            if (empty($order['error'])) {
                gang_sheet_api_call('POST', 'order', [
                    'order' => $order
                ]);
            }
        } catch (\Exception $exception) {
            gs_report($exception->getMessage());
        }
    }

    public function post_order_status($order_id, $status)
    {
        try {
            gang_sheet_api_call('POST', 'order/status', [
                'order_id' => $order_id,
                'status' => $status
            ]);
        } catch (\Exception $exception) {
            gs_report($exception->getMessage());
        }
    }

}
