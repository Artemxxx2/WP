<?php
namespace SabaiApps\Directories\Component\FrontendSubmit\Restrictor;

use SabaiApps\Directories\Component\Entity\Model\Bundle;

class DefaultRestrictor extends AbstractRestrictor
{
    protected function _frontendsubmitRestrictorInfo()
    {
        return [
            'label' => __('Default', 'directories-frontend'),
            'default_settings' => [],
        ];
    }

    protected function _frontendsubmitRestrictorLimitSettingsForm(array $bundles, array $settings, array $parents = [])
    {
        $form = [];
        foreach ($bundles as $bundle_name => $bundle_label) {
            $form[$bundle_name] = [
                '#type' => 'slider',
                '#title' => $bundle_label,
                '#default_value' => empty($settings[$bundle_name]) ? 0 : $settings[$bundle_name],
                '#horizontal' => true,
                '#min_value' => 0,
                '#max_value' => $this->_limitMax,
                '#step' => $this->_limitStep,
                '#min_text' => __('Unlimited', 'directories-frontend'),
                '#integer' => true,
            ];
        }
        return $form;
    }

    protected function _frontendsubmitRestrictorLimit(Bundle $bundle, array $settings, $userId)
    {
        return empty($settings[$bundle->name]) ? -1 : $settings[$bundle->name];
    }
}