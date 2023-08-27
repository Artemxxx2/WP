<?php
namespace SabaiApps\Directories\Component\Payment;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\CSV;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Application;

class PaymentComponent extends AbstractComponent implements
    System\IMainRouter,
    IFeatures,
    Display\IButtons,
    Display\ILabels,
    Field\ITypes,
    Field\IRenderers,
    Field\IWidgets,
    CSV\IExporters,
    CSV\IImporters
{
    const VERSION = '1.3.108', PACKAGE = 'directories-payments';
    const FEATURE_STATUS_PENDING = 0, FEATURE_STATUS_APPLIED = 1, FEATURE_STATUS_UNAPPLIED = 2;
    
    protected $_paymentComponentName;
    
    public static function interfaces()
    {
        return ['Dashboard\IPanels', 'WordPressContent\INotifications'];
    }
    
    public static function description()
    {
        return 'Provides a generic payment platform which allows other components to use any of the payment methods that plug into Payment.';
    }
    
    public function getDefaultConfig()
    {
        return array(
            'payment' => array(
                'component' => 'WooCommerce',
            ),
            'renewal' => array(
                'expiring_days' => 7,
                'grace_period_days' => null,
                'reactivation_fee' => null,
            ),
            'upgrade' => array(
                'switch_plan_fee' => null,
                'prorated_discount' => true,
            ),
        );
    }
    
    public function systemMainRoutes($lang = null)
    {
        $routes = [];
        if ($this->_application->isComponentLoaded('Dashboard')) {
            $dashboard_slug = '/' . $this->_application->getComponent('Dashboard')->getSlug('dashboard', $lang);
            $routes[$dashboard_slug . '/:user_name/:panel_name/payment_orders'] = [
                'controller' => 'DashboardOrders',
                'callback_path' => 'dashboard',
            ];
            $routes[$dashboard_slug . '/:user_name/:panel_name/payment_subscriptions'] = [
                'controller' => 'DashboardSubscriptions',
                'callback_path' => 'dashboard',
            ];
            $routes[$dashboard_slug . '/:user_name/:panel_name/posts/:entity_id/submit'] = array(
                'controller' => 'SubmitPost',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'submit',
                'priority' => 5,
            );
            $routes[$dashboard_slug . '/:user_name/:panel_name/posts/:entity_id/renew'] = array(
                'controller' => 'SubmitPost',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'renew',
                'priority' => 5,
            );
            $routes[$dashboard_slug . '/:user_name/:panel_name/posts/:entity_id/upgrade'] = array(
                'controller' => 'SubmitPost',
                'access_callback' => true,
                'title_callback' => true,
                'callback_path' => 'upgrade',
                'priority' => 5,
            );
            $routes[$dashboard_slug . '/:user_name/:panel_name/posts/:entity_id/order_addon'] = array(
                'controller' => 'OrderAddon',
                'title_callback' => true,
                'access_callback' => true,
                'callback_path' => 'order_addon',
                'priority' => 3,
            );
        }

        if ($this->_application->isComponentLoaded('FrontendSubmit')) {
            foreach ($this->_application->Entity_Bundles() as $bundle) {
                if (!$this->_application->isComponentLoaded($bundle->component)
                    || empty($bundle->info['payment_enable'])
                    || !empty($bundle->info['parent'])
                ) continue;

                $routes['/' . $this->_application->FrontendSubmit_AddEntitySlug($bundle, $lang)] = array(
                    'controller' => 'AddEntity',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => 'add_entity',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                    ),
                    'callback_component' => 'FrontendSubmit',
                    'priority' => 5,
                );
                $routes[$bundle->getPath(false, $lang) . '/pricing'] = array(
                    'controller' => 'PricingTable',
                    'title_callback' => true,
                    'callback_path' => 'pricing',
                    'priority' => 3,
                );

                if ($this->_application->isComponentLoaded('Claiming')
                    && !empty($bundle->info['claiming_enable'])
                    && ($claim_bundle = $this->_application->Entity_Bundle('claiming_claim', $bundle->component, $bundle->group))
                ) {
                    $routes['/' . $this->_application->FrontendSubmit_AddEntitySlug($claim_bundle, $lang)] = array(
                        'controller' => 'ClaimEntity',
                        'access_callback' => true,
                        'title_callback' => true,
                        'callback_path' => 'add_child_entity',
                        'data' => array(
                            'bundle_type' => $claim_bundle->type,
                            'component' => $claim_bundle->component,
                            'group' => $claim_bundle->group,
                        ),
                        'callback_component' => 'FrontendSubmit',
                        'priority' => 5,
                    );
                }
            }
        }
        
        return $routes;
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'dashboard':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ($context->dashboard_panel !== 'payment_payments') return false;
                }
                return true;
            case 'submit':
            case 'renew':
            case 'upgrade':
                if ($accessType === Application::ROUTE_ACCESS_LINK) return true;
                
                $context->action = $path;
                return $this->_application->Entity_IsRoutable($context->bundle, $context->action, $context->entity);
            case 'order_addon':
                if ($accessType === Application::ROUTE_ACCESS_LINK) return true;

                return $this->_application->Entity_IsRoutable($context->bundle, $path, $context->entity, false);
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'renew':
                return $this->_application->Entity_Title($context->entity) . ' - ' . __('Renew', 'directories-payments');
            case 'upgrade':
                return $this->_application->Entity_Title($context->entity) . ' - ' . __('Upgrade / Downgrade', 'directories-payments');
            case 'order_addon':
                return __('Order Add-on', 'directories-payments');
            case 'pricing':
                return __('Pricing Table', 'directories-payments');
        }
    }
    
    public function fieldGetTypeNames()
    {
        return array('payment_plan', 'payment_orders');
    }
    
    public function fieldGetType($name)
    {
        switch ($name) {
            case 'payment_plan':
                return new FieldType\PlanFieldType($this->_application, $name);
            case 'payment_orders':
                return new FieldType\OrdersFieldType($this->_application, $name);
        }
    }
    
    public function fieldGetRendererNames()
    {
        return array('payment_plan');
    }
    
    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'payment_plan':
                return new FieldRenderer\PlanFieldRenderer($this->_application, $name);
        }
    }
    
    public function fieldGetWidgetNames()
    {
        return array('payment_plan', 'payment_orders');
    }
    
    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'payment_plan':
                return new FieldWidget\PlanFieldWidget($this->_application, $name);
            case 'payment_orders':
                return new FieldWidget\OrdersFieldWidget($this->_application, $name);
        }
    }
        
    public function paymentGetFeatureNames()
    {
        return array('payment_plan', 'payment_featured_entity', 'payment_taxonomy_terms', 'payment_fields', 'payment_amp');
    }
    
    public function paymentGetFeature($name)
    {
        switch ($name) {
            case 'payment_plan':
                return new Feature\PlanFeature($this->_application, $name);
            case 'payment_fields':
                return new Feature\FieldsFeature($this->_application, $name);
            case 'payment_featured_entity':
                return new Feature\FeaturedEntityFeature($this->_application, $name);
            case 'payment_taxonomy_terms':
                return new Feature\TaxonomyTermsFeature($this->_application, $name);
            case 'payment_amp':
                return new Feature\AmpFeature($this->_application, $name);
        }
    }
    
    public function onEntityFormFilter(&$form, $bundle, $entity, $options)
    {
        if (empty($bundle->info['payment_enable'])) return;

        // Apply plan features to form
        if (!empty($options['payment_plan'])
            && $options['payment_plan'] instanceof IPlan
        ) {
            $features = [$options['payment_plan']->paymentPlanFeatures()];
            $form['#class'] .= ' drts-payment-plan-' . $options['payment_plan']->paymentPlanId();
        } else {
            $features = $this->_application->Payment_Plan_features(isset($entity) ? $entity : $bundle);
        }
        foreach (array_keys($features[0]) as $feature_name) {
            if (!$feature = $this->_application->Payment_Features_impl($feature_name, true)) continue;
                        
            $feature_settings = array(
                $features[0][$feature_name],
                isset($features[1][$feature_name]) ? $features[1][$feature_name] : [], // additional if any
             );
            $feature->paymentFeatureOnEntityForm($bundle, $feature_settings, $form, $entity, $options['is_admin']);
        }
    }
    
    public function onEntityFieldValuesLoaded($entity, $bundle, $cache)
    {
        if (!$cache
            || empty($bundle->info['payment_enable'])
        ) return;
        
        $features = $this->_application->Payment_Plan_features($entity);
        
        // Limit fields by level
        if (!empty($features[0]['payment_fields']['fields_disabled'])) {
            if (!empty($features[1]['payment_fields']['fields'])) {
                $features[0]['payment_fields']['fields_disabled'] = array_diff($features[0]['payment_fields']['fields_disabled'], $features[1]['payment_fields']['fields']);
            }
            foreach ($features[0]['payment_fields']['fields_disabled'] as $field_name) {
                // TODO: Invoke filter or action to let components modify values
                if (in_array($field_name, ['post_title', 'post_content'])) {
                    $entity->setFieldValue($field_name, false);
                } else {
                    unset($entity->$field_name);
                }
            }
        }
        
        // Limit by taxonomy terms
        if (!empty($bundle->info['taxonomies'])) {
            // Limit number of allowed taxonomies
            foreach (array_keys($bundle->info['taxonomies']) as $taxonomy_bundle_type) {
                if (isset($features[0]['payment_taxonomy_terms'][$taxonomy_bundle_type])) {
                    $_settings = $features[0]['payment_taxonomy_terms'][$taxonomy_bundle_type];
                    if (!empty($_settings['unlimited'])
                        || (!$taxonomy_terms = $entity->getFieldValue($taxonomy_bundle_type))
                    ) continue;
                    
                    if (!isset($_settings['num'])) {
                        $max_num_allowed = $this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'is_hierarchical') ? 1 : 3;
                    } else {
                        $max_num_allowed = empty($_settings['num']) ? 0 : $_settings['num'];
                    }
                    if (!empty($features[1]['payment_taxonomy_terms'][$taxonomy_bundle_type]['num'])) { // any additional num of taxonomy terms allowed?
                        $max_num_allowed += $features[1]['payment_taxonomy_terms'][$taxonomy_bundle_type]['num'];
                    }
                    
                    $current_num = count($taxonomy_terms);
                    if ($current_num <= $max_num_allowed) continue;
                    
                    $entity->setFieldValue($taxonomy_bundle_type, array_slice($taxonomy_terms, 0, $max_num_allowed));
                }
            }
        }

        // Load translated payment plan ID
        if (empty($bundle->info['is_taxonomy'])
            && ($payment_plan = $entity->getSingleFieldValue('payment_plan'))
            && $this->_application->getPlatform()->isTranslatable($entity->getType(), $entity->getBundleName())
            && ($default_lang = $this->_application->getPlatform()->getDefaultLanguage())
            && ($lang = $this->_application->getPlatform()->getLanguageFor($entity->getType(), $entity->getBundleName(), $entity->getId()))
            && ($default_lang !== $lang)
            && ($payment_component = $this->getPaymentComponent())
            && ($plan_id = $payment_component->paymentGetPlanId($payment_plan['plan_id'], $lang))
            && ($plan_id !== $payment_plan['plan_id'])
        ) {
            $payment_plan['plan_id'] = $plan_id;
            $entity->setFieldValue('payment_plan', [$payment_plan]);
        }
    }
    
    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {
        if (empty($info['payment_enable'])
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
            || empty($info['public'])
        ) return;
        
        $form['payment_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable payments', 'directories-payments'),
            '#default_value' => !empty($settings['payment_enable']) || is_null($settings),
            '#horizontal' => true,
            '#weight' => 50,
        );
    }
    
    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {        
        if (!isset($info['payment_enable'])) return;
        
        if (!empty($info['is_taxonomy'])
            || !empty($info['parent'])
            || empty($info['public'])
        ) {
            unset($info['payment_enable']);
        }
        
        if (isset($settings['payment_enable']) && !$settings['payment_enable']) {
            $info['payment_enable'] = false;
        }
    }
    
    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'payment_enable';
    }

    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'payment_default_features';
    }
    
    protected function _onEntityCreateBundlesSuccess($bundles, $update = false)
    {
        foreach ($bundles as $bundle) {
            if (empty($bundle->info['is_taxonomy'])) {
                // Add plan field?
                if (!empty($bundle->info['payment_enable'])) {
                    $this->_application->getComponent('Entity')->createEntityField(
                        $bundle,
                        'payment_plan',
                        array(
                            'type' => 'payment_plan',
                            'settings' => [],
                            'weight' => 99,
                            'max_num_items' => 1,
                        )
                    );
                    $this->_application->getComponent('Entity')->createEntityField(
                        $bundle,
                        'payment_orders',
                        array(
                            'type' => 'payment_orders',
                            'label' => __('Orders', 'directories-payments'),
                        )
                    );
                }
            }
        }
    }
        
    public function onEntityCreateBundlesSuccess($bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles);
    }
    
    public function onEntityUpdateBundlesSuccess($bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles, true);
    }

    public function onSystemCron(&$logs, &$lastRun, $force)
    {
        if (!$force) {
            if (!$this->_application->callHelper('System_Cron_canRunTask', [$this->_name, &$logs, &$lastRun])) return;
        }

        $grace_period_seconds = 86400 * $this->_config['renewal']['grace_period_days'];
        $expiring_ts = time() + 86400 * $this->_config['renewal']['expiring_days'];
        
        // Fetch entities with expired level 
        foreach ($this->_application->Entity_BundleTypes_byFeatures(['payment_enable']) as $entity_type => $bundle_types) {
            $entities = $this->_application->Entity_Query($entity_type)
                ->fieldIsIn('bundle_type', $bundle_types)
                ->fieldIsIn('status', $this->_application->Entity_Status($entity_type, ['publish', 'private']))
                ->fieldIsOrSmallerThan('payment_plan', $expiring_ts, 'expires_at')
                ->fieldIsGreaterThan('payment_plan', 0, 'expires_at') // exclude those that never expire
                ->fieldIs('payment_plan', 0, 'deactivated_at') // exclude those already deactivated
                ->fetch();
            
            if (!count($entities)) continue;
            
            // Group entities expiring and those need deactivated
            $entities_expiring = $entities_expired = $entities_fully_expired = [];
            foreach (array_keys($entities) as $entity_id) {
                if (!$plan = $entities[$entity_id]->getSingleFieldValue('payment_plan')) { // this should not happen, but just in case
                    unset($entities[$entity_id]);
                    continue;
                }
                
                if ($plan['expires_at'] > time()) {
                    $entities_expiring[$entities[$entity_id]->getBundleName()][$entity_id] = $entities[$entity_id];
                    unset($entities[$entity_id]);
                    continue;
                }
            
                if ($plan['expires_at'] + $grace_period_seconds < time()) {
                    // Expired and renewal grace period has passed
                    $entities_fully_expired[$entities[$entity_id]->getBundleName()][$entity_id] = $entities[$entity_id];
                    unset($entities[$entity_id]);
                    continue;
                }

                $entities_expired[$entities[$entity_id]->getBundleName()][$entity_id] = $entities[$entity_id];
                unset($entities[$entity_id]);
            }

            // Hook for expiring entities
            if (!empty($entities_expiring)) {
                foreach (array_keys($entities_expiring) as $bundle_name) {
                    $logs['info'][] = sprintf(__('Found %d expiring item(s).', 'directories-payments'), count($entities_expiring[$bundle_name])) . ' - ' . $bundle_name;
                    $this->_application->Action('payment_entities_expiring', [$bundle_name, $entities_expiring[$bundle_name], &$logs]);
                }
            }

            // Hook for expired but within renewal graceful period entities
            if (!empty($entities_expired)) {
                foreach (array_keys($entities_expired) as $bundle_name) {
                    $logs['info'][] = sprintf(__('Found %d expired item(s).', 'directories-payments'), count($entities_expired[$bundle_name])) . ' - ' . $bundle_name;
                    $this->_application->Action('payment_entities_expired', [$bundle_name, $entities_expired[$bundle_name], &$logs]);
                }
            }
        
            // Process entities expired and passed renewal grace period
            if (!empty($entities_fully_expired)) {
                foreach (array_keys($entities_fully_expired) as $bundle_name) {
                    $logs['info'][] = sprintf(__('Found %d fully expired item(s).', 'directories-payments'), count($entities_fully_expired[$bundle_name])) . ' - ' . $bundle_name;
                    $this->_application->callHelper('Payment_Util_handleExpired', [$entity_type, $entities_fully_expired[$bundle_name], &$logs]);
                    // Hook for expired items that have been processed
                    $this->_application->Action('payment_entities_expired_processed', [$bundle_name, $entities_fully_expired[$bundle_name], &$logs]);
                }
            }
        }
    }
    
    public function onEntityBundleSettingsFormFilter(&$form, $bundle, $submitValues)
    {
        if (empty($bundle->info['payment_enable'])) return;

        $values = empty($bundle->info['payment_default_features']) ? [] : $bundle->info['payment_default_features'];
        $form['#tabs'][$this->_name] = array(
            '#title' => __('Payment', 'directories-payments'),
            '#weight' => 25,
        );
        $form['payment_default_features'] = array(
            '#title' => __('Default Feature Settings', 'directories-payments'),
            '#description' => __('Configure features applied to items without any payment plan.', 'directories-payments'),
            '#tree' => true,
            '#weight' => 10,
            '#tab' => $this->_name,
        ) + $this->_application->Payment_Features_form(
            $bundle,
            'base',
            isset($values['enabled']) && isset($values['disabled'])
                ? $values['enabled'] + $values['disabled']
                : $values,
            array('payment_default_features')
        );
        unset($form['payment_default_features']['payment_plan']);
    }

    public function displayGetButtonNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        if ($this->_application->isComponentLoaded('Dashboard')) {
            if (!empty($bundle->info['payment_enable'])) {
                $ret[] = 'payment_renew';
                $ret[] = 'payment_upgrade';
                $ret[] = 'payment_order_addon';
            }
        }
        return $ret;
    }
    
    public function displayGetButton($name)
    {
        return new DisplayButton\PostDisplayButton($this->_application, $name);
    }
    
    public function displayGetLabelNames(Entity\Model\Bundle $bundle)
    {
        return empty($bundle->info['payment_enable']) ? [] : ['payment_plan'];
    }
    
    public function displayGetLabel($name)
    {
        return new DisplayLabel\PlanDisplayLabel($this->_application, $name);
    }
    
    public function dashboardGetPanelNames()
    {
        return array('payment_payments');
    }
    
    public function dashboardGetPanel($name)
    {
        return new DashboardPanel\PaymentsDashboardPanel($this->_application, $name);
    }
    
    public function onDirectoryShortcodesFilter(&$shortcodes)
    {
        $shortcodes['payment-pricing'] = '/pricing';
    }
    
    public function onEntityIsRoutableFilter(&$isRoutable, $bundle, $action, $entity = null)
    {
        if ($isRoutable === false
            || !isset($entity)
            || !in_array($action, array('submit', 'renew', 'upgrade', 'order_addon'))
            || empty($bundle->info['payment_enable'])
        ) return;

        $isRoutable = false;

        if ($action === 'submit') {
            if (!$this->getPaymentComponent(false)) {
                $isRoutable = true;
                return;
            }

            if (!$this->_application->Payment_Plan($entity)
                || $entity->getSingleFieldValue('payment_plan', 'deactivated_at')
            ) {
                $plans = $this->_application->Payment_Plans(
                    $bundle->name,
                    $this->_application->Filter('payment_base_plan_types', ['base'], [$bundle->name]),
                    true
                );
                $isRoutable = count($plans) > 0;
            }
            return;
        }

        if (!$this->getPaymentComponent(false)
            || !$entity->isPublished()
        ) return;
        
        switch ($action) {
            case 'order_addon':
                if (!$plan = $this->_application->Payment_Plan($entity)) return;

                // Make sure it has not yet expired
                $expires_at = $entity->getSingleFieldValue('payment_plan', 'expires_at');
                if (!empty($expires_at)
                    && $expires_at < time()
                ) return;
                
                // Check perm
                $perm = ($this->_application->Entity_IsAuthor($entity) ? 'entity_edit_' : 'entity_edit_others_') . $entity->getBundleName();
                if (!$this->_application->HasPermission($perm)) return;
        
                // Have at least one orderable addon plan?
                $addon_plans = $this->_application->Payment_Plans_orderableAddons($entity);
                $isRoutable = count($addon_plans) > 0;
                return;
        
            case 'renew':
                if (!$plan = $this->_application->Payment_Plan($entity)) return;

                if ($plan->paymentPlanType() !== 'base') return;

                // Make sure it has expired
                $expires_at = $entity->getSingleFieldValue('payment_plan', 'expires_at');
                $grace_period = isset($this->_config['renewal']['grace_period_days']) ? (int)$this->_config['renewal']['grace_period_days'] : 30;
                if (empty($expires_at) // never expires
                    || $expires_at - 86400 * $this->_config['renewal']['expiring_days'] > time() // has not yet reached X days before expiration
                    || $expires_at + 86400 * $grace_period < time() // has passed renewal redemption period
                ) return false;
                
                // Check perm
                $perm = ($this->_application->Entity_IsAuthor($entity) ? 'entity_edit_' : 'entity_edit_others_') . $entity->getBundleName();
                if (!$this->_application->HasPermission($perm)) return;
                
                // All passed!
                $isRoutable = true;
                return;

            case 'upgrade':
                if ($plan = $this->_application->Payment_Plan($entity)) {
                    if ($plan->paymentPlanType() !== 'base') return;

                    // Make sure it has not yet expired
                    $expires_at = $entity->getSingleFieldValue('payment_plan', 'expires_at');
                    if (!empty($expires_at)
                        && $expires_at < time()
                    ) return false;

                    $min_plan_num = 1;
                } else {
                    $min_plan_num = 0;
                }

                // Check perm
                $perm = ($this->_application->Entity_IsAuthor($entity) ? 'entity_edit_' : 'entity_edit_others_') . $entity->getBundleName();
                if (!$this->_application->HasPermission($perm)) return;
                
                // Make sure there are more than the min number of plans to which current plan can upgrade/downgrade
                $plans = $this->_application->Payment_Plans(
                    $entity->getBundleName(),
                    $this->_application->Filter('payment_base_plan_types', ['base'], [$entity->getBundleName()]),
                    true
                );
                $isRoutable = count($plans) > $min_plan_num;
                return;
        }
    }

    /*
     * @return IGateway
     */
    public function getPaymentComponent($throwError = false, $nameOnly = false)
    {
        if (!isset($this->_paymentComponentName)) {
            if (isset($this->_config['payment']['component'])
                && ($this->_config['payment']['component'] = strtolower($this->_config['payment']['component'])) // compat with 1.2.x
                && ($payment_gateway = $this->_application->Payment_Gateways_impl($this->_config['payment']['component'], true))
                && $payment_gateway->paymentIsEnabled()
            ) {
                $this->_paymentComponentName = $this->_config['payment']['component'];
            } else {
                $this->_paymentComponentName = false;
            }
        }
        if (!$this->_paymentComponentName) {
            if ($throwError) {
                throw new Exception\RuntimeException('Invalid payment component. A valid payment component must be configured in Settings -> Payment!');
            }
            return false;
        }
        
        return $nameOnly ? $this->_paymentComponentName : $this->_application->Payment_Gateways_impl($this->_paymentComponentName);
    }
    
    public function wpGetNotificationNames()
    {
        return array('payment_expiring', 'payment_expired', 'payment_deactivated');
    }
    
    public function wpGetNotification($name)
    {
        return new WordPressNotification\PaymentWordPressNotification($this->_application, $name);
    }
    
    public function onPaymentEntitiesExpiring($bundleName, $entities, &$logs)
    {
        if ($this->_application->isComponentLoaded('WordPressContent')) {
            $expiring_remaining = null;
            if ($this->_config['renewal']['expiring_days'] > 1
                && ($send_notification_once = (int)$this->_application->Filter('payment_expiring_send_notification_once', false))
                && $send_notification_once < $this->_config['renewal']['expiring_days']
            ) {
                // Remaining time must be more than [expiring_days - X] days if notifying limited times
                $expiring_remaining = 86400 * ($this->_config['renewal']['expiring_days'] - $send_notification_once);
            }
            foreach (array_keys($entities) as $k) {
                if (empty($expiring_remaining)
                    || $entities[$k]->getSingleFieldValue('payment_plan', 'expires_at') - time() > $expiring_remaining
                ) {
                    $this->_application->WordPressContent_Notifications_send('payment_expiring', $entities[$k]);
                }
            }
        }
    }
    
    public function onPaymentEntitiesExpired($bundleName, $entities, &$logs)
    {
        if ($this->_application->isComponentLoaded('WordPressContent')) {
            $max_notify_period = null;
            if ($this->_config['renewal']['grace_period_days'] > 1
                && ($send_notification_once = (int)$this->_application->Filter('payment_expired_send_notification_once', false))
                && $send_notification_once < $this->_config['renewal']['grace_period_days']
            ) {
                $max_notify_period = 86400 * $send_notification_once;
            }
            foreach (array_keys($entities) as $k) {
                if (empty($max_notify_period)
                    || time() - $entities[$k]->getSingleFieldValue('payment_plan', 'expires_at') <= $max_notify_period
                ) {
                    $this->_application->WordPressContent_Notifications_send('payment_expired', $entities[$k]);
                }
            }
        }
    }
    
    public function onPaymentEntitiesExpiredProcessed($bundleName, $entities, &$logs)
    {
        switch ($this->_config['expired']['action']) {
            case 'deactivate':
                if ($this->_application->isComponentLoaded('WordPressContent')) {
                    foreach (array_keys($entities) as $k) {
                        $this->_application->WordPressContent_Notifications_send('payment_deactivated', $entities[$k]);
                    }
                }
                break;
            default:
        }
    }
        
    public function onWordpressNotificationMessageFilter(&$message, $entity)
    {
        if (strpos($message, '[drts_payment_expire_days]') !== false) {
            $message = str_replace('[drts_payment_expire_days]', '[drts_entity field="payment_plan" key="expire_days" format="%value%"]', $message);
        }
        if (strpos($message, '[drts_payment_expire_on]') !== false) {
            $message = str_replace('[drts_payment_expire_on]', '[drts_entity field="payment_plan" key="expire_on" format="%value%"]', $message);
        }
        if (strpos($message, '[drts_payment_renew_grace_period_days]') !== false) {
            $message = str_replace('[drts_payment_renew_grace_period_days]', $this->_config['renewal']['grace_period_days'], $message);
        }
    }
    
    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['tabs'][$this->_name] = [
            '#title' => __('Payment', 'directories-payments'),
            '#weight' => 25,
        ];
        
        $config = $this->_config;
        // Fetch all available payment components
        $payment_components = $payment_components_disabled = $payment_component_settings_form = [];

        foreach (array_keys($this->_application->Payment_Gateways()) as $gateway_name) {
            if (!$gateway = $this->_application->Payment_Gateways_impl($gateway_name, true)) continue;

            $payment_components[$gateway_name] = $gateway->paymentGatewayInfo('label');
            if (!$gateway->paymentIsEnabled()) {
                $payment_components_disabled[$gateway_name] = $gateway_name;
            } else {
                $_config = isset($config[$gateway_name]) ? $config[$gateway_name] : [];
                if ($gateway_name === 'woocommerce'
                    && isset($config['WooCommerce'])
                ) {
                    $_config += $config['WooCommerce']; // compat with 1.2.x
                }
                $payment_component_settings_form[$gateway_name] = [
                    '#weight' => 35,
                    '#states' => [
                        'visible' => [
                            '[name="' . $this->_name . '[payment][component]"]' => ['value' => $gateway_name],
                        ],
                    ],
                ] + $gateway->paymentSettingsForm($_config, [$this->_name, $gateway_name]);
            }
        }

        // Get current payment component currency
        if ($payment_component = $this->getPaymentComponent()) {
            $currency_symbol = $payment_component->paymentGetCurrency(true);
        } else {
            $currency_symbol = '$';
        }
        
        $form['fields'][$this->_name] = [
            '#component' => $this->_name,
            '#tab' => $this->_name,
            'payment' => [
                '#weight' => 1,
                '#title' => __('General Settings', 'directories-payments'),
                'component' => [
                    '#type' => 'select',
                    '#title' => __('Payment gateway', 'directories-payments'),
                    '#options' => ['' => __('— Select —', 'directories-payments')] + $payment_components,
                    '#options_disabled' => $payment_components_disabled,
                    '#default_value' => isset($config['payment']['component']) ? strtolower($config['payment']['component']) : null,
                    '#horizontal' => true,
                ],
            ],
            'expired' => [
                '#weight' => 10,
                'action' => [
                    '#type' => 'select',
                    '#title' => __('Action after payment plan expiration', 'directories-payments'),
                    '#options' => [
                        'deactivate' => __('Deactivate', 'directories-payments'),
                        'no_plan_author' => __('Unassign payment plan and author', 'directories-payments'),
                        'no_plan' => __('Unassign payment plan', 'directories-payments'),
                        'trash' => __('Trash', 'directories-payments'),
                        'none' => __('None', 'directories-payments'),
                    ],
                    '#default_value' => isset($config['expired']['action']) ? $config['expired']['action'] : 'deactivate',
                    '#horizontal' => true,
                ],
            ],
            'selection' => [
                '#weight' => 20,
                'allow_none' => [
                    '#title' => __('Add "No payment plan" option', 'directories-payments'),
                    '#type' => 'checkbox',
                    '#default_value' => !empty($config['selection']['allow_none']),
                    '#horizontal' => true,
                ],
                'none_label' => [
                    '#field_prefix' => __('Label', 'directories-payments'),
                    '#type' => 'textfield',
                    '#default_value' => isset($config['selection']['none_label']) ? $config['selection']['none_label'] : __('No payment plan (FREE)', 'directories-payments'),
                    '#horizontal' => true,
                    '#states' => [
                        'visible' => [
                            'input[name="Payment[selection][allow_none]"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'none_desc' => [
                    '#placeholder' => __('Description', 'directories-payments'),
                    '#type' => 'textarea',
                    '#rows' => 3,
                    '#default_value' => isset($config['selection']['none_desc']) ? $config['selection']['none_desc'] : null,
                    '#horizontal' => true,
                    '#states' => [
                        'visible' => [
                            'input[name="Payment[selection][allow_none]"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ],
            'pricing_table' => [
                '#weight' => 30,
                'show' => [
                    '#title' => __('Display pricing table', 'directories-payments'),
                    '#type' => 'checkbox',
                    '#default_value' => !empty($config['pricing_table']['show']),
                    '#horizontal' => true,
                ],
                'color' => [
                    '#type' => 'radios',
                    '#default_value' => isset($config['pricing_table']['color']) ? $config['pricing_table']['color'] : null,
                    '#options' => $this->_application->System_Util_colorOptions(),
                    '#option_no_escape' => true,
                    '#default_value_auto' => true,
                    '#horizontal' => true,
                    '#columns' => 4,
                    '#states' => [
                        'visible' => [
                            'input[name="Payment[pricing_table][show]"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
                'btn_text' => [
                    '#type' => 'textfield',
                    '#default_value' => isset($config['pricing_table']['btn_text']) ? $config['pricing_table']['btn_text'] : null,
                    '#placeholder' => __('Choose Plan', 'directories-payments'),
                    '#horizontal' => true,
                    '#field_prefix' => __('Button label', 'directories-payments'),
                    '#states' => [
                        'visible' => [
                            'input[name="Payment[pricing_table][show]"]' => ['type' => 'checked', 'value' => true],
                        ],
                    ],
                ],
            ],
            'renewal' => [
                '#weight' => 40,
                '#title' => __('Plan Renewal Settings', 'directories-payments'),
                'expiring_days' => [
                    '#title' => __('Renewal period in days', 'directories-payments'),
                    '#type' => 'slider',
                    '#integer' => true,
                    '#min_value' => 0,
                    '#max_value' => 100,
                    '#default_value' => isset($config['renewal']['expiring_days'])
                        ? $config['renewal']['expiring_days']
                        : 7,
                    '#horizontal' => true,
                ],
                'grace_period_days' => [
                    '#title' => __('Renewal grace period in days', 'directories-payments'),
                    '#type' => 'slider',
                    '#integer' => true,
                    '#min_value' => 0,
                    '#max_value' => 100,
                    '#default_value' => isset($config['renewal']['grace_period_days'])
                        ? $config['renewal']['grace_period_days']
                        : 30,
                    '#horizontal' => true,
                ],
                'reactivation_fee' => [
                    '#title' => sprintf(_x('Re-activation fee (%s)', 'currency symbol', 'directories-payments'), $currency_symbol),
                    '#type' => 'number',
                    '#numeric' => true,
                    '#min_value' => 0,
                    '#default_value' => isset($config['renewal']['reactivation_fee']) ? $config['renewal']['reactivation_fee'] : 0,
                    '#horizontal' => true,
                ],
            ],
            'upgrade' => [
                '#weight' => 50,
                '#title' => __('Plan Upgrade/Downgrade Settings', 'directories-payments'),
                'switch_plan_fee' => [
                    '#title' => sprintf(_x('Switch plan fee (%s)', 'currency symbol', 'directories-payments'), $currency_symbol),
                    '#type' => 'number',
                    '#numeric' => true,
                    '#min_value' => 0,
                    '#default_value' => isset($config['upgrade']['switch_plan_fee']) ? $config['upgrade']['switch_plan_fee'] : 0,
                    '#horizontal' => true,
                ],
                'prorated_discount' => [
                    '#title' => __('Enable prorated discount', 'directories-payments'),
                    '#type' => 'checkbox',
                    '#default_value' => !empty($config['upgrade']['prorated_discount']),
                    '#horizontal' => true,
                ],
            ],
        ];

        if (!empty($payment_component_settings_form)) {
            $form['fields'][$this->_name] += $payment_component_settings_form;
        }
    }

    public function onEntityHtmlClassesFilter(&$classes, $entity)
    {
        if ($plan_id = $this->_application->Payment_Plan($entity, true)) {
            $classes[] = 'drts-entity-payment-plan-' . $plan_id;
        }
    }

    public function csvGetImporterNames()
    {
        return ['payment_plan'];
    }

    public function csvGetImporter($name)
    {
        return new CSVImporter\PaymentCSVImporter($this->_application, $name);
    }

    public function csvGetExporterNames()
    {
        return ['payment_plan'];
    }

    public function csvGetExporter($name)
    {
        return new CSVExporter\PaymentCSVExporter($this->_application, $name);
    }

    public function onViewEntities($bundle, $query, $context)
    {
        if (!empty($context->settings['sort']['payment_plans'])) {
            if (!empty($context->settings['sort']['payment_plan_orders'])) {
                $query->sortByCases('payment_plan', $this->_application->Filter('payment_plan_orders', $context->settings['sort']['payment_plan_orders'], [$bundle, $context->view]), 'plan_id');
            } else {
                $query->sortByField('payment_plan', 'EMPTY_LAST', 'plan_id'); // moves NULL or 0 to last in order
            }
        }
    }

    public function onViewFeatureSettingsFormFilter(&$form, $bundle, $settings, $submitValues)
    {
        $plans = $this->_application->Payment_Plans(
            $bundle->name,
            $this->_application->Filter('payment_base_plan_types', ['base'], [$bundle->name])
        );
        if (!$plans) return;

        foreach (array_keys($plans) as $plan_id) {
            $plans[$plan_id] = $plans[$plan_id]->paymentPlanTitle();
        }
        $form['sort']['payment_plans'] = [
            '#type' => 'checkbox',
            '#title' => __('Show items with a payment plan first', 'directories-payments'),
            '#default_value' => !empty($settings['sort']['payment_plans']),
            '#horizontal' => true,
            '#weight' => 20,
        ];
        $form['sort']['payment_plan_orders'] = [
            '#type' => 'sortablecheckboxes',
            '#options' => $plans,
            '#default_value' => isset($settings['sort']['payment_plan_orders']) ? $settings['sort']['payment_plan_orders'] : array(current(array_keys($plans))),
            '#title' => __('Payment plan sort order', 'directories-payments'),
            '#horizontal' => true,
            '#weight' => 20,
            '#states' => [
                'visible' => [
                    'input[name="sort[payment_plans]"]' => ['type' => 'checked', 'value' => true],
                ],
            ],
        ];
    }

    public function onClaimingClaimApproved($claim, $claimedEntity)
    {
        if (!$payment_component = $this->getPaymentComponent()) return;

        if (!$order_id = $payment_component->paymentGetClaimOrderId($claim->getId())) {
            $this->_application->logWarning('No payment order found for claim ID: ' . $claim->getId());
            return;
        }
        $this->_application->Payment_Features_apply($claimedEntity, $order_id);
    }

    public function onClaimingClaimRejected($claim, $claimedEntity)
    {
        if (!$payment_component = $this->getPaymentComponent()) return;

        if (!$order_id = $payment_component->paymentGetClaimOrderId($claim->getId())) {
            $this->_application->logWarning('No payment order found for claim ID: ' . $claim->getId());
            return;
        }
        $payment_component->paymentRefundOrder($order_id, __('Claim Rejected', 'directories-payments'));
    }

    public function onEntityCreateEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        if (!empty($bundle->info['is_taxonomy'])
            || !$this->_application->getPlatform()->isTranslatable($entity->getType(), $entity->getBundleName())
            || (!$default_lang = $this->_application->getPlatform()->getDefaultLanguage())
        ) return;

        if ((!$default_entity_id = $this->_application->getPlatform()->getTranslatedId($entity->getType(), $entity->getBundleName(), $entity->getId(), $default_lang))
            || $default_entity_id == $entity->getId()
        ) {
            // Saving translated item but no default item or saving default item

            // Get payment plan already assigned to translated items if any
            if (!$translated_entities = $this->_application->Entity_Translations($entity)) return;

            foreach ($translated_entities as $translated_entity) {
                if ($payment_plan = $translated_entity->getSingleFieldValue('payment_plan')) {
                    break;
                }
            }
            if (empty($payment_plan)) {
                // No payment plan assigned to any translated item, so make sure no payment plan is assigned

                if (!$entity->getSingleFieldValue('payment_plan')) return; // already empty

                // Remove payment plan
                $payment_plan = false;
            }
        } else {
            // Saving translated item and original exists

            if (!$default_entity = $this->_application->Entity_Entity($entity->getType(), $default_entity_id)) return;

            // Remove payment plan if default item does not have any
            if ((!$payment_plan = $default_entity->getSingleFieldValue('payment_plan'))
                || empty($payment_plan['plan_id'])
            ) {
                // Default item does not have a payment plan

                if (!$entity->getSingleFieldValue('payment_plan')) return; // already empty

                // Remove payment plan from translated item
                $payment_plan = false;
            }
        }

        $this->_application->Entity_Save(
            $entity,
            ['payment_plan' => $payment_plan],
            ['payment_skip_plan_sync' => true] // prevents loop
        );
    }

    public function onEntityUpdateEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        if (!empty($extraArgs['payment_skip_plan_sync'])
            || !empty($bundle->info['is_taxonomy'])
            || !isset($values['payment_plan'])
            || !$this->_application->getPlatform()->isTranslatable($entity->getType(), $entity->getBundleName())
            || (!$default_lang = $this->_application->getPlatform()->getDefaultLanguage())
            || (!$default_entity_id = $this->_application->getPlatform()->getTranslatedId($entity->getType(), $entity->getBundleName(), $entity->getId(), $default_lang))
            || $default_entity_id != $entity->getId()
            || (!$translated_entities = $this->_application->Entity_Translations($entity))
        ) return;

        // Payment plan of default item modified, update payment plan of translated items

        if ((!$payment_plan = $entity->getSingleFieldValue('payment_plan'))
            || empty($payment_plan['plan_id'])
        ) {
            $payment_plan = false;
        }
        foreach (array_keys($translated_entities) as $lang) {
            $this->_application->Entity_Save(
                $translated_entities[$lang],
                ['payment_plan' => $payment_plan],
                ['payment_skip_plan_sync' => true] // prevents loop
            );
        }
    }

    public function onClaimingIsGuestClaimableFilter(&$result, $bundle)
    {
        $result = !empty($bundle->info['parent'])
            && ($parent_bundle = $this->_application->Entity_Bundle($bundle->info['parent']))
            && !empty($parent_bundle->info['payment_enable'])
            && ($payment_component = $this->getPaymentComponent())
            && !$payment_component->paymentIsGuestCheckoutEnabled()
            && ($payment_component->paymentIsGuestSignupEnabled() || $payment_component->paymentIsGuestLoginEnabled());
    }

    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        parent::upgrade($current, $newVersion, $progress);
        if (version_compare($current->version, '1.3.0', '<')) {
            foreach ($this->_application->Entity_Bundles(['directory__listing'], 'Directory', 'directory') as $bundle) {
                $info = $bundle->info;
                $info['payment_default_features']['enabled']['review_reviews']['enable'] = 1;
                $bundle->setInfo($info)->commit();
            }
        }

        return $this;
    }
}