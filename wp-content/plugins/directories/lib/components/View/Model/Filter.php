<?php
namespace SabaiApps\Directories\Component\View\Model;

class Filter extends Base\Filter
{
    public function isCustomFilter()
    {
        return strpos($this->name, 'field_') === 0 || !empty($this->data['is_custom']);
    }

    public function getField()
    {
        return $this->_model->getComponentEntity('Entity', 'Field', $this->field_id);
    }

    public function getFilterConditions()
    {
        return isset($this->data['conditions']) ? (array)$this->data['conditions'] : [];
    }
    
    public function toDisplayElementArray()
    {
        if ((!$field = $this->fetchObject('Field'))
            && (!$field = $this->_model->getComponentEntity('Entity', 'Field', $this->field_id))
        ) return; // field does not exist
        
        return [
            'name' => 'view_filter_' . $field->getFieldName(),
            'system' => false,
            'data' => [
                'settings' => [
                    'filter_id' => $this->id,
                    'filter' => $this->type,
                    'filter_name' => $this->name,
                    'filter_settings' => [
                        $this->name => $this->data['settings']
                    ],
                ],
            ],
        ];
    }
}

class FilterRepository extends Base\FilterRepository
{
}
