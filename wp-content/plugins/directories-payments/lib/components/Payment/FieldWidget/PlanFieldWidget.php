<?php
namespace SabaiApps\Directories\Component\Payment\FieldWidget;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Payment\IPlan;
use SabaiApps\Directories\Component\Payment\Feature\IAddonFeature;

class PlanFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Payment Plan', 'directories-payments'),
            'field_types' => array($this->_name),
        );
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (empty($field->Bundle->info['payment_enable'])) return;

        if (!$this->_application->IsAdministrator()
            || (isset($entity)
                && ($default_entity_id = $this->_application->getPlatform()->getTranslatedId($entity->getType(), $entity->getBundleName(), $entity->getId(), $this->_application->getPlatform()->getDefaultLanguage()))
                && $default_entity_id != $entity->getId())
            || (!isset($entity) && $this->_application->getPlatform()->isAdminAddTranslation($_REQUEST))
        ) {
            if (empty($value['plan_id'])) return; // do not show for new content

            $current_plan_id = $value['plan_id'];
            if (($current_lang = $this->_application->getPlatform()->getCurrentLanguage())
                && ($payment_component = $this->_application->getComponent('Payment')->getPaymentComponent())
            ) {
                $current_plan_id = $payment_component->paymentGetPlanId($current_plan_id, $current_lang);
            }
            if (!$plan = $this->_application->Payment_Plan($field->Bundle->name, $current_plan_id)) return;

            if (!empty($value['expires_at'])) {
                $expires_at = $this->_application->System_Date_datetime($value['expires_at']);
                if ($value['expires_at'] < time()) {
                    $expires_at .= ' <span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-danger">'
                        . $this->_application->H(__('Expired', 'directories-payments')) . '</span>';
                }
            } else {
                $expires_at = $this->_application->H(__('None', 'directories-payments'));
            }
            return [
                'plan_id' => [
                    '#type' => 'hidden',
                    '#default_value' => $current_plan_id,
                    '#render_hidden_inline' => true,
                    '#attributes' => ['data-alt-value' => $plan->paymentPlanName()],
                ],
                'plan_name' => [
                    '#type' => 'markup',
                    '#default_value' => $this->_getPlanTitle($plan),
                    '#weight' => 1,
                    '#group' => true,
                ],
                'expires_at' => [
                    '#title' => __('Exp. Date', 'directories-payments'),
                    '#type' => 'item',
                    '#markup' => $expires_at,
                    '#weight' => 2,
                ],
            ];
        }

        // Do not show if pending payment for submission
        if (isset($entity)
            && $entity->isPending()
            && $this->_application->Payment_Util_hasPendingOrder($entity, ['add', 'submit'])
        ) return;

        if (($current_plan_id = empty($value['plan_id'])
                ? (isset($entity) ? $entity->getSingleFieldValue('payment_plan', 'plan_id') : null) // value may be null before submit, fixes validateAddonFeatures not working because of empty plan id key
                : $value['plan_id'])
            && ($current_lang = $this->_application->getPlatform()->getCurrentLanguage())
            && ($payment_component = $this->_application->getComponent('Payment')->getPaymentComponent())
        ) {
            $current_plan_id = $payment_component->paymentGetPlanId($current_plan_id, $current_lang);
        }
        $plan_id_field_selector = sprintf('[name="%s[plan_id]"]', $this->_application->Form_FieldName($parents));
        if (!isset($entity)
            || (!$plan = $this->_application->Payment_Plan($entity))
            || $plan->paymentPlanType() === 'base'
        ) {
            if (!$plans = $this->_application->Payment_Plans($field->Bundle->name, 'base')) return;

            $plan_options = array(0 => __('— Select —', 'directories-payments'));
            foreach ($plans as $plan_id => $plan) {
                if (!$plan->paymentPlanIsPurchasable()
                    && $plan_id !== $current_plan_id
                ) continue;

                $plan_options[$plan_id] = [
                    '#title' => $plan->paymentPlanTitle(),
                    '#attributes' => ['data-alt-value' => $plan->paymentPlanName()],
                ];
            }
            $expires_at_title = $this->_application->H(__('Exp. Date', 'directories-payments'));
            if (!empty($value['expires_at'])
                && $value['expires_at'] < time()
            ) {
                $expires_at_title .= ' <span class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-danger">'
                    . $this->_application->H(__('Expired', 'directories-payments')) . '</span>';
            }

            $form = [
                'plan_id' => [
                    '#type' => 'select',
                    '#options' => $plan_options,
                    '#default_value' => $current_plan_id,
                    '#weight' => 1,
                    '#group' => true,
                ],
                'expires_at' => [
                    '#title' => $expires_at_title,
                    '#title_no_escape' => true,
                    '#type' => 'datepicker',
                    '#default_value' => empty($value['expires_at']) ? null : $value['expires_at'],
                    '#weight' => 2,
                    '#states' => [
                        'invisible' => [
                            $plan_id_field_selector => ['value' => 0],
                        ],
                    ],
                    '#disable_time' => true,
                ],
            ];
        } else {
            $form = [
                'plan_id' => [
                    '#type' => 'hidden',
                    '#default_value' => $current_plan_id,
                    '#render_hidden_inline' => true,
                    '#attributes' => ['data-alt-value' => $plan->paymentPlanName()],
                ],
                'plan_name' => [
                    '#type' => 'markup',
                    '#default_value' => $this->_getPlanTitle($plan),
                    '#weight' => 1,
                    '#group' => true,
                ],
            ];
            $plans = [$current_plan_id => $plan];
        }

        $features_orderable = [];
        foreach (array_keys($this->_application->Payment_Features()) as $feature_name) {
            if ((!$feature = $this->_application->Payment_Features_impl($feature_name, true))
                || !$feature instanceof IAddonFeature
                || !$feature->paymentAddonFeatureSupports($field->Bundle)
            ) continue;

            $current_addon_feature_settings = isset($value['addon_features'][$feature_name]) ? (array)$value['addon_features'][$feature_name] : [];

            // Fetch plans that can accept orders for this add-on feature
            foreach (array_keys($plans) as $plan_id) {
                $plan_features = $plans[$plan_id]->paymentPlanFeatures();
                if (!$feature->paymentAddonFeatureExtraIsOrderable($plan_features)) {
                    continue;
                }
                if ($feature_form = $feature->paymentAddonFeatureExtraSettingsForm(
                    $field->Bundle,
                    $plan_features,
                    $current_addon_feature_settings,
                    array_merge($parents, array('addon_features', $plan_id, $feature_name))
                )) {
                    $form['addon_features'][$plan_id][$feature_name] = $feature_form + array(
                        '#weight' => (null !== $weight = $feature->paymentFeatureInfo('weight')) ? $weight : 10,
                        '#states' => array(
                            'visible' => array(
                                $plan_id_field_selector => array('value' => $plan_id),
                            ),
                        ),
                    );
                    $features_orderable[$plan_id][] = $feature_name;
                }
            }
        }

        $form['#element_validate'] = array(array(array($this, 'validateAddonFeatures'), array($field->Bundle, $features_orderable)));

        if (isset($form['addon_features'])) {
            $form['addon_features']['#weight'] = 5;
        }

        return $form;
    }

    public function validateAddonFeatures(Form\Form $form, &$value, $element, Entity\Model\Bundle $bundle, array $features)
    {
        if (empty($value['addon_features'])) return;

        $plan_id = $value['plan_id'];
        if (empty($features[$plan_id])) {
            $value['addon_features'] = [];
            return;
        }

        $addon_features = [];
        foreach ($features[$plan_id] as $feature_name) {
            if (!$feature = $this->_application->Payment_Features_impl($feature_name, true)) continue;

            $feature_settings = isset($value['addon_features'][$plan_id][$feature_name]) ? $value['addon_features'][$plan_id][$feature_name] : [];
            if (!$feature->paymentAddonFeatureIsEnabled($bundle, $feature_settings)) {
                $addon_features[$feature_name] = false; // make sure the feature is removed from the current addon_features value in the database
            } else {
                $addon_features[$feature_name] = $feature_settings;
            }
        }
        $value['addon_features'] = $addon_features;
    }

    protected function _getPlanTitle(IPlan $plan)
    {
        return '<div style="margin-bottom:1.5em;">'
            . '<span style="font-size:1.2em;" class="' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-secondary">'
            . $this->_application->H($plan->paymentPlanTitle()) . '</span></div>';
    }
}
