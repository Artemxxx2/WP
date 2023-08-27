<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;

class ReferenceFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\ICopiable
{
    protected function _fieldTypeInfo()
    {
        return array(
            'label' => _x('Reference', 'reference field type', 'directories'),
            'icon' => 'fas fa-sync-alt',
            'default_settings' => array(
                'bundle' => null,
            ),
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

    public function fieldTypeSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        $options = $sync_field_options = [];
        foreach ($this->_application->Entity_Bundles() as $_bundle) {
            if (!empty($_bundle->info['parent'])
                || !empty($_bundle->info['internal'])
                || $bundle->entitytype_name !== $_bundle->entitytype_name
            ) continue;

            $options[$_bundle->name] = $_bundle->getGroupLabel() . ' - ' . $_bundle->getLabel('singular');

            if ($bundle->name !== $_bundle->name) {
                $_bundle_fields = $this->_application->Entity_Field($_bundle);
                foreach (array_keys($_bundle_fields) as $field_name) {
                    $_bundle_field = $_bundle_fields[$field_name];
                    if ($_bundle_field->getFieldType() !== 'entity_reference') continue;

                    $bundle_field_settings = $_bundle_field->getFieldSettings();
                    if ($bundle_field_settings['bundle'] !== $bundle->name) continue; // not referencing current bundle

                    $sync_field_options[$_bundle->name][$field_name] = $_bundle_field->getFieldLabel() . ' (' . $field_name . ')';
                }
            }
        }
        $ret = [
            'bundle' => [
                '#type' => 'select',
                '#title' => __('Content type', 'directories'),
                '#options' => $options,
                '#default_value' => $settings['bundle'],
                '#required' => true,
            ],
            '#submit' => array(
                11 => [[[$this, '_configureSync'], [$fieldType, $bundle->name, $parents, $sync_field_options]]], // 11 is weight
            ),
        ];
        foreach ($sync_field_options as $bundle_name => $bundle_fields) {
            $ret['sync'][$bundle_name] = [
                '#type' => 'select',
                '#title' => __('Synchronize with field', 'directories'),
                '#options' => ['' => '— ' . __('Select field', 'directories') . ' —'] + $bundle_fields,
                '#default_value' => isset($settings['sync'][$bundle_name]) ? $settings['sync'][$bundle_name] : null,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s"]', $this->_application->Form_FieldName(array_merge($parents, ['bundle']))) => ['value' => $bundle_name],
                    ],
                ],
            ];
        }

        return $ret;
    }

    public function _configureSync(Form\Form $form, $fieldType, $bundleName, $parents, $syncFieldOptions)
    {
        $settings = $form->getValue($parents);
        if (empty($settings['bundle'])
            || empty($settings['sync'][$settings['bundle']])
        ) return;

        $sync_field_name = $settings['sync'][$settings['bundle']];
        if (!$sync_field = $this->_application->Entity_Field($settings['bundle'], $sync_field_name)) return;

        $sync_field_settings = $sync_field->getFieldSettings();
        if ($sync_field_settings['bundle'] !== $bundleName) return; // this shouldn't happen but just in case

        if ($fieldType instanceof Field\IField) {
            $field_name = $fieldType->getFieldName();
        } else {
            $field_name = $form->getValue('setting', 'name');
        }
        if (isset($sync_field_settings['sync'][$bundleName])
            && $sync_field_settings['sync'][$bundleName] === $field_name
        ) return; // already configured

        $sync_field_settings['sync'] = [$bundleName => $field_name];
        $sync_field->setFieldSettings($sync_field_settings);
        $sync_field->FieldConfig->commit();
    }

    public function fieldTypeOnSave(Field\IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = $ids = [];
        foreach ($values as $weight => $value) {
            if (is_array($value)) {  // autocomplete field widget
                foreach ($value as $id) {
                    if (empty($id)) {
                        continue;
                    }
                    $ids[$id] = $id;
                }
            } elseif (!empty($value)) {
                $ids[$value] = $value;
            }
        }
        foreach ($ids as $id) {
            $ret[]['value'] = (int)$id;
        }
        return $ret;
    }

    public function fieldTypeOnLoad(Field\IField $field, array &$values, Entity\Type\IEntity $entity, array $allValues)
    {
        $entities = [];
        foreach ($values as $key => $value) {
            $entities[$value['value']] = $key;
        }
        $values = [];
        foreach ($this->_application
            ->Entity_Types_impl($this->_getReferenceEntityType($field))
            ->entityTypeEntitiesByIds(array_keys($entities), $this->_getReferenceBundleName($field))
        as $entity) {
            $key = $entities[$entity->getId()];
            $values[$key] = $entity;
        }
        ksort($values); // re-order as it was saved
    }

    protected function _getReferenceEntityType(Field\IField $field)
    {
        return $field->Bundle->entitytype_name;
    }

    protected function _getReferenceBundleName(Field\IField $field)
    {
        $settings = $field->getFieldSettings();
        return isset($settings['bundle']) ? $settings['bundle'] : null;
    }

    public function fieldTypeIsModified(Field\IField $field, $valueToSave, $currentLoadedValue)
    {
        $current_value = [];
        foreach ($currentLoadedValue as $entity) {
            $current_value[]['value'] = $entity->getId();
        }
        return $current_value !== $valueToSave;
    }

    public function fieldQueryableInfo(Field\IField $field, $inAdmin = false)
    {
        return array(
            'example' => '1,5,13,0',
            'tip' => __('Enter IDs separated with commas. Enter 0 for no matching items.', 'directories'),
        );
    }

    public function fieldQueryableQuery(Field\Query $query, $fieldName, $paramStr, Entity\Model\Bundle $bundle)
    {
        if (!$ids = $this->_queryableParams($paramStr)) return;

        $ids = $this->_getReferenceEntityIds($ids, $include_null);
        if (!empty($ids)) {
            if ($include_null) {
                $query->startCriteriaGroup('OR')
                    ->fieldIsIn($fieldName, $ids)
                    ->fieldIsNull($fieldName)
                    ->finishCriteriaGroup();
            } else {
                $query->fieldIsIn($fieldName, $ids);
            }
        } else {
            if ($include_null) {
                $query->fieldIsNull($fieldName);
            }
        }
    }

    protected function _getReferenceEntityIds(array $ids, &$includeNull)
    {
        $ret = [];
        foreach ($ids as $id) {
            if ($id == 0) {
                $includeNull = true;
            } else {
                $ret[] = $id;
            }
        }
        return $ret;
    }

    public function fieldCopyValues(Field\IField $field, array $values, array &$allValues, $lang = null)
    {
        if (!empty($lang)) {
            $field_settings = $field->getFieldSettings();
            if (empty($field_settings['bundle'])
                || (!$bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
            ) {
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
