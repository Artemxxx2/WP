<?php
namespace SabaiApps\Directories\Component\Payment\FieldRenderer;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;

class PlanFieldRenderer extends Field\Renderer\AbstractRenderer
{
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'show_label' => true,
                'show_exp' => false,
                'show_deactivated' => true,
            ),
            'inlineable' => true,
        );
    }

    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        return array(
            'show_label' => array(
                '#type' => 'checkbox',
                '#title' => __('Show payment plan label', 'directories-payments'),
                '#default_value' => !empty($settings['show_label']),
            ),
            'show_exp' => array(
                '#type' => 'checkbox',
                '#title' => __('Show payment plan expiration date', 'directories-payments'),
                '#default_value' => !empty($settings['show_exp']),
            ),
            'show_deactivated' => array(
                '#type' => 'checkbox',
                '#title' => __('Show "Deactivated" label', 'directories-payments'),
                '#default_value' => !empty($settings['show_deactivated']),
            ),
        );
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        if (!$plan = $this->_application->Payment_Plan($entity)) return;

        $ret = [];
        if ($settings['show_label']) {
            $ret[] = '<span class="drts-payment-plan-label">' . $this->_application->H($plan->paymentPlanTitle()) . '</span>';
        }
        if ($settings['show_deactivated']
            && !empty($values[0]['deactivated_at'])
        ) {
            $ret[] = '<span class="drts-payment-plan-deactivated ' . DRTS_BS_PREFIX . 'badge ' . DRTS_BS_PREFIX . 'badge-danger" title="'
                . $this->_application->System_Date_datetime($values[0]['deactivated_at']) . '">' . $this->_application->H(__('Deactivated', 'directories-payments'))
                . '</span>';
        }
        if ($settings['show_exp']
            && !empty($values[0]['expires_at'])
        ) {
            if ($values[0]['expires_at'] < time()) {
                $color = 'danger';
            } elseif ($values[0]['expires_at'] < time() + 86400 * $this->_application->getComponent('Payment')->getConfig('renewal', 'expiring_days')) {
                $color = 'warning';
            } else {
                $color = 'success';
            }
            $ret[] = '<span class="drts-payment-plan-expires-at ' . DRTS_BS_PREFIX . 'text-' . $color . '">'
                . $this->_application->System_Date($values[0]['expires_at'], true) . '</span>';
        }

        return empty($ret) ? '' : implode(' ', $ret);
    }
}
