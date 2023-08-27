<?php
namespace SabaiApps\Directories\Component\View;

use SabaiApps\Directories\Component\AbstractComponent;
use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Component\System;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Application;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Exception;

class ViewComponent extends AbstractComponent implements
    Display\IElements,
    System\IMainRouter,
    System\IAdminRouter,
    IModes,
    Field\IFilters
{
    const VERSION = '1.3.108', PACKAGE = 'directories';

    public static function description()
    {
        return 'Provides functions and features to display content in various formats and layouts.';
    }

    public function getDefaultConfig()
    {
        return [
            'filters' => [
                'facet_count' => true,
                'btn_icon' => 'fas fa-sliders-h',
                'btn_label' => '',
            ],
        ];
    }

    public function systemMainRoutes($lang = null)
    {
        $routes = [];

        foreach ($this->_application->Entity_Bundles() as $bundle) {
            if (!$this->_application->isComponentLoaded($bundle->component)) continue;

            if (empty($bundle->info['parent'])
                && empty($bundle->info['internal'])
            ) {
                $routes[$bundle->getPath(false, $lang)] = array(
                    'controller' => 'ViewEntities',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => empty($bundle->info['is_taxonomy']) ? 'entities' : 'taxonomy_entities',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                        'component' => $bundle->component,
                        'bundle_group' => $bundle->group,
                    ),
                    'priority' => 3,
                );
                $routes[$bundle->getPath(true, $lang) . '/:slug'] = array(
                    'controller' => empty($bundle->info['is_taxonomy']) ? 'ViewEntity' : 'ViewEntities',
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => empty($bundle->info['is_taxonomy']) ? 'entity' : 'taxonomy_entity',
                    'format' => array(':slug' => '[a-z0-9~\.:_\-%]+'),
                    'priority' => 3,
                );
            } elseif (!empty($bundle->info['parent'])) {
                if (!$this->_application->Entity_Bundle($bundle->info['parent'])) return $routes;

                $permalink_path = $bundle->getPath(true, $lang);
                $routes[$bundle->getPath(false, $lang)] = array(
                    'controller' => 'ViewEntities',
                    'access_callback' => true,
                    'callback_path' => 'child_entities',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                        'component' => $bundle->component,
                        'bundle_group' => $bundle->group,
                        'embed_only' => true,
                    ),
                    'priority' => 3,
                );
                $routes[$permalink_path] = array(
                    'controller' => 'ViewEntities',
                    'access_callback' => true,
                    'callback_path' => 'child_entities',
                    'data' => array(
                        'bundle_type' => $bundle->type,
                        'component' => $bundle->component,
                        'bundle_group' => $bundle->group,
                        'embed_only' => empty($bundle->info['public']),
                    ),
                    'priority' => 3,
                );
                $routes[$permalink_path . '/:entity_id'] = array(
                    'controller' => 'ViewEntity',
                    'format' => array(':entity_id' => '\d+'),
                    'access_callback' => true,
                    'title_callback' => true,
                    'callback_path' => 'child_entity',
                    'priority' => 3,
                );
            } elseif (!empty($bundle->info['internal'])) {
                $routes[$bundle->getPath(false, $lang)] = [
                    'controller' => 'ViewEntities',
                    'access_callback' => true,
                    'callback_path' => 'internal_entities',
                    'data' => [
                        'bundle_type' => $bundle->type,
                        'component' => $bundle->component,
                        'bundle_group' => $bundle->group,
                        'embed_only' => true,
                    ],
                    'priority' => 3,
                ];
            }
        }

        return $routes;
    }

    protected function _getBundle(array $route)
    {
        return $this->_application->Entity_Bundle($route['data']['bundle_type'], $route['data']['component'], $route['data']['bundle_group']);
    }

    public function systemOnAccessMainRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'entities':
            case 'internal_entities':
                if (!$bundle = $this->_getBundle($route)) return false;

                $context->bundle = $bundle;

                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    // Set the default error URL
                    $context->setErrorUrl($route['path']);

                    return true;
                } else {
                    return $this->_application->Entity_IsRoutable($bundle, 'list');
                }
            case 'taxonomy_entities':
                if (!$bundle = $this->_getBundle($route)) return false;

                $context->taxonomy_bundle = $bundle;

                return true;
            case 'child_entities':
                if (!$bundle = $this->_getBundle($route)) return false;

                $context->child_bundle = $bundle;

                if ($accessType === Application::ROUTE_ACCESS_LINK) return true;

                return $this->_application->Entity_IsRoutable($bundle, 'list', isset($context->entity) ? $context->entity : null);
            case 'entity':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $slug = $context->getRequest()->asStr('slug');
                    if (!strlen($slug)
                        || (!$slug = rawurldecode($slug))
                        || (!$entity = $this->_application->Entity_Types_impl($context->bundle->entitytype_name)->entityTypeEntityBySlug($context->bundle->name, $slug))
                    ) {
                        if (!is_numeric($slug)
                            || (!$entity = $this->_application->Entity_Types_impl($context->bundle->entitytype_name)->entityTypeEntityById($slug))
                        ) {
                            $context->setNotFoundError();
                            return false;
                        }
                    }

                    if (!$this->_application->Entity_IsRoutable($context->bundle, 'link', $entity)) {
                        // Suppress error message by default
                        $error = $this->_application->Filter('view_entity_unroutable_error', '', [$entity, $this->_application->getUser(),  'link']);
                        if ($this->_application->getUser()->isAnonymous()) {
                            $context->setUnauthorizedError(null, $error);
                        } else {
                            $context->setError($error);
                        }
                        return false;
                    }

                    $this->_application->Entity_Field_load($entity);
                    $route['permalink_url'] = $this->_application->Entity_PermalinkUrl($entity);
                    $context->entity = $entity;
                    if (in_array($context->getContainer(), ['#drts-content', '#drts-modal'])) {
                        $GLOBALS['drts_entity'] = $entity;
                    }
                    return true;
                } else {
                    if (!$this->_application->Entity_IsRoutable($context->bundle, 'view', $context->entity)) {
                        // Suppress error message by default
                        $error = $this->_application->Filter('view_entity_unroutable_error', '', [$context->entity, $this->_application->getUser(), 'view']);
                        if ($this->_application->getUser()->isAnonymous()) {
                            $context->setUnauthorizedError(null, $error);
                        } else {
                            $context->setError($error);
                        }
                        return false;
                    }
                    return true;
                }
            case 'taxonomy_entity':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    $slug = $context->getRequest()->asStr('slug');
                    if (!strlen($slug)
                        || (!$slug = rawurldecode($slug))
                        || (!$entity = $this->_application->Entity_Types_impl($context->taxonomy_bundle->entitytype_name)->entityTypeEntityBySlug($context->taxonomy_bundle->name, $slug))
                        || !$entity->isPublished()
                    ) {
                        if (!is_numeric($slug)
                            || (!$entity = $this->_application->Entity_Types_impl($context->taxonomy_bundle->entitytype_name)->entityTypeEntityById($slug))
                            || !$entity->isPublished()
                        ) {
                            $context->setNotFoundError();
                            return false;
                        }
                    }
                    $this->_application->Entity_Field_load($entity);
                    $route['permalink_url'] = $this->_application->Entity_PermalinkUrl($entity);
                    $context->entity = $entity;
                    if (in_array($context->getContainer(), [
                        '#drts-content',
                        '#drts-content-view-term-entities', // requested via AJAX on single term page
                    ])) {
                        $GLOBALS['drts_entity'] = $entity;
                    }
                    $context->_taxonomy_bundle = $context->taxonomy_bundle;
                    unset($context->taxonomy_bundle);
                }
                return true;
            case 'child_entity':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$id = $context->getRequest()->asInt('entity_id'))
                        || (!$entity = $this->_application->Entity_Types_impl($context->child_bundle->entitytype_name)->entityTypeEntityById($id))
                        || $entity->getBundleName() !== $context->child_bundle->name
                    ) {
                        $context->setNotFoundError();
                        return false;
                    }

                    if (!$this->_application->Entity_IsRoutable($context->child_bundle, 'link', $entity)) {
                        $context->setError($this->_application->Filter('view_entity_unroutable_error', '', [$entity, 'link'])); // suppress error message by default
                        return false;
                    }

                    $this->_application->Entity_Field_load($entity);
                    $context->entity = $entity;
                    if ($context->getContainer() === '#drts-content') {
                        $GLOBALS['drts_entity'] = $entity;
                    }
                    return true;
                } else {
                    if (!$this->_application->Entity_IsRoutable($context->child_bundle, 'view', $context->entity)) {
                        $context->setError($this->_application->Filter('view_entity_unroutable_error', '', [$context->entity, 'view'])); // suppress error message by default
                        return false;
                    }
                    return true;
                }
        }
    }

    public function systemMainRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'entities':
                return $titleType === Application::ROUTE_TITLE_TAB
                    ? $context->bundle->getLabel()
                    : $this->_application->getComponent($context->bundle->component)->getTitle($context->bundle->group);
            case 'taxonomy_entities':
                // taxonomy_bundle is null if viewing single term and called for breadcrumb
                return $context->taxonomy_bundle ? $context->taxonomy_bundle->getLabel() : $context->_taxonomy_bundle->getLabel();
            case 'entity':
            case 'taxonomy_entity':
                return $this->_application->Entity_Title($context->entity);
            case 'child_entity':
                // Child entities do not have an archive index, so render with bundle label
                return sprintf($context->child_bundle->getLabel('page'), $this->_application->Entity_Title($context->entity));
        }
    }

    protected function _hasSupportedFilter(array $filters, Field\IField $field)
    {
        foreach (array_keys($filters) as $filter_type) {
            if (($filter = $this->_application->Field_Filters_impl($filter_type, true))
                && $filter->fieldFilterSupports($field)
            ) return true;
        }
        return false;
    }

    public function displayGetElementNames(Entity\Model\Bundle $bundle)
    {
        $ret = [];
        $field_types = $this->_application->Field_Types();
        foreach ($this->_application->Entity_Field($bundle->name) as $field) {
            if (!$field_type = @$field_types[$field->getFieldType()]) {
                continue;
            }
            if (isset($field_type['entity_types'])
                && !in_array($bundle->entitytype_name, $field_type['entity_types'])
            ) {
                // the field type does not support the entity type of the current bundle
                continue;
            }
            if (isset($field_type['bundles'])
                && !in_array($bundle->type, $field_type['bundles'])
            ) {
                // the field type does not support the current bundle type
                continue;
            }

            if (!empty($bundle->info['no_title'])
                && $field->getFieldType() === 'entity_title'
            ) continue;

            if (!empty($field_type['filters'])
                && $this->_hasSupportedFilter($field_type['filters'], $field)
            ) {
                $ret[] = 'view_filter_' . $field->getFieldName();
            }
        }

        if (empty($bundle->info['is_taxonomy'])) {
            if (empty($bundle->info['parent'])) {
                foreach ($this->_application->Entity_BundleTypes_children($bundle->type) as $bundle_type) {
                    if (!$this->_application->Entity_Bundle($bundle_type, $bundle->component, $bundle->group)) continue;

                    $ret[] = 'view_child_entities_' . $bundle_type;
                }
                foreach ($this->_application->Entity_Bundles_referencing($bundle->name) as $field_name => $bundle_names) {
                    foreach ($bundle_names as $bundle_name) {
                        $ret[] = 'view_referencing_entities_' . $bundle_name . '-' . $field_name;
                    }
                }
            }
        } else {
            foreach ($this->_application->Entity_TaxonomyContentBundleTypes($bundle->type) as $content_bundle_type) {
                $ret[] = 'view_term_entities_' . $content_bundle_type;
            }
        }

        return $ret;
    }

    public function displayGetElement($name)
    {
        if (strpos($name, 'view_filter_') === 0) {
            return new DisplayElement\FilterDisplayElement($this->_application, $name);
        } elseif (strpos($name, 'view_child_entities_') === 0) {
            return new DisplayElement\ChildEntitiesDisplayElement($this->_application, $name);
        } elseif (strpos($name, 'view_referencing_entities_') === 0) {
            return new DisplayElement\ReferencingEntitiesDisplayElement($this->_application, $name);
        } elseif (strpos($name, 'view_term_entities_') === 0) {
            return new DisplayElement\TermEntitiesDisplayElement($this->_application, $name);
        }
    }

    public function viewGetModeNames()
    {
        return ['list', 'masonry'];
    }

    public function viewGetMode($name)
    {
        switch ($name) {
            case 'list':
                return new Mode\ListMode($this->_application, $name);
            case 'masonry':
                return new Mode\MasonryMode($this->_application, $name);
        }
    }

    public function systemAdminRoutes()
    {
        $routes = [];
        foreach (array_keys($this->_application->Entity_BundleTypes()) as $bundle_type) {
            if ((!$admin_path = $this->_application->Entity_BundleTypeInfo($bundle_type, 'admin_path'))
                || isset($routes[$admin_path . '/views']) // path added already
                || $this->_application->Entity_BundleTypeInfo($bundle_type, 'internal')
            ) continue;

            $routes += array(
                $admin_path . '/views' => array(
                    'controller' => 'Views',
                    'title_callback' => true,
                    'access_callback' => true,
                    'callback_path' => 'views',
                    'type' => Application::ROUTE_TAB,
                    'weight' => 15,
                ),
                $admin_path . '/views/:view_id' => array(
                    'controller' => 'EditView',
                    'title_callback' => true,
                    'access_callback' => true,
                    'callback_path' => 'edit_view',
                    'format' => array(':view_id' => '\d+'),
                ),
                $admin_path . '/views/:view_id/delete' => array(
                    'controller' => 'DeleteView',
                    'title_callback' => true,
                    'callback_path' => 'delete_view',
                ),
                $admin_path . '/views/:view_id/clone' => array(
                    'controller' => 'CloneView',
                    'title_callback' => true,
                    'callback_path' => 'clone_view',
                ),
                $admin_path . '/views/add' => array(
                    'controller' => 'AddView',
                    'title_callback' => true,
                    'callback_path' => 'add_view',
                    'type' => Application::ROUTE_MENU,
                    'data' => array(
                        'link_options' => array(
                            'icon' => 'fas fa-plus',
                            'container' => 'modal',
                            'modalSize' => 'xl',
                            'cache' => 'drts-view-add-view',
                        ),
                        'link_attr' => array(
                            'class' => DRTS_BS_PREFIX . 'btn ' . DRTS_BS_PREFIX . 'btn-success',
                        ),
                    ),
                    'weight' => 1,
                ),
                $admin_path . '/views/filters' => array(
                    'controller' => 'Filters',
                    'title_callback' => true,
                    'access_callback' => true,
                    'callback_path' => 'filters',
                    'type' => Application::ROUTE_MENU,
                    'weight' => 2,
                ),
                $admin_path . '/views/set_default' => array(
                    'controller' => 'SetDefaultView',
                    'type' => Application::ROUTE_CALLBACK,
                    'method' => 'post',
                ),
            );
        }
        return $routes;
    }

    public function systemOnAccessAdminRoute(Context $context, $path, $accessType, array &$route)
    {
        switch ($path) {
            case 'views':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    return !empty($context->bundle->info['public'])
                        && empty($context->bundle->info['internal']);
                }
                return true;
            case 'edit_view':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    if ((!$view_id = $context->getRequest()->asInt('view_id'))
                        || (!$view = $this->getModel('View')->fetchById($view_id))
                        || $view->bundle_name !== $context->bundle->name
                    ) return false;

                    // Compat for <1.2.42
                    if ($view->mode === 'photoslider') {
                        $view->mode = 'slider_photos';
                        $view->commit();
                    }

                    $context->view = $view;
                }
                return true;
            case 'filters':
                if ($accessType === Application::ROUTE_ACCESS_LINK) {
                    return $this->isFilterable($context->bundle);
                }
                return true;
        }
    }

    public function systemAdminRouteTitle(Context $context, $path, $titleType, array $route)
    {
        switch ($path) {
            case 'views':
                return __('Manage Views', 'directories');
            case 'edit_view':
                return $context->view->getLabel();
            case 'delete_view':
                return __('Delete View', 'directories');
            case 'clone_view':
                return __('Clone View', 'directories');
            case 'add_view':
                return __('Add View', 'directories');
            case 'filters':
                return __('Manage Filters', 'directories');
        }
    }

    public function fieldGetFilterNames()
    {
        return array('view_term_list', 'view_term_select', 'view_glossary');
    }

    public function fieldGetFilter($name)
    {
        switch ($name) {
            case 'view_term_list':
                return new FieldFilter\TermListFieldFilter($this->_application, $name);
            case 'view_term_select':
                return new FieldFilter\TermSelectFieldFilter($this->_application, $name);
            case 'view_glossary':
                return new FieldFilter\GlossaryFieldFilter($this->_application, $name);
        }
    }

    public function isFilterable($bundle)
    {
        $bool = !empty($bundle->info['public'])
            && empty($bundle->info['is_taxonomy'])
            && empty($bundle->info['internal']);
        return $this->_application->Filter('view_is_filterable', $bool, [$bundle]);
    }

    public function onEntityBundleAdminLinksFilter(&$links, $bundle)
    {
        if (empty($bundle->info['public'])
            || !empty($bundle->info['internal'])
        ) return;

        $links['views'] = array(
            'weight' => 15,
            'link' => array(
                'path' => '/views',
                'label' => __('Manage Views', 'directories'),
            ),
        );
    }

    public function onDirectoryShortcodesFilter(&$shortcodes)
    {
        $shortcodes['view'] = ['path' => ''];
    }

    public function onDirectoryDoShortcodeFilter(&$ret, $name, $bundle)
    {
        if ($name !== 'view') return;

        $ret['class'] = 'drts-view-shortcode';

        if (!isset($ret['atts']['name'])
            || !strlen($ret['atts']['name'] = trim($ret['atts']['name']))
        ) {
            // Default view, so no atts should be passed
            $ret['atts'] = [];
            return;
        }

        // Not a default view, fetch settings
        if ($view = $this->getModel('View')->bundleName_is($bundle->name)->name_is($ret['atts']['name'])->fetchOne()) {
            $ret['atts']['mode'] = $view->mode;
            $ret['atts']['settings'] = $view->data['settings'];
            $ret['atts']['is_default_view'] = (bool)$view->default;
        } else {
            throw new Exception\RuntimeException('Directory shortcode [view]: invalid "name" attribute.');
        }
        unset($ret['atts']['view']);

        // Override with custom settings if any
        foreach (array_keys($ret['atts']) as $key) {
            if (!$pos = strpos($key, '_')) continue;

            $value = trim($ret['atts'][$key]);
            $type = substr($key, 0, $pos);
            $_key = substr($key, $pos + 1);
            switch ($key) {
                case 'sort_default':
                case 'sort_secondary':
                case 'filter_display':
                    $ret['atts']['settings'][$type][$_key] = $value;
                    break;
                case 'pagination_no_pagination':
                case 'pagination_allow_perpage':
                case 'filter_show':
                case 'filter_shown':
                case 'filter_show_modal':
                case 'sort_stick_featured':
                case 'sort_stick_featured_term_only':
                case 'sort_payment_plans':
                    $ret['atts']['settings'][$type][$_key] = empty($value) ? 0 : 1;
                    break;
                case 'pagination_perpage':
                case 'query_limit':
                    $ret['atts']['settings'][$type][$_key] = (int)$value;
                    break;
                case 'sort_options':
                    if ($value = explode(',', $value)) {
                        $ret['atts']['settings'][$type][$_key] = $value;
                    }
                    break;
                case 'pagination_perpages':
                case 'sort_payment_plans_order':
                    if ($value = explode(',', $value)) {
                        $ret['atts']['settings'][$type][$_key] = array_filter(array_map('intval', $value));
                    }
                    break;
                case 'query_fields':
                    parse_str(str_replace('&amp;', '&', $value), $fields);
                    if (!empty($fields)) {
                        foreach ($fields as $field_name => $query_str) {
                            if (is_array($query_str)) $query_str = array_pop($query_str);
                            $ret['atts']['settings'][$type][$_key][$field_name] = $query_str;
                        }
                    }
                    break;
                default:
            }
        }
    }

    public function onEntityDeleteBundlesCommitted(array $bundles, $deleteContent)
    {
        // Delete views
        foreach ($this->getModel('View')->bundleName_in(array_keys($bundles))->fetch() as $view) {
            $view->markRemoved();
        }
        // Delete filters
        foreach ($this->getModel('Filter')->bundleName_in(array_keys($bundles))->fetch() as $filter) {
            $filter->markRemoved();
        }
        $this->getModel()->commit();
    }

    public function onViewEntities($bundle, $query, $context)
    {
        if (!isset($context->entity) || !$context->entity->isTaxonomyTerm()) return;

        if ($this->_application->Filter('view_term_entities_query_by_term_id', true, [$context->entity, $bundle, $query, $context])) {
            // Query by term
            $bundle_type = $context->entity->getBundleType();
            $ignore_auto_terms = !$this->_application->Entity_BundleTypeInfo($bundle_type, 'is_hierarchical');
            $query->taxonomyTermIdIs(
                $bundle_type,
                $context->entity->getId(),
                $this->_application->Filter('view_term_entities_ignore_auto_terms', $ignore_auto_terms, [$bundle_type]),
                $bundle_type . '_view_term', // table alias
                $bundle_type . '_view_term' // criteria name
            );
        }

        // Show detailed display of term and list of entities
        $context->setTitle($this->_application->Entity_PageTitle($context->entity, true));
        if (!$context->getRequest()->isAjax()) {
            $display = 'detailed';
            $context->addTemplate('view_term_entities')
                ->setAttributes(array(
                    'display' => $display,
                ));
            // Invoke other components
            $this->_application->Action('view_entity', array($context->entity, $display, $context));
        }
        if (!strpos($context->getContainer(), 'drts-platform-render-')) { // make sure not requested from term entities display element
            if (!strpos($context->getContainer(), '-view-term-entities')) {
                $context->settings['filter']['target'] = $context->getContainer() . '-view-term-entities';
            }
        }
    }

    public function onDirectoryAdminSettingsFormFilter(&$form)
    {
        $form['fields'][$this->_name] = [
            '#submit' => array(
                9 => array( // weight
                    function (Form\Form $form) {
                        if ($btn_label = $form->getValue($this->_name, 'filters', 'btn_label')) {
                            $this->_application->getPlatform()->registerString(
                                $btn_label,
                                'nav_filter_btn_label',
                                'view'
                            );
                        }
                    },
                ),
            ),
            '#component' => $this->_name,
            '#tab' => 'Directory',
            'filters' => [
                '#title' => __('Filter Settings', 'directories'),
                'facet_count' => [
                    '#type' => 'checkbox',
                    '#title' => __('Enable facet count', 'directories'),
                    '#default_value' => !empty($this->_config['filters']['facet_count']),
                    '#horizontal' => true,
                ],
                'btn_icon' => [
                    '#type' => 'iconpicker',
                    '#title' => __('Filter button icon', 'directories'),
                    '#default_value' => $this->_config['filters']['btn_icon'],
                    '#horizontal' => true,
                ],
                'btn_label' => [
                    '#type' => 'textfield',
                    '#title' => __('Filter button label', 'directories'),
                    '#default_value' => $this->_config['filters']['btn_label'],
                    '#horizontal' => true,
                ],
                'auto_submit' => [
                    '#title' => __('Auto submit filter form', 'directories'),
                    '#type' => 'checkbox',
                    '#default_value' => !isset($this->_config['filters']['auto_submit']) || $this->_config['filters']['auto_submit'],
                    '#horizontal' => true,
                ],
                'scroll_disable' => [
                    '#title' => __('Disable auto scroll after submit', 'directories'),
                    '#type' => 'checkbox',
                    '#default_value' => !empty($this->_config['filters']['scroll_disable']),
                    '#horizontal' => true,
                ],
                'scroll_offset' => [
                    '#title' => __('Scroll offset in pixels', 'directories'),
                    '#type' => 'slider',
                    '#default_value' => isset($this->_config['filters']['scroll_offset']) ? $this->_config['filters']['scroll_offset'] : 0,
                    '#min_value' => 0,
                    '#max_value' => 300,
                    '#horizontal' => true,
                    '#field_suffix' => 'px',
                    '#states' => [
                        'visible' => [
                            'input[name="' . $this->_name .'[filters][scroll_disable]"]' => ['type' => 'checked', 'value' => false],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function upgrade($current, $newVersion, System\Progress $progress = null)
    {
        parent::upgrade($current, $newVersion, $progress);
        if (version_compare($current->version, '1.2.37-dev.1', '<')) {
            $db = $this->_application->getDB();
            $sql = sprintf(
                'UPDATE %1$sview_filter SET filter_display_name = %2$s WHERE filter_display_name = \'\'',
                $db->getResourcePrefix(),
                $db->escapeString('default')
            );
            try {
                $db->exec($sql);
            } catch (\Exception $e) {}
        }
        return $this;
    }
}
