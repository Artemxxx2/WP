<?php
namespace SabaiApps\Directories\Component\CSV\Importer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Exception;

class EntityImporter extends AbstractImporter implements IWpAllImportImporter
{
    protected $_parentBundle, $_termTitles = [];
    
    protected function _csvImporterInfo()
    {
        switch ($this->_name) {
            case 'entity_featured':
                $columns = array(
                    'value' => __('Priority', 'directories'),
                    'featured_at' => __('Featured Date', 'directories'),
                    'expires_at' => __('End Date', 'directories'),
                );
                break;
            case 'entity_activity':
                $columns = array(
                    'active_at' => __('Last Active Date', 'directories'),
                    'edited_at' => __('Edited Date', 'directories'),
                );
                break;
            default:
                $columns = null;
        }
        return array(
            'field_types' => array($this->_name),
            'columns' => $columns,
        );
    }
    
    public function csvImporterSupports(Entity\Model\Bundle $bundle, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'entity_post_views':
                return empty($bundle->info['parent']) && !empty($bundle->info['public']);
            case 'entity_slug':
                return empty($bundle->info['parent']);
            case 'entity_terms':
                // Skip taxonomies that are assigned through another field
                return ($bundle = $field->getTaxonomyBundle())
                    && false !== $this->_application->Entity_BundleTypeInfo($bundle, 'taxonomy_assignable');
            case 'entity_term_parent':
                return !empty($bundle->info['is_hierarchical']);
        }
        return true;
    }
    
    public function csvImporterSettingsForm(Entity\Model\Field $field, array $settings, $column, $enclosure, array $parents = [])
    {
        switch ($this->_name) {
            case 'entity_published':
                return $this->_getDateFormatSettingsForm($parents);
            case 'entity_author':
                return $this->_getUserSettingsForm();
            case 'entity_featured':
                return in_array($column, array('featured_at', 'expires_at')) ? $this->_getDateFormatSettingsForm($parents) : null;
            case 'entity_activity':
                return in_array($column, array('active_at', 'edited_at')) ? $this->_getDateFormatSettingsForm($parents) : null;
            case 'entity_child_count':
                return array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => $title = __('Content type/count separator', 'directories'),
                        '#description' => __('Enter the character used to separate the content type and count.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents, array('separator' => $title));
            case 'entity_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Content item ID type', 'directories'),
                        '#description' => __('Select the type of data used to specify exported items.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );
            case 'entity_term_parent':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Parent term data type', 'directories'),
                        '#description' => __('Select the type of data used to specify terms.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                            'title' => __('Title', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                );     
            case 'entity_terms':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Taxonomy term data type', 'directories'),
                        '#description' => __('Select the type of data used to specify terms.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                            'title' => __('Title', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                    'create' => array(
                        '#type' => 'checkbox',
                        '#title' => __('Create non-existent terms', 'directories'),
                        '#default_value' => true,
                        '#horizontal' => true,
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'title'),
                            ),
                        ),
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents);
            case 'entity_term_content_count':
                return array(
                    'separator' => array(
                        '#type' => 'textfield',
                        '#title' => $sep_title = __('Content type/count separator', 'directories'),
                        '#description' => __('Enter the character used to separate the content type and count.', 'directories'),
                        '#default_value' => '|',
                        '#horizontal' => true,
                        '#min_length' => 1,
                        '#required' => true,
                        '#weight' => 1,
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents, array('separator' => $sep_title));
            case 'entity_reference':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => __('Content item ID type', 'directories'),
                        '#description' => __('Select the type of data used to specify exported items.', 'directories'),
                        '#options' => array(
                            'id' => __('ID', 'directories'),
                            'slug' => __('Slug', 'directories'),
                        ),
                        '#default_value' => 'slug',
                        '#horizontal' => true,
                    ),
                ) + $this->_acceptMultipleValues($field, $enclosure, $parents);
        }
    }
    
    public function csvImporterDoImport(Entity\Model\Field $field, array $settings, $column, $value, &$formStorage, array &$logs)
    {
        switch ($this->_name) {
            case 'entity_published':
                return false !== ($value = $this->_strToTime($value, $settings)) ? $value : null;
            case 'entity_author':
                if ($settings['id_format'] === 'username') {
                    $value = $this->_application->getPlatform()->getUserIdentityFetcher()->fetchByUsername($value)->id;
                }
                return $value;
            case 'entity_featured':
                if (in_array($column, array('featured_at', 'expires_at'))) {
                    if (false === $value = $this->_strToTime($value, $settings)) return null;
                }
                return array(array($column => $value));
            case 'entity_activity':
                if (in_array($column, array('active_at', 'edited_at'))) {
                    if (false === $value = $this->_strToTime($value, $settings)) return null;
                }
                return array(array($column => $value));
            case 'entity_child_count':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = [];
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'child_bundle_name' => $value[0],
                            'value' => $value[1],
                        );
                    }
                }
                return $ret;
            case 'entity_parent':
                if ($settings['type'] === 'slug') {
                    if (!isset($this->_parentBundle)
                        && (!$this->_parentBundle = $this->_application->Entity_Bundle($field->Bundle->info['parent']))
                    ) return false;
                    
                    if (!$entity = $this->_application->Entity_Types_impl($this->_parentBundle->entitytype_name)
                        ->entityTypeEntityBySlug($this->_parentBundle->name, $value)
                    ) return;
                    
                    return $entity->getId();
                } else {
                    return $value;
                }   
            case 'entity_terms':
                $value = str_replace('&amp;', '&', $value);
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = [];
                switch ($settings['type']) {
                    case 'title':
                        if (!$bundle = $field->getTaxonomyBundle()) return;

                        if (!empty($this->_termTitles)) {
                            foreach (array_keys($values) as $i) {
                                if ($term_id = array_search(strtolower($values[$i]), $this->_termTitles)) {
                                    $ret[] = $term_id;
                                    unset($values[$i]);
                                }
                            }
                        }
                        if (!empty($values)) {
                            $terms = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntitiesByTitles($bundle->name, $values);
                            foreach ($terms as $term) {
                                $this->_termTitles[$term->getId()] = strtolower($term->getTitle());
                                $ret[] = $term->getId();
                            }
                            if ($settings['create']) {
                                foreach ($values as $title) {
                                    if (!in_array(strtolower($title), $this->_termTitles)) {
                                        try { 
                                            $term = $this->_application->Entity_Save($bundle, array('title' => $title, 'parent' => 0));
                                        } catch (Exception\IException $e) {
                                            $logs['error'][] = $e->getMessage();
                                            continue;
                                        }
                                        $this->_termTitles[$term->getId()] = strtolower($term->getTitle());
                                        $ret[] = $term->getId();
                                    }
                                }
                            }
                        }
                        break;
                    case 'slug':
                        if (!$bundle = $field->getTaxonomyBundle()) return;
                        
                        $terms = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntitiesBySlugs($bundle->name, $values);
                        foreach ($terms as $term) {
                            $ret[] = $term->getId();
                        }
                        break;
                    case 'id':
                        $ret = $values;
                        break;
                }
                return $ret;  
            case 'entity_term_parent':
                switch ($settings['type']) {
                    case 'slug':
                        $bundle = $field->Bundle;
                        if (!$term = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntityBySlug($bundle->name, $value)) return;

                        return $term->getId();
                    case 'title':
                        $bundle = $field->Bundle;
                        if (!$term = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntityByTitle($bundle->name, $value)) return;

                        return $term->getId();
                    default:
                        return $value;
                }
            case 'entity_term_content_count':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = [];
                foreach ($values as $value) {
                    if ($value = explode($settings['separator'], $value)) {
                        $ret[] = array(
                            'content_bundle_name' => $value[0],
                            'value' => $value[1],
                            'merged' => $value[2],
                        );
                    }
                }
                return $ret;
            case 'entity_reference':
                if (!empty($settings['_multiple'])) {
                    if (!$values = explode($settings['_separator'], $value)) {
                        return;
                    }
                } else {
                    $values = array($value);
                }
                $ret = [];
                switch ($settings['type']) {
                    case 'slug':
                        $field_settings = $field->getFieldSettings();
                        if (empty($field_settings['bundle'])
                            || (!$bundle = $this->_application->Entity_Bundle($field_settings['bundle']))
                        ) return;

                        $entities = $this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntitiesBySlugs($bundle->name, $values);
                        foreach ($entities as $entity) {
                            $ret[] = $entity->getId();
                        }
                        break;
                    case 'id':
                        $ret = $values;
                        break;
                }
                return $ret;
            default:
                return $value;
        }
    }

    public function csvWpAllImportImporterAddField(\RapidAddon $addon, Entity\Model\Field $field)
    {
        switch ($this->_name) {
            case 'entity_featured':
                $addon->add_title($field->getFieldLabel());
                $addon->add_field(
                    $field->getFieldName(),
                    '',
                    'radio',
                    [
                        1 => [
                            __('Yes', 'directories'),
                            $addon->add_field($field->getFieldName() . '-expires_at', __('End date', 'directories'), 'text'),
                            $addon->add_field(
                                $field->getFieldName() . '-priority',
                                __('Priority', 'directories'),
                                'radio',
                                [
                                    9 => __('High', 'directories'),
                                    5 => __('Normal', 'directories'),
                                    1 => __('Low', 'directories'),
                                ],
                                '',
                                true,
                                5
                            ),
                        ],
                        0 => __('No', 'directories'),
                    ],
                    '',
                    true,
                    0
                );
                return true;
        }
    }

    public function csvWpAllImportImporterDoImport(\RapidAddon $addon, Entity\Model\Field $field, array $data, $options, array $article)
    {
        switch ($this->_name) {
            case 'entity_featured':
                if (empty($data[$field->getFieldName()])) return;

                if (!isset($data[$field->getFieldName()  . '-priority']) || !in_array($priority = $data[$field->getFieldName()  . '-priority'], [1, 9])) {
                    $priority = 5;
                }
                if (empty($data[$field->getFieldName()  . '-expires_at'])) {
                    $expires_at = 0;
                } else {
                    if (is_numeric($data[$field->getFieldName()  . '-expires_at'])) {
                        $expires_at = $data[$field->getFieldName()  . '-expires_at'];
                    } else {
                        if (!$expires_at = strtotime($data[$field->getFieldName()  . '-expires_at'])) return;
                    }
                    if ($expires_at < time()) return;
                }
                return [[
                    'value' => $priority,
                    'expires_at' => $expires_at,
                ]];
        }
    }
}