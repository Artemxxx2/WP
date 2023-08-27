<?php
namespace SabaiApps\Directories\Component\Entity;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

class EntityComponent extends AbstractComponent implements
    Field\ITypes,
    Field\IWidgets,
    Field\IRenderers,
    Field\IFilters,
    Display\IElements,
    Display\ILabels,
    Display\IStatistics,
    System\IMainRouter,
    System\IAdminRouter,
    System\ITools
{
    const VERSION = '1.3.108', PACKAGE = 'directories';
    const FIELD_REALM_ALL = 0, FIELD_REALM_ENTITY_TYPE_DEFAULT = 1, FIELD_REALM_BUNDLE_DEFAULT = 2;

    public static function interfaces()
    {
        return ['WordPressContent\INotifications'];
    }

    public static function description()
    {
        return 'Provides general functions and features to manage content.';
    }

    public function onCoreComponentsLoaded()
    {
        $this->_application->setHelper('Entity_Query', array(__CLASS__, 'queryHelper'))
            ->setHelper('Entity_Storage', array(__CLASS__, 'storageHelper'))
            ->setHelper('Entity_Component', array(__CLASS__, 'componentHelper'))
            ->setHelper('Entity_Entity', array(__CLASS__, 'entityHelper'))
            ->setHelper('Entity_Path', array(__CLASS__, 'pathHelper'))
            ->setHelper('Entity_Title', array(__CLASS__, 'titleHelper'))
            ->setHelper('Entity_Icon', array(__CLASS__, 'iconHelper'))
            ->setHelper('Entity_Image', array(__CLASS__, 'imageHelper'))
            ->setHelper('Entity_Color', array(__CLASS__, 'colorHelper'))
            ->setHelper('Entity_Status', array(__CLASS__, 'statusHelper'))
            ->setHelper('Entity_Author', array(__CLASS__, 'authorHelper'))
            ->setHelper('Entity_IsAuthor', array(__CLASS__, 'isAuthorHelper'))
            ->setHelper('Entity_HtmlClass', array(__CLASS__, 'htmlClassHelper'));
    }

    public static function titleHelper(Application $application, Type\IEntity $entity, $noFilter = false)
    {
        $title = strip_tags($entity->getTitle());
        if (!strlen($title)) {
            $title = $application->Summarize($entity->getContent(), 150);
            if (!strlen($title)) $title = __('(no title)', 'directories');
        }
        return $noFilter ? $title : $application->Filter('entity_title', $title, [$entity]);
    }

    public static function imageHelper(Application $application, Type\IEntity $entity, $size = 'medium', $fieldName = null)
    {
        if ((!$bundle = $application->Entity_Bundle($entity))
            || (!$field_name = isset($fieldName) ? $fieldName : (isset($bundle->info['entity_image']) ? $bundle->info['entity_image'] : null))
            || (!$value = $entity->getSingleFieldValue($field_name))
            || (!$field_type = $application->Field_Type($entity->getFieldType($field_name), true))
            || !$field_type instanceof Field\Type\IImage
        ) return;

        switch ($size) {
            case 'icon':
                return $field_type->fieldImageGetIconUrl($value);
            case 'icon_lg':
                return $field_type->fieldImageGetIconUrl($value, 'lg');
            case 'icon_xl':
                return $field_type->fieldImageGetIconUrl($value, 'xl');
            case 'full':
                return $field_type->fieldImageGetFullUrl($value);
            default:
                return $field_type->fieldImageGetUrl($value, $size);
        }
    }

    public static function iconHelper(Application $application, Type\IEntity $entity, $fallbackBundleIcon = true, $iconField = null)
    {
        if ($bundle = $application->Entity_Bundle($entity)) {
            if (!isset($iconField)
                && isset($bundle->info['entity_icon'])
            ) {
                $iconField = $bundle->info['entity_icon'];
            }
            if (!empty($iconField)
                && ($icon = $entity->getSingleFieldValue($iconField))
            ) {
                return $icon;
            }
        }

        return $fallbackBundleIcon ? $application->Entity_BundleTypeInfo($entity->getBundleType(), 'icon') : null;
    }

    public static function colorHelper(Application $application, Type\IEntity $entity)
    {
        if (($bundle = $application->Entity_Bundle($entity))
            && !empty($bundle->info['entity_color'])
            && ($color = $entity->getSingleFieldValue($bundle->info['entity_color']))
        ) return $color;
    }

    public static function queryHelper(Application $application, $entityType, $bundleName = null, $operator = null)
    {
        return $application->Entity_Types_impl($entityType)->entityTypeGetQuery($operator, $bundleName);
    }

    public static function storageHelper(Application $application)
    {
        return Storage::getInstance($application);
    }

    public static function componentHelper(Application $application, $entityOrBundleName)
    {
        return $application->getComponent($application->Entity_Bundle($entityOrBundleName)->component);
    }

    public static function entityHelper(Application $application, $entityType, $entityId, $loadEntityFields = true)
    {
        try {
            if (is_array($entityId)) {
                if (count($entityId) !== 2) throw new Exception\InvalidArgumentException();
                // Array with bundle name and entity slug passed
                if (!$entity = $application->Entity_Types_impl($entityType)->entityTypeEntityBySlug($entityId[0], $entityId[1])) return;
            } else {
                if (!$entity = $application->Entity_Types_impl($entityType)->entityTypeEntityById($entityId)) return;
            }
            if ($loadEntityFields) $application->Entity_Field_load($entity);
            return $entity;
        } catch (Exception\IException $e) {
            $application->logError($e);
        }
    }

    public static function pathHelper(Application $application, Type\IEntity $entity, $lang = null)
    {
        $bundle = $application->Entity_Bundle($entity, null, '', true);
        if (!empty($bundle->info['parent'])) { // child entity bundles do not have custom permalinks
            return ($parent = $application->Entity_ParentEntity($entity, false))
                ? str_replace(':slug', $parent->getSlug(), $bundle->getPath(true, $lang)) . '/' . $entity->getId()
                : '';
        }
        return $bundle->getPath(true, $lang) . '/' . $entity->getSlug();
    }

    public static function authorHelper(Application $application, Type\IEntity $entity)
    {
        if ((!$author = $entity->getAuthor())
            && $entity->getAuthorId()
        ) {
            $author = $application->UserIdentity($entity->getAuthorId());
            if ($entity->getType() === 'post') {
                $entity->setAuthor($application->Filter('entity_author', $author, [$entity]));
            }
        }
        return $author;
    }

    public static function isAuthorHelper(Application $application, Type\IEntity $entity, \SabaiApps\Framework\User\User $user = null)
    {
        if (!isset($user)) $user = $application->getUser();

        if (!$user->isAnonymous()) return $entity->getAuthorId() === $user->id;

        return $application->Filter('entity_is_author', false, array($entity, $user));
    }

    public static function htmlClassHelper(Application $application, Type\IEntity $entity, $asArray = false)
    {
        $classes = array(
            'drts-entity',
            'drts-entity-' . $entity->getType(),
        );
        if ($featured = $entity->isFeatured()) {
            $classes[] = 'drts-entity-featured';
            $classes[] = 'drts-entity-featured-' . $featured;
        }

        $classes = $application->Filter('entity_html_classes', $classes, array($entity));

        return $asArray ? $classes : implode(' ', $classes);
    }

    public static function statusHelper(Application $application, $entityType, $status = 'publish')
    {
        $info = $application->Entity_Types_impl($entityType)->entityTypeInfo('status');
        if (is_array($status)) {
            $ret = [];
            foreach ($status as $_status) {
                $ret[] = isset($info[$_status]) ? $info[$_status] : $_status;
            }
            return $ret;
        }
        return isset($info[$status]) ? $info[$status] : $status;
    }

    public function onEntityITypesInstalled(AbstractComponent $component)
    {
        if (!$new_entity_types = $component->entityGetTypeNames()) return;

        $this->_createEntityTypes($component, $new_entity_types);
        $this->_application->getPlatform()->deleteCache('entity_types');
    }

    public function onEntityITypesUninstalled(AbstractComponent $component)
    {
        $entity_types = $this->_application->Entity_Types(true);
        if (!isset($entity_types[$component->getName()])) return;

        $this->_deleteEntityTypes($component, $entity_types[$component->getName()]);
        $this->_application->getPlatform()->deleteCache('entity_types');
    }

    public function onEntityITypesUpgraded(AbstractComponent $component)
    {
        $entity_types = $this->_application->Entity_Types(true);
        if (!$names = $component->entityGetTypeNames()) {
            if (isset($entity_types[$component->getName()])) {
                $this->_deleteEntityTypes($component, $entity_types[$component->getName()]);
                unset($entity_types[$component->getName()]);
            }
        } else {
            if (!isset($entity_types[$component->getName()])) {
                $this->_createEntityTypes($component, $names);
                $entity_types[$component->getName()] = $names;
            } else {
                $old_entity_types = $entity_types[$component->getName()];
                $entity_types[$component->getName()] = [];
                if ($new_entity_types = array_diff($names, $old_entity_types)) {
                    $this->_createEntityTypes($component, $new_entity_types);
                    foreach ($new_entity_types as $new_entity_type) {
                        $entity_types[$component->getName()][] = $new_entity_type;
                    }
                }
                if ($current_entity_types = array_intersect($old_entity_types, $names)) {
                    $this->_updateEntityTypes($component, $current_entity_types);
                    foreach ($current_entity_types as $current_entity_type) {
                        $entity_types[$component->getName()][] = $current_entity_type;
                    }
                }
                if ($deleted_entity_types = array_diff($old_entity_types, $names)) {
                    $this->_deleteEntityTypes($component, $deleted_entity_types);
                }
            }
        }
        $this->_application->getPlatform()->deleteCache('entity_types');
    }

    private function _createEntityTypes(AbstractComponent $component, array $entityTypes)
    {
        $model = $this->getModel();
        $entity_types = [];
        foreach ($entityTypes as $name) {
            $info = $component->entityGetType($name)->entityTypeInfo();
            // Create entity type specific fields if any
            if (!empty($info['properties'])) {
                foreach ($info['properties'] as $property_name => $property_info) {
                    if (empty($property_info)) continue;

                    $this->_createEntityPropertyFieldConfig($name, $property_name, $property_info);
                }
            }
            $entity_types[] = $name;
        }
        $model->commit();

        $this->_application->Action('entity_create_entity_types_success', array($entity_types));
    }

    private function _updateEntityTypes(AbstractComponent $component, $entityTypes)
    {
        $new_fields_by_entity_type = [];
        foreach ($entityTypes as $entity_type) {
            $info = $component->entityGetType($entity_type)->entityTypeInfo();
            // Update property fields
            $current_fields = $this->getModel('FieldConfig')
                ->entitytypeName_is($entity_type)
                ->property_isNot('')
                ->fetch();
            if (!empty($info['properties'])) {
                // Check for re-named properties
                $was = [];
                foreach ($info['properties'] as $property => $property_info) {
                    if (isset($property_info['was'])) {
                        $was[$property_info['was']] = $property;
                    }
                }
                $fields_already_installed = [];
                foreach ($current_fields as $current_field) {
                    if (empty($info['properties'][$current_field->property])) {
                        if (isset($was[$current_field->property])) {
                            // Rename
                            $current_field->property = $was[$current_field->property];
                            $current_field->name = $entity_type . '_' . $current_field->property;
                        } else {
                            $current_field->markRemoved();
                            continue;
                        }
                    }
                    if (isset($info['properties'][$current_field->property]['settings'])) {
                        $current_field->settings = $info['properties'][$current_field->property]['settings'];
                    }
                    if ($current_field->type !== $info['properties'][$current_field->property]['type']) {
                        $current_field->type = $info['properties'][$current_field->property]['type'];
                    }
                    $fields_already_installed[] = $current_field->property;
                }
                // Create newly added fields
                foreach (array_diff(array_keys($info['properties']), $fields_already_installed) as $property_name) {
                    if (empty($info['properties'][$property_name])) continue;

                    if ($new_field = $this->_createEntityPropertyFieldConfig($entity_type, $property_name, $info['properties'][$property_name])) {
                        $new_fields_by_entity_type[$entity_type][$new_field->name] = array($new_field, $info['properties'][$property_name]);
                    }
                }
            } else {
                foreach ($current_fields as $current_field) {
                    $current_field->markRemoved();
                }
            }
        }
        $this->getModel()->commit();

        $this->_application->Action('entity_update_entity_types_success', array($entityTypes));

        // Add new entity type fields to current active bundles
        if (!empty($new_fields_by_entity_type)) {
            $this->_application->Field_Types(false); // reload field types
            foreach ($new_fields_by_entity_type as $entity_type => $new_entity_type_fields) {
                foreach ($this->getModel('Bundle')->entitytypeName_is($entity_type)->fetch() as $bundle) {
                    if (!$this->_application->isComponentLoaded($bundle->component)) continue;

                    foreach ($new_entity_type_fields as $field_name => $field_data) {
                        $this->_createEntityPropertyField($bundle, $field_data[0], $field_data[1]);
                    }
                }
            }
            $this->getModel()->commit();
        }
    }

    private function _deleteEntityTypes(AbstractComponent $component, array $entityTypes)
    {
        foreach ($entityTypes as $entity_type) {
            if ($delete_bundles = $this->getModel('Bundle')->entitytypeName_is($entity_type)->fetch()->getArray()) {
                $this->_deleteEntityBundles($delete_bundles);
            }
            $this->entityFieldCacheClean($entity_type);
        }
        $this->getModel()->commit();
        $this->_application->Action('entity_delete_entity_types_success', array($entityTypes));
    }

    private function _createEntityPropertyFieldConfig($entityType, $propertyName, array $propertyInfo)
    {
        if (isset($propertyInfo['field_name'])) {
            // Not a real property but an alias to a field

            if (!isset($propertyInfo['settings'])) return;

            $field_config = $this->_createEntityFieldConfig(
                $propertyInfo['field_name'],
                [
                    'type' => $propertyInfo['type'],
                    'settings' => $propertyInfo['settings'],
                ],
                self::FIELD_REALM_ENTITY_TYPE_DEFAULT,
                $entityType
            );

            $this->_createFieldStorage([$field_config]);
        } else {
            $field_config = $this->getModel()
                ->create('FieldConfig')
                ->markNew()
                ->set('name', $entityType . '_' . $propertyName)
                ->set('type', $propertyInfo['type'])
                ->set('system', self::FIELD_REALM_ENTITY_TYPE_DEFAULT)
                ->set('entitytype_name', $entityType)
                ->set('settings', isset($propertyInfo['settings']) ? (array)$propertyInfo['settings'] : []);
        }
        $field_config->set('property', $propertyName);

        return $field_config;
    }

    public function createEntityBundles($componentName, array $bundles, $group = '')
    {
        $this->_createEntityBundles(
            $componentName,
            $this->_application->Filter('entity_bundles_info', $bundles, array($componentName, $group)),
            $group
        );
    }

    public function updateEntityBundles($componentName, array $bundles, $group = '')
    {
        $bundles = $this->_application->Filter('entity_bundles_info', $bundles, array($componentName, $group));
        $bundles_to_update = $bundles_info_to_update = $bundles_to_delete = [];
        foreach ($this->getModel('Bundle')->component_is($componentName)->group_is($group)->fetch() as $bundle) {
            if (isset($bundles[$bundle->type])) {
                $bundles_to_update[$bundle->name] = $bundle;
                $bundles_info_to_update[$bundle->type] = $bundles[$bundle->type];
                unset($bundles[$bundle->type]);
            } else {
                $bundles_to_delete[$bundle->name] = $bundle;
            }
        }
        // Delete old bundles
        if (!empty($bundles_to_delete)) {
            $this->_deleteEntityBundles($bundles_to_delete);
        }
        // Create new bundles
        if (!empty($bundles)) {
            $this->_createEntityBundles($componentName, $bundles, $group);
        }
        // Update current bundles
        if (!empty($bundles_to_update)) {
            $this->_updateEntityBundles($bundles_to_update, $bundles_info_to_update);
        }
    }

    public function deleteEntityBundles($componentName, $group = '', $deleteContent = false)
    {
        $bundles_to_delete = [];
        foreach ($this->getModel('Bundle')->component_is($componentName)->group_is($group)->fetch() as $bundle) {
            $bundles_to_delete[$bundle->name] = $bundle;
        }
        // Delete old bundles
        if (!empty($bundles_to_delete)) {
            $this->_deleteEntityBundles($bundles_to_delete, $deleteContent);
        }
    }

    public function onEntityIBundleTypesUninstalled(AbstractComponent $component)
    {
        if ($delete_bundles = $this->getModel('Bundle')->component_is($component->getName())->fetch()->getArray()) {
            $this->_deleteEntityBundles($delete_bundles);
        }
    }

    public function onEntityIBundleTypesUpgraded(AbstractComponent $component)
    {
        if (!$names = $component->entityGetBundleTypeNames()) {
            $this->onEntityIBundleTypesUninstalled($component);
        } else {
            // Should we update existing bundle types on component updates?
        }
    }

    protected function _createEntityBundles($componentName, array $bundles, $group, array &$newFields = null)
    {
        if (!isset($newFields)) $newFields = [];
        $created_bundles = [];
        foreach (array_keys($bundles) as $bundle_type) {
            foreach (array('fields', 'displays', 'views') as $key) {
                if (empty($bundles[$bundle_type][$key])) continue;

                try {
                    $bundles[$bundle_type][$key] = $this->_maybeGetSettingsFromFile($bundles[$bundle_type][$key]);
                } catch (Exception\RuntimeException $e) {
                    $this->_application->logError('Skipping ' . $key . ' for bundle ' . $bundle_type . ': ' . $e);
                    unset($bundles[$bundle_type][$key]);
                }
            }
            $bundles[$bundle_type] = $this->_application->Filter('entity_bundle_info', $bundles[$bundle_type], array($componentName, $group));
            if ($bundle = $this->_createEntityBundle($componentName, $bundle_type, $bundles[$bundle_type], $group, $newFields)) {
                $created_bundles[$bundle->name] = $bundle;
            }
        }

        if (empty($created_bundles)) return;

        // Clear bundles cache
        $this->_application->getPlatform()->deleteCache('entity_bundle_types');

        // Update fields
        if (!empty($newFields)) {
            $this->_createFieldStorage($newFields);
        }

        $this->_application->Action('entity_create_bundles_success', array($created_bundles, $bundles));

        $this->getModel()->commit();

        $this->_application->Action('entity_create_bundles_committed', array($created_bundles, $bundles));

        // Remove cache from Entity_Bundle helper
        foreach (array_keys($created_bundles) as $bundle_name) {
            Helper\BundleHelper::remove($bundle_name);
        }
    }

    protected function _updateEntityBundles(array $bundles, array $bundleInfo)
    {
        $newFields = $updatedFields = [];
        $current_bundles = [];
        foreach ($bundles as $bundle) {
            foreach (array('fields', 'displays', 'views') as $key) {
                if (empty($bundleInfo[$bundle->type][$key])) continue;

                try {
                    $bundleInfo[$bundle->type][$key] = $this->_maybeGetSettingsFromFile($bundleInfo[$bundle->type][$key]);
                } catch (Exception\RuntimeException $e) {
                    $this->_application->logError('Skipping ' . $key . ' for bundle ' . $bundle->type . ': ' . $e);
                    unset($bundleInfo[$bundle->type][$key]);
                }
            }
            $bundleInfo[$bundle->type]= $this->_application->Filter('entity_bundle_info', $bundleInfo[$bundle->type], array($bundle->component, $bundle->group));
            $current_bundles[$bundle->name] = $this->_updateEntityBundle($bundle, $bundleInfo[$bundle->type], $newFields, $updatedFields);
        }

        // Clear bundles cache
        $this->_application->getPlatform()->deleteCache('entity_bundle_types');

        // Update fields
        if (!empty($newFields)) {
            $this->_createFieldStorage($newFields);
        }
        if (!empty($updatedFields)) {
            $this->_updateFieldStorage($updatedFields);
        }

        $this->_application->Action('entity_update_bundles_success', array($current_bundles, $bundleInfo));

        $this->getModel()->commit();

        $this->_application->Action('entity_update_bundles_committed', array($current_bundles, $bundleInfo));

        // Remove cache from Entity_Bundle helper
        foreach ($current_bundles as $bundle) {
            Helper\BundleHelper::remove($bundle->name);

            // Also remove sort options cache
            $this->_application->Entity_Sorts_clearCache($bundle->name);
        }
    }

    protected function _deleteEntityBundles(array $bundles, $deleteContent = false)
    {
        $removed_field_names = [];
        foreach ($bundles as $bundle) {
            // Cache bundle info for 7 days just in case
            $this->_application->getPlatform()->setCache($bundle->info, 'entity_deleted_bundle_info_' . $bundle->name, 604800);

            $bundle->markRemoved();
            foreach ($bundle->Fields->with('FieldConfig') as $field) {
                // No need to call markRemoved for fields since they are removed when bundle is deleted

                if (!$field->isPropertyField()) {
                    // Save removed field name for later use
                    $removed_field_names[] = $field->getFieldName();
                }
            }
        }

        // Clear bundles cache
        $this->_application->getPlatform()->deleteCache('entity_bundle_types');

        if ($deleteContent) {
            // Delete field data of deleted bundles
            $this->_application->Entity_Storage()->purgeValuesByBundle(array_keys($bundles), $removed_field_names);
        }

        $this->_application->Action('entity_delete_bundles_success', array($bundles, $deleteContent));

        $this->getModel()->commit();

        $this->_application->Action('entity_delete_bundles_committed', array($bundles, $deleteContent));
    }

    private function _createEntityBundle($componentName, $bundleType, array $info, $group, array &$newFields)
    {
        if (empty($info['entity_type'])) return;

        if (isset($info['name'])) {
            $bundle_name = $info['name'];
        } elseif (isset($info['suffix'])) {
            if ($info['entity_type'] === 'post') {
                if (strlen($info['suffix']) > 7) return; // WP limits post type name to 20 chars
            } elseif ($info['entity_type'] === 'term') {
                if (strlen($info['suffix']) > 19) return; // WP limits taxonomy name to 32 chars
            } else {
                if (strlen($info['suffix']) > 27) return; // Bundle table limits bundle name to 40 chars
            }
            $bundle_name = $group . '_' . $info['suffix'];
        } else {
            return;
        }

        // Get the model
        $model = $this->getModel();

        // Make sure bundle with same name does not exist
        if ($model->Bundle->name_is($bundle_name)->count()) return;

        // Restore previous bundle info if any
        if ($_info = $this->_application->getPlatform()->getCache('entity_deleted_bundle_info_' . $bundle_name)) {
            $info = $_info + $info;
        }

        $bundle = $model->create('Bundle')
            ->markNew()
            ->set('entitytype_name', $info['entity_type'])
            ->set('name', $bundle_name)
            ->set('type', $bundleType)
            ->set('component', $componentName)
            ->set('group', $group)
            ->setInfo($info);

        // Create entity type property fields
        $this->_assignEntityPropertyFields($bundle, $info);

        // Add extra fields associated with the bundle if any
        if (!empty($info['fields'])
            && is_array($info['fields'])
        ) {
            foreach (array_keys($info['fields']) as $field_name) {
                $field_info = $info['fields'][$field_name];
                if (!isset($field_info['realm'])) {
                    $field_info['realm'] = strpos($field_name, 'field_') === 0 ? self::FIELD_REALM_ALL : self::FIELD_REALM_BUNDLE_DEFAULT;
                }
                if ($field = $this->_createEntityField($bundle, $field_name, $field_info)) {
                    if (!$field->FieldConfig->id) {
                        $newFields[$field->getFieldName()] = $field->FieldConfig;
                    }
                }
            }
        }

        $this->getModel()->commit();

        return $bundle->reload();
    }

    protected function _maybeGetSettingsFromFile($settings)
    {
        if (is_string($settings)) {
            if (!file_exists($settings)
               || (!$settings = include $settings)
            ) {
                throw new Exception\RuntimeException('Invalid file.');
            }
        }
        if (!is_array($settings)) {
            throw new Exception\RuntimeException('Invalid settings.');
        }
        return $settings;
    }

    private function _updateEntityBundle(Model\Bundle $bundle, array $info, array &$newFields, array &$updatedFields)
    {
        $bundle->setInfo($info, false);
        // Update entity type property fields
        $this->_assignEntityPropertyFields($bundle, $info);
        // Fetch bundle specific fields
        $current_bundle_fields = $this->getModel('Field')
            ->bundleName_is($bundle->name)
            ->fetch()
            ->with('FieldConfig');
        if (!empty($info['fields'])
            && is_array($info['fields'])
        ) {
            $_current_bundle_fields = [];
            foreach ($current_bundle_fields as $current_field) {
                if ($current_field->FieldConfig->system !== self::FIELD_REALM_BUNDLE_DEFAULT) continue;

                $field_name = $current_field->fieldconfig_name;
                if (!isset($info['fields'][$field_name])) {
                    $current_field->markRemoved();

                    continue;
                }
                $field_info = $info['fields'][$field_name];
                if (!isset($field_info['realm'])) {
                    $field_info['realm'] = strpos($field_name, 'field_') === 0 ? self::FIELD_REALM_ALL : self::FIELD_REALM_BUNDLE_DEFAULT;
                }
                // Create or update the field.
                $this->_createEntityField($bundle, $current_field->FieldConfig, $field_info, false, $updatedFields);
                $_current_bundle_fields[] = $field_name;
            }
            // Create or update other fields
            foreach (array_diff(array_keys($info['fields']), $_current_bundle_fields) as $field_name) {
                $field_info = $info['fields'][$field_name];
                if (!isset($field_info['realm'])) {
                    $field_info['realm'] = strpos($field_name, 'field_') === 0 ? self::FIELD_REALM_ALL : self::FIELD_REALM_BUNDLE_DEFAULT;
                }
                // Create if the field does not exist and it is a bundle specific field. If the field exists, update.
                if ($field = $this->_createEntityField($bundle, $field_name, $field_info, false, $updatedFields, $field_info['realm'] !== self::FIELD_REALM_ALL)) {
                    if (!$field->FieldConfig->id) {
                        $newFields[$field->FieldConfig->name] = $field->FieldConfig;
                    }
                }
            }
        } else {
            if (isset($info['fields'])
                && $info['fields'] !== false
            ) {
                foreach ($current_bundle_fields as $current_field) {
                    if ($current_field->FieldConfig->system !== self::FIELD_REALM_BUNDLE_DEFAULT) continue;

                    $current_field->markRemoved();
                }
            }
        }

        $this->getModel()->commit();

        return $bundle->reload();
    }

    public function createEntityField(Model\Bundle $bundle, $fieldName, array $fieldInfo, $overwrite = false)
    {
        $updatedFields = [];
        if (!$field = $this->_createEntityField($bundle, $fieldName, $fieldInfo, $overwrite, $updatedFields)) return;

        $is_new = $field->FieldConfig->id ? false : true;
        $field->FieldConfig->commit();
        $field->commit();

        if ($is_new) {
            $this->_createFieldStorage(array($field->FieldConfig));
        } else {
            // Update field storage if schema has changed
            if (!empty($updatedFields)) {
                $this->_updateFieldStorage($updatedFields);
            }
        }

        return $field;
    }

    public function createEntityPropertyField(Model\Bundle $bundle, Model\FieldConfig $fieldConfig, array $fieldInfo, $commit = true)
    {
        if (!$field = $this->_createEntityPropertyField($bundle, $fieldConfig, $fieldInfo, true)) return;

        if ($commit) $this->getModel()->commit();

        return $field;
    }

    private function _createEntityPropertyField(Model\Bundle $bundle, Model\FieldConfig $fieldConfig, array $fieldInfo, $overwrite = false)
    {
        if (!$field_type_info = $this->_isValidFieldInfo($bundle, $fieldInfo)) return;

        // Fetch field
        $field = null;
        foreach ($fieldConfig->Fields as $_field) {
            if ($_field->bundle_name === $bundle->name) {
                $field = $_field;
                break;
            }
        }
        if (!$field) {
            // Create field
            $field = $bundle->createField()->markNew()->set('FieldConfig', $fieldConfig);
        }

        $fieldInfo['max_num_items'] = 1;
        return $this->_updateEntityField($field, $fieldInfo, $field_type_info, $overwrite);
    }

    private function _isValidFieldInfo(Model\Bundle $bundle, array $fieldInfo)
    {
        if (!isset($fieldInfo['type'])) return false;

        $field_types = $this->_application->Field_Types();
        if (!$field_type_info = @$field_types[$fieldInfo['type']]) return false;

        if (isset($field_type_info['entity_types'])
            && !in_array($bundle->entitytype_name, $field_type_info['entity_types'])
        ) {
            // the field type does not support the entity type of the bundle
            return false;
        }

        return $field_type_info;
    }

    private function _updateEntityField(Model\Field $field, array $fieldInfo, array $fieldTypeInfo, $overwrite)
    {
        $admin_only = (true === $this->_application->Field_Type($fieldInfo['type'])->fieldTypeInfo('admin_only'));

        // Set custom field data
        if (!empty($fieldInfo['data'])) {
            foreach (array_keys($fieldInfo['data']) as $data_k) {
                if (strpos($data_k, '_') === 0) { // custom field data name must start with underscore
                    $field->setFieldData($data_k, $fieldInfo['data'][$data_k]);
                } else {
                    if (!isset($fieldInfo[$data_k])) {
                        $fieldInfo[$data_k] = $fieldInfo['data'][$data_k];
                    }
                }
            }
        }
        if ($overwrite
            || !$field->id
            || $admin_only
        ) {
            $widget = isset($fieldInfo['widget']) ? $fieldInfo['widget'] : $fieldTypeInfo['default_widget'];
            if (!isset($fieldInfo['max_num_items'])) {
                $fieldInfo['max_num_items'] = $widget && $this->_application->Field_Widgets_impl($widget)->fieldWidgetInfo('accept_multiple') ? 0 : 1;
            }
            if (!isset($fieldInfo['widget_settings'])) {
                $fieldInfo['widget_settings'] = $widget && ($defaults = $this->_application->Field_Widgets_impl($widget)->fieldWidgetInfo('default_settings')) ? $defaults : [];
            }
            $hide_label = false;
            if (isset($fieldInfo['label'])) {
                if (is_array($fieldInfo['label'])) {
                    $label = $fieldInfo['label'][0];
                    $hide_label = !empty($fieldInfo['label'][1]);
                } else {
                    $label = $fieldInfo['label'];
                }
            } else {
                $label = $fieldTypeInfo['label'];
            }
            $field->setFieldLabel($label, $hide_label)
                ->setFieldDescription($description = isset($fieldInfo['description']) ? $fieldInfo['description'] : '')
                ->setFieldDefaultValue(@$fieldInfo['default_value'])
                ->setFieldRequired(!empty($fieldInfo['required']))
                ->setFieldDisabled(!empty($fieldInfo['disabled']))
                ->setFieldWeight((int)@$fieldInfo['weight'])
                ->setFieldMaxNumItems($fieldInfo['max_num_items'])
                ->setFieldWidget($widget)
                ->setFieldWidgetSettings($fieldInfo['widget_settings'])
                ->setFieldConditions(empty($fieldInfo['conditions']) ? [] : $fieldInfo['conditions']);
            // Regsitrer label/description for translation
            $this->_application->getPlatform()->registerString($label, $field->bundle_name . '_' . $field->getFieldName() . '_label', 'entity_field')
                ->registerString($description, $field->bundle_name . '_' . $field->getFieldName() . '_description', 'entity_field');
        } else {
            // Set field widget if there isn't any set
            if (!$field->getFieldWidget()
                && ($widget = isset($fieldInfo['widget']) ? $fieldInfo['widget'] : $fieldTypeInfo['default_widget'])
            ) {
                if (!isset($fieldInfo['widget_settings'])) {
                    $widget_settings = ($defaults = $this->_application->Field_Widgets_impl($widget)->fieldWidgetInfo('default_settings')) ? $defaults : [];
                } else {
                    $widget_settings = $fieldInfo['widget_settings'];
                }
                $field->setFieldWidget($widget)->setFieldWidgetSettings($widget_settings);
                if (!$this->_application->Field_Widgets_impl($widget)->fieldWidgetInfo('accept_multiple')) {
                    $field->setFieldMaxNumItems(1);
                }
            }
        }

        return $field;
    }

    private function _createEntityField(Model\Bundle $bundle, $fieldName, array $fieldInfo, $overwrite = false, array &$updatedFields = [], $create =true)
    {
        if (!$field_type_info = $this->_isValidFieldInfo($bundle, $fieldInfo)) return;

        if (!$field_config = $this->_createEntityFieldConfig($fieldName, $fieldInfo, null, $bundle, $overwrite, $create, $updatedFields)) return;

        if ($field_config->id) {
            foreach ($field_config->Fields as $_field) {
                if ($_field->bundle_name === $bundle->name) {
                    $field = $_field;
                    break;
                }
            }
        }
        if (!isset($field)) {
            if (!$create) return;

            $field = $bundle->createField()->markNew();
        }
        $field->FieldConfig = $field_config;

        return $this->_updateEntityField($field, $fieldInfo, $field_type_info, $overwrite);
    }

    protected function _createEntityFieldConfig($fieldName, array $fieldInfo, $realm = null, $bundleOrEntityType = null, $overwrite = false, $create = true, array &$updatedFields = null)
    {
        $field_type_impl = $this->_application->Field_Type($fieldInfo['type']);
        $field_settings = isset($fieldInfo['settings']) ? $fieldInfo['settings'] : [];
        $field_settings += (array)$field_type_impl->fieldTypeInfo('default_settings');
        if (!is_object($fieldName)) {
            $fieldName = strtolower(trim($fieldName));
            if (strlen($fieldName) === 0) return;

            if (!$field_config = $this->getModel('FieldConfig')->name_is($fieldName)->fetchOne()) {
                if (!$create) return;

                if (!isset($realm)) $realm = isset($fieldInfo['realm']) ? $fieldInfo['realm'] : self::FIELD_REALM_ALL;
                $field_config = $this->getModel()
                    ->create('FieldConfig')
                    ->markNew()
                    ->set('name', $fieldName)
                    ->set('system', $realm)
                    ->set('settings', $field_settings);
                if ($realm !== self::FIELD_REALM_ALL
                    && isset($bundleOrEntityType)
                ) {
                    if ($bundleOrEntityType instanceof Bundle) {
                        $field_config->set('bundle_type', $bundleOrEntityType->type)->set('entitytype_name', $bundleOrEntityType->entitytype_name);
                    } else {
                        $field_config->set('entitytype_name', $bundleOrEntityType);
                    }
                }
            } else {
                $is_update = true;
            }
        } else {
            $is_update = true;
            $field_config = $fieldName;
        }
        $field_schema = (array)$field_type_impl->fieldTypeSchema();
        if (!empty($is_update)) {
            if ($overwrite) {
                $field_config->settings = $field_settings;
            } else {
                $field_config->settings += $field_settings;
            }
            //if ($field_config->schema !== $field_schema) {
            // Notify that field schema has changed
            $field_config->oldSchema = $field_config->schema;
            if (isset($updatedFields)) {
                $updatedFields[$field_config->name] = $field_config;
            }
            //}
        }
        $field_config->schema = $field_schema;
        $field_config->type = $fieldInfo['type'];
        $field_config->schema_type = ($schema_type = $field_type_impl->fieldTypeInfo('schema_type')) ? $schema_type : $field_config->type;

        return $field_config;
    }

    private function _assignEntityPropertyFields(Model\Bundle $bundle, array $info)
    {
        $property_fields = $this->getModel('FieldConfig')->entitytypeName_is($bundle->entitytype_name)->bundleType_is('')->fetch()->with('Fields');
        if (count($property_fields)) {
            $entity_type_info = $this->_application->Entity_Types_impl($bundle->entitytype_name, false)->entityTypeInfo();
            foreach ($property_fields as $property_field) {
                if (!isset($entity_type_info['properties'][$property_field->property])) {
                    // Delete stale data
                    $property_field->markRemoved()->commit();
                    continue;
                }
                $property_field_settings = $entity_type_info['properties'][$property_field->property];
                // Each bundle can set custom field settings but not overwrite the default
                if (!empty($info['properties'][$property_field->property])) {
                    $property_field_settings += $info['properties'][$property_field->property];
                    // Except for label/weight which can be overwritten
                    foreach (array('label', 'weight') as $property_key) {
                        if (isset($info['properties'][$property_field->property][$property_key])) {
                            $property_field_settings[$property_key] = $info['properties'][$property_field->property][$property_key];
                        }
                    }
                }
                $this->_createEntityPropertyField($bundle, $property_field, $property_field_settings);
            }
        }
    }

    /**
     * Delete entities
     * @param type $entityType
     * @param array $entities An array of Type\IEntity objects indexed by entity IDs
     */
    public function deleteEntities($entityType, array $entities, array $extraArgs = [])
    {
        if (empty($entities)) return;

        // Load field values from storage so that all field values can be accessed by other components upon delete event
        $this->_application->Entity_Field_load($entityType, $entities, true, false);

        if (empty($extraArgs['fields_only'])) {
            $this->_application->Entity_Types_impl($entityType)->entityTypeDeleteEntities($entities);
        }

        // Delete fields
        $entities_by_bundle = $field_values = $bundles_arr = [];
        foreach ($entities as $entity) {
            $entities_by_bundle[$entity->getBundleName()][$entity->getId()] = $entity;
        }
        $bundles = $this->_application->Entity_Bundles_collection(array_keys($entities_by_bundle))->with('Fields', 'FieldConfig');
        foreach ($bundles as $bundle) {
            foreach ($bundle->Fields as $field) {
                $field_values[$bundle->name][$field->getFieldName()] = $field->getFieldName();
            }
            $bundles_arr[$bundle->name] = $bundle;
        }
        foreach ($field_values as $bundle_name => $_field_values) {
            $this->_application->Entity_Storage()->purgeValues($entityType, array_keys($entities_by_bundle[$bundle_name]), $_field_values);
            // Clear cached entity fields
            $this->_application->Entity_Field_removeCache($bundle_name, array_keys($entities_by_bundle[$bundle_name]));
        }



        // Notify entities have been deleted
        foreach ($entities as $entity) {
            $bundle = $bundles_arr[$entity->getBundleName()];
            $this->_invokeEntityEvents($entityType, $bundle->type, array($bundle, $entity, array_keys($entities), $extraArgs), 'delete', 'success');
        }

        foreach ($entities_by_bundle as $bundle_name => $entities) {
            $bundle = $bundles_arr[$bundle_name];
            $this->_invokeEntityEvents($entityType, $bundle->type, array($bundle, $entities, $extraArgs), 'bulk_delete', 'success');
        }
    }

    private function _invokeEntityEvents($entityType, $bundleType, array $params, $prefix, $suffix = '')
    {
        $prefix = 'entity_' . $prefix;
        $suffix = $suffix !== '' ? 'entity_' . $suffix : 'entity';
        $this->_application->Action($prefix . '_' . $suffix, $params);
        $this->_application->Action($prefix . '_' . $entityType . '_' . $suffix, $params);
        $this->_application->Action($prefix . '_' . $entityType . '_' . $bundleType . '_' . $suffix, $params);
    }

    protected function _createFieldStorage(array $fieldConfigs)
    {
        $fields = [];
        foreach ($fieldConfigs as $field_config) {
            $fields[$field_config->name] = $field_config;
        }
        $this->_application->Entity_Storage()->create($fields);
    }

    public function deleteFieldStorage(array $fieldConfigs, $force = false)
    {
        $fields = [];
        foreach ($fieldConfigs as $field_config) {
            $fields[$field_config->name] = $field_config;
        }
        $this->_application->Entity_Storage()->delete($fields, $force);
    }

    protected function _updateFieldStorage(array $fieldConfigs)
    {
        $fields = [];
        foreach ($fieldConfigs as $field_config) {
            $fields[$field_config->name] = $field_config;
        }
        $this->_application->Entity_Storage()->update($fields);
    }

    public function onFieldTypeDeleted($fieldType)
    {
        $field_configs = [];
        foreach ($this->getModel('FieldConfig')->type_is($fieldType->name)->fetch()->with('Fields') as $field_config) {
            $field_config->markRemoved();
            $field_configs[$field_config->name] = $field_config;
        }
        $this->getModel()->commit();
        $this->deleteFieldStorage($field_configs);
    }

    public function uninstall($removeData = false)
    {
        if ($removeData) {
            // Remove tables created by custom fields

            $fields = [];
            foreach ($this->getModel('FieldConfig')->fetch() as $field) {
                if ($field->property) continue;

                $fields[] = $field;
            }

            // Remove field tables
            if (!empty($fields)) {
                $this->deleteFieldStorage($fields, true);
            }
        }

        parent::uninstall($removeData);
    }

    protected function _hasSupportedRenderer(Model\Bundle $bundle, Field\IField $field, array $renderers)
    {
        foreach (array_keys($renderers) as $renderer_type) {
            if (($renderer = $this->_application->Field_Renderers_impl($renderer_type, true))
                && $renderer->fieldRendererSupports($bundle, $field)
            ) return true;
        }
        return false;
    }

    public function displayGetElementNames(Model\Bundle $bundle)
    {
        $ret = array('entity_fieldlist', 'entity_fieldtemplate');
        $field_types = $this->_application->Field_Types();

        // Elements for "form" display type
        foreach (array_keys($field_types) as $field_type_name) {
            $field_type = $field_types[$field_type_name];
            if (isset($field_type['entity_types'])
                && !in_array($bundle->entitytype_name, $field_type['entity_types'])
            ) {
                // the field type does not support the entity type of the current bundle
                unset($field_types[$field_type_name]);
                continue;
            }
            if (isset($field_type['bundles'])
                && !in_array($bundle->type, $field_type['bundles'])
            ) {
                // the field type does not support the current bundle type
                continue;
            }

            if (!empty($field_type['widgets']) && !$field_type['admin_only']) {
                $ret[] = 'entity_form_' . $field_type_name;
            }
        }

        foreach ($this->_application->Entity_Field($bundle->name) as $field) {
            if (!$field_type = $this->_isFieldRenderable($bundle->type, $field, $field_types)) continue;

            if ($this->_hasSupportedRenderer($bundle, $field, $field_type['renderers'])) {
                $ret[] = 'entity_field_' . $field->getFieldName();
            }
        }
        if (!empty($bundle->info['parent'])) {
            foreach ($this->_application->Entity_Field($bundle->info['parent']) as $field) {
                if (!$this->_isFieldRenderable($bundle->type, $field, $field_types)) continue;

                $ret[] = 'entity_parent_field_' . $field->getFieldName();
            }
        } else {
            if (!empty($bundle->info['is_taxonomy'])) {
                if (!empty($bundle->info['is_hierarchical'])) {
                    $ret[] = 'entity_child_terms';
                }
            }
        }
        if (!empty($bundle->info['taxonomies'])) {
            foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy_bundle_name) {
                if (!$taxonomy_bundle_fields = $this->_application->Entity_Field($taxonomy_bundle_name)) continue;
                
                foreach ($taxonomy_bundle_fields as $field) {
                    if (!$this->_isFieldRenderable($taxonomy_bundle_type, $field, $field_types)) continue;

                    $ret[] = 'entity_tax_field_' . $field->getFieldName() . '__' . $taxonomy_bundle_type;
                }
            }
        }

        return $ret;
    }

    public function displayGetElement($name)
    {
        switch ($name) {
            case 'entity_fieldlist':
                return new DisplayElement\FieldListDisplayElement($this->_application, $name);
            case 'entity_fieldtemplate':
                return new DisplayElement\FieldTemplateDisplayElement($this->_application, $name);
            case 'entity_child_terms':
                return new DisplayElement\ChildTermsDisplayElement($this->_application, $name);
            default:
                if (strpos($name, 'entity_field_') === 0) {
                    return new DisplayElement\FieldDisplayElement($this->_application, $name);
                } elseif (strpos($name, 'entity_parent_field_') === 0) {
                    return new DisplayElement\ParentFieldDisplayElement($this->_application, $name);
                } elseif (strpos($name, 'entity_form_') === 0) {
                    return new DisplayElement\FormDisplayElement($this->_application, $name);
                } elseif (strpos($name, 'entity_tax_field_') === 0) {
                    $name = explode('__', $name);
                    return new DisplayElement\TaxonomyFieldDisplayElement($this->_application, $name[0], $name[1]);
                }
        }
    }

    protected function _isFieldRenderable($bundleType, Field\IField $field, array $fieldTypes)
    {
        if (!isset($fieldTypes[$field->getFieldType()])) return;

        $field_type = $fieldTypes[$field->getFieldType()];
        if (empty($field_type['renderers'])) return;

        if (isset($field_type['bundles'])
            && !in_array($bundleType, $field_type['bundles'])
        ) {
            // the field type does not support the parent bundle type
            return;
        }
        return $field_type;
    }

    public function systemAdminRoutes()
    {
        $routes = [
            '/_drts/entity/field' => [
                'controller' => 'FieldInfo',
            ],
        ];
        foreach (array_keys($this->_application->Entity_BundleTypes()) as $bundle_type) {
            if ((!$admin_path = $this->_application->Entity_BundleTypeInfo($bundle_type, 'admin_path'))
                || isset($routes[$admin_path]) // path added already
            ) continue;

            $routes += array(
                $admin_path => array(
                    'controller' => 'Edit',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => 'edit',
                    'type' => Application::ROUTE_TAB,
                ),
                $admin_path . '/fields' => array(
                    'controller' => 'Fields',
                    'title_callback' => true,
                    'callback_path' => 'fields',
                    'type' => Application::ROUTE_TAB,
                    'weight' => 1,
                ),
                $admin_path . '/displays' => array(
                    'controller' => 'Displays',
                    'title_callback' => true,
                    'callback_path' => 'displays',
                    'type' => Application::ROUTE_TAB,
                    'weight' => 10,
                ),
            );
        }

        return $routes;
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'edit':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$bundle_name = $context->getRequest()->asStr('bundle_name'))
                        || (!$bundle = $this->_application->Entity_Bundle($bundle_name))
                    ) return false;

                    $context->bundle = $bundle;
                }
                return true;
        }
    }

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'edit':
                if ($titleType === Application::ROUTE_TITLE_INFO) {
                    $context->clearTabs(true)->clearMenus();
                    return $context->bundle->getLabel('singular');
                } else {
                    return __('Edit', 'directories');
                }
            case 'fields':
                return __('Manage Fields', 'directories');
            case 'displays':
                return __('Manage Displays', 'directories');
        }
    }

    public function systemMainRoutes($lang = null)
    {
        $routes = array(
            '/_drts/entity/:bundle_type' => array(
                'type' => Application::ROUTE_CALLBACK,
                'access_callback' => true,
            ),
            '/_drts/entity/:bundle_type/query' => array(
                'controller' => 'QueryEntities',
                'type' => Application::ROUTE_CALLBACK,
            ),
            '/_drts/entity/:bundle_type/query/:bundle' => array(
                'controller' => 'QueryEntities',
                'type' => Application::ROUTE_CALLBACK,
            ),
            '/_drts/entity/:bundle_type/taxonomy_terms' => array(
                'controller' => 'ListTaxonomyTerms',
                'type' => Application::ROUTE_CALLBACK,
            ),
            '/_drts/entity/:bundle_type/taxonomy_terms/:bundle' => array(
                'controller' => 'ListTaxonomyTerms',
                'type' => Application::ROUTE_CALLBACK,
            ),
        );

        return $routes;
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case '/_drts/entity/:bundle_type':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$bundle_type = $context->getRequest()->asStr('bundle_type'))
                        || !$this->_application->Entity_BundleTypes($bundle_type)
                        || false === $this->_application->Entity_BundleTypeInfo($bundle_type, 'public')
                    ) return false;

                    $context->bundle_type = $bundle_type;
                }
                return true;
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route){}

    public function fieldGetTypeNames()
    {
        return ['entity_id', 'entity_bundle_name', 'entity_bundle_type', 'entity_slug',
            'entity_parent', 'entity_child_count', 'entity_reference', 'entity_activity',
            'entity_title', 'entity_published', 'entity_modified', 'entity_author',
            'entity_terms', 'entity_term_content_count', 'entity_term_parent',
            'entity_featured', 'entity_expires'
        ];
    }

    public function fieldGetType($name)
    {
        switch ($name) {
            case 'entity_id':
                return new FieldType\IdFieldType($this->_application, $name);
            case 'entity_slug':
                return new FieldType\SlugFieldType($this->_application, $name);
            case 'entity_title':
                return new FieldType\TitleFieldType($this->_application, $name);
            case 'entity_published':
                return new FieldType\PublishedFieldType($this->_application, $name);
            case 'entity_modified':
                return new FieldType\ModifiedFieldType($this->_application, $name);
            case 'entity_author':
                return new FieldType\AuthorFieldType($this->_application, $name);
            case 'entity_parent':
                return new FieldType\ParentFieldType($this->_application, $name);
            case 'entity_child_count':
                return new FieldType\ChildCountFieldType($this->_application, $name);
            case 'entity_reference':
                return new FieldType\ReferenceFieldType($this->_application, $name);
            case 'entity_activity':
                return new FieldType\ActivityFieldType($this->_application, $name);
            case 'entity_terms':
                return new FieldType\TermsFieldType($this->_application, $name);
            case 'entity_term_content_count':
                return new FieldType\TermContentCountFieldType($this->_application, $name);
            case 'entity_term_parent':
                return new FieldType\TermParentFieldType($this->_application, $name);
            case 'entity_featured':
                return new FieldType\FeaturedFieldType($this->_application, $name);
            case 'entity_expires':
                return new FieldType\ExpiresFieldType($this->_application, $name);
            default:
                return new FieldType\FieldType($this->_application, $name);
        }
    }

    public function fieldGetWidgetNames()
    {
        return ['entity_parent', 'entity_title', 'entity_term_select', 'entity_term_list',
            'entity_term_tagging', 'entity_term_parent', 'entity_author', 'entity_reference',
            'entity_reference_hidden', 'entity_featured', 'entity_expires', 'entity_term_multi_select',
            'entity_reference_checkboxes'
        ];
    }

    public function fieldGetWidget($name)
    {
        switch ($name) {
            case 'entity_parent':
                return new FieldWidget\ParentFieldWidget($this->_application, $name);
            case 'entity_title':
                return new FieldWidget\TitleFieldWidget($this->_application, $name);
            case 'entity_term_select':
                return new FieldWidget\TermSelectFieldWidget($this->_application, $name);
            case 'entity_term_multi_select':
                return new FieldWidget\TermMultiSelectFieldWidget($this->_application, $name);
            case 'entity_term_list':
                return new FieldWidget\TermListFieldWidget($this->_application, $name);
            case 'entity_term_tagging':
                return new FieldWidget\TermTaggingFieldWidget($this->_application, $name);
            case 'entity_term_parent':
                return new FieldWidget\TermParentFieldWidget($this->_application, $name);
            case 'entity_author':
                return new FieldWidget\AuthorFieldWidget($this->_application, $name);
            case 'entity_reference':
                return new FieldWidget\ReferenceFieldWidget($this->_application, $name);
            case 'entity_reference_hidden':
                return new FieldWidget\ReferenceHiddenFieldWidget($this->_application, $name);
            case 'entity_reference_checkboxes':
                return new FieldWidget\ReferenceCheckboxesFieldWidget($this->_application, $name);
            case 'entity_featured':
                return new FieldWidget\FeaturedFieldWidget($this->_application, $name);
            case 'entity_expires':
                return new FieldWidget\ExpiresFieldWidget($this->_application, $name);
        }
    }

    public function fieldGetRendererNames()
    {
        return ['entity_id', 'entity_title', 'entity_published', 'entity_modified', 'entity_author', 'entity_terms',
            'entity_term_content_count', 'entity_reference', 'entity_reference_link', 'entity_expires', 'entity_featured_end_date',
        ];
    }

    public function fieldGetRenderer($name)
    {
        switch ($name) {
            case 'entity_id':
                return new FieldRenderer\IdFieldRenderer($this->_application, $name);
            case 'entity_title':
                return new FieldRenderer\TitleFieldRenderer($this->_application, $name);
            case 'entity_published':
                return new FieldRenderer\PublishedFieldRenderer($this->_application, $name);
            case 'entity_modified':
                return new FieldRenderer\ModifiedFieldRenderer($this->_application, $name);
            case 'entity_author':
                return new FieldRenderer\AuthorFieldRenderer($this->_application, $name);
            case 'entity_terms':
                return new FieldRenderer\TermsFieldRenderer($this->_application, $name);
            case 'entity_term_content_count':
                return new FieldRenderer\TermContentCountFieldRenderer($this->_application, $name);
            case 'entity_reference':
                return new FieldRenderer\ReferenceFieldRenderer($this->_application, $name);
            case 'entity_reference_link':
                return new FieldRenderer\ReferenceLinkFieldRenderer($this->_application, $name);
            case 'entity_expires':
                return new FieldRenderer\ExpiresFieldRenderer($this->_application, $name);
            case 'entity_featured_end_date':
                return new FieldRenderer\FeaturedEndDateFieldRenderer($this->_application, $name);
        }
    }

    public function fieldGetFilterNames()
    {
        return ['entity_author'];
    }

    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'entity_author':
                return new FieldFilter\AuthorFieldFilter($this->_application, $name);
        }
    }

    protected function _onEntityCreateBundlesSuccess($bundles, $update = false)
    {
        foreach ($bundles as $bundle) {
            if (empty($bundle->info['is_taxonomy'])) {
                // Add activity field?
                if ($this->_application->Entity_BundleTypeInfo($bundle, 'entity_track_activity')) {
                    $this->createEntityField(
                        $bundle,
                        'entity_activity',
                        array(
                            'type' => 'entity_activity',
                            'settings' => [],
                            'weight' => 99,
                            'max_num_items' => 1,
                        )
                    );
                }
                // Add taxonomy terms field if has taxonomies
                if (!empty($bundle->info['taxonomies'])) {
                    $term_bundles = [];
                    foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $settings) {
                        if (!$term_bundle = $this->_application->Entity_Bundle($taxonomy_bundle_type, $bundle->component, $bundle->group)) continue;

                        $taxonomy_max_num_items = isset($settings['max_num_items']) ? intval($settings['max_num_items']) : 0;
                        $data = array('_bundle_name' => $term_bundle->name);
                        if (false === $this->_application->Entity_BundleTypeInfo($term_bundle, 'taxonomy_assignable')) {
                            $data['_no_ui'] = true;
                        }
                        if (!empty($settings['data'])) {
                            $data += (array)$settings['data'];
                        }
                        $this->createEntityField(
                            $bundle,
                            $term_bundle->type,
                            array(
                                'type' => 'entity_terms',
                                'label' => isset($settings['label']) ? $settings['label'] : ($taxonomy_max_num_items === 1 ? $term_bundle->getLabel('singular') : $term_bundle->getLabel()),
                                'description' => isset($settings['description']) ? $settings['description'] : '',
                                'settings' => [],
                                'required' => !empty($settings['required']),
                                'max_num_items' => $taxonomy_max_num_items,
                                'data' => $data,
                                'widget' => $widget = isset($settings['widget']) ?
                                    $settings['widget'] :
                                    (empty($term_bundle->info['is_hierarchical']) ? 'entity_term_tagging' : 'entity_term_select'),
                                'widget_settings' => isset($settings['widget_settings']) ? $settings['widget_settings'] : null,
                                'weight' => isset($settings['weight']) ? $settings['weight'] : 10,
                            )
                        );
                        $term_bundles[$taxonomy_bundle_type] = $term_bundle->name;
                    }
                    $bundle->setInfo(array('taxonomies' => $term_bundles));
                }
                // Add featured field?
                if ($this->_application->Entity_BundleTypeInfo($bundle, 'featurable')) {
                    $this->createEntityField(
                        $bundle,
                        'entity_featured',
                        array(
                            'type' => 'entity_featured',
                            'max_num_items' => 1,
                        )
                    );
                }
                // Check if the bundle is a child content type
                if (!empty($bundle->info['parent'])
                    && ($parent_bundle = $this->_application->Entity_Bundle($bundle->info['parent'], $bundle->component, $bundle->group))
                ) {
                    $bundle->setInfo(array('parent' => $parent_bundle->name)); // change from bundle type to name
                    // Create field for "parent" property if the property requires external field
                    $properties = $this->_application->Entity_Types_impl($bundle->entitytype_name, false)->entityTypeInfo('properties');
                    if (!empty($properties['parent']['field_name'])) {
                        $this->createEntityField(
                            $bundle,
                            'entity_parent',
                            array(
                                'type' => 'entity_parent',
                                'settings' => [],
                                'label' => $parent_bundle->getLabel('singular'),
                                'weight' => 99,
                                'required' => true,
                                'max_num_items' => 1,
                            )
                        );
                    }
                    if (!empty($bundle->info['public'])) {
                        $this->createEntityField(
                            $parent_bundle,
                            'entity_child_count',
                            array(
                                'type' => 'entity_child_count',
                                'settings' => [],
                                'weight' => 99,
                                'max_num_items' => 0,
                            )
                        );
                    }
                }
                // Add expiration date field?
                if ($this->_application->Entity_BundleTypeInfo($bundle, 'expirable')) {
                    $this->createEntityField(
                        $bundle,
                        'entity_expires',
                        [
                            'type' => 'entity_expires',
                            'max_num_items' => 1,
                        ]
                    );
                }
            } else {
                // Add term content count field
                $this->createEntityField(
                    $bundle,
                    'entity_term_content_count',
                    array(
                        'type' => 'entity_term_content_count',
                        'settings' => [],
                        'weight' => 99,
                    )
                );
            }
        }
    }

    public function onEntityCreateBundlesSuccess($bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles);
    }

    public function onEntityUpdateBundlesSuccess($bundles)
    {
        $this->_onEntityCreateBundlesSuccess($bundles, true);
    }

    protected function _onEntityCreateBundlesCommitted(array $bundles, array $bundleInfo)
    {
        // Create displays and views if any
        foreach ($bundles as $bundle) {
            if (!empty($bundleInfo[$bundle->type]['displays'])) {
                 foreach (array_keys($bundleInfo[$bundle->type]['displays']) as $display_type) {
                    foreach (array_keys($bundleInfo[$bundle->type]['displays'][$display_type]) as $display_name) {
                        $display = $bundleInfo[$bundle->type]['displays'][$display_type][$display_name];
                        $this->_application->Display_Create($bundle, $display_type, $display_name, $display);
                    }
                }
            }
            if (!empty($bundleInfo[$bundle->type]['views'])) {
                foreach (array_keys($bundleInfo[$bundle->type]['views']) as $view_name) {
                    $view = $bundleInfo[$bundle->type]['views'][$view_name];
                    $this->_application->View_AdminView_add($bundle, $view_name, $view['mode'], $view['label'], $view['settings'], !empty($view['default']));
                }
            }
        }
    }

    public function onEntityCreateBundlesCommitted(array $bundles, array $bundleInfo)
    {
        $this->_onEntityCreateBundlesCommitted($bundles, $bundleInfo);
    }

    public function onEntityUpdateBundlesCommitted(array $bundles, array $bundleInfo)
    {
        $this->_onEntityCreateBundlesCommitted($bundles, $bundleInfo);
    }

    public function onEntityCreateEntity($bundle, &$values)
    {
        if (!isset($values['entity_activity'])
            && $this->_application->Entity_BundleTypeInfo($bundle, 'entity_track_activity')
        ) {
            $values['entity_activity'] = [
                [
                    'active_at' => !empty($values['published']) ? $values['published'] : time(),
                    'edited_at' => 0,
                    'last_action' => 'new',
                ],
            ];
        }

        // Maybe populate title with value of another field
        $this->_maybePopulateTitle($bundle, $values);

        // Maybe add parent term entries to entity_terms field table
        $this->_maybeAddParentTermValues($bundle, $values);

        // Set expiration date?
        if (!array_key_exists('entity_expires', $values)
            && !empty($bundle->info['entity_expire'])
            && !empty($bundle->info['entity_expire_days'])
        ) {
            $values['entity_expires'] = [
                ['value' => time() + 86400 * $bundle->info['entity_expire_days']],
            ];
        }
    }

    public function onEntityUpdateEntity($bundle, $entity, &$values)
    {
        // Maybe populate title with value of another field
        $this->_maybePopulateTitle($bundle, $values, $entity);

        if (!isset($values['entity_activity'])
            && $this->_application->Entity_BundleTypeInfo($bundle, 'entity_track_activity')
        ) {
            if (isset($values['content']) || isset($values['title'])) {
                $values['entity_activity'] = array(
                    array(
                        'active_at' => time(),
                        'edited_at' => time(),
                        'last_action' => 'edit',
                    ),
                );
            }
        }

        // Maybe add parent term entries to entity_terms field table
        $this->_maybeAddParentTermValues($bundle, $values);

        // Remove expiration date if payment plan assigned
        if (!empty($bundle->info['entity_expire'])
            && !empty($values['payment_plan'][0])
        ) {
            $values['entity_expires'] = false;
        }
    }

    protected function _maybePopulateTitle($bundle, &$values, $entity = null)
    {
        // Populate title if title is disabled
        if (empty($bundle->info['no_title'])) return;

        if (empty($bundle->info['title_field'])
            || (!$title_field = $this->_application->Entity_Field($bundle, $bundle->info['title_field']))
        ) {
            $content = isset($values['content']) ? $values['content'] : (isset($entity) ? $entity->getContent() : null);
            $values['title'] = strlen($content) ? $this->_application->Summarize($content, 150) : '';
            return;
        }

        $title_field_key = ($property = $title_field->isPropertyField()) ? $property : $bundle->info['title_field'];
        if (isset($values[$title_field_key])
            && ($title_field_type = $this->_application->Field_Type($title_field->getFieldType()))
        ) {
            $title_field_values = $values[$title_field_key];
            if (!is_array($title_field_values)
                || !array_key_exists(0, $title_field_values)
            ) {
                $title_field_values = [$title_field_values];
            }
            if (null !== $title = $title_field_type->fieldTitle($title_field, $title_field_values)) {
                $values['title'] = $title;
            }
        }
    }

    public function onEntityCreateEntitySuccess($bundle, $entity, $values, $extraArgs)
    {
        if ($entity->isPublished()) {
            $this->_application->Action('entity_entity_published', [$entity, null]);
        }

        if (!empty($extraArgs['entity_meta'])) {
            foreach (array_keys($extraArgs['entity_meta']) as $meta_key) {
                $this->_application->getPlatform()->setEntityMeta($entity->getType(), $entity->getId(), $meta_key, $extraArgs['entity_meta'][$meta_key]);
            }
        }

        if (!empty($bundle->info['is_taxonomy'])) {
            $this->_application->Entity_TaxonomyTerms_clearCache($bundle->name);
            return;
        }

        // Update parent post
        if (!empty($bundle->info['parent'])
            && empty($extraArgs['entity_skip_update_parent'])
        ) {
            $this->updateParentPostStats($entity, $entity->getTimestamp(), $entity->isPublished());
        }

        // Update taxonomy term content count
        if (!empty($bundle->info['taxonomies'])) {
            // Get terms added
            $terms_updated = [];
            foreach ($bundle->info['taxonomies'] as $taxonomy_name) {
                if (!$taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy_name)) continue;

                $new_terms = $entity->getFieldValue($taxonomy_bundle->type);
                if (empty($new_terms)) continue;

                foreach ($new_terms as $new_term) {
                    $terms_updated[$taxonomy_bundle->name][$new_term->getId()] = $new_term;
                }
            }
            if (!empty($terms_updated)) {
                foreach (array_keys($terms_updated) as $taxonomy) {
                    $this->_application->Entity_UpdateTermContentCount($taxonomy, $terms_updated[$taxonomy], $bundle);
                }
            }
        }

        if ($reference_field_names = $entity->getFieldNamesByType('entity_reference')) {
            foreach ($reference_field_names as $field_name) {
                if (!isset($values[$field_name])) continue;

                $this->_syncReferenceField($field_name, $entity);
            }
        }

        if ($entity->isPublished()) {
            // Clear facet count cache
            $this->_application->Entity_Facets_clearCache();
        }
    }

    public function onEntityUpdateEntitySuccess($bundle, $entity, $oldEntity, $values, $extraArgs)
    {
        if ($entity->isPublished()) {
            if ($oldEntity->isPending()) {
                $this->_application->Action('entity_entity_published', [$entity, $oldEntity]);
            }
        }

        if (!empty($extraArgs['entity_meta'])) {
            foreach (array_keys($extraArgs['entity_meta']) as $meta_key) {
                if (null === $extraArgs['entity_meta'][$meta_key]) {
                    $this->_application->getPlatform()->deleteEntityMeta($entity->getType(), $entity->getId(), $meta_key);
                } else {
                    $this->_application->getPlatform()->setEntityMeta($entity->getType(), $entity->getId(), $meta_key, $extraArgs['entity_meta'][$meta_key]);
                }
            }
        }

        if (!empty($bundle->info['is_taxonomy'])) {
            $this->_application->Entity_TaxonomyTerms_clearCache($bundle->name);
            return;
        }

        // Update parent post
        if (!empty($bundle->info['parent'])
            && empty($extraArgs['entity_skip_update_parent'])
            && ($this->_application->Entity_ParentEntity($entity, false))
        ) {
            $timestamp = $update_children_count = false;
            // Content updated?
            if (isset($values['content']) || isset($values['title'])) {
                if ($this->_application->Entity_BundleTypeInfo($bundle, 'entity_track_activity')) {
                    // Update last activity timestamp of the parent with the last edited timestamp of the updated entity
                    $timestamp = $entity->getSingleFieldValue('entity_activity', 'edited_at');
                } else {
                    $timestamp = time();
                }
            }
            // Content status changed?
            if (isset($values['status'])
                && ($oldEntity->isPublished() || $entity->isPublished())
            ) {
                // The content was published or unpublished
                $update_children_count = true;
            }

            $this->updateParentPostStats($entity, $timestamp, $update_children_count);
        }

        // Update taxonomy term content count
        if (!empty($bundle->info['taxonomies'])) {
            $is_published_or_unpublished = isset($values['status'])
                && ($entity->isPublished() || $oldEntity->isPublished());

            $taxonomy_updated = [];
            foreach ($bundle->info['taxonomies'] as $taxonomy_name) {
                if (!$taxonomy_bundle = $this->_application->Entity_Bundle($taxonomy_name)) continue;

                if ($is_published_or_unpublished
                    || isset($values[$taxonomy_bundle->type])
                ) {
                    $taxonomy_updated[$taxonomy_bundle->type] = $taxonomy_bundle->name;
                }
            }

            if (!empty($taxonomy_updated)) {
                $terms_updated = [];
                foreach ($taxonomy_updated as $taxonomy_type => $taxonomy_name) {
                    // Get terms newly assigned or unassigned
                    $current_terms = (array)@$entity->getFieldValue($taxonomy_type);
                    if ($old_terms = (array)@$oldEntity->getFieldValue($taxonomy_type)) {
                        // Exclude terms deleted
                        if (!empty($extraArgs['taxonomy_terms_deleted'][$taxonomy_type])) {
                            foreach (array_keys($old_terms) as $old_term_key) {
                                if (in_array($old_terms[$old_term_key]->getId(), $extraArgs['taxonomy_terms_deleted'][$taxonomy_type])) {
                                    unset($old_terms[$old_term_key]);
                                }
                            }
                        }
                    }
                    if (empty($current_terms) && empty($old_terms)) continue;

                    foreach ($current_terms as $current_term) {
                        $terms_updated[$taxonomy_name][$current_term->getId()] = $current_term;
                    }
                    if ($is_published_or_unpublished) {
                        // The content was either published or unpublished. Update all terms.
                        foreach ($old_terms as $old_term) {
                            $terms_updated[$taxonomy_name][$old_term->getId()] = $old_term;
                        }
                    } else {
                        // Update terms that were newly added or removed
                        foreach ($old_terms as $old_term) {
                            if (isset($terms_updated[$taxonomy_name][$old_term->getId()])) {
                                unset($terms_updated[$taxonomy_name][$old_term->getId()]);
                            } else {
                                $terms_updated[$taxonomy_name][$old_term->getId()] = $old_term;
                            }
                        }
                    }
                    if (empty($terms_updated[$taxonomy_name])) {
                        unset($terms_updated[$taxonomy_name]);
                    }
                }
                if (!empty($terms_updated)) {
                    foreach (array_keys($terms_updated) as $taxonomy) {
                        $this->_application->Entity_UpdateTermContentCount($taxonomy, $terms_updated[$taxonomy], $bundle);
                    }
                }
            }
        }

        if (empty($extraArgs['entity_skip_reference_sync'])) {
            if ($reference_field_names = $entity->getFieldNamesByType('entity_reference')) {
                foreach ($reference_field_names as $field_name) {
                    if (!isset($values[$field_name])) continue; // the field was not updated

                    $this->_syncReferenceField($field_name, $entity, $oldEntity);
                }
            }
        }

        if (!(!$entity->isPublished() && !$oldEntity->isPublished())
        ) {
            // Clear facet count cache
            $this->_application->Entity_Facets_clearCache();
        }
    }

    protected function _syncReferenceField($fieldName, Type\IEntity $entity, Type\IEntity $oldEntity = null)
    {
        if ((!$field = $this->_application->Entity_Field($entity, $fieldName))
            || (!$field_settings = $field->getFieldSettings())
            || empty($field_settings['bundle'])
            || empty($field_settings['sync'][$field_settings['bundle']])
            || (!$bundle2sync = $this->_application->Entity_Bundle($field_settings['bundle']))
            || (!$field2sync = $this->_application->Entity_Field($bundle2sync, $field_settings['sync'][$field_settings['bundle']]))
        ) return;

        $ref_entities = $old_ref_entities = [];
        foreach ((array)$entity->getFieldValue($fieldName) as $ref_entity) {
            $ref_entities[$ref_entity->getId()] = $ref_entity;
        }
        if (isset($oldEntity)) {
            foreach ((array)$oldEntity->getFieldValue($fieldName) as $ref_entity) {
                $old_ref_entities[$ref_entity->getId()] = $ref_entity;
            }
        }

        // Add reference
        if ($ref_entities_new = array_diff_key($ref_entities, $old_ref_entities)) {
            $this->_application->Entity_Field_load($bundle2sync->entitytype_name, $ref_entities_new);
            foreach ($ref_entities_new as $ref_entity) {
                $ref_entity_ref_ids = [];
                if ($ref_entity_refs = $ref_entity->getFieldValue($field2sync->getFieldName())) {
                    foreach ($ref_entity_refs as $ref_entity_ref) {
                        $ref_entity_ref_ids[] = $ref_entity_ref->getId();
                    }
                }
                if (!in_array($entity->getId(), $ref_entity_ref_ids)) {
                    $ref_entity_ref_ids[] = $entity->getId();
                    $this->_application->Entity_Save(
                        $ref_entity,
                        [$field2sync->getFieldName() => $ref_entity_ref_ids],
                        ['entity_skip_reference_sync' => true] // prevents loop
                    );
                }
            }
        }
        // Remove reference
        if ($ref_entities_removed = array_diff_key($old_ref_entities, $ref_entities)) {
            $this->_application->Entity_Field_load($bundle2sync->entitytype_name, $ref_entities_removed);
            foreach ($ref_entities_removed as $ref_entity) {
                if (!$ref_entity_refs = $ref_entity->getFieldValue($field2sync->getFieldName())) continue;

                $ref_entity_ref_ids = [];
                foreach ($ref_entity_refs as $ref_entity_ref) {
                    $ref_entity_ref_ids[$ref_entity_ref->getId()] = $ref_entity_ref->getId();
                }
                if (isset($ref_entity_ref_ids[$entity->getId()])) {
                    unset($ref_entity_ref_ids[$entity->getId()]);
                    $this->_application->Entity_Save(
                        $ref_entity,
                        [$field2sync->getFieldName() => $ref_entity_ref_ids],
                        ['entity_skip_reference_sync' => true] // prevents loop
                    );
                }
            }
        }
    }

    public function updateParentPostStats(Type\IEntity $entity, $timestamp = false, $updateChildrenCount = false, $isParent = false)
    {
        if (!$isParent) {
            if (!$parent = $this->_application->Entity_ParentEntity($entity)) {
                return;
            }
        } else {
            // Parent post was passed as the first argument
            $parent = $entity;
        }

        $values = [];

        if ($timestamp
            && $this->_application->Entity_BundleTypeInfo($parent->getBundleType(), 'entity_track_activity')
        ) {
            if (is_bool($timestamp)) {
                $last_child_entity = $this->_application->Entity_Query($entity->getType())
                    ->fieldIs('parent', $parent->getId())
                    ->sortByField('entity_activity', 'DESC', 'active_at')
                    ->fetch();
                if ($last_child_entity) {
                    $last_child_entity = array_shift($last_child_entity);
                    $timestamp = $last_child_entity->getSingleFieldValue('entity_activity', 'active_at');
                } else {
                    // No last active child entity, then fetch last published
                    $last_child_entity = $this->_application->Entity_Query($entity->getType())
                        ->fieldIs('parent', $parent->getId())
                        ->sortByField('published', 'DESC')
                        ->fetch();
                    if ($last_child_entity) {
                        $last_child_entity = array_shift($last_child_entity);
                        $timestamp = $last_child_entity->getTimestamp();
                    } else {
                        // No last published child entity
                        unset($last_child_entity);
                        $timestamp = 0;
                    }
                }
            }
            // Update active timestamp of the parent post
            $parent_edited_at = (int)$parent->getSingleFieldValue('entity_activity', 'edited_at');
            if ($timestamp < $parent_edited_at) {
                $timestamp = $parent_edited_at;
                $active_post_id = 0;
            } else {
                $active_post_id = $isParent ? 0 : (isset($last_child_entity) ? $last_child_entity->getId() : $entity->getId());
            }
            $values = array(
                'entity_activity' => array(
                    'active_at' => $timestamp,
                    'edited_at' => $parent_edited_at,
                    'active_post_id' => $active_post_id,
                ),
            );
        }

        if ($updateChildrenCount
            && ($child_bundle_types = (array)$this->_application->Entity_BundleTypes_children($parent->getBundleType()))
        ) {
            $values['entity_child_count'] = [];
            // Count the total number of child posts
            $count = $this->_application->Entity_Query($parent->getType())
                ->fieldIs('parent', $parent->getId())
                ->fieldIs('status', $this->_application->Entity_Status($parent->getType(), 'publish'))
                ->fieldIsIn('bundle_type', $child_bundle_types)
                ->groupByField('bundle_type')
                ->count();
            if (!empty($count)) {
                foreach ($count as $child_bundle_type => $_count) {
                    if (!empty($_count)) {
                        $values['entity_child_count'][] = array('value' => $_count, 'child_bundle_type' => $child_bundle_type);
                    }
                }
            }
            if (empty($values['entity_child_count'])) {
                // Remove field value
                $values['entity_child_count'] = false;
            }
        }

        if (!empty($values)) {
            $this->_application->Entity_Save($parent, $values);
        }
    }

    public function onSystemSlugsFilter(&$slugs)
    {
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$this->_application->isComponentLoaded($bundle->component)
                || empty($bundle->info['public'])
            ) continue;

            if (!empty($bundle->info['parent'])
                && (!$parent_bundle = $this->_application->Entity_Bundle($bundle->info['parent']))
            ) continue;

            $slugs[$bundle->component][$bundle->group . '-' . $bundle->info['slug']] = array(
                'slug' => $bundle->info['slug'],
                'component' => $bundle->component,
                'parent' => empty($bundle->info['parent']) ? $bundle->group : $bundle->group . '-' . $parent_bundle->info['slug'],
                'bundle_group' => $bundle->group,
                'bundle_type' => $bundle->type,
                'is_taxonomy' => !empty($bundle->info['is_taxonomy']),
                'admin_title' => $bundle->getGroupLabel() . ' - ' . $bundle->getLabel('singular'),
            );
        }
    }

    protected function _maybeAddParentTermValues($bundle, &$values)
    {
        if (!empty($bundle->info['is_taxonomy'])
            || empty($bundle->info['taxonomies'])
        ) return;

        $entity_type_impl  = null;
        foreach ($bundle->info['taxonomies'] as $taxonomy_bundle_type => $taxonomy) {
            if (empty($values[$taxonomy_bundle_type])
                || false === $this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'is_hierarchical')
            ) continue;

            $all_values = [];
            foreach (array_keys($values[$taxonomy_bundle_type]) as $i) {
                if (empty($values[$taxonomy_bundle_type][$i]['value'])) continue;

                $all_values[] = $values[$taxonomy_bundle_type][$i]['value'];
            }
            if (!isset($entity_type_impl)) {
                $entity_type_impl = $this->_application->Entity_Types_impl($this->_application->Entity_BundleTypeInfo($taxonomy_bundle_type, 'entity_type'));
            }
            $parent_ids = [];
            foreach ($all_values as $term_id) {
                foreach ($entity_type_impl->entityTypeParentEntityIds($term_id, $taxonomy) as $parent_id) {
                    if (in_array($parent_id, $all_values)
                        || in_array($parent_id, $parent_ids)
                    ) continue;

                    $values[$taxonomy_bundle_type][] = array(
                        'value' => $parent_id,
                        'auto' => true,
                    );
                    $parent_ids[] = $parent_id;
                }
            }
        }
    }

    public function displayGetStatisticNames(Model\Bundle $bundle)
    {
        $ret = [];
        if (empty($bundle->info['is_taxonomy'])
            && empty($bundle->info['parent'])
        ) {
            foreach ($this->_application->Entity_BundleTypes_children($bundle->type) as $bundle_type) {
                if (!$this->_application->Entity_Bundle($bundle_type, $bundle->component, $bundle->group)) continue;

                $ret[] = 'entity_child_entity_count_' . $bundle_type;
            }
        }
        return $ret;
    }

    public function displayGetStatistic($name)
    {
        return new DisplayStatistic\ChildEntityCountDisplayStatistic($this->_application, $name);
    }

    public function displayGetLabelNames(Model\Bundle $bundle)
    {
        $ret = [];
        if ($this->_application->Entity_BundleTypeInfo($bundle, 'featurable')) {
            $ret[] = 'entity_featured';
        }
        if (empty($bundle->info['is_taxonomy'])) {
            $ret[] = 'entity_status';
            $ret[] = 'entity_new';
        }

        return $ret;
    }

    public function displayGetLabel($name)
    {
        switch ($name) {
            case 'entity_status':
                return new DisplayLabel\StatusDisplayLabel($this->_application, $name);
            case 'entity_featured':
                return new DisplayLabel\FeaturedEntityDisplayLabel($this->_application, $name);
            case 'entity_new':
                return new DisplayLabel\NewEntityDisplayLabel($this->_application, $name);
        }
    }

    public function onSystemCron(&$logs, &$lastRun, $force)
    {
        if (!$force) {
            if (!$this->_application->callHelper('System_Cron_canRunTask', [$this->_name, &$logs, &$lastRun])) return;
        }

        // Fetch entities to un-feature
        foreach ($this->_application->Entity_BundleTypes_byFeatures(['featurable']) as $entity_type => $bundle_types) {
            $entities = $this->_application->Entity_Query($entity_type)
                ->fieldIsIn('bundle_type', $bundle_types)
                ->fieldIsOrSmallerThan('entity_featured', time(), 'expires_at')
                ->fieldIsGreaterThan('entity_featured', 0, 'expires_at') // exclude those that never expire
                ->fetch();
            foreach (array_keys($entities) as $entity_id) {
                $this->_application->Entity_Save($entities[$entity_id], ['entity_featured' => false]);
                $logs['success'][] = sprintf(__('Unfeatured item: %s', 'directories'), $entities[$entity_id]->getTitle());
            }
            $this->_application->Action('entity_entities_unfeatured', [$entity_type, $entities, &$logs]);
        }

        // Fetch entities with expired level
        if ($this->_application->Entity_Storage()->getFieldSchemaType('entity_expires')) { // @Todo: Do not require field schema check
            foreach ($this->_application->Entity_BundleTypes_byFeatures(['expirable']) as $entity_type => $bundle_types) {
                foreach ($this->_application->Entity_Bundles_byType($bundle_types) as $bundle) {
                    if (empty($bundle->info['entity_expire'])) continue;

                    $query = $this->_application->Entity_Query($entity_type, $bundle->name)
                        ->fieldIs('status', $this->_application->Entity_Status($bundle->entitytype_name, 'publish'))
                        ->fieldIsOrSmallerThan('entity_expires', time());
                    if (!empty($bundle->info['payment_enable'])
                        && $this->_application->isComponentLoaded('Payment')
                    ) {
                        $query->fieldIsNull('payment_plan', 'plan_id');
                    }
                    $entities = $query->fetch();
                    $values = [];
                    if ($status = $this->_application->Filter('entity_expired_status', null, [$bundle])) {
                        if (is_array($status)) {
                            $values['status'] = $status[0];
                            if (isset($status[1])) {
                                if ($status[1] === true) {
                                    if (!empty($bundle->info['entity_expire_days'])) {
                                        $values['entity_expires'] = time() + 86400 * $bundle->info['entity_expire_days'];
                                    }
                                } else {
                                    if ($status[1] === false
                                        || (is_int($status[1]) && $status[1] > time())
                                        || ($status[1] = strtotime($status[1]))
                                    ) {
                                        $values['entity_expires'] = $status[1];
                                    }
                                }
                            }
                        } else {
                            $values['status'] = $status;
                        }
                    } else {
                        $values['status'] = $this->_application->Entity_Status($bundle->entitytype_name, 'trash');
                    }
                    foreach (array_keys($entities) as $entity_id) {
                        $this->_application->Entity_Save($entities[$entity_id], $values);
                        $logs['success'][] = sprintf(
                            __('Expired item trashed: %s (%s)', 'directories'),
                            $entities[$entity_id]->getTitle(),
                            $bundle->getGroupLabel() . ' - ' . $bundle->getLabel('singular')
                        );
                    }
                    $this->_application->Action('entity_entities_expired', [$bundle, $entities, &$logs]);
                }
            }
        }
    }

    public function wpGetNotificationNames()
    {
        return array('pending');
    }

    public function wpGetNotification($name)
    {
        return new WordPressNotification\EntityWordPressNotification($this->_application, $name);
    }

    public function onDisplayRenderFilter(&$ret, $display, $bundle, $var, $options)
    {
        if ($display['type'] !== 'entity'
            || $display['name'] !== 'detailed'
        ) return;

        if (!empty($bundle->info['entity_schemaorg']['type'])) {
            $this->_application->Entity_SchemaOrg($var, $bundle->info['entity_schemaorg']);
        }
        if (!empty($bundle->info['entity_opengraph']['type'])) {
            $this->_application->Entity_OpenGraph($var, $bundle->info['entity_opengraph']);
        }
    }

    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'entity_image';
        $keys[] = 'entity_icon';
        $keys[] = 'entity_icon_is_image';
        $keys[] = 'entity_color';
        $keys[] = 'entity_title';
        $keys[] = 'entity_expire';
        $keys[] = 'entity_expire_days';
    }

    public function systemGetToolNames()
    {
        $ret = ['entity_refresh_field_cache', 'entity_refresh_term_cache', 'entity_recount'];
        if (($langs = $this->_application->getPlatform()->getLanguages())
            && count($langs) > 1
        ) {
            $ret[] = 'entity_gen_trans';
        }
        return $ret;
    }

    public function systemGetTool($name)
    {
        switch ($name) {
            case 'entity_refresh_field_cache':
                return new SystemTool\RefreshFieldCacheSystemTool($this->_application, $name);
            case 'entity_refresh_term_cache':
                return new SystemTool\RefreshTermCacheSystemTool($this->_application, $name);
            case 'entity_recount':
                return new SystemTool\RecountSystemTool($this->_application, $name);
            case 'entity_gen_trans':
                return new SystemTool\GenerateTranslationsSystemTool($this->_application, $name);
        }
    }

    public function onDisplayElementRenderFilter(&$render, $bundle, array $element, $var)
    {
        if (!$render
            || !$var instanceof Type\IEntity
            || empty($element['data']['entity_conditions'])
        ) return;

        $render = $this->_application->Entity_Field_checkConditions($element['data']['entity_conditions'], $var);
    }

    public function onDisplayElementSettingsFormFilter(&$form, $bundle, $display, $element, $elementData)
    {
        if ($display->type !== 'entity'
            || false === $element->displayElementInfo($bundle, 'conditionable')
        ) return;

        $form['#tabs']['entity_conditions'] = [
            '#title' => __('Conditions', 'directories'),
            '#weight' => 40,
        ];
        $form['entity_conditions'] = [
            '#tab' => 'entity_conditions',
        ] + $this->_application->Entity_Field_conditionSettingsForm(
            $bundle->name,
            isset($elementData['entity_conditions']) ? $elementData['entity_conditions'] : [], // settings
            ['entity_conditions'],
            true,
            [], // exculdeFields
            [] // submitValues
        );
    }

    public function onEntityFormFilter(&$form, $bundle, $entity, $options)
    {
        // Remove warning message for "Exp. Date" field if no payment plan field.
        if ($this->_application->getPlatform()->isAdmin()
            && !isset($form['payment_plan'])
        ) {
            unset($form['entity_expires'][0]['warning']);
        }
    }
}
