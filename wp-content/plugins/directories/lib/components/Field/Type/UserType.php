<?php
namespace SabaiApps\Directories\Component\Field\Type;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Application;

class UserType extends AbstractType implements IQueryable, IOpenGraph, IHumanReadable, ICopiable, IConditionable
{
    use QueryableUserTrait, ConditionableDefaultTrait;
    
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => _x('User', 'field type', 'directories'),
            'default_settings' => [],
            'icon' => 'fas fa-user',
        );
    }

    public function fieldTypeSchema()
    {
        return array(
            'columns' => array(
                'value' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'value',
                    'default' => 0,
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = $user_ids = [];
        foreach ($values as $weight => $value) {
            if (is_array($value)) {  // autocomplete field widget
                foreach ($value as $user_id) {
                    if (!is_numeric($user_id)) {
                        continue;
                    }
                    $user_ids[$user_id] = $user_id;     
                }
            } elseif (is_numeric($value)) {
                $user_ids[$value] = $value;
            } elseif (is_object($value)) {
                $user_ids[$value->id] = $value->id;
            }
        }
        foreach ($user_ids as $user_id) {
            $ret[]['value'] = $user_id;
        }
        return $ret;
    }

    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues)
    {
        $users = [];
        foreach ($values as $key => $value) {
            $users[$value['value']] = $key;
        }
        foreach ($this->_application->UserIdentity(array_keys($users)) as $identity) {
            if (!$identity->id) {
                continue;
            }
            $key = $users[$identity->id];
            $values[$key] = $identity;
            unset($users[$identity->id]);
        }
        // Remove values that were not found
        foreach ($users as $key) {
            unset($values[$key]);
        }
        // Re-order as it was saved
        ksort($values);
    }
    
    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        $current = $new = [];
        foreach ($currentLoadedValue as $identity) {
            $current[] = (int)$identity->id;
        }
        foreach ($valueToSave as $value) {
            $new[] = $value['value'];
        }
        return $current !== $new;
    }
    
    public function fieldOpenGraphProperties()
    {
        return array('profile:username');
    }
    
    public function fieldOpenGraphRenderProperty(IField $field, $property, IEntity $entity)
    {
        if (!$identity = $entity->getSingleFieldValue($field->getFieldName())) return;
        
        return $identity->username;
    }
    
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $identity) {
            $ret[] = $identity->username;
        }
        return implode(isset($separator) ? $separator : ', ', $ret);
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValue, $lang = null)
    {
        return $values;
    }
}