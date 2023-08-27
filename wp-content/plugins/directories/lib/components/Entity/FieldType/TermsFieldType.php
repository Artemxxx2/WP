<?php
namespace SabaiApps\Directories\Component\Entity\FieldType;

use SabaiApps\Directories\Component\Entity\Type\IEntity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Field\IField;

class TermsFieldType extends Field\Type\AbstractType implements
    Field\Type\IQueryable,
    Field\Type\IHumanReadable,
    Field\Type\IConditionable,
    Field\Type\ICopiable,
    Field\Type\ILabellable
{
    use QueryableTermsTrait;

    protected function _fieldTypeInfo()
    {
        return [
            'label' => __('Taxonomy Terms', 'directories'),
            'entity_types' => ['post'],
            'default_renderer' => 'entity_terms',
            'creatable' => false,
            'disablable' => false,
            'icon' => strpos($this->_name, 'tag') !== false ? 'fas fa-tag' : 'far fa-folder-open',
        ];
    }
    
    public function fieldTypeSchema()
    {
        return [
            'columns' => [
                'value' => [
                    'type' => Application::COLUMN_INTEGER,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'value',
                    'default' => 0,
                ],
                'auto' => [
                    'type' => Application::COLUMN_BOOLEAN,
                    'notnull' => true,
                    'unsigned' => true,
                    'was' => 'auto',
                    'default' => false,
                ],
            ],
        ];
    }

    public function fieldTypeOnSave(IField $field, array $values, array $currentValues = null, array &$extraArgs = [])
    {
        $ret = $term_ids = [];
        if ($field->getFieldWidget() === 'entity_tag_term') {
            foreach ($values as $value) {
                if (is_array($value)) {  // tagging
                    $term_ids = $value;
                } elseif (!empty($value)) {
                    $term_ids[] = $value;
                }
            }
        } else {
            $term_ids = $values;
        }
        foreach ($term_ids as $term_id) {
            if (is_array($term_id)) {
                if (empty($term_id['value'])) continue;

                $auto = !empty($term_id['auto']);
                $term_id = $term_id['value'];
                $ret[$term_id] = [
                    'value' => $term_id,
                    'auto' => $auto,
                ];
            } else {
                if (empty($term_id)) continue;
                
                $ret[$term_id]['value'] = $term_id;
            }
        }
        return array_values($ret);
    }

    public function fieldTypeOnLoad(IField $field, array &$values, IEntity $entity, array $allValues)
    {
        if (!$taxonomy_bundle = $field->getTaxonomyBundle()) return;
        
        $term_ids = [];
        foreach ($values as $value) {
            if (!empty($value['auto'])) continue; // exclude the ones auto saved for facet counts
            
            $term_ids[$value['value']] = $value['value'];
        }
        $values = [];
        $is_hierarchical = !empty($taxonomy_bundle->info['is_hierarchical']);
        $fields_to_load = [];
        if (!empty($taxonomy_bundle->info['entity_image'])) {
            $fields_to_load['image'] = $taxonomy_bundle->info['entity_image'];
        } elseif (!empty($taxonomy_bundle->info['entity_icon'])) {
            $fields_to_load['icon'] = $taxonomy_bundle->info['entity_icon'];
            $icon_is_image = !empty($taxonomy_bundle->info['entity_icon_is_image']);
            if (!$icon_is_image
                && !empty($taxonomy_bundle->info['entity_color'])
            ) {
                $fields_to_load['color'] = $taxonomy_bundle->info['entity_color'];
            }
        }

        $fields_to_load['entity_term_content_count'] = 'entity_term_content_count';

        // Allow modifying what fields to load
        $fields_to_load = $this->_application->Filter('entity_terms_field_load_fields', $fields_to_load, [$entity, $taxonomy_bundle]);

        $entity_type = $taxonomy_bundle->entitytype_name;
        foreach ($this->_application->Entity_Entities($entity_type, $term_ids, $fields_to_load, true) as $term_id => $term) {
            $parent_ids = null;
            if ($is_hierarchical
                && $term->getParentId()
            ) {
                $parent_ids = $this->_application->Entity_Types_impl($entity_type)->entityTypeParentEntityIds($term_id, $taxonomy_bundle->name);
            }
            $fields_to_load_from_parent = [];
            if (isset($fields_to_load['image'])) {
                if ($image = $this->_application->Entity_Image($term, 'icon', $fields_to_load['image'])) {
                    $term->setCustomProperty('image_src', $image); // set as custom property so it can be cached
                } else {
                    $fields_to_load_from_parent['image'] = $fields_to_load['image']; // not found, so will try loading from parent
                }
            } elseif (isset($fields_to_load['icon'])) {
                if ($icon_is_image) {
                    if ($icon = $this->_application->Entity_Image($term, 'full', $fields_to_load['icon'])) {
                        $term->setCustomProperty('icon_src', $icon); // set as custom property so it can be cached
                    }
                } else {
                    if ($icon = $this->_application->Entity_Icon($term, false)) {
                        $term->setCustomProperty('icon', $icon); // set as custom property so it can be cached
                    }
                    if (isset($fields_to_load['color'])) {
                        if ($color = $this->_application->Entity_Color($term)) {
                            $term->setCustomProperty('color', $color); // set as custom property so it can be cached
                        } else {
                            $fields_to_load_from_parent['color'] = $fields_to_load['color']; // not found, so will try loading from parent
                        }
                    }
                }
                if (!$icon) {
                    $fields_to_load_from_parent['icon'] = $fields_to_load['icon']; // not found, so will try loading from parent
                }
            }

            if ($parent_ids) {
                $parent_slugs = $parent_titles = [];
                $parent_ids = array_reverse($parent_ids); // reverse to get data from clsoest parent
                foreach ($this->_application->Entity_Entities($entity_type, $parent_ids, $fields_to_load_from_parent, true) as $parent_id => $parent_entity) {
                    if (isset($fields_to_load_from_parent['image'])) {
                        if ($image = $this->_application->Entity_Image($parent_entity, 'icon', $fields_to_load_from_parent['image'])) {
                            $term->setCustomProperty('image_src', $image); // set as custom property so it can be cached
                            unset($fields_to_load_from_parent['image']);
                        }
                    } elseif (isset($fields_to_load_from_parent['icon'])) {
                        if ($icon_is_image) {
                            $icon_field_to_load = $fields_to_load_from_parent['icon'];
                            if ($icon = $this->_application->Entity_Image($parent_entity, 'full', $icon_field_to_load)) {
                                $term->setCustomProperty('icon_src', $icon); // set as custom property so it can be cached
                            }
                        } else {
                            if ($icon = $this->_application->Entity_Icon($parent_entity, false)) {
                                $term->setCustomProperty('icon', $icon); // set as custom property so it can be cached
                                unset($fields_to_load_from_parent['icon']);
                            }
                            if (isset($fields_to_load_from_parent['color'])) {
                                if ($color = $this->_application->Entity_Color($parent_entity)) {
                                    $term->setCustomProperty('color', $color); // set as custom property so it can be cached
                                    unset($fields_to_load_from_parent['color']);
                                }
                            }
                        }
                    }
                    $parent_slugs[$parent_id] = $parent_entity->getSlug();
                    $parent_titles[$parent_id] = $parent_entity->getTitle();
                }
                $term->setCustomProperty('parent_slugs', array_reverse($parent_slugs))
                    ->setCustomProperty('parent_titles', array_reverse($parent_titles));
            }
            // Set content count
            $term->setCustomProperty('content_count', (int)$term->getSingleFieldValue('entity_term_content_count', '_all'));
            $term->initFields([], [], false);
            $values[] = $term;
        }

        if (!empty($values)) {
            $values = $this->_application->Filter('entity_terms_field_load_values', $values, [$entity, $taxonomy_bundle]);
        }
    }
    
    public function fieldTypeIsModified(IField $field, $valueToSave, $currentLoadedValue)
    {        
        $current = $new = [];
        if (!empty($currentLoadedValue)) {
            foreach ($currentLoadedValue as $value) {
                $current[] = (int)$value->getId();
            }
        }
        foreach ($valueToSave as $value) {
            $new[] = (int)$value['value'];
        }
        return $current !== $new;
    }
    
    public function fieldHumanReadableText(IField $field, IEntity $entity, $separator = null, $key = null)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return '';
        
        $ret = [];
        foreach ($values as $term) {
            $ret[] = $term->getTitle();
        }
        
        return implode(isset($separator) ? $separator : PHP_EOL, $ret);
    }
    
    public function fieldConditionableInfo(IField $field, $isServerSide = false)
    {
        if (!$this->_isTaxonomyConditionable($field, $isServerSide)) return;
        
        return [
            '' => [
                'compare' => ['value', '!value', 'one', 'empty', 'filled'],
                'tip' => __('Enter taxonomy term IDs and/or slugs separated with commas.', 'directories'),
                'example' => '1,5,arts,17',
            ],
        ];
    }
    
    public function fieldConditionableRule(IField $field, $compare, $value = null, $_name = '')
    {
        switch ($compare) {
            case 'value':
            case '!value':
            case 'one':
                $value = trim($value);
                if (strpos($value, ',')) {
                    if (!$value = explode(',', $value)) return;
                    
                    $value = array_map('trim', $value);
                } else {
                    $value = [$value];
                }
                if ($compare === 'one') {
                    if (!$taxonomy_bundle = $field->getTaxonomyBundle()) return;

                    if ($taxonomy_bundle->info['is_hierarchical']) {
                        // Include descendant terms if "+" is added after ID
                        $terms = [];
                        foreach ($value as $term_id_or_slug) {
                            if (strpos($term_id_or_slug, '+')
                                && ($term_id = substr($term_id_or_slug, 0, -1))
                                && is_numeric($term_id)
                            ) {
                                $terms[] = $term_id;
                                $descendant_term_ids = $this->_application->Entity_Types_impl($taxonomy_bundle->entitytype_name)
                                    ->entityTypeDescendantEntityIds($term_id, $taxonomy_bundle->name);
                                foreach ($descendant_term_ids as $descendant_term_id) {
                                    $terms[] = $descendant_term_id;
                                }
                            } else {
                                $terms[] = $term_id_or_slug;
                            }
                        }
                        $value = $terms;
                    }
                }
                return ['type' => $compare, 'value' => $value];
            case 'empty':
                return ['type' => 'filled', 'value' => false];
            case 'filled':
                return ['type' => 'empty', 'value' => false];
            default:
        }
    }

    public function fieldConditionableMatch(IField $field, array $rule, array $values, IEntity $entity)
    {
        switch ($rule['type']) {
            case 'value':
            case '!value':
            case 'one':
                if (empty($values)) return $rule['type'] === '!value';

                foreach ((array)$rule['value'] as $rule_value) {
                    $rule_value = (int)$rule_value;
                    foreach ($values as $input) {
                        if (is_object($input)) {
                            if ($input instanceof IEntity) {
                                $term_id = $input->getId();
                            } else {
                                continue;
                            }
                        } elseif (is_array($input)) {
                            $term_id = (int)$input['value'];
                        } else {
                            $term_id = (int)$input;
                        }
                        if ($term_id === $rule_value) {
                            if ($rule['type'] === '!value') return false;
                            if ($rule['type'] === 'one') return true;
                            continue 2;
                        }
                    }
                    // One of rules did not match
                    if ($rule['type'] === 'value') return false;
                }
                // All rules matched or did not match.
                return $rule['type'] !== 'one' ? true : false;
            case 'empty':
                return empty($values) === $rule['value'];
            case 'filled':
                return !empty($values) === $rule['value'];
            default:
                return false;
        }
    }

    protected function _isTaxonomyConditionable(IField $field, $isServerSide)
    {
        return (($taxonomy_bundle = $field->getTaxonomyBundle())
            && ($isServerSide || !empty($taxonomy_bundle->info['is_hierarchical'])) // tagging form field is currently not supported
            && false !== $this->_application->Entity_BundleTypeInfo($taxonomy_bundle, 'taxonomy_assignable')
        ) ? $taxonomy_bundle : false;
    }

    public function fieldCopyValues(IField $field, array $values, array &$allValues, $lang = null)
    {
        if (!empty($lang)) {
            if (!$taxonomy_bundle = $field->getTaxonomyBundle()) {
                $this->_application->logError('Failed fetching taxonomy field bundle.');
                return;
            }

            if ($this->_application->getPlatform()->isTranslatable($taxonomy_bundle->entitytype_name, $taxonomy_bundle->name)) {
                foreach (array_keys($values) as $k) {
                    if (empty($values[$k]['value'])) continue;

                    $translation_id = (int)$this->_application->getPlatform()->getTranslatedId(
                        $taxonomy_bundle->entitytype_name,
                        $taxonomy_bundle->name,
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

    public function fieldLabellableLabels(IField $field, IEntity $entity)
    {
        if (!$values = $entity->getFieldValue($field->getFieldName())) return;

        $ret = [];
        foreach ($values as $term) {
            $ret[] = $term->getTitle();
        }

        return $ret;
    }
}