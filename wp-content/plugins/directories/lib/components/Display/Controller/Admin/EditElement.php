<?php
namespace SabaiApps\Directories\Component\Display\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;

class EditElement extends Form\Controller
{
    protected $_element, $_display;

    protected function _doGetFormSettings(Context $context, array &$formStorage)
    {        
        if ((!$this->_display = $this->_getDisplay($context))
            || (!$element_id = $context->getRequest()->asStr('element_id'))
            || (!$this->_element = $this->getModel('Element', 'Display')->fetchById($element_id))
        ) {
            $context->setError();
            return;
        }

        // Set options
        $this->_submitButtons = [[
            '#btn_label' => __('Save Changes', 'directories'),
            '#btn_color' => 'primary',
            '#btn_size' => 'lg',
        ]];
        $this->_ajaxCancelType = 'hide';
        $this->_ajaxOnCancel = 'function(target){ target.closest(".drts-display-element").removeClass("drts-display-element-editing-inline"); }';
        $this->_ajaxOnSuccess = 'function(result, target, trigger) {
            $(DRTS).trigger("display_element_updated.sabai", {trigger: trigger, result: result, target: target});
        }';
        $this->_ajaxOnSuccessRedirect = $this->_ajaxOnErrorRedirect = false;
        $this->_ajaxModalHideOnSend = true;

        return $this->Display_ElementForm($this->_display, $this->_element, $context->getRoute(), $this->_getSubimttedValues($context, $formStorage));
    }

    public function submitForm(Form\Form $form, Context $context)
    {
        $result = $this->Display_AdminElement_update($this->_display, $this->_element, $form->values);
        $context->setSuccess($this->_getSuccessUrl($context), $result);
        
        // Clear display cache
        $this->Display_Display_clearCache($this->_display);
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
