<?php
namespace SabaiApps\Directories\Component\WooCommerce\PaymentGateway;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\WooCommerce\PaymentPlan;
use SabaiApps\Directories\Component\WooCommerce\PaymentOrder;
use SabaiApps\Directories\Component\WooCommerce\PaymentSubscription;
use SabaiApps\Directories\Component\WooCommerce\IProduct;

class WooCommercePaymentGateway extends Payment\Gateway\AbstractGateway
{
    protected function _paymentGatewayInfo()
    {
        return ['label' => 'WooCommerce'];
    }

    public function paymentIsEnabled()
    {
        return $this->_application->getComponent('WooCommerce')->isWooCommerceActive();
    }

    public function paymentIsGuestCheckoutEnabled()
    {
        return get_option('woocommerce_enable_guest_checkout') === 'yes';
    }

    public function paymentIsGuestSignupEnabled()
    {
        return get_option('woocommerce_enable_signup_and_login_from_checkout') === 'yes';
    }

    public function paymentIsGuestLoginEnabled()
    {
        return get_option('woocommerce_enable_checkout_login_reminder') === 'yes';
    }

    public function paymentGetCurrency($symbol = false)
    {
        return $symbol ? get_woocommerce_currency_symbol() : get_woocommerce_currency();
    }

    public function paymentGetPlanIds($bundleName, $lang = null)
    {
        global $wpdb;
        $base = 'drts_' . $bundleName;
        $terms = [$base];
        foreach (array_keys($this->_application->getComponent('WooCommerce')->getProductTypes()) as $product_type) {
            if (strpos($product_type, $base . '__') === 0) {
                $terms[] = $product_type;
            }
        }
        if (empty($terms)) return [];

        if ($this->_application->Filter('wooocmmerce_get_plan_ids_by_sql', false, [$bundleName, $lang])) {
            $term_ids = get_terms([
                'taxonomy' => 'product_type',
                'slug' => $terms,
                'fields' => 'ids',
            ]);
            if (is_wp_error($term_ids)) return [];

            $ids = [];
            $sql = 'SELECT ' . $wpdb->prefix . 'posts.ID FROM ' . $wpdb->prefix . 'posts'
                . ' LEFT JOIN ' . $wpdb->prefix . 'term_relationships ON ' . $wpdb->prefix . 'posts.ID = ' . $wpdb->prefix . 'term_relationships.object_id'
                . ' WHERE ' . $wpdb->prefix . 'term_relationships.term_taxonomy_id IN (' . implode(',', $term_ids) . ') AND ' . $wpdb->prefix . 'posts.post_type = \'product\' AND ' . $wpdb->prefix . 'posts.post_status = \'publish\''
                . ' GROUP BY ' . $wpdb->prefix . 'posts.ID ORDER BY ' . $wpdb->prefix . 'posts.menu_order ASC';
            foreach ($wpdb->get_results($sql, ARRAY_N) as $row) {
                $ids[] = $row[0];
            }
        } else {
            $ids = wc_get_products([
                'return' => 'ids',
                'type' => $terms,
                'limit' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC',
                'suppress_filters' => $lang === false ? true : false, // $lang === false for all languages
            ]);
        }

        return $this->_application->Filter('woocommerce_payment_plan_ids', $ids, [$bundleName, $lang]);
    }

    public function paymentGetPlan($id)
    {
        if ((!$product = wc_get_product($id))
            || !$product instanceof IProduct
        ) return;

        return new PaymentPlan($product);
    }

    public function paymentGetOrders($entityId, $limit = 0, $offset = 0)
    {
        global $wpdb;
        $order_items = $ret = [];
        $sql = 'SELECT items.order_item_id, items.order_item_name, items.order_id, itemmeta2.meta_value AS action, itemmeta3.meta_value AS was_deactivated FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id AND items.order_item_type = \'line_item\''
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta2 ON itemmeta2.order_item_id = itemmeta.order_item_id AND itemmeta2.meta_key = \'_drts_action\''
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta3 ON itemmeta3.order_item_id = itemmeta.order_item_id AND itemmeta3.meta_key = \'_drts_was_deactivated\''
            . ' WHERE itemmeta.meta_key = \'_drts_entity_id\' AND itemmeta.meta_value = %d ORDER BY order_id DESC';
        if (!empty($limit)) {
            $sql .= ' LIMIT %d, %d';
            $query = $wpdb->prepare($sql, $entityId, $offset, $limit);
        } else {
            // WPDB::prepare needs to be passed parameters exactly tne same number of placeholders
            $query = $wpdb->prepare($sql, $entityId);
        }
        foreach ($wpdb->get_results($query, ARRAY_N) as $row) {
            $order_items[$row[2]][$row[0]] = array($row[1], ['action' => $row[3], 'was_deactivated' => !empty($row[4])]);
        }
        if (!empty($order_items)) {
            foreach ($order_items as $order_id => $_order_items) {
                if ((!$order = wc_get_order($order_id))
                    || $order->get_type() !== 'shop_order'
                ) continue;

                foreach ($_order_items as $item_id => $item) {
                    $ret[] = new PaymentOrder($order, $item_id, $item[0], $item[1], $entityId);
                }
            }
        }

        return $ret;
    }

    public function paymentGetOrder($orderItemId = null)
    {
        if (($order_id = wc_get_order_id_by_order_item_id($orderItemId))
            && ($order = wc_get_order($order_id))
        ) {
            foreach ($order->get_items() as $item_id => $item) {
                if ($item_id == $orderItemId) {
                    return new PaymentOrder(
                        $order,
                        $item_id,
                        $item->get_name(),
                        ['action' => $item->get_meta('_drts_action'), 'was_deactivated' => $item->get_meta('_drts_was_deactivated') ? true : false],
                        $item->get_meta('_drts_entity_id')
                    );
                }
            }
        }
    }

    public function paymentGetUserOrders($userId, $limit = 0, $offset = 0)
    {
        global $wpdb;
        $order_items = $ret = [];
        $sql = 'SELECT items.order_item_id, items.order_item_name, items.order_id, itemmeta.meta_value AS action, itemmeta2.meta_value AS entity_id, itemmeta3.meta_value AS was_deactivated'
            . ' FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
            . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta2 ON itemmeta2.order_item_id = itemmeta.order_item_id'
            . ' AND itemmeta2.meta_key = \'_drts_entity_id\''
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta3 ON itemmeta3.order_item_id = itemmeta.order_item_id'
            . ' AND itemmeta3.meta_key = \'_drts_was_deactivated\''
            . ' WHERE itemmeta.meta_key = \'_drts_action\''
            . ' AND posts.post_parent = 0'
            . ' AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d'
            . ' ORDER BY order_id DESC';
        if (!empty($limit)) {
            $sql .= ' LIMIT %d, %d';
        }
        $query = $wpdb->prepare($sql, $userId, $offset, $limit);
        foreach ($wpdb->get_results($query, ARRAY_N) as $row) {
            $order_items[$row[2]][$row[0]] = array($row[1], ['action' => $row[3], 'was_deactivated' => !empty($row[5])], $row[4]);
        }
        if (!empty($order_items)) {
            foreach ($order_items as $order_id => $_order_items) {
                if (!$order = wc_get_order($order_id)) continue;

                foreach ($_order_items as $item_id => $item) {
                    $ret[] = new PaymentOrder($order, $item_id, $item[0], $item[1], $item[2]);
                }
            }
        }

        return $ret;
    }

    public function paymentCountUserOrders($userId)
    {
        global $wpdb;
        $sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
            . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' WHERE itemmeta.meta_key = \'_drts_action\' AND posts.post_parent = 0 AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d';
        return $wpdb->get_var($wpdb->prepare($sql, $userId));
    }

    public function paymentHasPendingOrder(Entity\Type\IEntity $entity, array $actions)
    {
        if ($this->_application->getPlatform()->isTranslatable($entity->getType(), $entity->getBundleName())) {
            // Include orders for translated entities
            $entity_ids = $this->_application->Entity_Translations_ids($entity);
        } else {
            $entity_ids = [];
        }
        $entity_ids[] = $entity->getId();
        $db = $this->_application->getDB();
        $statuses = [
            'wc-completed', 'wc-refunded', 'wc-cancelled', 'wc-failed', 'trash', 'wc-expired',
            'wc-active' // WC Subscriptions
        ];
        $sql = sprintf('
            SELECT COUNT(*) FROM %1$sposts posts
              LEFT JOIN %1$swoocommerce_order_items item ON item.order_id = posts.ID
              LEFT JOIN %1$swoocommerce_order_itemmeta meta1 ON meta1.order_item_id = item.order_item_id
              LEFT JOIN %1$swoocommerce_order_itemmeta meta2 ON meta2.order_item_id = item.order_item_id
              WHERE posts.post_status NOT IN (%2$s)
                AND meta1.meta_key = \'_drts_entity_id\' AND meta1.meta_value IN (%2$s)
                AND meta2.meta_key = \'_drts_action\' AND meta2.meta_value IN (%3$s)',
            $GLOBALS['wpdb']->prefix,
            implode(',', array_map([$db, 'escapeString'], $statuses)),
            implode(',', $entity_ids),
            implode(',', array_map([$db, 'escapeString'], $actions))
        );

        return $this->_application->getDB()->query($sql)->fetchSingle() > 0;
    }

    public function paymentOnSubmit(Entity\Type\IEntity $entity, Payment\IPlan $plan, $action)
    {
        if (!$plan instanceof PaymentPlan) return false;

        if (in_array($action, ['add', 'claim', 'submit'])
            && $plan->paymentPlanType() === 'base'
            && $plan->paymentPlanIsFree()
            && $this->_application->getComponent('Payment')->getConfig($this->_name, 'free_no_checkout')
            && ($user_id = $entity->getAuthorId())
        ) {
            // Manually create order and process
            $order = wc_create_order(['status' => 'pending']);
            $order->set_customer_id($user_id);
            $item_id = $order->add_product($plan->getProduct(), 1);
            if (!$item = $order->get_item($item_id, false)) {
                throw new Exception\RuntimeException('Failed fetching order item for free payment plan.');
            }
            // Add required order item meta data
            foreach ([
                '_drts_action' => $action,
                '_drts_entity_id' => $entity->getId(),
                '_drts_entity_type' => $entity->getType(),
                'drts_post_id' => $entity->getId(), // for displaying order details
                'drts_action' => $this->_application->Payment_Util_actionLabel($action),
            ] as $meta_key => $meta_value) {
                $item->add_meta_data($meta_key, $meta_value);
            }
            // Set billing address
            $user_meta = get_user_meta($user_id);
            $address = [];
            foreach (['first_name', 'last_name', 'email', 'phone', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country'] as $addr_field) {
                $address[$addr_field] = isset($user_meta['billing_' . $addr_field][0]) ? $user_meta['billing_' . $addr_field][0] : '';
            }
            $order->set_address($address, 'billing');
            $order->calculate_totals();

            // Update entity status
            if (!$entity->isPublished()
                && !$entity->isPending()
            ) {
                $this->_application->Entity_Save($entity, [
                    'status' => $this->_application->Entity_Status($entity->getType(), 'pending'),
                ]);
            }

            try {
                // Create features
                $this->_application->Payment_Features_create($entity, $plan, $item_id);
            } catch (Exception\IException $e) {
                $this->_application->logError('Failed creating features for entity. Entity type: ' . $entity->getType() . '; Entity ID: ' . $entity->getId() . '; Order item ID: ' . $item->get_id());
            }

            // Mark order completed
            if (!$order->update_status('completed', 'Order for free payment plan completed manually.', true)) {
                throw new Exception\RuntimeException('Error updating order status to completed for free payment plan.');
            }

            return false; // return false to cancel checkout
        }

        // Remove from cart if already added
        if ($cart_item_key = $this->_isEntityInCart($entity)) {
            WC()->cart->remove_cart_item($cart_item_key);
        }

        // Add to cart
        $meta = [
            '_drts_action' => $action,
            '_drts_entity_id' => $entity->getId(),
            '_drts_entity_type' => $entity->getType(),
            'drts_post_id' => $entity->getId(), // for displaying order details
        ];
        if ($action === 'submit') {
            $was_deactivated = $entity->getSingleFieldValue('payment_plan', 'deactivated_at') ? 1 : 0;
            $meta['_drts_was_deactivated'] = $was_deactivated;
            $meta['drts_action'] = $this->_application->Payment_Util_actionLabel($action, $was_deactivated);  // for displaying order details
        } else {
            $meta['drts_action'] = $this->_application->Payment_Util_actionLabel($action); // for displaying order details
        }
        WC()->cart->add_to_cart(
            $plan->paymentPlanId(),
            1, // quantity
            0, // variation ID
            [], // variation attributes
            $meta
        );

        return true;
    }

    public function paymentCheckoutUrl()
    {
        if ($this->_application->getComponent('Payment')->getConfig($this->_name, 'bypass_cart')) {
            return wc_get_checkout_url();
        }
        return wc_get_cart_url();
    }

    public function paymentIsSubscriptionEnabled()
    {
        return $this->_application->getComponent('WooCommerce')->isWCSActive();
    }

    public function paymentGetSubscription($subscriptionId, $itemId)
    {
        if ($subscription = wcs_get_subscription($subscriptionId)) {
            foreach ($subscription->get_items() as $item_id => $item) {
                if ($item_id == $itemId) {
                    return new PaymentSubscription(
                        $subscription,
                        $item_id,
                        $item->get_name(),
                        $item->get_meta('_drts_entity_id')
                    );
                }
            }
        }
    }

    public function paymentGetUserSubscriptions($userId, $limit = 0, $offset = 0)
    {
        global $wpdb;
        $order_items = $ret = [];
        $sql = 'SELECT items.order_item_id, items.order_item_name, items.order_id, itemmeta2.meta_value AS entity_id'
            . ' FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
            . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta2 ON itemmeta2.order_item_id = itemmeta.order_item_id'
            . ' AND itemmeta2.meta_key = \'_drts_entity_id\''
            . ' WHERE itemmeta.meta_key = \'_drts_action\''
            . ' AND posts.post_parent != 0'
            . ' AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d'
            . ' ORDER BY order_id DESC';
        if (!empty($limit)) {
            $sql .= ' LIMIT %d, %d';
        }
        $query = $wpdb->prepare($sql, $userId, $offset, $limit);
        foreach ($wpdb->get_results($query, ARRAY_N) as $row) {
            $order_items[$row[2]][$row[0]] = array($row[1], $row[3]);
        }
        if (!empty($order_items)) {
            foreach ($order_items as $order_id => $_order_items) {
                $subscription = wcs_get_subscription($order_id);
                foreach ($_order_items as $item_id => $item) {
                    $ret[] = new PaymentSubscription($subscription, $item_id, $item[0], $item[1]);
                }
            }
        }

        return $ret;
    }

    public function paymentCountUserSubscriptions($userId)
    {
        global $wpdb;
        $sql = 'SELECT COUNT(*) FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id'
            . ' AND items.order_item_type = \'line_item\''
            . ' INNER JOIN ' . $wpdb->prefix . 'posts posts ON posts.ID = items.order_id'
            . ' INNER JOIN ' . $wpdb->prefix . 'postmeta postmeta ON postmeta.post_id = posts.ID'
            . ' WHERE itemmeta.meta_key = \'_drts_action\' AND posts.post_parent != 0 AND postmeta.meta_key = \'_customer_user\' AND postmeta.meta_value = %d';
        return $wpdb->get_var($wpdb->prepare($sql, $userId));
    }

    public function paymentGetClaimOrderId($claimId)
    {
        global $wpdb;
        $sql = 'SELECT items.order_item_id FROM ' . $wpdb->prefix . 'woocommerce_order_itemmeta itemmeta'
            . ' LEFT JOIN ' . $wpdb->prefix . 'woocommerce_order_items items ON itemmeta.order_item_id = items.order_item_id AND items.order_item_type = \'line_item\''
            . ' WHERE itemmeta.meta_key = \'_drts_entity_id\' AND itemmeta.meta_value = %d';
        return $wpdb->get_var($wpdb->prepare($sql, $claimId));
    }

    public function paymentRefundOrder($orderId, $reason = '')
    {
        $this->_application->WooCommerce_RefundOrder($orderId, $reason);
    }

    public function paymentGetPlanId($planId, $lang)
    {
        return $this->_application->getPlatform()->getTranslatedId('post', 'product', $planId, $lang);
    }

    public function paymentSettingsForm(array $settings, array $parents)
    {
        $form = [
            'free_no_checkout' => [
                '#type' => 'checkbox',
                '#title' => __('Skip cart/checkout pages for free payment plans', 'directories-payments'),
                '#default_value' => !empty($settings['free_no_checkout']),
                '#horizontal' => true,
            ],
            'bypass_cart' => [
                '#type' => 'checkbox',
                '#title' => __('Skip cart page', 'directories-payments'),
                '#default_value' => !empty($settings['bypass_cart']),
                '#horizontal' => true,
            ],
        ];
        if ($this->_application->getComponent('WooCommerce')->isWCMActive()) {
            $form['memberships'] = [
                'expire' => [
                    '#type' => 'checkbox',
                    '#title' => __('Expire payment plans when WooCommerce Memberships membership becomes inactive', 'directories-payments'),
                    '#default_value' => !empty($settings['memberships']['expire']),
                    '#horizontal' => true,
                ],
            ];
        }

        return $form;
    }

    protected function _isEntityInCart(Entity\Type\IEntity $entity)
    {
        foreach (WC()->cart->get_cart() as $cart_item_key => $values) {
            if ($values['data'] instanceof IProduct
                && isset($values['_drts_entity_id'])
                && $values['_drts_entity_id'] === $entity->getId()
            ) {
                return $cart_item_key;
            }
        }
        return false;
    }
}