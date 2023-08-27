<?php
namespace SabaiApps\Directories\Component\View\Controller;

use SabaiApps\Directories\Controller;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;
use SabaiApps\Directories\Exception\RuntimeException;

class QueryEntities extends Controller
{
    protected $_settings, $_settingsCacheId;
    protected static $_defaultSettings = [];
    
    protected function _doExecute(Context $context)
    {
        // Init 
        if (!$bundle = $this->_getBundle($context)) {
            $context->setError();
            return;
        }
        $context->bundle = $bundle;
        
        // Init settings and view
        try {
            $this->_settings = $this->_getSettings($context, $bundle) + static::$_defaultSettings;
        } catch (RuntimeException $e) {
            $context->setError($e->getMessage());
            return;
        }
        $view_mode_name = isset($this->_settings['mode']) ? $this->_settings['mode'] : 'list';
        if (!$view = $this->View_Modes_impl($view_mode_name)) {
            $context->setError(sprintf('Invalid view mode: %s.', $view_mode_name));
            return;
        }
        if (!$view->viewModeSupports($bundle)) {
            $context->setError(sprintf('View mode %s is not supported by %s.', $view_mode_name, $bundle->getLabel()));
            return;
        }
        
        if (!isset($this->_settings['settings'])) {
            $this->_settings['settings'] = [];
        }
        $this->_settings['settings'] += $view->viewModeInfo('default_settings');
        $view_settings = $this->Filter('view_entities_settings', $this->_settings['settings'], [$bundle, $view]);
        unset($this->_settings['settings']);
        
        // Init URL params
        $url_params = $this->_getUrlParams($context, $bundle);
        
        // Init sorts
        $current_sort = null;
        if (isset($view_settings['sort'])
            && ($sorts = (array)$this->Filter('view_entities_sorts', $this->_getSorts($context, $bundle, $view_settings['sort']), [$bundle, $context->getRequest()->getParams(), $view_settings]))
        ) {
            $sort_keys = array_keys($sorts);
            $default_sort = isset($view_settings['sort']['default']) && isset($sorts[$view_settings['sort']['default']]) ? $view_settings['sort']['default'] : array_shift($sort_keys);
            $url_params['sort'] = $current_sort = $context->getRequest()->asStr('sort', $default_sort, $sort_keys);
        } else {
            $sorts = [];
        }
        
        // Init pagination
        $perpage = 0;
        if (!empty($view_settings['pagination'])
            && empty($view_settings['pagination']['no_pagination'])
        ) {
            if (isset($view_settings['pagination']['perpage'])) {
                $perpage = (int)$view_settings['pagination']['perpage'];
            }
            if (empty($perpage)) $perpage = 20;
            if (!empty($view_settings['pagination']['allow_perpage'])
                && !empty($view_settings['pagination']['perpages'])
            ) {
                $url_params['num'] = $perpage = $context->getRequest()->asInt('num', $perpage, $view_settings['pagination']['perpages']);
            }
        }
        
        // Init context
        $context->setAttributes([
            'view_name' => isset($this->_settings['name']) ? $this->_settings['name'] : null,
            'view' => $view,
            'is_default_view' => !empty($this->_settings['is_default_view']),
            'bundle' => $bundle,
            'url_params' => $url_params,
            'settings' => $view_settings,
            'sorts' => $sorts,
            'sort' => $current_sort, 
            'entities' => [],
            'filter' => [],
            'paginator' => null,
            'perpage' => $perpage,
        ]);

        // Create query
        $query_settings = $this->Filter(
            'view_entities_query_settings',
            isset($view_settings['query']) ? $view_settings['query'] : [],
            [$bundle, $context]
        );
        $query = $this->_createQuery($bundle, $query_settings);
        // Showing child items?
        if (!empty($bundle->info['parent'])
            && isset($context->entity)
            && $bundle->info['parent'] === $context->entity->getBundleName()
        ) {
            $query->fieldIs('parent', $context->entity->getId());
        }
        // Show featured first?
        if (!empty($view_settings['sort']['stick_featured'])
            && $this->Entity_BundleTypeInfo($bundle, 'featurable')
        ) {
            if (!empty($this->_settings['is_default_view'])
                && !empty($view_settings['sort']['stick_featured_term_only'])
            ) {
                // Only on single taxonomy term pages
                if (isset($GLOBALS['drts_entity'])
                    && $GLOBALS['drts_entity']->isTaxonomyTerm()
                ) {
                    $query->sortByField('entity_featured', 'DESC');
                }
            } else {
                $query->sortByField('entity_featured', 'DESC');
            }
        }
        
        // Notify
        $this->Action('view_entities', [$bundle, $query, $context]);
        $view->viewModeOnView($bundle, $query, $context);
        
        // Filter
        $context->url_params_before_filter = $context->url_params;
        $render_filters = false;
        if (isset($view_settings['filter'])
            && $this->getComponent('View')->isFilterable($bundle)
        ) {
            $this->_filter($context, $bundle, $query);
            if (!empty($view_settings['filter']['show'])) {
                if ($context->settings['filter']['show'] = empty($this->_settings['is_default_view']) || empty($view_settings['filter']['show_mobile_only']) || $this->isMobile()) {
                    $render_filters = true;
                }
            }
            if ($context->getContentType() === 'json' && $this->_isFilterRequested($context)) { // filter submitted from outside container
                $render_filters = true;
            }
        }
        
        // Sort
        if (isset($context->sort)) {
            $query_sorts = [$context->sort];
            $query_sorts_available = $context->sorts;
            if (!empty($view_settings['sort']['secondary'])
                && ($secondary_sort = $view_settings['sort']['secondary'])
                && $context->sort !== $secondary_sort
                && ($available_sorts = $this->_getAvailableSorts($bundle))
                && isset($available_sorts[$secondary_sort])
            ) {
                $query_sorts[] = $secondary_sort;
                $query_sorts_available[$secondary_sort] = $available_sorts[$secondary_sort];
            }
            $query->sort($query_sorts, $query_sorts_available, $context->getContainer());
        }
        
        // Do query
        $facets_enabled = (bool)$this->getComponent('View')->getConfig('filters', 'facet_count');
        $save_found_entity_ids = $render_filters && $facets_enabled;
        $query_limit = isset($view_settings['query']['limit']) ? $view_settings['query']['limit'] : 0;
        if ($perpage) {
            $page = $context->getRequest()->asInt($this->getPlatform()->getPageParam(), 1);
            $context->paginator = $paginator = $query->paginate($perpage, $query_limit, null, true, $save_found_entity_ids)->setCurrentPage($page);
            $context->entities = $paginator->getElements();
            $context->num_found = $paginator->getElementCount();
            $context->num_shown = $paginator->getElementLimit();
            $context->num_start = $context->num_found ? $paginator->getElementOffset() + 1 : 0;
        } else {
            $context->entities = $query->fetch($query_limit, 0, null, true, $save_found_entity_ids);
            $context->num_found = $context->num_shown = count($context->entities);
            $context->num_start = $context->num_found ? 1 : 0;
        }
        $context->query = $query;

        // Show filter form?
        $query->view_enable_facet_count = $facets_enabled && $query_limit === 0;
        if ($render_filters
            && ($filter_form = $this->_getFilterForm($context, $context->bundle, $query))
            && ($filter_form_rendered = $this->View_FilterForm_render($filter_form, null, $context->getContentType() === 'json'))
        ) {
            $context->filter['form'] = $filter_form_rendered;
        } else {
            $context->filter['form'] = null;
        }
    }
    
    protected function _getSettings(Context $context, Entity\Model\Bundle $bundle)
    {     
        // Any custom settings?
        if (!empty($context->settings)) {
            $settings = $context->settings;
            // Preserve entity if inside another entity page
            if (isset($GLOBALS['drts_entity'])
                && $GLOBALS['drts_entity'] instanceof Entity\Type\IEntity
            ) {
                $settings['_entity'] = $GLOBALS['drts_entity'];
            }
            // Cache custom settings
            $this->_settingsCacheId = md5(get_class($this) . serialize($settings));
            $this->getPlatform()->setCache($settings, $this->_settingsCacheId);
        } elseif ($this->_settingsCacheId = $context->getRequest()->asStr('settings_cache_id', $this->_settingsCacheId)) {
            if ($settings = $this->getPlatform()->getCache($this->_settingsCacheId)) {
                // Reconstruct entity if inside another entity page
                if (isset($settings['_entity'])
                    && $settings['_entity'] instanceof Entity\Type\IEntity
                ) {
                    $this->Entity_Field_load($settings['_entity']);
                    $GLOBALS['drts_entity'] = $settings['_entity'];
                }
            }
        }
        // Use default view settings if no settings
        if (empty($settings)) {
            if ($view = $this->getModel('View', 'View')->bundleName_is($bundle->name)->default_is(true)->fetchOne()) {
                $settings = ['name' => $view->name, 'mode' => $view->mode, 'settings' => $view->data['settings'], 'is_default_view' => true];
            } else {
                throw new RuntimeException('No view defined for bundle: ' . $bundle->name);
            }
        } else {
            // Merge settings of a specific view
            if (isset($settings['load_view'])) {
                if (!$view = $this->getModel('View', 'View')->bundleName_is($bundle->name)->name_is($settings['load_view'])->fetchOne()) {
                    throw new RuntimeException('Invalid view name: ' . $settings['load_view']);
                }
                unset($settings['load_view'], $settings['name'], $settings['mode']);
                $settings = array_replace_recursive(['name' => $view->name, 'mode' => $view->mode, 'settings' => $view->data['settings']], $settings);
                $settings['is_default_view'] = (bool)$view->default;
            }
        }
        // Disable push_state if showing on parent entity page.
        if (!empty($bundle->info['parent'])
            && isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity'] instanceof Entity\Type\IEntity
            && $GLOBALS['drts_entity']->getBundleName() === $bundle->info['parent']
        ) {
            $settings['push_state'] = false;
        } else {
            if ($context->isEmbed()) {
                // Let enable push_state if default view, otherwise disable.
                $settings['push_state'] = $this->Filter('view_entities_url_push_state', !empty($settings['is_default_view']), [$bundle, !empty($settings['is_default_view'])]);
            }
        }
        return $settings;
    }
        
    protected function _createQuery(Entity\Model\Bundle $bundle, array $settings = [])
    {
        $query = $this->Entity_Query($bundle->entitytype_name, $bundle->name);
        if (empty($bundle->info['is_taxonomy'])) {
            if (!empty($bundle->info['public'])) {
                if (empty($settings['status'])
                    || !empty($settings['user_id']) // do not allow custom status if viewing other user's entities
                ) {
                    // No status specified, fetch published entities
                    $query->fieldIs('status', $this->Entity_Status($bundle->entitytype_name, 'publish'));
                    if (!empty($settings['user_id'])) {
                        $query->fieldIs('author', $settings['user_id']);
                    }
                } else {
                    $statuses = [];
                    foreach ($settings['status'] as $status) {
                        $statuses[$status] = $this->Entity_Status($bundle->entitytype_name, $status);
                    }
                    if (empty($settings['status_others'])) {
                        $query->fieldIs('author', $this->getUser()->id)
                            ->fieldIsIn('status', $statuses);
                    } else {
                        $statuses_other = [];
                        foreach ($settings['status_others'] as $status) {
                            $statuses_other[$status] = $this->Entity_Status($bundle->entitytype_name, $status);
                        }
                        $query->startCriteriaGroup('OR')
                            ->startCriteriaGroup('AND')
                                ->fieldIs('author', $this->getUser()->id)
                                ->fieldIsIn('status', $statuses)
                                ->finishCriteriaGroup()
                            ->startCriteriaGroup('AND')
                                ->fieldIsNot('author', $this->getUser()->id)
                                ->fieldIsIn('status', $statuses_other)
                                ->finishCriteriaGroup()
                            ->finishCriteriaGroup();
                    }
                }
            } else {
                $query->fieldIs('status', $this->Entity_Status($bundle->entitytype_name, 'publish'))
                    ->fieldIs('author', $this->getUser()->id);
            }
        }
        
        // Query specific entities by field?
        if (!empty($settings['fields'])) {
            $field_query = $query->getFieldQuery();
            foreach ($settings['fields'] as $field_name => $query_str) {
                if (!is_int($field_name)) {
                    $this->_queryField($bundle, $field_query, $field_name, $query_str);
                } else {
                    if (is_array($query_str)) {
                        // Grouped query
                        $field_query->startCriteriaGroup('OR');
                        foreach ($query_str as $_field_name => $_query_str) {
                            $this->_queryField($bundle, $field_query, $_field_name, $_query_str);
                        }
                        $query->finishCriteriaGroup();
                    }
                }
            }
        }
        
        return $query;
    }
    
    protected function _queryField(Entity\Model\Bundle $bundle, Field\Query $query, $fieldName, $queryStr)
    {
        if (($field = $this->Entity_Field($bundle->name, $fieldName))
            && ($field_type = $this->Field_Type($field->getFieldType(), true))
            && $field_type instanceof Field\Type\IQueryable
        ) {
            $field_type->fieldQueryableQuery($query, ($property = $field->isPropertyField()) ? $property : $fieldName, $queryStr, $bundle);
        } else {
            $this->logError(sprintf('Invalid query field for %s: %s', $bundle->name, $fieldName));
        }
    }
    
    protected function _filter(Context $context, Entity\Model\Bundle $bundle, Entity\Type\Query $query)
    {   
        // Do filter?
        if (!$this->_isFilterRequested($context)
            || (!$request_params = $this->_getRequestedFilters($context))
        ) return;
        
        $filters_filterable = $filter_requests = $filters = $filter_values = $filter_labels = [];
        $display_name = isset($context->settings['filter']['display']) ? $context->settings['filter']['display'] : 'default';
        foreach ($this->getModel('Filter', 'View')->bundleName_is($bundle->name)->displayName_is($display_name)->fetch()->with('Field', 'FieldConfig') as $filter) {
            $filters[$filter->name] = $filter;
            if (isset($request_params[$filter->name])
                && ($ifilter = $this->Field_Filters_impl($filter->type, true))
                && ($field = $filter->getField())
                && $ifilter->fieldFilterIsFilterable($field, $filter->data['settings'], $request_params[$filter->name], $request_params)
            ) {
                $filters_filterable[$filter->type][$filter->name] = $filter->name;
                $filter_requests[$filter->name] = $request_params[$filter->name];
            }
        }
        if (!empty($filters_filterable)) {
            // Create and submit filter form
            $filter_form_settings = $this->_getFilterFormSettings($context, $bundle, $query, $context->url_params_before_filter, $filters, $filter_requests);
            $context->filter['form'] = $this->Form_Build($filter_form_settings);
            if ($context->filter['form']->submit($filter_requests, true)) { // force submit since there is no form build ID
                $filter_values = $context->filter['form']->values;
                foreach (array_keys($filters_filterable) as $filter_type) {
                    foreach ($filters_filterable[$filter_type] as $filter_name) {
                        if (!isset($filter_values[$filter_name])) { // form validation failed
                            unset($filters_filterable[$filter_type][$filter_name], $filter_requests[$filter_name]);
                        }
                    }
                }
                if (!empty($filter_requests)) {
                    $context->url_params['filter'] = 1;
                    $context->url_params += $filter_requests;
                }
            } else {
                if ($context->filter['form']->hasError() && $context->getRequest()->isAjax()) {
                    $errors = $context->filter['form']->getError();
                    foreach (array_keys($errors) as $key) {
                        $errors[$key] = ($label = $context->filter['form']->getLabel($key)) ? $label . ': ' . $errors[$key] : $errors[$key];
                    }
                    $context->setValidateFormError(null, implode(' ', $errors));
                    return;
                }
                $filters_filterable = [];
            }

            // Apply filters and add remove filter links
            foreach (array_keys($filters_filterable) as $filter_type) {
                $filter_impl = $this->Field_Filters_impl($filter_type);
                foreach ($filters_filterable[$filter_type] as $filter_name) {
                    $filter = $filters[$filter_name];
                    if (!$field = $filter->getField()) continue;
                    
                    $filter_impl->fieldFilterDoFilter(
                        $query->getFieldQuery(),
                        $field,
                        $filter->data['settings'],
                        $filter_values[$filter_name],
                        $context->sorts
                    );
                    $default_label = $field->getFieldLabel(true);
                    if (!$_filter_labels = $filter_impl->fieldFilterLabels(
                        $field,
                        $filter->data['settings'],
                        $filter_values[$filter_name],
                        $filter_form_settings[$filter_name],
                        $default_label
                    )) {
                        $_filter_labels = ['' => $default_label];
                    }
                    $filter_labels[$filter_name] = $_filter_labels;
                }
            }
            
            $context->filter['filters'] = $filters;
            $context->filter['filter_values'] = $filter_requests;
            $context->filter['filters_applied'] = $filters_filterable;
            $context->filter['filters_applied_labels'] = $filter_labels;
        }
    }
        
    protected function _getFilterFormSettings(Context $context, Entity\Model\Bundle $bundle, Entity\Type\Query $query, array $urlParams = [], array $filters = null, array $filterRequests = null, array $currentForm = null)
    {
        $url = $this->Url($context->getRoute(), $urlParams);
        if (isset($context->entity)
            && ($context->entity->isTaxonomyTerm() || $context->entity->getBundleName() === $bundle->name)
        ) {
            $url = $this->Entity_PermalinkUrl($context->entity);
        }
        return $this->View_FilterForm(
            $bundle->name,
            $query,
            empty($context->settings['filter']['display']) ? 'default' : $context->settings['filter']['display'],
            [
                'url' => $url,
                'container' => isset($context->settings['filter']['target']) ? $context->settings['filter']['target'] : $context->getContainer(),
                'target' => '.drts-view-entities-container',
                'filters' => $filters,
                'values' => $filterRequests,
                'push_state' => $this->_settings['push_state'],
                'current' => $currentForm,
            ]
        );
    }
    
    protected function _isFilterRequested(Context $context)
    {
        return $context->getRequest()->asBool('filter', false);
    }
    
    protected function _getRequestedFilters(Context $context)
    {
        return $context->getRequest()->getParams();
    }
    
    protected function _getSorts(Context $context, Entity\Model\Bundle $bundle, array $settings)
    {
        $possible_sorts = $this->_getAvailableSorts($bundle);
        if (!empty($settings['options'])) {
            $ret = [];
            foreach ($settings['options'] as $sort_name) {
                if (!isset($possible_sorts[$sort_name])) continue;
                
                $ret[$sort_name] = $possible_sorts[$sort_name];
            }
        } else {
            $ret = $possible_sorts;
        }
        
        return $ret;
    }
    
    protected function _getAvailableSorts(Entity\Model\Bundle $bundle)
    {
        return $this->Entity_Sorts($bundle);
    }

    protected function _getUrlParams(Context $context, Entity\Model\Bundle $bundle)
    {
        $ret = isset($this->_settings['url_params']) ? (array)$this->_settings['url_params'] : [];
        if ($this->_settingsCacheId) {
            $ret['settings_cache_id'] = $this->_settingsCacheId;
        }
        return $ret;
    }
    
    /*
     * @return Entity\Model\Bundle 
     */
    protected function _getBundle(Context $context)
    {
        return $context->child_bundle ?: ($context->taxonomy_bundle ?: $context->bundle);
    }

    protected function _getFilterForm(Context $context, Entity\Model\Bundle $bundle, Entity\Type\Query $query)
    {
        // Create or rebuild form with query
        if (isset($context->filter['form'])) {
            if (!empty($context->filter['filters'])
                && $query->view_enable_facet_count
            ) {
                // Form already exists, but regenerate with query to enable facets
                $filter_form_settings = $this->_getFilterFormSettings(
                    $context,
                    $bundle,
                    $query,
                    $context->url_params_before_filter,
                    $context->filter['filters'],
                    $context->filter['filter_values'],
                    $context->filter['form']->settings
                );
                if (!$filter_form_settings) return;
            } else {
                $filter_form_settings = $context->filter['form']->settings;
            }
            $requested_filters = $this->_getRequestedFilters($context);
        } else {
            $filter_form_settings = $this->_getFilterFormSettings(
                $context,
                $bundle,
                $query,
                $context->url_params_before_filter,
                null,
                null,
                null
            );
            if (!$filter_form_settings) return;

            $requested_filters = null;
        }

        return $this->Form_Build($filter_form_settings, true, $requested_filters);
    }
}