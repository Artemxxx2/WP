<?php
namespace SabaiApps\Directories\Component\Search\Field;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Entity\Type\Query;

class TermField extends AbstractField
{
    protected $_bundleType;
    
    public function __construct(Application $application, $name, $bundleType)
    {
        parent::__construct($application, $name);
        $this->_bundleType = $bundleType;
    }
    
    protected function _searchFieldInfo()
    {
        return [
            'label' => sprintf(
                _x('%s Selection Search', 'search settings label', 'directories'),
                $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'label_singular')
            ),
            'weight' => 3,
            'default_settings' => [
                'disabled' => false,
                'hide_empty' => false,
                'hide_count' => false,
                'depth' => 0,
                'no_fancy' => true,
                'form' => [
                    'order' => 3,
                ],
            ],
        ];
    }
    
    public function searchFieldSupports(Bundle $bundle)
    {
        return isset($bundle->info['taxonomies'][$this->_bundleType]);
    }
    
    public function searchFieldSettingsForm(Bundle $bundle, array $settings, array $parents = [])
    {
        $ret = [
            'depth' => [
                '#type' => 'slider',
                '#title' => __('Depth of term hierarchy tree', 'directories'),
                '#default_value' => $settings['depth'],
                '#min_value' => 0,
                '#max_value' => 10,
                '#min_text' => __('Unlimited', 'directories'),
                '#integer' => true,
                '#weight' => 1,
                '#horizontal' => true,
            ],
            'hide_empty' => [
                '#type' => 'checkbox',
                '#title' => __('Hide empty terms', 'directories'),
                '#default_value' => !empty($settings['hide_empty']),
                '#horizontal' => true,
                '#weight' => 6,
            ],
            'hide_count' => [
                '#type' => 'checkbox',
                '#title' => __('Hide post counts', 'directories'),
                '#default_value' => !empty($settings['hide_count']),
                '#horizontal' => true,
                '#weight' => 7,
            ],
            'no_fancy' => [
                '#type' => 'checkbox',
                '#title' => __('Disable fancy dropdown', 'directories'),
                '#default_value' => !empty($settings['no_fancy']),
                '#weight' => 8,
                '#horizontal' => true,
            ],
        ];
        
        return $ret;
    }
    
    public function searchFieldForm(Bundle $bundle, array $settings, $request = null, array $requests = null, array $parents = [])
    {
        $taxonomy_bundle = $this->_application->Entity_Bundle($this->_bundleType, $bundle->component, $bundle->group);
        $current_term_id = null;
        if (isset($GLOBALS['drts_entity']) && $GLOBALS['drts_entity']->getBundleType() === $this->_bundleType) {
            $current_term_id = $GLOBALS['drts_entity']->getId();
        } else {
            if (isset($settings['default_term'])
                && ($default_term = trim($settings['default_term']))
            ) {
                if (!is_numeric($default_term)) {
                    if ($current_term = $this->_application->Entity_Entity($taxonomy_bundle->entitytype_name, [$taxonomy_bundle->name, $default_term])) {
                        $current_term_id = $current_term->getSlug();
                    }
                } else {
                    $current_term_id = $default_term;
                }
            }
        }
        $_options = [
            'depth' => $settings['depth'],
            'hide_empty' => !empty($settings['hide_empty']),
            'hide_count' => !empty($settings['hide_count']),
            'prefix' => '—',
            'parent' => isset($settings['parent']) ? (int)$settings['parent'] : 0
        ];

        $options = $this->_application->Entity_TaxonomyTerms_html(
            $taxonomy_bundle->name,
            ['content_bundle' => $bundle->type, 'count_no_html' => true] + $_options
        );
        if (!count($options)) return;

        $default_text = $taxonomy_bundle->getLabel('select');
        $attributes = ['data-component' => $bundle->component];
        
        $form = [
            '#type' => 'select',
            '#select2' => empty($settings['no_fancy']),
            '#placeholder' => $default_text,
            '#options' => ['' => $default_text] + $options,
            '#default_value' => isset($current_term_id) ? $current_term_id : $request,
            '#attributes' => isset($attributes) ? $attributes : [],
            '#required' => !empty($settings['required']),
            '#error_no_output' => true,
            '#empty_value' => '', // required for required validation to work
            //'#multiselect' => true,
            //'#multiple' => true,
        ];
        
        return $form;
    }
    
    public function searchFieldIsSearchable(Bundle $bundle, array $settings, &$value, array $requests = null)
    {
        return is_array($value) ? !empty($value) : $value !== '';
    }
    
    public function searchFieldSearch(Bundle $bundle, Query $query, array $settings, $value, $sort, array &$sorts)
    {
        $query->taxonomyTermIdIn(
            $this->_bundleType,
            is_array($value) ? $value : [$value],
            !$this->_application->Entity_BundleTypeInfo($this->_bundleType, 'is_hierarchical'),
            $this->_bundleType . '_search_term', // table alias
            $this->_bundleType . '_search_term' // criteria name
        );
    }
    
    public function searchFieldLabels(Bundle $bundle, array $settings, $value)
    {
        $titles = [];
        $entity_type = $this->_application->Entity_BundleTypeInfo($this->_bundleType, 'entity_type');
        foreach ($this->_application->Entity_Entities($entity_type, (array)$value, false) as $entity) {
            $titles[] = $entity->getTitle();
        }
        return [$titles];
    }
}