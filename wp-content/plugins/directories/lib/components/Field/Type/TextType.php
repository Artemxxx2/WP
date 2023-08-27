<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Application;

class TextType extends AbstractStringType
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Paragraph Text', 'directories'),
            'default_widget' => 'textarea',
            'default_settings' => array(
                'min_length' => null,
                'max_length' => null,
                'char_validation' => 'none',
                'regex' => null,
            ),
            'icon' => 'fas fa-bars',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_TEXT,
                    'notnull' => true,
                    'was' => 'value',
                ),
            ),
        );
    }

    public function fieldTypeDefaultValueForm($fieldType, Bundle $bundle, array $settings, array $parents = [])
    {
        return [
            '#type' => 'textarea',
            '#rows' => 3,
        ];
    }
    
    public function fieldSchemaProperties()
    {
        return array('description', 'text', 'reviewBody', 'articleBody');
    }
    
    public function fieldSchemaRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;
        
        $ret = [];
        switch ($property) {
            case 'description':
                foreach ($values as $value) {
                    $ret[] = $this->_application->Summarize(is_array($value) ? $value['value'] : $value, 300);
                }
                break;
            case 'text':
            case 'reviewBody':
                foreach ($values as $value) {
                    $ret[] = $this->_application->Summarize(is_array($value) ? $value['value'] : $value, 0);
                }
                break;
        }
        
        return $ret;
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('og:description');
    }
    
    public function fieldOpenGraphRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$value = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        return array($this->_application->Summarize(is_array($value) ? $value['value'] : $value, 300));
    }
    
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        return implode(isset($separator) ? $separator : PHP_EOL . PHP_EOL, $values);
    }

    public function fieldColumnableInfo(IField $field){}

    public function fieldTitle(IField $field, array $values)
    {
        $value = parent::fieldTitle($field, $values);

        return strlen($value) ? $this->_application->Summarize($value, 150) : null;
    }
}