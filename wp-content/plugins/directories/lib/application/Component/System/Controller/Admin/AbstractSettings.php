<?php
namespace SabaiApps\Directories\Component\System\Controller\Admin;

use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Context;

abstract class AbstractSettings extends Form\Controller
{    
    protected function _doExecute(Context $context)
    {        
        $this->_cancelUrl = null;
        if ($context->getContainer() !== '#drts-content') {
            $this->_ajaxSubmit = true;
            $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        }
        parent::_doExecute($context);
        if ($context->isSuccess()) {
            $context->setSuccess($this->_getSuccessUrl($context));
        }
    }
    
    final protected function _doGetFormSettings(Context $context, array &$storage)
    {
        $form = $this->_getSettingsForm($context, $storage);
        if (empty($this->_submitButtons)) {
            $this->_submitButtons[] = array(
                '#btn_label' => __('Save Changes', 'directories'),
                '#btn_color' => 'primary',
                '#btn_size' => 'lg',
            );
        }
        return $form;
    }
    
    abstract protected function _getSettingsForm(Context $context, array &$formStorage);
    
    protected function _getSuccessUrl(Context $context)
    {
        if ($context->getContainer() !== '#drts-content') return ''; // return empty for no redirection URL
       
        return $this->Url($context->getRoute());
    }
    
    public function submitForm(Form\Form $form, Context $context)
    {
        unset($form->values[Form\FormComponent::FORM_SUBMIT_BUTTON_NAME]);
        $this->_saveConfig($context, $form->values, $form);
        $this->clearComponentInfoCache();
        if (!isset($this->_successFlash)) {
            $this->_successFlash = __('Settings saved.', 'directories');
        }
    }
    
    protected function _saveConfig(Context $context, array $config, Form\Form $form)
    {
        $component_configs = $this->_getComponentConfigs($context, $config, $form);
        foreach (array_keys($component_configs) as $component_name) {
            $this->System_Component_saveConfig($component_name, $component_configs[$component_name], true);
        }
    }
    
    protected function _getComponentConfigs(Context $context, array $config, Form\Form $form)
    {
        $component_configs = [];
        $form_settings = $this->_getComponentConfigFormSettings($form);
        foreach (array_keys($form_settings) as $key) {
            if (isset($form_settings[$key])
                && is_array($form_settings[$key])
                && isset($form_settings[$key]['#component'])
                && isset($config[$key])
            ) {
                $component_name = $form_settings[$key]['#component'];
                if (!isset($component_configs[$component_name])) {
                    $component_configs[$component_name] = [];
                }
                $component_configs[$component_name] += $config[$key];
            }
        }
        
        return $component_configs;
    }

    /*
     * @return array
     */
    protected function _getComponentConfigFormSettings(Form\Form $form)
    {
        return $form->settings;
    }
}