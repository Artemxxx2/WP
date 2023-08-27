<?php
namespace SabaiApps\Directories\Component\Field\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Field\IField;

class ChoiceOptionsHelper
{
    public function help(Application $application, $field, $sort = false, $language = null)
    {
        if (is_array($field)) {
            // 0: bundle name, 1: field name
            if (!$field = $application->Entity_Field($field[0], $field[1])) {
                throw new Exception\RuntimeException('Invalid field');
            }
        } elseif (!$field instanceof IField) {
            throw new Exception\RuntimeException('Invalid field');
        }
        if ($field->getFieldType() !== 'choice') throw new Exception\RuntimeException('Invalid field type: ' . $field->getFieldType() . '; Field name: ' . $field->getFieldName());
        
        $field_settings = $field->getFieldSettings();
        $options = $field_settings['options'];
        if (!isset($language)) $language = $application->getPlatform()->getCurrentLanguage();
        
        if (isset($language)
            && $field->bundle_name
        ) {
            foreach (array_keys($options['options']) as $key) {
                $options['options'][$key] = $application->System_TranslateString(
                    $options['options'][$key],
                    $field->bundle_name . '_' . $field->getFieldName() . '_choice_' . $key,
                    'entity_field',
                    $language
                );
            }
        }

        if ($sort) asort($options['options']);
        
        return $options;
    }
}