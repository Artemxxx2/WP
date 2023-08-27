<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;

class ParentFieldType extends Field\Type\AbstractType implements
    Field\Type\ISchemable,
    Field\Type\IQueryable,
    Field\Type\ICopiable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => 'Parent Entity',
            'creatable' => false,
            'requirable' => false,
            'required' => true,
            'disablable' => false,
            'icon' => 'fas fa-sitemap',
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
        );
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = [];
        foreach ($values as $weight => $value) {
            if (strlen((string)$value) === 0) continue;

            $ret[]['value'] = $value;
            break;
        }
        return $ret;   
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, IEntity $entity, array $allValues)
    {
        $parent_id = $values[0]['value'];
        $values = [];
        if ($parent_id
            && ($parent_entity = $this->_application
                ->Entity_Types_impl($entity->getType())
                ->entityTypeEntityById($parent_id))
        ) {
            $values[] = $parent_entity;
        }
    }
    
    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        $current = $new = [];
        foreach ($currentLoadedValue as $value) {
            $current[] = (int)$value->getId();
        }
        foreach ($valueToSave as $value) {
            $new[] = (int)$value['value'];
        }
        return $current !== $new;
    }
    
    public function fieldSchemaProperties()
    {
        return array('parentItem', 'itemReviewed');
    }
    
    public function fieldSchemaRenderProperty(Field\IField $field, $property, IEntity $entity)
    {   
        switch ($property) {
            case 'parentItem':
                // Fetch parent item
                if (!$parent = $this->_application->Entity_ParentEntity($entity, false)) return;
                
                return array(array(
                    '@type' => 'Question', // the parentItem property expects Question type
                    'name' => $this->_application->Entity_Title($parent),
                    'text' => $this->_application->Summarize($parent->getContent(), 0),
                    'url' => (string)$this->_application->Entity_PermalinkUrl($parent),
                ));
            case 'itemReviewed':
                // Fetch parent item with field values
                if (!$parent = $this->_application->Entity_ParentEntity($entity)) return;
                
                // Return schema.org markup of parent item
                if ((!$parent_bundle = $this->_application->Entity_Bundle($parent))
                    || empty($parent_bundle->info['entity_schemaorg'])
                ) return;
                
                $parent_schema = $parent_bundle->info['entity_schemaorg'];
                if (!in_array($parent_schema['type'], [
                    'Book', 'Course', 'CreativeWorkSeason', 'CreativeWorkSeries', 'Episode', 'Event', 'Game', 'HowTo', 'LocalBusiness',
                    'MediaObject', 'Movie', 'MusicPlaylist', 'MusicRecording', 'Organization', 'Product', 'Recipe', 'SoftwareApplication',
                ])) return;

                return $this->_application->Entity_SchemaOrg_json($parent, $parent_schema['type'], (array)$parent_schema['properties']);
        }
    }
    
    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        return array(
            'example' => '',
            'tip' => __('Enter a parent ID.', 'directories'),
        );
    }
    
    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Bundle $bundle)
    {
        $query->fieldIs($fieldName, trim($paramStr));
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValues, $lang = null)
    {
        if (!empty($lang)) {
            if (!$bundle = $field->Bundle) {
                $this->_application->logError('Failed fetching reference field bundle.');
                return;
            }

            if ($this->_application->getPlatform()->isTranslatable($bundle->entitytype_name, $bundle->name)) {
                foreach (array_keys($values) as $k) {
                    if (empty($values[$k]['value'])) continue;

                    $translation_id = (int)$this->_application->getPlatform()->getTranslatedId(
                        $bundle->entitytype_name,
                        $bundle->name,
                        $values[$k]['value'],
                        $lang
                    );
                    if (empty($translation_id)) {
                        unset($values[$k]);
                    } else {
                        $values[$k]['value'] = $translation_id;
                    }
                }

            }
        }

        return $values;
    }
}