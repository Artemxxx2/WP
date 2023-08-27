<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field;

class CompanyNameGenerator extends AbstractGenerator
{   
    protected function _fakerGeneratorInfo()
    {
        return array(
            'label' => __('Company Name Generator'),
            'field_types' => array('entity_title', 'field_string'),
            'default_settings' => array(
                'max' => 5,
            ),
        );
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($field->getFieldType()) {
            case 'field_string':
                return array(
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        switch ($field->getFieldType()) {
            case 'entity_title':
                return $this->_getFaker()->company();
            case 'field_string':
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $ret[] =  $this->_getFaker()->company();
                }
                return empty($ret) ? null : $ret;
        } 
    }
}