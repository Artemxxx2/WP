<?php
namespace SabaiApps\Directories\Component\Faker\Generator;

use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\WordPressContent;
use SabaiApps\Directories\Request;

class WPGenerator extends AbstractGenerator
{
    protected $_ids = [], $_idCount = [];
    
    protected function _fakerGeneratorInfo()
    {
        $info = parent::_fakerGeneratorInfo();
        switch ($this->_name) {
            case 'wp_image':
            case 'wp_file':
                $info['default_settings'] = array(
                    'probability' => 100,
                    'max' => null,
                    'ids' => null,
                );
                $info['description'] = __('Select images/files from WordPress media manager.', 'directories-faker');
                break;
            case 'wp_post_content':
                $info['default_settings'] = array(
                    'length' => array('min' => 300, 'max' => 1000),
                );
                break;
            case 'wp_term_description':
                $info['default_settings'] = array(
                    'length' => array('min' => 200, 'max' => 500),
                );
                break;
            case 'wp_post_parent':
                $info['default_settings'] = array(
                    'type' => 'random',
                    'entries' => null,
                    'num' => 100,
                );
                break;
        }
        return $info;
    }
    
    public function fakerGeneratorSupports(Entity\Model\Bundle $bundle, Field\IField $field)
    {
        switch ($this->_name) {
            case 'wp_post_parent':
                return !empty($bundle->info['parent']);
        }
        return true;
    }
    
    public function fakerGeneratorSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        switch ($this->_name) {
            case 'wp_image':
            case 'wp_file':
                $form = array(
                    'probability' => $this->_getProbabilitySettingForm($settings['probability']),
                    'max' => $this->_getMaxNumItemsSettingForm($field, $settings['max']),
                    'ids' => array(
                        '#title' => __('Select files to attach', 'directories-faker'),
                        '#type' => current_user_can('upload_files') ? 'wp_media_manager' : 'wp_upload',
                        '#multiple' => true,
                    ),
                );
                if ($this->_name === 'wp_image') {
                    $form['ids']['#allow_only_images'] = true;
                } else {
                    $widget_settings = $field->getFieldWidgetSettings();
                    if (empty($widget_settings['allowed_extensions'])) return;
                
                    if (current_user_can('upload_files')) {
                        $extensions = $widget_settings['allowed_extensions'];
                    } else {
                        $extensions = [];
                        foreach ($widget_settings['allowed_extensions'] as $ext) {
                            if (strpos($ext, '|')) {
                                if ($ext === WordPressContent\FieldWidget\FileFieldWidget::$txtExtensions) {
                                    $extensions[] = 'txt';
                                } else {
                                    foreach (explode('|', $ext) as $_ext) {
                                        $extensions[] = $_ext;
                                    }
                                }
                            } else {
                                $extensions[] = $ext;
                            }
                        } 
                    }
                    $form['ids']['#allowed_extensions'] = $extensions;
                }
                return $form;
            case 'wp_post_content':
            case 'wp_term_description':
                return array(
                    'length' => array(
                        '#type' => 'range',
                        '#title' => __('Text length range', 'directories-faker'),
                        '#integer' => true,
                        '#min_value' => 100,
                        '#max_value' => 1000,
                        '#step' => 50,
                        '#default_value' => $settings['length'],
                    ),
                );
            case 'wp_post_parent':
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
        }
    }
    
    public function fakerGeneratorGenerate(Field\IField $field, array $settings, array &$values, array &$formStorage)
    {        
        switch ($this->_name) {
            case 'wp_image':
            case 'wp_file':
                if (empty($settings['ids'])) return false;
                
                if (mt_rand(0, 100) > $settings['probability']) return;
                
                $ret = [];
                $num = $this->_getMaxNumItems($field, $settings['max']);
                $max_index = count($settings['ids']) - 1;
                for ($i = 0; $i < $num; ++$i) {
                    $index = mt_rand(0, $max_index);
                    $ret[$index] = $settings['ids'][$index];
                }
                return empty($ret) ? null : array_values($ret);
            case 'wp_post_content':
            case 'wp_term_description':
                return str_replace("\n", "\n\n", $this->_getFaker()->text(mt_rand($settings['length']['min'], $settings['length']['max'])));
            case 'wp_post_parent':                
                if (!isset($this->_ids[$this->_name])) {
                    if ($settings['type'] === 'entries') {
                        $this->_ids[$this->_name] = $settings['entries'];
                    } else {
                        if (!$entity_type_impl = $this->_application->Entity_Types_impl('post', true)) return false;
                        
                        $this->_ids[$this->_name] = $entity_type_impl->entityTypeRandomEntityIds($field->Bundle->info['parent'], $settings['num']);
                    }
                    $this->_idCount[$this->_name] = count($this->_ids[$this->_name]);
                }
                return empty($this->_idCount[$this->_name]) ? null : $this->_ids[$this->_name][mt_rand(0, $this->_idCount[$this->_name] - 1)];  
        }
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
    
    public function _getDefaultOptions($defaultValue, array &$options, $entityType, $bundleName)
    {
        foreach ($this->_application->Entity_Types_impl($entityType)->entityTypeEntitiesByIds($defaultValue, $bundleName) as $entity) {
            $options[$entity->getId()] = $this->_application->Entity_Title($entity);
        }
    }
}