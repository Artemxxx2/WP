<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field\IField;
use SabaiApps\Directories\Component\Field\Query;
use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\Type\AbstractType;
use SabaiApps\Directories\Component\Field\Type\IHumanReadable;
use SabaiApps\Directories\Component\Field\Type\IQueryable;
use SabaiApps\Directories\Component\Field\Type\ISortable;

class TermContentCountFieldType extends AbstractType implements
    IHumanReadable,
    IQueryable,
    ISortable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => __('Term content count', 'directories'),
            'entity_types' => array('term'),
            'creatable' => false,
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
                    'length' => 10,
                ),
                'content_bundle_name' => array(
                    'type' => Application::COLUMN_VARCHAR,
                    'notnull' => true,
                    'length' => 40,
                    'was' => 'content_bundle_name',
                    'default' => '',
                ),
                'merged' => array(
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'merged',
                    'default' => 0,
                    'length' => 10,
                ),
            ),
            'indexes' => array(
                'value' => array(
                    'fields' => array('value' => array('sorting' => 'ascending')),
                    'was' => 'value',
                ),
                'merged' => array(
                    'fields' => array('merged' => array('sorting' => 'ascending')),
                    'was' => 'merged',
                ),
            ),
        );
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $value) {
            if (!is_array($value)) {
                $ret[] = false; // delete
            } else {
                $ret[] = $value;
            }
        }
        return $ret;
    }

    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues)
    {
        $all = $all_merged = 0;
        foreach ($values as $value) {
            // Index by child bundle name for easier access to counts
            $values[0][$value['content_bundle_name']] = (int)$value['value'];
            $values[0]['_' . $value['content_bundle_name']] = (int)$value['merged'];
            $all += $value['value'];
            $all_merged += $value['merged'];
            unset($values[0]['value'], $values[0]['content_bundle_name'], $values[0]['merged']);
        }
        $values[0]['all'] = $all;
        $values[0]['_all'] = $all_merged;
    }
    
    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {
        $current = $new = [];
        if (!empty($currentLoadedValue[0])) {
            foreach ($currentLoadedValue[0] as $content_bundle_name => $value) {
                if (strpos($content_bundle_name, '_') === 0) continue;
                
                $current[] = array(
                    'value' => $value,
                    'content_bundle_name' => $content_bundle_name,
                    'merged' => $currentLoadedValue[0]['_' . $content_bundle_name]
                );
            }
        }
        foreach ($valueToSave as $value) {
            $new[] = array(
                'value' => (int)$value['value'],
                'content_bundle_name' => $value['content_bundle_name'],
                'merged' => (int)$value['merged']
            );
        }
        return $current !== $new;
    }
    
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$content_bundle_types = $this->_getCountableContentBundleTypes($field->Bundle)) return '';
        
        $content_bundle_type = array_shift($content_bundle_types);
        if ((!$count = (int)$entity->getSingleFieldValue('entity_term_content_count', '_' . $content_bundle_type))
            || (!$bundle = $field->Bundle)
            || (!$content_bundle = $this->_application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group))
        ) return '';
        
        return sprintf(_n($content_bundle->getLabel('count'), $content_bundle->getLabel('count2'), $count), $count);
    }
    
    public function fieldQueryableInfo(IField $field, $inAdmin = false)
    {
        return array(
            'example' => '5',
            'tip' => __('Enter the minimum number of content items for each taxonomy term.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        $params = $this->_queryableParams($paramStr);
        if (empty($params[0])) return;
        
        if (empty($params[1])) {
            if (!isset($bundle)
                || (!$content_bundle_types = $this->_getCountableContentBundleTypes($bundle))
            ) return;
            
            $content_bundle_name = array_shift($content_bundle_types);
        } else {
            $content_bundle_name = $params[1];
        }

        $query->fieldIs($fieldName, $content_bundle_name, 'content_bundle_name')
            ->fieldIsOrGreaterThan($fieldName, $params[0], 'merged');
    }

    protected function _getCountableContentBundleTypes(Bundle $bundle)
    {
        return $this->_application->Entity_TaxonomyContentBundleTypes($bundle->type);
    }

    public function fieldSortableOptions(IField $field)
    {
        if ((!$bundle = $field->Bundle)
            || (!$content_bundle_types = $this->_getCountableContentBundleTypes($bundle))
        ) return;

        $ret = [];
        foreach ($content_bundle_types as $content_bundle_type) {
            if (!$content_bundle = $this->_application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group)) continue;

            $ret[] = [
                'label' => sprintf(_x('Most %s', 'sort option label', 'directories'), $content_bundle->getLabel()),
                'args' => [$content_bundle_type],
            ];
            $ret[] = [
                'label' => sprintf(_x('Least %s', 'sort option label', 'directories'), $content_bundle->getLabel()),
                'args' => [$content_bundle_type, 'asc'],
            ];
        }

        return empty($ret) ? false : $ret;
    }

    public function fieldSortableSort(Query $query, $fieldName, array $args = null)
    {
        if (!isset($args[0])) return;

        $query->startCriteriaGroup('OR')
            ->fieldIs($fieldName, $args[0], 'content_bundle_name')
            ->fieldIsNull($fieldName, 'content_bundle_name') // include those without any child bundle count
            ->finishCriteriaGroup()
            ->sortByField($fieldName, isset($args[1]) && $args[1] === 'asc' ? 'ASC' : 'DESC', 'merged', null, 0);
    }
}