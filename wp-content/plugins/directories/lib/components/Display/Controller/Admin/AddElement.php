<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class AddElement extends Form\Controller
{    
    protected $_display;
    
    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {        
        if ((!$this->_display = $this->_getDisplay($context))
            || (!$element_name = $context->getRequest()->asStr('element'))
        ) {
            $context->setError();
            return;
        }

        // Set options
        $this->_submitButtons = [[
            '#btn_label' => __('Add Element', 'directories'),
            '#btn_color' => 'success',
            '#btn_size' => 'lg',
        ]];
        $this->_ajaxCancelType = 'hide';
        $this->_ajaxOnCancel = 'function(target){ target.empty().closest(".drts-display-element").removeClass("drts-display-element-editing-inline"); }';
        $this->_ajaxOnSuccess = 'function(result, target, trigger) {
            $(DRTS).trigger("display_element_created.sabai", {trigger: trigger, result: result, target: target});
        }';
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_ajaxModalHideOnSend = true;

        return $this->Display_ElementForm($this->_display, $element_name, $context->getRoute(), $this->_getSubimttedValues($context, $formStorage));
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $result = $this->Display_AdminElement_create(
            $this->_display,
            $form->values['element'],
            (int)@$form->values['parent_id'],
            $form->values
        );
        $context->setSuccess($this->_getSuccessUrl($context), $result);
        
        // Clear display and elements cache
        $this->Display_Display_clearCache($this->_display);
        $this->getPlatform()->deleteCache('display_elements_' . $this->_display->bundle_name);
    }
    
    protected function _getDisplay(Context $context)
    {
        if ((!$display_id = $context->getRequest()->asInt('display_id'))
            || (!$display = $this->getModel('Display', 'Display')->fetchById($display_id))
        ) return false;
        
        return $display;
    }
    
    protected function _getSuccessUrl(Context $context)
    {
        return dirname($context->getRoute());
    }
}
