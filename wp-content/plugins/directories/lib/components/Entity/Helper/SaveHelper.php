<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Framework\User\AbstractIdentity;

class SaveHelper
{
    public function help(Application $application, $bundleOrEntity, array $values, array $extraArgs = [], AbstractIdentity $identity = null)
    {
        $entity = $bundle = null;
        if ($bundleOrEntity instanceof Entity\Type\IEntity) {
            $entity = $bundleOrEntity;
        } elseif ($bundleOrEntity instanceof Entity\Model\Bundle) {
            $bundle = $bundleOrEntity;
        } elseif (is_numeric($bundleOrEntity)) {
            if (!$entity = $application->Entity_Entity('post', $bundleOrEntity)) {
                throw new Exception\RuntimeException('Invalid entity.');
            }
        } elseif (is_string($bundleOrEntity)) {
            if (!$bundle = $application->Entity_Bundle($bundleOrEntity)) {
                throw new Exception\RuntimeException('Invalid bundle: ' . $bundleOrEntity);
            }
        } elseif (is_array($bundleOrEntity)) {
            if (!$entity = $application->Entity_Entity($bundleOrEntity[0], $bundleOrEntity[1])) {
                throw new Exception\RuntimeException('Invalid entity.');
            }
        }
        if ($entity) {
            return $this->_updateEntity($application, $entity, $values, $extraArgs);
        }
        if ($bundle) {
            return $this->_createEntity($application, $bundle, $values, $extraArgs, $identity);
        }
        throw new Exception\InvalidArgumentException();
    }
    
    protected function _createEntity(Application $application, Entity\Model\Bundle $bundle, array $values, array $extraArgs = [], AbstractIdentity $identity = null)
    {
        // Notify that an entity is being created
        $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, &$values, &$extraArgs, $identity), 'before_create');
        // Extract field values for saving
        $values = $this->_extractFieldValues($application, $bundle->entitytype_name, $fields = $application->Entity_Field($bundle), $values, null, $extraArgs);
        // Notify that an entity is being created, with extracted fields values
        $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, &$values, &$extraArgs, $identity), 'create');
        // Save entity
        $entity = $this->_saveEntity($application, $bundle, $values, $fields, null, $identity, $extraArgs);
        // Notify that an entity has been created
        $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $entity, $values, &$extraArgs), 'after_create');
        // Load entity fields
        $application->Entity_Field_load($entity, null, true);
        // Notify that an entity has been saved
        $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $entity, $values, $extraArgs), 'create', 'success');

        return $entity;
    }

    protected function _updateEntity(Application $application, Entity\Type\IEntity $entity, array $values, array $extraArgs = [])
    {
        if (!$bundle = $application->Entity_Bundle(isset($extraArgs['bundle']) ? $extraArgs['bundle'] : $entity)) {
            throw new Exception\RuntimeException('Invalid bundle.');
        }
        if ($entity->isFromCache()) {
            // this entity was loaded from cache, so load again from storage to make sure the current values are available
            if (!$entity = $application->Entity_Entity($bundle->entitytype_name, $entity->getId(), false)) {
                throw new Exception\RuntimeException('Invalid entity.');
            }
        }
        
        // Make sure all the fields are loaded
        $application->Entity_Field_load($entity, null, true, false);
        
        if (empty($extraArgs['force_create_events'])) {
            // Notify that an entity is being updated
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $entity, &$values, &$extraArgs), 'before_update');
        } else {
            // Notify that an entity is being created
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, &$values, &$extraArgs), 'before_create');
        }
        
        // Extract modified field values for saving
        $values = $this->_extractFieldValues($application, $bundle->entitytype_name, $fields = $application->Entity_Field($bundle), $values, $entity, $extraArgs);
        
        if (empty($extraArgs['force_create_events'])) {
            // Notify that an entity is being updated, with extracted field values
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $entity, &$values, &$extraArgs), 'update');
        } else {
            // Notify that an entity is being created, with extracted fields values
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, &$values, &$extraArgs), 'create');
        }
        
        // Save entity
        $updated_entity = $this->_saveEntity($application, $bundle, $values, $fields, $entity, null, $extraArgs);
        
        if (empty($extraArgs['force_create_events'])) {
            // Notify that an entity has been updated
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $updated_entity, $entity, $values, &$extraArgs), 'after_update');
        } else {
            // Notify that an entity has been created
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $updated_entity, $values, &$extraArgs), 'after_create');
        }
            
        // Clear cached entity fields
        $application->Entity_Field_removeCache($updated_entity->getBundleName(), array($updated_entity->getId()));
        // Field values may have changed, so reload entity
        $application->Entity_Field_load($updated_entity, null, true);
        
        if (empty($extraArgs['force_create_events'])) {
            // Notify that an entity has been saved
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $updated_entity, $entity, $values, $extraArgs), 'update', 'success');
        } else {
            // Notify that an entity has been saved
            $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, array($bundle, $updated_entity, $values, $extraArgs), 'create', 'success');
        }
        
        return $updated_entity;
    }
    
    private function _extractFieldValues(Application $application, $entityType, array $fields, array $fieldValues, Entity\Type\IEntity $entity = null, array &$extraArgs = [])
    {     
        // Extract field values to save
        $ret = [];
        
        // Convert field values to properties if properties are saved as external fields for the entity type
        foreach ($application->Entity_Types_impl($entityType)->entityTypeInfo('properties') as $property_name => $property) {
            if (empty($property)) continue; // does not support this property
            
            if (null === ($property_value = @$fieldValues[$property_name]) // saving with property name?
                && null === ($property_value = @$fieldValues[$entityType . '_' . $property_name]) // saving with property field name?
            ) {
                if (null === ($field_name = @$property['field_name']) // property saved as external field?
                    || !isset($fieldValues[$field_name]) // saving this property?   
                    || null === ($field = @$fields[$field_name]) // valid field?
                    || (!$field_type = $application->Field_Type($field->getFieldType(), true)) // valid field type?
                ) continue; // not saving this property
                
                // External property field
                
                $property_value = $fieldValues[$field_name];
                unset($fieldValues[$field_name]);

                // Property field may come as array
                if (is_array($property_value)
                    && array_key_exists(0, $property_value)
                ) {
                    $property_value = $property_value[0];
                }
                
                // Always pass field value as array
                $field_value = array($property_value);             
                // Get current value if this is an update
                $current_field_value = isset($entity) ? $entity->getFieldValue($field_name) : null;
                // Let the field type component for the field to work on values before saving to the storage
                $field_value = $field_type->fieldTypeOnSave($field, $field_value, $current_field_value, $extraArgs);
                if (!is_array($field_value)
                    || (empty($field_value) && empty($current_field_value))
                ) continue;
                
                // If this is an update, make sure that the new value is different from the existing one
                if (isset($entity)
                    && $current_field_value !== null
                    && (empty($extraArgs['skip_is_modified_check'])
                        || (is_array($extraArgs['skip_is_modified_check']) && empty($extraArgs['skip_is_modified_check'][$field_name]))
                    )
                    && !$field_type->fieldTypeIsModified($field, $field_value, $current_field_value)
                ) {
                    // value hasn't changed, so do not update this field
                    continue;
                }
                
                $ret[$property_name] = $field_value[0];
            } else {
                // Normal property field
                
                $field_name = $entityType . '_' . $property_name;
                unset($fieldValues[$property_name], $fieldValues[$field_name]);
                
                // Property field may come as array
                if (is_array($property_value)) {
                    if (!array_key_exists(0, $property_value)) continue; // invalid array
                    
                    $property_value = $property_value[0];
                }

                if (isset($entity)
                    && (empty($extraArgs['skip_is_modified_check'])
                        || (is_array($extraArgs['skip_is_modified_check']) && empty($extraArgs['skip_is_modified_check'][$property_name]))
                    )
                    && !$entity->isPropertyModified($field_name, $property_value)
                ) {
                    // value hasn't changed, so do not update this property
                    continue;
                } 
                
                $ret[$property_name] = $property_value;
            }
        }
     
        foreach ($fields as $field_name => $field) {
            if ($field->isPropertyField()
                || null === ($field_value = @$fieldValues[$field_name])
                || (!$field_type = $application->Field_Type($field->getFieldType(), true))
            ) continue;
                
            // Always pass value as array
            if (!is_array($field_value)
                || (!empty($field_value) && !array_key_exists(0, $field_value))
            ) {
                $field_value = array($field_value);
            } else {
                unset($field_value['_add']); // remove add more button value
            }
            
            // Get current value if this is an update
            if (!isset($entity)
                || (false === $current_field_value = $entity->getFieldValue($field_name))
            ) {
                $current_field_value = null;
            }
            // Let the field type component for the field to work on values before saving to the storage
            $field_value = $field_type->fieldTypeOnSave($field, $field_value, $current_field_value, $extraArgs);
            if (!is_array($field_value)
                || (empty($field_value) && empty($current_field_value))
            ) continue;
            
            // If this is an update, make sure that the new value is different from the existing one
            if (isset($entity)
                && $current_field_value !== null
                && (empty($extraArgs['skip_is_modified_check'])
                    || (is_array($extraArgs['skip_is_modified_check']) && empty($extraArgs['skip_is_modified_check'][$field_name]))
                )
                && !$field_type->fieldTypeIsModified($field, $field_value, $current_field_value)
            ) {
                // the value hasn't changed, so skip this field
                continue;
            }
            
            // Is the maximum number of items for this field limited?
            $max_num_items = isset($extraArgs['entity_field_max_num_items'][$field_name]) ? $extraArgs['entity_field_max_num_items'][$field_name] : $field->getFieldMaxNumItems();
            if (!is_numeric($max_num_items)) {
                $max_num_items = 10; // defaults to 10
            }
            if ($max_num_items && count($field_value) > $max_num_items) {
                $field_value = array_slice($field_value, 0, $max_num_items);
            }
            $ret[$field_name] = $field_value;
        }        
        
        return $ret;
    }

    private function _saveEntity(Application $application, Entity\Model\Bundle $bundle, array $fieldValues, $fields, Entity\Type\IEntity $entity = null, AbstractIdentity $identity = null, array &$extraArgs = [])
    {
        // Extract field values to save
        $field_values = $properties = [];
        
        foreach ($application->Entity_Types_impl($bundle->entitytype_name)->entityTypeInfo('properties') as $property_name => $property) {
            if (empty($property) // does not support this property
                || (null === $property_value = @$fieldValues[$property_name])
            ) continue;
            
            unset($fieldValues[$property_name]);
            
            if ($field_name = @$property['field_name']) { // property saved as external field?
                if (null === ($field = @$fields[$field_name]) // valid field?
                    || (!$field_type = $application->Field_Type($field->getFieldType(), true)) // valid field type?
                ) continue;
                
                $field_values[$field_name] = [$property_value];
            }
            $properties[$property_name] = $property_value;
        }
        
        foreach ($fields as $field) {
            if ($field->isPropertyField()) continue;
            
            $field_name = $field->getFieldName();
            if (!isset($fieldValues[$field_name])) continue;
            
            $field_values[$field_name] = $fieldValues[$field_name];
        }

        // Save entity
        $entity_type_impl = $application->Entity_Types_impl($bundle->entitytype_name);
        if (!isset($entity)) {
            $is_new = true;
            if (!isset($identity)) {
                $identity = $application->getUser()->getIdentity();
            }
            $ret = $entity_type_impl->entityTypeCreateEntity($bundle, $properties, $identity);
        } else {
            $is_new = false;
            $ret = $entity_type_impl->entityTypeUpdateEntity($entity, $bundle, $properties);
        }

        // Save fields
        $this->invokeEvents($application, $bundle->entitytype_name, $bundle->type, [$bundle, $ret, $is_new, &$field_values], 'save', 'fields');
        if (!empty($field_values)) {
            $application->Entity_Storage()->saveValues($ret, $field_values);
        }
        
        return $ret;
    }
    
    public function invokeEvents(Application $application, $entityType, $bundleType, array $params, $prefix, $suffix = '')
    {
        $prefix = 'entity_' . $prefix;
        $suffix = $suffix !== '' ? 'entity_' . $suffix : 'entity';
        $application->Action($prefix . '_' . $suffix, $params);
        $application->Action($prefix . '_' . $entityType . '_' . $suffix, $params);
        $application->Action($prefix . '_' . $entityType . '_' . $bundleType . '_' . $suffix, $params);
    }
}