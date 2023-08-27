<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Request;

class EntityGenerator extends AbstractGenerator
{
    protected $_users, $_userCount, $_ids = [], $_idCount = [];
    
    protected function _fakerGeneratorInfo()
    {
        $info = parent::_fakerGeneratorInfo();
        switch ($this->_name) {
            case 'entity_title':
                $info += array(
                    'default_settings' => array(
                        'length' => array('min' => 30, 'max' => 80),
                    ),
                );
                break;
            case 'entity_published':
                $info += array(
                    'default_settings' => array(
                        'range' => array('from' => time() - 86400 * 10, 'to' => time() - 86400 * 1),
                    ),
                );
                break;
            case 'entity_activity':
                $info += array(
                    'default_settings' => array(
                        'active_at' => time(),
                        'edited_at' => time(),
                    ),
                );
                break;
            case 'entity_views':
                $info += array(
                    'default_settings' => array(
                        'range' => array('min' => 0, 'max' => 1000),
                    ),
                );
                break;
            case 'entity_author':
                $info += array(
                    'default_settings' => array(
                        'type' => 'none',
                        'num' => 10,
                        'users' => array($this->_application->getUser()->id),
                    ),
                );
                break;
            case 'entity_parent':
                $info += array(
                    'default_settings' => array(
                        'type' => 'random',
                        'entries' => null,
                        'num' => 100,
                    ),
                );
                break;
            case 'entity_featured':
                $info += array(
                    'default_settings' => array(
                        'probability' => 5,
                        'priority' => array(1, 5, 9),
                        'featured_at' => array(
                            'from' => time() - 86400 * 30,
                            'to' => time() - 86400 * 1,
                        ),
                        'days' => array('min' => 10, 'max' => 30),
                    ),
                );
                break;
            case 'entity_term_parent':
                $info += array(
                    'default_settings' => array(
                        'probability' => 30,
                        'type' => 'random',
                        'num' => 20,
                    ),
                );
                break;
            case 'entity_terms':
                $info += array(
                    'default_settings' => array(
                        'probability' => 100,
                        'type' => 'random',
                        'num' => 1000,
                        'max' => 3,
                    ),
                );
                break;
        }
        
        return $info;
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'entity_title':
                $min = $max = null;
                if ($field->getFieldWidget() === 'entity_title') {
                    $widget_settings = $field->getFieldWidgetSettings();
                    $min = isset($widget_settings['min']) && $widget_settings['min'] > 5 ? $widget_settings['min'] : null;
                    $max = isset($widget_settings['max']) && $widget_settings['max'] > 5 ? $widget_settings['max'] : null;
                }
                if (!empty($field->Bundle->info['is_taxonomy'])) {
                    $settings['length'] = array('min' => 5, 'max' => 30);
                }
                return array(
                    'length' => array(
                        '#type' => 'range',
                        '#title' => __('Text length range', 'directories-faker'),
                        '#integer' => true,
                        '#min_value' => isset($min) ? $min : min(array(5, $settings['length']['min'])),
                        '#max_value' => isset($max) ? $max : max(100, $settings['length']['max']),
                        '#default_value' => array(
                            'min' => isset($min) ? max(array($min, $settings['length']['min'])) : $settings['length']['min'],
                            'max' => isset($max) ? min(array($max, $settings['length']['max'])) : $settings['length']['max'],
                        ),
                    ),
                );
            case 'entity_published':
                return array(
                    'range' => $this->_getTimestampSettingForm($settings['range'])
                );
            case 'entity_activity':
                return array(
                    'active_at' => array('#title' => __('Last active date range', 'directories-faker')) + $this->_getTimestampSettingForm($settings['active_at']),
                    'edited_at' => array('#title' => __('Last edited date range', 'directories-faker')) + $this->_getTimestampSettingForm($settings['edited_at'])
                );
            case 'entity_views':
                return array(
                    'range' => array(
                        '#type' => 'range',
                        '#title' => __('View count range', 'directories-faker'),
                        '#integer' => true,
                        '#max_value' => 1000,
                        '#description' => __('Select the range of possible number of view counts for the field.', 'directories-faker'),
                        '#default_value' => $settings['range'],
                    ),
                );
            case 'entity_author':
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => $field->getFieldLabel(),
                        '#options' => array(
                            'none' => __('No author', 'directories-faker'),
                            'admin' => __('Administrators', 'directories-faker'),
                            'users' => __('Specific users', 'directories-faker'),
                            'newest' => __('Newest users', 'directories-faker'),
                        ),
                        '#default_value' => $settings['type'],
                    ),
                    'users' => array(
                        '#type' => 'user',
                        '#multiple' => true,
                        '#default_value' => $settings['users'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'users'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'users'; },
                    ),
                    'num' => array(
                        '#type' => 'slider',
                        '#title' => __('Max number of users', 'directories-faker'),
                        '#min_value' => 1,
                        '#max_value' => 100,
                        '#default_value' => $settings['num'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'newest'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'newest'; },
                    ),
                );
            case 'entity_parent':     
                $parent_bundle = $this->_getParentBundle($field);
                return array(
                    'type' => array(
                        '#type' => 'select',
                        '#title' => $parent_bundle->getLabel(),
                        '#options' => array(
                            'entries' => __('Select manually', 'directories-faker'),
                            'random' => __('Random', 'directories-faker'),
                        ),
                        '#default_value' => $settings['type'],
                    ),
                    'entries' => array(
                        '#type' => 'autocomplete',
                        '#title' => $parent_bundle->getLabel('select'),
                        '#default_options_callback' => array(array($this, '_getDefaultOptions'), array($parent_bundle->entitytype_name, $parent_bundle->name)),
                        '#select2' => true,
                        '#select2_ajax' => true,
                        '#select2_item_text_key' => 'title',
                        '#select2_ajax_url' => $this->_application->MainUrl(
                            '/_drts/entity/' . $parent_bundle->type . '/query',
                            array('bundle' => $parent_bundle->name, Request::PARAM_CONTENT_TYPE => 'json'),
                            '',
                            '&'
                        ),
                        '#multiple' => true,
                        '#default_value' => $settings['entries'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'entries'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'entries'; },
                    ),
                    'num' => array(
                        '#type' => 'slider',
                        '#title' => sprintf(__('Max number of random %s to fetch from the database', 'directories-faker'), strtolower($parent_bundle->getLabel()), $parent_bundle->getLabel()),
                        '#min_value' => 1,
                        '#max_value' => 500,
                        '#default_value' => $settings['num'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'random'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'random'; },
                    ),
                );
            case 'entity_featured':
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'priority' => array(
                        '#type' => 'checkboxes',
                        '#title' => __('Prioirty', 'directories-faker'),
                        '#options' => Entity\FieldType\FeaturedFieldType::priorities(),
                        '#default_value' => $settings['priority'],
                    ),
                    'featured_at' => $this->_getTimestampSettingForm($settings['featured_at'], __('Featured date', 'directories-faker')),
                    'days' => array(
                        '#type' => 'range',
                        '#title' => __('Duration', 'directories-faker'),
                        '#min_value' => 0,
                        '#max_value' => 100,
                        '#default_value' => $settings['days'],
                        '#description' => __('Select the range of possible durations for the field.', 'directories-faker'),
                    ),
                );
            case 'entity_term_parent':     
                $taxonomy_bundle = $field->Bundle;
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'type' => array(
                        '#type' => 'select',
                        '#title' => $taxonomy_bundle->getLabel(),
                        '#options' => array(
                            'entries' => __('Select manually', 'directories-faker'),
                            'random' => __('Random', 'directories-faker'),
                        ),
                        '#default_value' => $settings['type'],
                    ),
                    'entries' => array(
                        '#type' => 'autocomplete',
                        '#title' => $taxonomy_bundle->getLabel('select'),
                        '#default_options_callback' => array(array($this, '_getDefaultOptions'), [$taxonomy_bundle->entitytype_name, $taxonomy_bundle->name]),
                        '#select2' => true,
                        '#select2_ajax' => true,
                        '#select2_item_text_key' => 'title',
                        '#select2_ajax_url' => $this->_application->MainUrl(
                            '/_drts/entity/' . $taxonomy_bundle->type . '/query',
                            array('bundle' => $taxonomy_bundle->name, Request::PARAM_CONTENT_TYPE => 'json'),
                            '',
                            '&'
                        ),
                        '#multiple' => true,
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'entries'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'entries'; },
                    ),
                    'num' => array(
                        '#type' => 'slider',
                        '#title' => sprintf(
                            __('Max number of random %s to fetch from the database', 'directories-faker'),
                            strtolower($taxonomy_bundle->getLabel()),
                            $taxonomy_bundle->getLabel()
                        ),
                        '#min_value' => 1,
                        '#max_value' => 100,
                        '#default_value' => $settings['num'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'random'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'random'; },
                    ),
                );
            case 'entity_terms':
                if (!$taxonomy_bundle = $field->getTaxonomyBundle()) return false;
                return array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'type' => array(
                        '#type' => 'select',
                        '#title' => $taxonomy_bundle->getLabel(),
                        '#options' => array(
                            'entries' => __('Select manually', 'directories-faker'),
                            'random' => __('Random', 'directories-faker'),
                        ),
                        '#default_value' => $settings['type'],
                    ),
                    'entries' => array(
                        '#type' => 'autocomplete',
                        '#title' => $taxonomy_bundle->getLabel('select'),
                        '#default_options_callback' => array(array($this, '_getDefaultOptions'), [$taxonomy_bundle->entitytype_name, $taxonomy_bundle->name]),
                        '#select2' => true,
                        '#select2_ajax' => true,
                        '#select2_item_text_key' => 'title',
                        '#select2_ajax_url' => $this->_application->MainUrl(
                            '/_drts/entity/' . $taxonomy_bundle->type . '/query',
                            array('bundle' => $taxonomy_bundle->name, Request::PARAM_CONTENT_TYPE => 'json'),
                            '',
                            '&'
                        ),
                        '#multiple' => true,
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'entries'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'entries'; },
                    ),
                    'num' => array(
                        '#type' => 'slider',
                        '#title' => sprintf(
                            __('Max number of random %s to fetch from the database', 'directories-faker'),
                            strtolower($taxonomy_bundle->getLabel()),
                            $taxonomy_bundle->getLabel()
                        ),
                        '#min_value' => 1,
                        '#max_value' => 1000,
                        '#step' => 10,
                        '#default_value' => $settings['num'],
                        '#states' => array(
                            'visible' => array(
                                sprintf('select[name="%s[type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'random'),
                            ),
                        ),
                        '#required' => function($form) use ($parents) { return $form->getValue(array_merge($parents, ['type'])) === 'random'; },
                    ),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                );
        }
    }
    
    public function _getDefaultOptions($defaultValue, array &$options, $entityType, $bundleName)
    {
        foreach ($this->_application->Entity_Types_impl($entityType)->entityTypeEntitiesByIds($defaultValue, $bundleName) as $entity) {
            $options[$entity->getId()] = $this->_application->Entity_Title($entity);
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {
        switch ($this->_name) {
            case 'entity_title':
                return str_replace('.', '', $this->_getFaker()->text(mt_rand($settings['length']['min'] + 1, $settings['length']['max'] + 1)));
            case 'entity_published':
                return $this->_generateTimestamp($settings['range']);
            case 'entity_activity':
                return array(array(
                    'active_at' => $this->_generateTimestamp($settings['active_at']),
                    'edited_at' => $this->_generateTimestamp($settings['edited_at']),
                ));
            case 'entity_views':
                return mt_rand($settings['range']['min'], $settings['range']['max']);
            case 'entity_author':
                if ($settings['type'] === 'none') return 0;
                if ($settings['type'] === 'users') {
                    return $settings['users'][mt_rand(0, count($settings['users']) - 1)];
                }
                if (!isset($this->_users)) {
                    if ($settings['type'] === 'admin') {
                        $this->_users = array_values($this->_application->getPlatform()->getAdministrators());
                    } else {
                        $this->_users = $this->_application
                            ->getPlatform()
                            ->getUserIdentityFetcher()
                            ->fetch($settings['num'], 0, 'timestamp', 'ASC');
                    }                    
                    $this->_userCount = count($this->_users);
                }
                return empty($this->_userCount) ? null : $this->_users[mt_rand(0, $this->_userCount - 1)]->id;  
            case 'entity_parent':                
                if (!isset($this->_ids[$this->_name])) {
                    if ($settings['type'] === 'entries') {
                        $this->_ids[$this->_name] = $settings['entries'];
                    } else {
                        if ((!$bundle = $this->_getParentBundle($field))
                            || (!$entity_type_impl = $this->_application->Entity_Types_impl($bundle->entitytype_name, true))
                        ) return false;
                        
                        $this->_ids[$this->_name] = $entity_type_impl->entityTypeRandomEntityIds($bundle->name, $settings['num']);
                    }
                    $this->_idCount[$this->_name] = count($this->_ids[$this->_name]);
                }
                return empty($this->_idCount[$this->_name]) ? null : $this->_ids[$this->_name][mt_rand(0, $this->_idCount[$this->_name] - 1)];  
            case 'entity_featured':
                if (mt_rand(0, 100) > $settings['probability']) return;
                
                return array(array(
                    'value' => $settings['priority'][array_rand($settings['priority'])],
                    'featured_at' => $featured_at = $this->_generateTimestamp($settings['featured_at']),
                    'expires_at' => $featured_at + (86400 * mt_rand($settings['days']['min'], $settings['days']['max'])),
                ));
            case 'entity_term_parent':
                if (mt_rand(0, 100) > $settings['probability']) return;
                
                if (!isset($this->_ids[$this->_name])) {
                    if ($settings['type'] === 'entries') {
                        $this->_ids[$this->_name] = $settings['entries'];
                    } else {
                        if ((!$entity_type_impl = $this->_application->Entity_Types_impl($field->Bundle->entitytype_name, true))
                            || !$entity_type_impl->entityTypeCount($field->Bundle->name)
                        ) return false;
                        
                        $this->_ids[$this->_name] = $entity_type_impl->entityTypeRandomEntityIds($field->Bundle->name, $settings['num']);
                    }
                    $this->_idCount[$this->_name] = count($this->_ids[$this->_name]);
                }
                if (!$this->_idCount[$this->_name]) return false;
                
                return $this->_ids[$this->_name][mt_rand(0, $this->_idCount[$this->_name] - 1)];
            case 'entity_terms':
                if (mt_rand(0, 100) > $settings['probability']) return;
                
                if (!$bundle = $field->getTaxonomyBundle()) return false;
                
                if (!isset($this->_ids[$this->_name][$bundle->name])) {
                    if ($settings['type'] === 'entries') {
                        $this->_ids[$this->_name][$bundle->name] = $settings['entries'];
                    } else {
                        if ((!$entity_type_impl = $this->_application->Entity_Types_impl($bundle->entitytype_name, true))
                            || !$entity_type_impl->entityTypeCount($bundle->name)
                        ) return false;
                        
                        $this->_ids[$this->_name][$bundle->name] = $entity_type_impl->entityTypeRandomEntityIds($bundle->name, $settings['num']);
                    }
                    $this->_idCount[$this->_name][$bundle->name] = count($this->_ids[$this->_name][$bundle->name]);
                }
                if (!$this->_idCount[$this->_name][$bundle->name]) return false;
                
                $ret = [];
                $count = $this->_getMaxNumItems($field, $settings['max']);
                for ($i = 0; $i < $count; ++$i) {
                    $id = $this->_ids[$this->_name][$bundle->name][mt_rand(0, $this->_idCount[$this->_name][$bundle->name] - 1)];
                    $ret[$id] = $id;
                }
                return array_values($ret);
        }
    }
    
    public function fakerGeneratorSupports(Entity\Model\Bundle $bundle, Field\IField $field)
    {
        switch ($this->_name) {
            case 'entity_parent':
                return (bool)$this->_getParentBundle($field);
            case 'entity_term_parent':
                return !empty($bundle->info['is_hierarchical']);
            case 'entity_terms':
                return ($bundle = $field->getTaxonomyBundle())
                    && true !== $this->_application->Entity_BundleTypeInfo($bundle, 'faker_disable_entity_terms');
        }
        return true;
    }
        
    protected function _getParentBundle($field)
    {        
        if (empty($field->Bundle->info['parent'])
            || (!$parent_bundle = $field->Bundle->info['parent'])
        ) {
            return false;
        }
        return $this->_application->Entity_Bundle($parent_bundle);
    }
}