<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Exception;

class ExistingFormDisplayElement extends FormDisplayElement
{
    protected $_fieldName, $_skipFieldNameCount = true;
    
    public function __construct(Application $application, $name)
    {
        $name = explode('__', substr($name, strlen('entity_existing_form_'))); // remove prefix and split field type and name
        parent::__construct($application, 'entity_form_' . $name[0]);
        $this->_fieldName = $name[1];
    }
    
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        $ret = parent::_displayElementInfo($bundle);
        $ret['label'] .= ' ' . $this->_fieldName;
        return $ret;
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'form'
            && !$this->_application->Entity_Field($bundle, $this->_fieldName); // make sure the field has not yet been added
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        $form = parent::displayElementSettingsForm($bundle, $settings, $display);
        $form['_name'] = array(
            '#type' => 'textfield',
            '#title' => __('Field name', 'directories'),
            '#value' => $this->_fieldName,
            '#horizontal' => true,
            '#disabled' => true,
            '#weight' => 2,
        );
        $form['field_name'] = array(
            '#type' => 'hidden',
            '#value' => $this->_fieldName,
        );
        $form['#field_name'] = $this->_fieldName; // let other components access this field on build form filter event
        unset($form['name']);
        
        return $form;
    }
}
