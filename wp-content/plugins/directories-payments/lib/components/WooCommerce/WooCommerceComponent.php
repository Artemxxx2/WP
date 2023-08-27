<?php
namespace SabaiApps\Directories\Component\WooCommerce;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Payment;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Request;

class WooCommerceComponent extends AbstractComponent implements Payment\IGateways
{
    const VERSION = '1.3.108', PACKAGE = 'directories-payments';

    public static function description()
    {
        return 'Enables integration with WooCommerce for accepting payments.';
    }

    public static function events()
    {
        return [
            // Make sure the callback is called after Dashboard component
            'directoryadminsettingsformfilter' => 99,
        ];
    }

    public function onCorePlatformWordpressInit()
    {
        if (!$this->isWooCommerceActive()) return;

        // Register product types
        $product_types = $this->_registerProductTypes();

        if (is_admin()) {
            // Product admin page
            add_action('admin_footer', [$this, 'adminFooterAction']);
            add_filter('product_type_selector', [$this, 'productTypeSelectorFilter']);
            add_filter('woocommerce_product_data_tabs', [$this, 'woocommerceProductDataTabsFilter'], 11);
            add_action('woocommerce_product_data_panels', [$this, 'woocommerceProductDataPanelsAction']);
            foreach ($product_types as $type) {
                add_action('woocommerce_process_product_meta_' . $type, [$this, 'woocommerceProcessProductMetaAction']);
            }
        }

        // Add to cart URL and text
        add_filter('woocommerce_product_add_to_cart_url', [$this, 'woocommerceProductAddToCartUrlFilter'], 10, 2);
        add_filter('woocommerce_product_add_to_cart_text', [$this, 'woocommerceProductAddToCartTextFilter'], 10, 2);

        // Cart
        add_filter('woocommerce_get_item_data', [$this, 'woocommerceGetItemDataFilter'], 10, 2);
        add_filter('woocommerce_cart_calculate_fees', [$this, 'woocommerceCartCalculateFeesFilter']);

        // Checkout
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'woocommerceCheckoutCreateOrderLineItemAction'], 10, 4);
        add_action('woocommerce_checkout_order_processed', [$this, 'woocommerceCheckoutOrderProcessedAction'], 10, 3);
        add_filter('woocommerce_order_again_cart_item_data', [$this, 'woocommerceOrderAgainCartItemDataFilter'], 10, 3);

        // Monitor order status
        add_action('woocommerce_order_status_changed', [$this, 'woocommerceOrderStatusChangedAction'], 10, 3);

        // Remove order again button
        add_filter('wc_get_template', [$this, 'wcGetTemplateFilter'], 10, 3);

        // Show order details
        add_filter('woocommerce_order_item_display_meta_key', [$this, 'woocommerceOrderItemDisplayMetaKeyFilter'], 10, 2);
        add_filter('woocommerce_order_item_display_meta_value', [$this, 'woocommerceOrderItemDisplayMetaValueFilter'], 10, 2);

        // My Account page
        $this->_initMyAccountPage();

        // WooCommerce Subscriptions
        if ($this->isWCSActive()) {
            // Required to save subscription meta data
            add_filter('woocommerce_subscription_product_types', [$this, 'wcSubscriptionProductTypesFilter']);

            // Required to show price HTML
            add_filter('woocommerce_is_subscription', [$this, 'wcIsSubscriptionFilter'], 10, 3);

            // Monitor subscription status
            add_action('woocommerce_subscription_status_updated', [$this, 'wcSubscriptionStatusUpdatedAction'], 10, 3);

            // Post link from switch subscription form
            add_filter('post_type_link', [$this, 'wcSubscriptionSwitchPostLinkFilter'], 12, 2);

            // Record subscription switching in the cart
            add_filter('woocommerce_add_cart_item_data', [$this, 'wcSubscriptionSwitchDetailsInCartFilter'], 11, 3);

            // Called upon subscription switch completion
            add_action('woocommerce_subscriptions_switch_completed', [$this, 'wcSubscriptionSwitchCompletedAction'], 10);

            // Disable re-subscription action since reactivation should be done through Directories only
            add_filter('wcs_view_subscription_actions', [$this, 'wcSubscriptionViewSubscriptionActionsFilter'], 10, 2);
        }

        if ($this->isWCMActive()) {
            add_action('wc_memberships_user_membership_status_changed', [$this, 'wcMembershipsUserMembershipStatusChangedAction'], 10, 3);
        }
    }

    protected function _registerProductTypes()
    {
        $ret = [];
        $product_types = $this->getProductTypes(false);
        foreach (array_keys($product_types) as $plan_type) {
            foreach (array_keys($product_types[$plan_type]['name']) as $type) {
                $ret[] = $type;
                $class = isset($product_types[$plan_type]['class']) ? $product_types[$plan_type]['class'] : '\WC_Product_Simple';
                if (!class_exists($class)) continue; // needs autoload set to true in order for this to work.

                $code = 'class WC_Product_' . $type . ' extends ' . $class . ' implements \SabaiApps\Directories\Component\WooCommerce\IProduct
{
    public function get_type()
    {
        return "' . $type . '";
    }

    public function get_sabai_plan_type()
    {
        return "' . $plan_type . '";
    }

    public function get_sabai_bundle_name()
    {
        return substr($this->get_type(), strlen("drts_"));
    }
        
    public function get_reviews_allowed($context = "view") {
        return "closed";
    }
}';
                eval($code);
            }
        }

        return $ret;
    }

    public function adminFooterAction()
    {
        if ('product' !== get_post_type()) return;

        ?><script type='text/javascript'>
        jQuery('body').bind('woocommerce-product-type-change',function() {
            jQuery('#woocommerce-product-data .panel-wrap').attr('data-drts-woocommerce-product-type', jQuery('select#product-type').val());
        });
        </script><?php

        $product_types = array_keys($this->getProductTypes());
        $show_class = 'show_if_' . implode(' show_if_', $product_types);
        $hide_class = 'hide_if_' . implode(' hide_if_', $product_types);
        ?>
        <script type='text/javascript'>
            document.addEventListener("DOMContentLoaded", function(e) {
                var $ = jQuery;
                $('#woocommerce-product-data')
                    .find('.options_group.pricing').addClass('<?php echo $show_class;?>').end()
                    .find('.options_group.reviews').addClass('<?php echo $hide_class;?>').end()
                    .find('.options_group.show_if_downloadable').addClass('<?php echo $hide_class;?>').end()
                    .find('._tax_status_field').closest('.options_group').addClass('<?php echo $show_class;?>').css('display', 'block');
                if ($.inArray($('select#product-type').val(), ['<?php echo implode("', '", $product_types);?>']) !== -1) {
                    $('#woocommerce-product-data')
                        .find('.options_group.pricing').css('display', 'block').end()
                        .find('.options_group.reviews').css('display', 'none').end()
                        .find('.options_group.show_if_downloadable').css('display', 'none').end()
                        .find('.product_data_tabs .general_options').css('display', 'block')
                        .find('a').click();
                }
            });
        </script>
        <?php
        $product_types = $this->_application->WooCommerce_ProductTypes();
        if (!empty($product_types['subscription']['name'])) {
            $subscription_product_type_names = array_keys($product_types['subscription']['name']);
            $show_class = 'show_if_' . implode(' show_if_', $subscription_product_type_names);
            ?>
            <script type='text/javascript'>
                document.addEventListener("DOMContentLoaded", function (e) {
                    var $ = jQuery;
                    jQuery('#woocommerce-product-data')
                        .find('.options_group.subscription_pricing').addClass('<?php echo $show_class;?>').end()
                        .find('.options_group.limit_subscription').addClass('<?php echo $show_class;?>');
                    if ($.inArray($('select#product-type').val(), ['<?php echo implode("', '", $subscription_product_type_names);?>']) !== -1) {
                        $('#woocommerce-product-data')
                            .find('.options_group.subscription_pricing').css('display', 'block').end()
                            .find('.options_group.limit_subscription').css('display', 'block');
                    }
                });
                jQuery('body').bind('woocommerce-product-type-change.drts', function () {
                    var $ = jQuery;
                    if ($.inArray($('select#product-type').val(), ['<?php echo implode("', '", $subscription_product_type_names);?>']) !== -1) {
                        setTimeout(function () {
                            $('#woocommerce-product-data')
                                .find('.options_group.pricing ._regular_price_field').css('display', 'none').end()
                                .find('#sale-price-period').css('display', 'inline').end()
                                .find('.hide_if_subscription').css('display', 'none');
                        }, 100);
                    }
                });
            </script>
            <?php
        }
    }

    public function productTypeSelectorFilter($types)
    {
        $types += $this->getProductTypes();

        return $types;
    }

    public function woocommerceProductDataTabsFilter($tabs)
    {
        $product_types = $this->getProductTypes();

        // Hide tabs except for General and Advanced tabs
        $class = 'hide_if_' . implode(' hide_if_', array_keys($product_types));
        foreach (array_keys($tabs) as $tab) {
            if (!in_array($tab, ['general', 'advanced'])) {
                if (!isset($tabs[$tab]['class'])) {
                    $tabs[$tab]['class'] = [];
                } else {
                    if (!is_array($tabs[$tab]['class'])) {
                        settype($tabs[$tab]['class'], 'array');
                    }
                }
                $tabs[$tab]['class'][] = $class;
            }
        }

        // Add product type specific tabs
        foreach (array_keys($product_types) as $type) {
            $tab_name_suffix = substr($type, strlen('drts_')); // remove drts_
            $tabs['drts_settings_' . $tab_name_suffix] = [
                'label' => __('Plan Features', 'directories-payments'),
                'target' => 'drts_settings_' . $tab_name_suffix,
                'class' => ['show_if_' . $type, 'drts_plan_features'],
            ];
        }

        return $tabs;
    }

    public function woocommerceProductDataPanelsAction()
    {
        $post_id = intval(empty($GLOBALS['thepostid']) ? $GLOBALS['post']->ID : $GLOBALS['thepostid']);
        $product_types = $this->getProductTypes();
        foreach (array_keys($product_types) as $type) {
            $tab_name_suffix = substr($type, strlen('drts_')); // remove drts_
            $tab_name = 'drts_settings_' . $tab_name_suffix;
            if (($dash_pos = strpos($tab_name_suffix, '__'))
                && $dash_pos + 1 === strrpos($tab_name_suffix, '_') // make sure __ appears as the last underscore chars
            ) {
                $plan_type = substr($tab_name_suffix, $dash_pos + 2);
                $bundle_name = substr($tab_name_suffix, 0, $dash_pos);
            } else {
                $plan_type = 'base';
                $bundle_name = $tab_name_suffix;
            }
            if ((!$bundle = $this->_application->Entity_Bundle($bundle_name))
                || empty($bundle->info['payment_enable'])
            ) continue;

            $form = $this->_getPlanFeaturesForm($bundle, $type, $plan_type, $post_id)->render();
            echo '<div id="' . $tab_name . '" class="panel woocommerce_options_panel drts">';
            echo '<div class="' . $form->getFormTagClass() . '" id="' . $form->settings['#id'] . '">';
            echo $form->getHtml();
            echo '</div></div>';
            echo $form->getHiddenHtml();
            echo $form->getJsHtml();
        }
        if (isset($form)) {
            $this->_application->getPlatform()->loadDefaultAssets();
        }
    }

    protected function _getPlanFeaturesForm(Entity\Model\Bundle $bundle, $productType, $planType, $postId)
    {
        return $this->_application->Form_Build(array(
            '#tree' => true,
            '#build_id' => false,
            '#token' => false,
            '_' . $productType => array(
                'features' => $this->_application->Payment_Features_form(
                    $bundle,
                    $planType,
                    (array)get_post_meta($postId, '_drts_entity_features', true) + (array)get_post_meta($postId, '_drts_entity_features_disabled', true),
                    array('_' . $productType, 'features')
                ),
            ),
        ));
    }

    protected function _submitPlanFeaturesForm(Entity\Model\Bundle $bundle, $productType, $planType, $postId, array $values)
    {
        $form = $this->_getPlanFeaturesForm($bundle, $productType, $planType, $postId);
        if (!$form->submit($values)) return;

        $features = $form->values['_' . $productType]['features'];
        update_post_meta($postId, '_drts_entity_features', $features['enabled']);
        update_post_meta($postId, '_drts_entity_features_disabled', $features['disabled']);
        return true;
    }

    public function woocommerceProcessProductMetaAction($postId)
    {
        $type = $_POST['product-type'];
        if (strpos($type, 'drts_') === 0
            && !empty($_POST['_' . $type])
        ) {
            if ($dash_pos = strpos($type, '__')) {
                $plan_type = substr($type, $dash_pos + 2);
                $bundle_name = substr($type, strlen('drts_'), -1 * (strlen($plan_type) + 2));
            } else {
                $plan_type = 'base';
                $bundle_name = substr($type, strlen('drts_'));
            }
            if ((!$bundle = $this->_application->Entity_Bundle($bundle_name))
                || !$this->_submitPlanFeaturesForm($bundle, $type, $plan_type, $postId, $_POST)
            ) return;

            update_post_meta($postId, '_virtual', 'yes');
            update_post_meta($postId, '_downloadable', 'yes');
            update_post_meta($postId, '_sold_individually', 'yes'); // prevent quantity change

            // Set catalog visibility hidden
            wp_set_object_terms($postId, array('exclude-from-search', 'exclude-from-catalog'), 'product_visibility', true);
        }
    }

    public function woocommerceGetItemDataFilter($itemData, $cartItem)
    {
        if (!$cartItem['data'] instanceof IProduct) return $itemData;

        if (!$entity = $this->_application->Entity_Entity($cartItem['_drts_entity_type'], $cartItem['_drts_entity_id'])) {
            $this->_application->logWarning('Failed fetching entity from cart data.');
            return $itemData;
        }

        // Show item data in cart
        $itemData[] = array(
            'name' =>  $this->_application->Payment_Util_actionLabel($cartItem['_drts_action'], !empty($cartItem['_drts_was_deactivated'])),
            'value' => $this->_application->Entity_Title($entity),
        );

        return $itemData;
    }

    public function woocommerceCartCalculateFeesFilter()
    {
        foreach (WC()->cart->get_cart() as $cart_item) {
            if (!$cart_item['data'] instanceof IProduct
                || empty($cart_item['_drts_entity_type'])
                || empty($cart_item['_drts_entity_id'])
                || (!$entity = $this->_application->Entity_Entity($cart_item['_drts_entity_type'], $cart_item['_drts_entity_id']))
                || (!$fees = $this->_application->Payment_CalculateFees($entity, $cart_item['data'], $cart_item['_drts_action']))
            ) continue;

            foreach (array_keys($fees) as $fee_name) {
                $fee = $fees[$fee_name];
                if (empty($fee['is_discount'])) {
                    WC()->cart->add_fee($fee['label'], $fee['amount'], true, '');
                } else {
                    if (0 < $discount = wc_cart_round_discount($fee['amount'], wc_get_price_decimals())) {
                        WC()->cart->add_fee($fee['label'], -1 * $discount);
                    }
                }
            }
        }
    }

    public function woocommerceCheckoutCreateOrderLineItemAction($item, $cartItemKey, $values, $order)
    {
        if (($product = $item->get_product())
            && $product instanceof IProduct
        ) {
            foreach (array_keys($values) as $key) {
                if (strpos($key, '_drts_') === 0 // private meta keys
                    || strpos($key, 'drts_') === 0 // public meta keys
                ) {
                    $item->add_meta_data($key, $values[$key]);
                }
            }
        }
    }

    protected function _getEntityByOrderItem($item, array $productTypes = null)
    {
        if (($product = $item->get_product())
            && $product instanceof IProduct
            && (!isset($productTypes) || in_array($product->get_sabai_plan_type(), $productTypes))
            && ($entity_type = $item->get_meta('_drts_entity_type'))
            && ($entity_id = $item->get_meta('_drts_entity_id'))
            && ($entity = $this->_application->Entity_Entity($entity_type, $entity_id))
        ) {
            return $entity;
        }
    }

    public function woocommerceCheckoutOrderProcessedAction($orderId, $postedData, $order)
    {
        foreach ($order->get_items() as $item) {
            if (!$entity = $this->_getEntityByOrderItem($item)) continue;

            if ($item->get_product()->get_sabai_plan_type() !== 'addon') {
                $values_to_save = [];
                if (!$entity->isPublished() // may already be published if renew/upgrade/downgrade
                    && !$entity->isPending()
                ) {
                    // Update post status to pending
                    $values_to_save['status'] = $this->_application->Entity_Status($entity->getType(), 'pending');
                }
                if (!$entity->getAuthorId()
                    && $order->get_user_id()
                ) {
                    // Guest has created an account on checkout
                    $values_to_save['author'] = $order->get_user_id();
                }
                if (!empty($values_to_save)) {
                    try {
                        $entity = $this->_application->Entity_Save($entity, $values_to_save);
                    } catch (Exception\IException $e) {
                        $this->_application->logError($e->getMessage());
                    }
                }
            }

            try {
                // Create features
                $this->_application->Payment_Features_create($entity, new PaymentPlan($item->get_product()), $item->get_id());
            } catch (Exception\IException $e) {
                $this->_application->logError('Failed creating features for entity. Entity type: ' . $entity->getType() . '; Entity ID: ' . $entity->getId() . '; Order item ID: ' . $item->get_id());
                continue;
            }
        }
    }

    public function woocommerceOrderAgainCartItemDataFilter($cartItemData, $item, $order)
    {
        foreach (['subscription_resubscribe' => 'resubscribe', 'subscription_renewal' => 'renew'] as $key => $action) {
            if (isset($cartItemData[$key])) {
                $cartItemData['_drts_entity_type'] = $cartItemData[$key]['custom_line_item_meta']['_drts_entity_type'];
                $cartItemData['_drts_entity_id'] = $cartItemData[$key]['custom_line_item_meta']['_drts_entity_id'];
                $cartItemData['_drts_action'] = $action;
                $cartItemData['drts_action'] = $this->_application->Payment_Util_actionLabel($action);
            }
        }
        return $cartItemData;
    }

    public function woocommerceOrderStatusChangedAction($orderId, $oldStatus, $newStatus)
    {
        switch ($newStatus) {
            case 'completed':
                if (!in_array($oldStatus, ['cancelled', 'refunded'])) {
                    $this->_onOrderStatusChanged($orderId);
                }
                break;
            case 'cancelled':
            case 'refunded':
                $this->_onOrderStatusChanged($orderId, 'cancel');
                break;
            case 'failed':
                $this->_onOrderStatusChanged($orderId, 'fail');
                break;
        }
    }

    protected function _onOrderStatusChanged($orderId, $action = null)
    {
        if (!$order = wc_get_order($orderId)) return;

        foreach ($order->get_items() as $item) {
            if (!$entity = $this->_getEntityByOrderItem($item, ['base', 'addon'])) continue;

            switch ($action) {
                case 'cancel':
                    $this->_application->Payment_Util_deactivateEntities($entity->getType(), [$entity->getId() => $entity]);
                    break;
                case 'fail':
                    $this->_application->Payment_Util_trashEntities($entity->getType(), [$entity->getId() => $entity]);
                    break;
                default:
                    try {
                        $this->_application->Payment_Features_apply($entity, $item->get_id());
                    } catch (Exception\IException $e) {
                        $this->_application->logError($e);
                    }
            }
        }
    }

    public function wcGetTemplateFilter($located, $templateName, $args)
    {
        // Do not show the default order again button if the order includes products from SabaiApps applications
        if ($templateName === 'order/order-again.php'
            && isset($args['order'])
        ) {
            foreach ($args['order']->get_items() as $item) {
                if (($product = $item->get_product())
                    && $product instanceof IProduct
                ) {
                    return $this->_application->getPlatform()->getAssetsDir('directories-payments') . '/templates/woocommerce_order_again.html.php';
                }
            }
        }
        return $located;
    }

    public function paymentGetGatewayNames()
    {
        return ['woocommerce'];
    }

    public function paymentGetGateway($name)
    {
        return new PaymentGateway\WooCommercePaymentGateway($this->_application, $name);
    }

    public function isWooCommerceActive()
    {
        return defined('WC_VERSION');
    }

    public function getProductTypes($flatten = true)
    {
        $product_types = $this->_application->WooCommerce_ProductTypes();
        if (!$flatten) return $product_types;

        $ret = [];
        foreach (array_keys($product_types) as $plan_type) {
            $ret += $product_types[$plan_type]['name'];
        }
        asort($ret);
        return $ret;
    }

    public function woocommerceOrderItemDisplayMetaKeyFilter($displayKey, $meta)
    {
        if ($meta->key === 'drts_action') {
            $displayKey = __('Purchase type', 'directories-payments');
        } elseif ($meta->key === 'drts_post_id') {
            $displayKey = __('Purchase item', 'directories-payments');
        }
        return $displayKey;
    }

    public function woocommerceOrderItemDisplayMetaValueFilter($displayValue, $meta)
    {
        if ($meta->key === 'drts_post_id') {
            if ($entity = $this->_application->Entity_Entity('post', $displayValue)) {
                $displayValue = sprintf('%s (ID: %d)', $this->_application->Entity_Title($entity), $displayValue);
            } else {
                $displayValue = 'ID: ' . $displayValue;
            }
        }
        return $displayValue;
    }

    public function onFormBuildDirectoryAdminSettingsForm(&$form)
    {
        if (!$this->isWooCommerceActive()) return;

        if ($this->_application->isComponentLoaded('Dashboard')) {
            $form['Dashboard']['Dashboard']['woocommerce'] = [
                '#title' => __('WooCommerce "My account" Page Integration', 'directories-payments'),
                '#class' => 'drts-form-label-lg',
                '#weight' => 99,
                '#states' => [
                    'visible' => [
                        'select[name="Payment[payment][component]"]' => ['value' => strtolower($this->_name)],
                    ]
                ],
                'account_show' => [
                    '#title' => __('Show dashboard panels', 'directories-payments'),
                    '#type' => 'checkbox',
                    '#default_value' => $this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_show'),
                    '#horizontal' => true,
                    '#description' => __('Check this option to show dashboard panels on the WooCommerce "My account" page.', 'directories-payments'),
                ],
                'account_redirect' => [
                    '#title' => __('Redirect dashboard access', 'directories-payments'),
                    '#type' => 'checkbox',
                    '#default_value' => $this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_redirect'),
                    '#horizontal' => true,
                    '#description' => __('Check this option to redirect dashboard access to the WooCommerce "My account" page.', 'directories-payments'),
                    '#states' => [
                        'visible' => [
                            'input[name="Dashboard[woocommerce][account_show]"]' => ['type' => 'checked', 'value' => true],
                        ]
                    ],
                ],
            ];
        }
    }

    public function onFormSubmitDirectoryAdminSettingsSuccess($form)
    {
        if ($this->isWooCommerceActive()
            && !empty($form->values['Payment']['payment']['component'])
            && $form->values['Payment']['payment']['component'] === 'woocommerce'
            && !empty($form->values['Dashboard']['woocommerce']['account_show'])
        ) {
            $mask = WC()->query->get_endpoints_mask();
            foreach ($this->_getAccountEndpoints(true) as $endpoint) {
                add_rewrite_endpoint($endpoint, $mask);
            }
            $this->_application->getPlatform()->flushRewriteRules();
        }
    }

    protected function _initMyAccountPage()
    {
        if ($endpoints = $this->_getAccountEndpoints()) {
            add_filter('woocommerce_account_menu_items', [$this, 'woocommerceAccountMenuItemsFilter']);
            add_filter('woocommerce_get_query_vars', [$this, 'woocommerceGetQueryVarsFilter']);
            $application = $this->_application;
            $dashboard_config = $application->getComponent('Dashboard')->getConfig();
            foreach ($endpoints as $panel_name => $endpoint) {
                add_filter('woocommerce_endpoint_' . $endpoint .  '_title', function ($title) use ($application, $panel_name) {
                    if ($panel = $application->Dashboard_Panels_impl($panel_name, true)) {
                        $title = $panel->dashboardPanelLabel();
                    }
                    return $title;
                });
                add_action('woocommerce_account_' . $endpoint .  '_endpoint', function () use ($application, $dashboard_config, $panel_name, $endpoint) {
                    if (!$panel = $application->Dashboard_Panels_impl($panel_name, true)) return;

                    $panel->dashboardPanelOnLoad();
                    $container = 'drts-dashboard-main';
                    $path = '/' . $application->getComponent('Dashboard')->getSlug('dashboard') . '/'. urlencode($application->getUser()->username) . '/' . $panel_name;
                    if ($endpoint_extra_path = get_query_var($endpoint)) {
                        $path .= '/' . $endpoint_extra_path;
                        // extract link name if posts panel
                        if (strpos($endpoint_extra_path, 'posts/') === 0) {
                            $link_name = substr($endpoint_extra_path, strlen('posts/'));
                        }
                    }
                    // Render links as tabs
                    $panel_settings = isset($dashboard_config['panel_settings'][$panel_name]) ? $dashboard_config['panel_settings'][$panel_name] : [];
                    $panel_settings += (array)$panel->dashboardPanelInfo('default_settings');
                    if (($links = $panel->panelHtmlLinks($panel_settings, isset($link_name) ? $link_name : true))
                        && count($links) > 1
                    ) {
                        echo '<div class="drts"><nav class="drts-dashboard-links ' . DRTS_BS_PREFIX . 'nav ' . DRTS_BS_PREFIX . 'nav-tabs ' . DRTS_BS_PREFIX . 'nav-justified ' . DRTS_BS_PREFIX . 'mb-4">';
                        foreach ($links as $link) {
                            echo '<a href="#" class="' . DRTS_BS_PREFIX . 'nav-item ' . DRTS_BS_PREFIX . 'nav-link ' . $application->H($link['attr']['class']) . '"' . $application->Attr($link['attr'], 'class') . '>' . $link['title'] . '</a>';
                        }
                        echo '</nav></div>';
                    }
                    echo $application->getPlatform()->render(
                        $path,
                        ['is_dashboard' => false], // attributes
                        [
                            'cache' => false,
                            'title' => false,
                            'container' => $container,
                        ]
                    );
                    echo $application->Dashboard_Panels_js('#' . $container, false, false);
                    if (($theme = wp_get_theme())
                        && $theme->get('Name') === 'Storefront'
                    ) {
                        $application->getPlatform()->addCssFile('woocommerce-dashboard.min.css', 'woocommerce-dashboard', [], 'directories-payments');
                    }
                    $application->Form_Scripts();
                });
            }
        }
    }

    protected function _getAccountEndpoints($skipEnabledCheck = false)
    {
        $ret = [];
        if ($this->_application->isComponentLoaded('Dashboard')
            && $this->_application->getComponent('Payment')->getConfig('payment', 'component') === 'woocommerce'
            && ($dashboard_config = $this->_application->getComponent('Dashboard')->getConfig())
            && ($skipEnabledCheck || !empty($dashboard_config['woocommerce']['account_show']))
            && ($panels = $this->_application->Dashboard_Panels())
        ) {
            if (!empty($dashboard_config['panels']['default'])) {
                foreach ($dashboard_config['panels']['default'] as $panel_name) {
                    if (in_array($panel_name, ['account', 'payment_payments'])
                        || !isset($panels[$panel_name])
                    ) continue;

                    $ret[$panel_name] = 'drts-' . $panel_name;
                }
            }
        }
        return $ret;
    }

    public function woocommerceGetQueryVarsFilter($vars)
    {
        foreach ($this->_getAccountEndpoints() as $endpoint) {
            $vars[$endpoint] = $endpoint;
        }
        return $vars;
    }

    public function woocommerceAccountMenuItemsFilter($items)
    {
        if ($account_endpoints = $this->_getAccountEndpoints()) {
            $panels_available = $this->_application->Dashboard_Panels();
            foreach ($account_endpoints as $panel_name => $endpoint) {
                if (!$panel = $this->_application->Dashboard_Panels_impl($panel_name, true)) continue;

                if (empty($panels_available[$panel_name]['labellable'])
                    || (null === $panel_label = $this->_application->getComponent('Dashboard')->getConfig('panels', 'options', $panel_name))
                ) {
                    $panel_label = $panel->dashboardPanelLabel();
                }

                $items[$endpoint] = $panel_label;
            }
            // Move logout menu item to the end
            if (isset($items['customer-logout'])) {
                $logout = $items['customer-logout'];
                unset($items['customer-logout']);
                $items['customer-logout'] = $logout;
            }
        }

        return $items;
    }

    protected function _getMyAccountPageUrl()
    {
        if (($page_id = wc_get_page_id('myaccount'))
            && ($permalink = get_permalink($page_id)) // need to use get_permalink to fetch the current language page
        ) {
            return $permalink;
        }
    }

    public function onCoreAccessRouteFilter(&$result, $context, $route, $paths)
    {
        if (!$this->isWooCommerceActive()
            || is_admin()
            || !$result
            || Request::isXhr()
            || Request::isPostMethod()
            || $context->getRequest()->asBool('public')
            || !$this->_application->isComponentLoaded('Dashboard')
            || $paths[0] !== $this->_application->getComponent('Dashboard')->getSlug('dashboard')
            || $context->getContainer() === '#drts-dashboard-main'
            || $this->_application->getComponent('Payment')->getConfig('payment', 'component') !== 'woocommerce'
            || !$this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_show')
            || !$this->_application->getComponent('Dashboard')->getConfig('woocommerce', 'account_redirect')
            || (!$url = $this->_getMyAccountPageUrl())
        ) return;

        if ($context->isEmbed()) {
            if (empty($paths[1])) {
                $this->_application->logError('The directory dashboard page contains custom content. Please make sure to remove any custom content including shortcodes in order for the WooCommerce My Account page redirection feature to work properly.');
            }
            return;
        }

        // Do not redirect if viewing other user's profile
        if (!empty($paths[1])
            && (urldecode($paths[1]) !== $this->_application->getUser()->username)
        ) return;

        $result = false;
        unset($paths[0], $paths[1]);
        if (isset($paths[2])) {
            $paths[2] = 'drts-' . $paths[2];
            $url = rtrim($url, '/');
            $url .= '/' . implode('/', $paths);
        }
        $params = $context->getRequest()->getParams();
        // Remove params already in the path
        unset($params['panel_name'], $params['entity_id']);
        $context->setRedirect($url . '?' . http_build_query($params, '', '&'));
    }

    public function onEntityCreateBundlesCommitted(array $bundles, array $bundleInfo)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function onEntityUpdateBundlesCommitted(array $bundles, array $bundleInfo)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function onEntityDeleteBundlesCommitted(array $bundles, $deleteContent)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function onEntityAdminBundleInfoEdited($bundle)
    {
        $this->_application->getPlatform()->deleteCache('woocommerce_product_types');
    }

    public function isWCSActive()
    {
        return class_exists('\WC_Subscriptions');
    }

    public function wcSubscriptionProductTypesFilter($productTypes)
    {
        $product_types = $this->_application->WooCommerce_ProductTypes('subscription');
        foreach (array_keys($product_types['name']) as $product_type) {
            $productTypes[] = $product_type;
        }
        return $productTypes;
    }

    public function wcIsSubscriptionFilter($isSubscription, $productId, $product)
    {
        if ($this->_isSubscriptionProduct($product)) $isSubscription = true;

        return $isSubscription;
    }

    public function wcSubscriptionStatusUpdatedAction($subscription, $newStatus, $oldStatus)
    {
        switch ($newStatus) {
            case 'active':
                if ($oldStatus === 'on-hold') {
                    $this->_onSubscriptionStatusUpdated($subscription, 'reactivate');
                } elseif ($oldStatus === 'pending') {
                    $this->_onSubscriptionStatusUpdated($subscription);
                }
                break;
            case 'expired':
                $this->_onSubscriptionStatusUpdated($subscription, 'expire');
                break;
            case 'cancelled':
                $this->_onSubscriptionStatusUpdated($subscription, 'cancel');
                break;
            case 'on-hold':
                if ($oldStatus === 'active') {
                    $this->_onSubscriptionStatusUpdated($subscription, 'deactivate');
                }
                break;
        }
    }

    protected function _isSubscriptionProduct($product)
    {
        return $product instanceof IProduct
            && $product->get_sabai_plan_type() === 'subscription';
    }

    protected function _onSubscriptionStatusUpdated($subscription, $action = null)
    {
        if (!$order = $subscription->get_parent()) {
            $this->_application->logError('Failed fetching parent order for subscription ' . $subscription->get_id());
            return;
        }

        foreach ($order->get_items() as $item) {
            if (!$entity = $this->_getEntityByOrderItem($item, ['subscription'])) continue;

            try {
                switch ($action) {
                    case 'unapply':
                        $this->_application->Payment_Features_unapply($entity, $item->get_id());
                        break;
                    case 'expire':
                        if ($payment_plan = $entity->getSingleFieldValue('payment_plan')) { // should never fall
                            $payment_plan['expires_at'] = time(); // Payment component will take care of expired entities
                            $this->_application->Entity_Save($entity, ['payment_plan' => $payment_plan]);
                        }
                        break;
                    case 'cancel':
                        if ($this->_application->Filter('woocommerce_subscription_cancelled_expire', false, [$entity])) {
                            $this->_application->Payment_Util_handleExpired($entity->getType(), [$entity->getId() => $entity]);
                            break;
                        }
                    case 'deactivate':
                        $this->_application->Payment_Util_deactivateEntities($entity->getType(), [$entity->getId() => $entity]);
                        break;
                    case 'reactivate':
                        $this->_application->Payment_Util_reactivateEntities($entity->getType(), [$entity->getId() => $entity]);
                        break;
                    default:
                        $this->_application->Payment_Features_apply($entity, $item->get_id());
                }
            } catch (Exception\IException $e) {
                $this->_application->logError($e);
            }
        }
    }

    public function onWooCommerceProductTypesFilter(&$productTypes, $baseLabels)
    {
        if (!$this->isWooCommerceActive() || !$this->isWCSActive()) return;

        $productTypes['subscription'] = ['name' => [], 'class' => '\WC_Product_Subscription'];
        foreach (array_keys($productTypes['base']['name']) as $product_type) {
            $productTypes['subscription']['name'][$product_type . '__subscription'] = $baseLabels[$product_type] . ' ' . __('(Subscription plan)', 'directories-payments');
        }
    }

    public function onPaymentBasePlanTypesFilter(&$planTypes)
    {
        if (!$this->isWooCommerceActive() || !$this->isWCSActive()) return;

        $planTypes[] = 'subscription';
    }

    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        parent::upgrade($current, $newVersion, $progress);
        if (version_compare($current->version, '1.2.0-dev.0', '<')) {
            global $wpdb;
            $product_types = $this->_application->WooCommerce_ProductTypes(null, true);
            foreach (array_keys($product_types['base']['name']) as $base_name) {
                $sql = sprintf(
                    'UPDATE %1$sterms SET name = \'%2$s\', slug = \'%2$s\' WHERE slug = \'%3$s\'',
                    $wpdb->prefix,
                    esc_sql($base_name . '__addon'),
                    esc_sql($base_name . '_addon')
                );
                $wpdb->query($sql);
            }
        }
        if (version_compare($current->version, '1.3.0', '<')) {
            if ($this->isWooCommerceActive()) {
                foreach ($this->_application->Entity_Bundles(['directory__listing'], 'Directory', 'directory') as $bundle) {
                    foreach ($this->paymentGetGateway('woocommerce')->paymentGetPlanIds($bundle->name) as $plan_id) {
                        if ($features = (array)get_post_meta($plan_id, '_drts_entity_features', true)) {
                            $features['review_reviews']['enable'] = 1;
                            update_post_meta($plan_id, '_drts_entity_features', $features);
                        }
                    }
                }
            }
        }

        return $this;
    }

    public function wcSubscriptionSwitchPostLinkFilter($permalink, $post)
    {
        if ($this->isWCSActive()
            && isset($_GET['switch-subscription'])
            && is_main_query()
            && is_product()
            && 'product' === $post->post_type
            && ($product = wc_get_product($post))
            && 'grouped' === wcs_get_objects_property($product, 'type')
            && ($children = $product->get_children())
        ) {
            // Check to see if the group contains a subscription.
            foreach ($children as $child) {
                $child_product = wc_get_product($child);
                if ($child_product instanceof IProduct
                    && $child_product->get_sabai_plan_type() === 'subscription'
                ) {
                    return self::add_switch_query_args($_GET['switch-subscription'], $_GET['item'], $permalink);
                }
            }
        }
        return $permalink;
    }

    /**
     * Copied from WC_Subscriptions_Switcher
     *
     * Add the switch parameters to a URL for a given subscription and item.
     *
     * @param int $subscription_id A subscription's post ID
     * @param int $item_id The order item ID of a subscription line item
     * @param string $permalink The permalink of the product
     * @param array $additional_query_args (optional) Additional query args to add to the switch URL
     * @since 2.0
     */
    protected static function add_switch_query_args( $subscription_id, $item_id, $permalink, $additional_query_args = array() ) {

        // manually add a nonce because we can't use wp_nonce_url() (it would escape the URL)
        $query_args = array_merge( $additional_query_args, array( 'switch-subscription' => absint( $subscription_id ), 'item' => absint( $item_id ), '_wcsnonce' => wp_create_nonce( 'wcs_switch_request' ) ) );
        $permalink  = add_query_arg( $query_args, $permalink );

        return apply_filters( 'woocommerce_subscriptions_add_switch_query_args', $permalink, $subscription_id, $item_id );
    }

    public function wcSubscriptionSwitchDetailsInCartFilter($cart_item_data, $product_id, $variation_id)
    {
        if (empty($cart_item_data['subscription_switch']['subscription_id'])
            || empty($cart_item_data['subscription_switch']['item_id'])
            || (!$subscription = wcs_get_subscription($cart_item_data['subscription_switch']['subscription_id']))
        ) return $cart_item_data;

        try {
            $item = wcs_get_order_item($cart_item_data['subscription_switch']['item_id'], $subscription);
        } catch (\Exception $e) {
            return $cart_item_data;
        }

        if ((!$entity_id = $item->get_meta('_drts_entity_id'))
            || (!$entity_type = $item->get_meta('_drts_entity_type'))
            || (!$post_id = $item->get_meta('drts_post_id'))
        ) return $cart_item_data;

        return [
            '_drts_action' => $action = 'upgrade',
            '_drts_entity_id' => $entity_id,
            '_drts_entity_type' => $entity_type,
            'drts_post_id' => $post_id, // for displaying order details
            'drts_action' => $this->_application->Payment_Util_actionLabel($action),
        ] + $cart_item_data;
    }

    public function wcSubscriptionSwitchCompletedAction($order)
    {
        foreach ($order->get_items() as $item) {
            if (!$entity = $this->_getEntityByOrderItem($item, ['subscription'])) continue;

            try {
                // Un-apply all the features of the old subscription plan
                $this->_application->Payment_Features_unapply($entity);

                // Create new feature group for the new subscription plan and apply
                $feature_group = $this->_application->Payment_Features_create($entity, new PaymentPlan($item->get_product()), $item->get_id());
                $this->_application->Payment_Features_apply($entity, $feature_group);
            } catch (Exception\IException $e) {
                $this->_application->logError($e);
            }
        }
    }

    public function wcSubscriptionViewSubscriptionActionsFilter($actions, $subscription)
    {
        foreach ($subscription->get_items() as $item) {
            if (($product = $item->get_product())
                && $this->_isSubscriptionProduct($product)
            ) {
                unset($actions['resubscribe']);
                break;
            }
        }
        return $actions;
    }

    public function isWCMActive()
    {
        return class_exists('\WC_Memberships_Loader');
    }

    public function onWoocommercePaymentPlanIdsFilter(&$ids, $bundleName, $lang)
    {
        if (!$this->isWCMActive()) return;

        // Check if products can be purchased by current user
        foreach (array_keys($ids) as $key) {
            if (!current_user_can('wc_memberships_purchase_restricted_product', $ids[$key])) {
                unset($ids[$key]);
            }
        }
    }

    public function wcMembershipsUserMembershipStatusChangedAction($userMembership, $oldStatus, $newStatus)
    {
        if (!in_array($newStatus, ['expired', 'cancelled'])
            || !$this->_application->getComponent('Payment')->getConfig('woocommerce', 'memberships', 'expire')
            || (!$user_id = $userMembership->get_user_id())
        ) return;

        // Expire items with base payment plan
        $entities = $this->_application->Entity_Query('post')
            ->fieldIs('author', $user_id)
            ->startCriteriaGroup('OR')
            ->fieldIsGreaterThan('payment_plan', time(), 'expires_at') // items that have not expired
            ->fieldIs('payment_plan', 0, 'expires_at') // items that never expires
            ->finishCriteriaGroup()
            ->fetch();
        foreach ($entities as $entity) {
            if ((!$payment_plan = $entity->getSingleFieldValue('payment_plan')) // should never fall
                || empty($payment_plan['plan_id'])
                || (!$product = wc_get_product($payment_plan['plan_id']))
                || ($this->isWCSActive() && $this->_isSubscriptionProduct($product))
            ) continue;

            $payment_plan['expires_at'] = time(); // Payment component will take care of expired entities
            try {
                $this->_application->Entity_Save($entity, ['payment_plan' => $payment_plan]);
                $this->_application->logNotice('Marked item associated with inactive WooCommerce Memberships membership as expired. Entity ID: ' . $entity->getId());
            } catch (Exception\IException $e) {
                $this->_application->logError('Failed marking item associated with inactive WooCommerce Memberships membership as expired. Error: ' . $e->getMessage() . '; Entity ID: ' . $entity->getId());
            }
        }
        // Expire subscriptions
        if ($this->isWCSActive()
            && ($subscriptions = wcs_get_users_subscriptions($user_id))
        ) {
            foreach ($subscriptions as $subscription) {
                if ($subscription->get_status() !== 'active') continue;

                foreach ($subscription->get_items() as $item) {
                    if ((!$product = $item->get_product())
                        || !$product instanceof IProduct
                        || $product->get_sabai_plan_type() !== 'subscription'
                    ) continue;
                }

                $subscription->update_status('expired', 'Status changed to Expired by WooCommerce Memberships membership expiration/cancellation.');
            }
        }
    }

    public function onPaymentAddToCartTextFilter(&$text, $bundleName, $action, $planId = null)
    {
        if (isset($planId)
            && !$this->_application->getUser()->isAnonymous()
            && in_array($action, ['add', 'claim'])
            && ($plan = $this->_application->getComponent('Payment')->getPaymentComponent()->paymentGetPlan($planId))
            && $plan->paymentPlanType() === 'base'
            && $plan->paymentPlanIsFree()
            && $this->_application->getComponent('Payment')->getConfig('woocommerce', 'free_no_checkout')
        ) {
            $text = null;
        } else {
            if ($this->_application->getComponent('Payment')->getConfig('woocommerce', 'bypass_cart')) {
                $text = __('Proceed to checkout', 'directories-payments');
            }
        }
    }

    public function woocommerceProductAddToCartUrlFilter($url, $product)
    {
        if ($product instanceof IProduct
            && $product->get_sabai_plan_type() !== 'addon'
            && $this->_application->isComponentLoaded('FrontendSubmit')
            && ($bundle = $this->_application->Entity_Bundle($product->get_sabai_bundle_name()))
        ) {
            $url = $this->_application->Url('/' . $this->_application->FrontendSubmit_AddEntitySlug($bundle), ['bundle' => $bundle->name, 'plan' => $product->get_id()]);
        }
        return $url;
    }

    public function woocommerceProductAddToCartTextFilter($text, $product)
    {
        if ($product instanceof IProduct
            && $product->get_sabai_plan_type() !== 'addon'
            && $this->_application->isComponentLoaded('FrontendSubmit')
            && ($bundle = $this->_application->Entity_Bundle($product->get_sabai_bundle_name()))
        ) {
            $text = $bundle->getLabel('add');
        }
        return $text;
    }
}
