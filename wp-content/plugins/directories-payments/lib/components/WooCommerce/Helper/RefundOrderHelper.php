<?php
namespace SabaiApps\Directories\Component\WooCommerce\Helper;

use SabaiApps\Directories\Application;

class RefundOrderHelper
{
    public function help(Application $application, $orderItemId, $reason = '')
    {
        $order_item_id = intval($orderItemId);
        try {
            $order_id = wc_get_order_id_by_order_item_id($order_item_id);
        } catch (\Exception $e) {
            $application->logError($e);
            return;
        }

        $order = wc_get_order($order_id);
        if (!$order instanceof \WC_Order) {
            $application->logError('Failed fetching WooCommerce order (ID: ' . $order_id . ').');
            return;
        }

        if (!$order_items = $order->get_items()) return;

        $amount = 0;
        $line_items = [];
        foreach ($order_items as $item_id => $item) {
            if (intval($item_id) !== $order_item_id) continue;

            $item_meta 	= $order->get_item_meta($item_id);
            $tax_data = $item_meta['_line_tax_data'];
            $refund_tax = 0;
            if (is_array($tax_data[0])) {
                $refund_tax = array_map('wc_format_decimal', $tax_data[0]);
            }
            $amount += wc_format_decimal($item_meta['_line_total'][0]);
            $line_items[$item_id] = [
                'qty' => $item_meta['_qty'][0],
                'refund_total' => wc_format_decimal($item_meta['_line_total'][0]),
                'refund_tax' => $refund_tax,
            ];
        }

        $refund =  wc_create_refund([
            'amount' => $amount,
            'reason' => $reason,
            'order_id' => $order_id,
            'line_items' => $line_items,
            'refund_payment' => true
        ]);

        if (is_wp_error($refund)) {
            $log = 'Refund for item (Order item ID: ' . $order_item_id . ') in order (ID: ' . $order_id . ') failed.'
                . ' Error: ' . $refund->get_error_message();
            $application->logError($log);
            return;
        }

        return $refund;
    }
}