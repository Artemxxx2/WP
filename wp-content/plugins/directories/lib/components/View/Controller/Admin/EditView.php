<?php
namespace SabaiApps\Directories\Component\View\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Form;

class EditView extends System\Controller\Admin\AbstractSettings
{    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        if ($context->getRequest()->asBool('show_settings')) {
            return array(
                'settings' => array(
                    '#type' => 'markup',
                    '#markup' => '<pre>' . print_r(array(
                        'name' => $context->view->name,
                        'mode' => $context->view->mode,
                        'label' => $context->view->getLabel(),
                        'settings' => $context->view->data['settings'],
                    ), true) . '</pre>',
                ),
            );
        }
        
        // Highlight row on success
        $this->_ajaxOnSuccessEdit = 'form.drts-view-admin-views tr[data-row-id=\'' . $context->view->id . '\']';
        
        return $this->View_SettingsForm($context->bundle, $context->view, $this->_getSubimttedValues($context, $formStorage));
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
        $view = $this->View_AdminView_update($context->view, $name, $mode, $label, $settings + $values);
        $this->View_AdminView_setDefault($context->bundle, $view);
    }
}
