<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;

class FieldsHelper
{
    /**
     * Returns all available form fields
     * @param Application $application
     */
    public function help(Application $application, $useCache = true)
    {
        if (!$useCache
            || (!$form_fields = $application->getPlatform()->getCache('form_fields'))
        ) {
            $form_fields = [];
            foreach ($application->InstalledComponentsByInterface('Form\IFields') as $component_name) {
                if (!$application->isComponentLoaded($component_name)) continue;
                
                foreach ($application->getComponent($component_name)->formGetFieldTypes() as $field_type) {
                    if (!$application->getComponent($component_name)->formGetField($field_type)) {
                        continue;
                    }
                    $form_fields[$field_type] = $component_name;
                }
            }
            $application->getPlatform()->setCache($form_fields, 'form_fields', 0);
        }

        return $form_fields;
    }
    
    private $_impls = [];

    /**
     * Gets an implementation of SabaiApps\Directories\Component\Form\Field\IField interface for a field type
     * @param Application $application
     * @param string $field
     */
    public function impl(Application $application, $field, $formId, $returnFalse = false, $useCache = true)
    {
        if (!isset($this->_impls[$formId][$field])) {
            $fields = $this->help($application, $useCache);
            // Valid field type?
            if (!isset($fields[$field])
                || (!$application->isComponentLoaded($fields[$field]))
            ) {
                // for deprecated renderer
                if ($field === 'file_file') {
                    return $this->help($application, 'file', $returnFalse, $useCache);
                }
                
                if ($returnFalse) return false;
                throw new Exception\UnexpectedValueException(sprintf('Invalid form field type: %s', $field));
            }
            $this->_impls[$formId][$field] = $application->getComponent($fields[$field])->formGetField($field);
        }

        return $this->_impls[$formId][$field];
    }
}