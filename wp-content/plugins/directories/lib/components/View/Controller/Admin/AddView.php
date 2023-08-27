<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Form;

class AddView extends System\Controller\Admin\AbstractSettings
{    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $this->_ajaxOnSuccessRedirect = true;
        $this->_submitButtons[] = [
            '#btn_label' => __('Add View', 'directories'),
            '#btn_color' => 'success',
            '#btn_size' => 'lg',
        ];

        return $this->View_SettingsForm($context->bundle, $this->_getSettings($context), $this->_getSubimttedValues($context, $formStorage));
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return dirname($context->getRoute());
    }
    
    protected function _saveConfig(Context $context, array $values, Form\Form $form)
    {
        $name = $values['general']['name'];
        $mode = $values['general']['mode'];
        $label = $values['general']['label'];
        $settings = isset($values['general']['mode_settings'][$mode]) ? $values['general']['mode_settings'][$mode] : [];
        unset($values['general']);
        $view = $this->View_AdminView_add($context->bundle, $name, $mode, $label, $settings + $values);
        $this->View_AdminView_setDefault($context->bundle, $view);
    }
    
    protected function _getSettings(Context $context)
    {
        return ['mode' => null, 'settings' => []];
    }
}
