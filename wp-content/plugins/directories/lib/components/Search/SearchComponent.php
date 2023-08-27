<?php
namespace SabaiApps\Directories\Component\Search;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;

class SearchComponent extends AbstractComponent implements
    System\IMainRouter,
    IFields
{
    const VERSION = '1.3.108', PACKAGE = 'directories',
        FORM_PARAM_PREFIX = 'search_', FORM_SEARCH_PARAM_NAME = 'drts-search';
    
    public static function description()
    {
        return 'Adds a feature rich search form to your site.';
    }
    
    public function systemMainRoutes($lang = null)
    {
        $routes = [];
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$this->_application->isComponentLoaded($bundle->component)
                || empty($bundle->info['search_enable'])
            ) continue;
            
            $routes[$bundle->getPath(false, $lang) . '/search'] = array(
                'controller' => 'Search',
                'title_callback' => true,
                'access_callback' => true,
                'callback_path' => 'search',
                'callback_component' => 'Search',
                'priority' => 3,
            );
        }
        
        return $routes;
    }
    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'search':
                return isset($context->bundle);
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'search':
                return $context->bundle->getLabel('search');
        }
    }
    
    public function searchGetFieldNames()
    {
        $names = array('keyword');
        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (empty($bundle->info['is_taxonomy'])
                || empty($bundle->info['is_hierarchical'])
            ) continue;
            
            $names[] = 'term_' . $bundle->type;
        }
        return $names;
    }
    
    public function searchGetField($name)
    {
        if ($name === 'keyword') {
            return new Field\KeywordField($this->_application, $name);
        }
        if (strpos($name, 'term_') === 0) {
            return new Field\TermField($this->_application, $name, substr($name, 5));
        }
    }
    
    public function onEntityDeleteBundlesSuccess($bundles)
    {
        foreach ($bundles as $bundle) {       
            $this->_application->getPlatform()->deleteOption('search_search_' . $bundle->name);
        }
    }
    
    public function onViewEntities($bundle, $query, $context)
    {        
        if (empty($context->is_default_view)
            || !$context->getRequest()->has(self::FORM_SEARCH_PARAM_NAME)
            || empty($bundle->info['search_enable'])
            || isset($context->entity) // this should not happen, but just in case
            || (!$params = $this->_application->Search_Form_params($context))
            || (!$form = $this->_application->callHelper('Search_Form_query', [$bundle, $query, $params, $context->sort, &$context->sorts]))
        ) return;
        
        if ($form->hasError()) {
            foreach ($form->getError() as $field_name => $error) {
                $log = 'Search form error: ' . $error;
                if ($field_name) $log .= ' (' . $field_name . ')';
                $this->_application->logError($log);
            }
            return;
        }
        
        $context->search_values = $form->values;
        $context->search_params = $form->search_params;
        $context->search_labels = $form->search_labels;
        $context->url_params += [self::FORM_SEARCH_PARAM_NAME => $context->getRequest()->get(self::FORM_SEARCH_PARAM_NAME)] + $form->search_params;

        if (!empty($form->search_labels)) {
            $context->setTitle(sprintf($this->_application->H(__('Search results for: %s', 'directories')), $this->_application->Search_Form_renderSearchLabels($form->search_labels)));
        }
    }
    
    public function onDirectoryShortcodesFilter(&$shortcodes)
    {
        $shortcodes['search'] = '/search';
    }
    
    public function onDirectoryContentTypeSettingsFormFilter(&$form, $directoryType, $contentType, $info, $settings, $parents, $submitValues)
    {        
        if (empty($info['search_enable'])
            || empty($info['is_primary'])
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
            || empty($info['public'])
        ) return;
        
        $form['search_enable'] = array(
            '#type' => 'checkbox',
            '#title' => __('Enable search', 'directories'),
            '#default_value' => !empty($settings['search_enable']) || is_null($settings),
            '#horizontal' => true,
            '#weight' => 30,
        );
    }
    
    public function onDirectoryContentTypeInfoFilter(&$info, $contentType, $settings = null)
    {        
        if (!isset($info['search_enable'])) return;
        
        if (empty($info['is_primary'])
            || !empty($info['is_taxonomy'])
            || !empty($info['parent'])
            || empty($info['public'])
        ) {
            unset($info['search_enable']);
        }
        
        if (isset($settings['search_enable']) && !$settings['search_enable']) {
            $info['search_enable'] = false;
        }
    }
    
    public function onEntityBundleInfoKeysFilter(&$keys)
    {
        $keys[] = 'search_enable';
    }

    public function onEntityBundleInfoUserKeysFilter(&$keys)
    {
        $keys[] = 'search_fields';
    }
    
    public function onEntityBundleSettingsFormFilter(array &$form, Entity\Model\Bundle $bundle, $submitValues)
    {
        if (empty($bundle->info['search_enable'])) return;

        // Add search settings
        $form['#tabs'][$this->_name] = array(
            '#title' => __('Search', 'directories'),
            '#weight' => 20,
        );
        $form['search_fields'] = array(
            '#tab' => $this->_name,
            '#fields' => [],
            '#tree' => true,
            '#element_validate' => array(array(array($this, '_validateBundleSettings'), array($bundle))),
        );
        $settings = empty($bundle->info['search_fields']) ? [] : $bundle->info['search_fields'];
        foreach (array_keys($this->_application->Search_Fields()) as $field_name) {
            $field_settings_form = $this->_application->Search_Fields_settingsForm(
                $bundle,
                $field_name,
                isset($settings[$field_name]) ? $settings[$field_name] : [],
                array('search_fields', $field_name)
            );
            if (!$field_settings_form) continue;
            
            $form['search_fields'][$field_name] = $field_settings_form;
            $form['search_fields']['#fields'][] = $field_name;
        }
        // Add label setting
        $form['general']['labels']['label_search'] = array(
            '#type' => 'textfield',
            '#title' => __('Search items label', 'directories'),
            '#default_value' => $bundle->getLabel('search'),
            '#horizontal' => true,
            '#placeholder' => $this->_application->Entity_BundleTypeInfo($bundle, 'label_search'),
            '#required' => true,
        );
    }
    
    public function _validateBundleSettings(Form\Form $form, &$value, array $element, Entity\Model\Bundle $bundle)
    {
        // Register strings for translation
        foreach ($form->settings['search_fields']['#fields'] as $field_name) {
            if (isset($form->settings['search_fields'][$field_name]['settings']['form']['label'])) {
                $this->_application->getPlatform()->registerString(
                    $value[$field_name]['settings']['form']['label'],
                    $bundle->name . '_' . $field_name . '_field_label',
                    'search'
                );
            }
            if (isset($form->settings['search_fields'][$field_name]['settings']['form']['placeholder'])) {
                $this->_application->getPlatform()->registerString(
                    $value[$field_name]['settings']['form']['placeholder'],
                    $bundle->name . '_' . $field_name . '_field_placeholder',
                    'search'
                );
            }
        }
    }
}
