<?php
namespace SabaiApps\Directories\Component\View\Controller;

use SabaiApps\Directories\Assets;
use SabaiApps\Directories\Context;

class ViewEntities extends QueryEntities
{
    protected static $_defaultSettings = [
        'container_template' => 'view_entities_container',
        'push_state' => true,
        'hide_empty' => false,
    ];
    
    protected function _doExecute(Context $context)
    {
        parent::_doExecute($context);

        if ($context->isError()) return;

        // Notify
        $this->Action('view_display_entities', [$context]);
        
        // Init context
        if (!$context->hasTemplate()) {
            $context->addTemplate('view_entities');
        }
        $context->setAttributes(array(
            'container_template' => $this->_settings['container_template'],
            'push_state' => $this->_settings['push_state'],
            'hide_empty' => $this->_settings['hide_empty'],
            'nav' => $context->view->viewModeNav($context->bundle, $context->settings),
            'view_url' => $this->Url($context->getRoute(), $context->url_params_before_filter),
        ));
        if (isset($context->settings['display'])) {
            if (empty($context->settings['display_cache'])
                || (!empty($context->settings['display_cache_guest_only']) && !$this->getUser()->isAnonymous())
                || !$this->Filter('view_entities_display_is_cacheable', true, [$context])
            ) {
                $context->settings['display_cache'] = false;
            }
        }
        
        // Set template
        if ($is_ajax = $context->getRequest()->isAjax()) {
            if (strpos($is_ajax, '.drts-view-entities-container')) {
                $context->addTemplate($this->_settings['container_template']);
                if (isset($context->settings['ajax_container_template'])) {
                    $context->addTemplate($context->settings['ajax_container_template']);
                }
                //if ('html' !== $context->getContentType()) { // make sure html content was not requested
                    $context->setContentType('json'); 
                //}
            }
        } else {
            // Load view specific assets if any
            if ($assets = $context->view->viewModeAssets($context->bundle, $context->settings)) {
                Assets::load($this->getPlatform(), $assets);
            }

            if ((!empty($this->_settings['is_default_view']) || $context->getContainer() === '#drts-content')
                && get_class($this) === __CLASS__
                && (!isset($GLOBALS['drts_entity']) || $GLOBALS['drts_entity']->isTaxonomyTerm())  // Show if displaying single term
            ) {
                // For widgets, though there may be a better way to pass data
                $GLOBALS['drts_view_entites_context'] = array(
                    'container' => $context->getContainer(),
                    'route' => isset($context->entity) ? $this->Entity_PermalinkUrl($context->entity) : $context->getRoute(),
                    'bundle' => $context->bundle,
                    'url_params' => $context->url_params_before_filter,
                    'query' => $context->query,
                    'limit' => empty($context->settings['query']['limit']) ? 0 : $context->settings['query']['limit'],
                    'filter_target' => isset($context->settings['filter']['target']) ? $context->settings['filter']['target'] : null,
                    'filter_show' => isset($context->filter['form']),
                    'filter_display' => !empty($context->settings['filter']['display']) ? $context->settings['filter']['display'] : 'default',
                    'filters' => isset($context->filter['filters']) ? $context->filter['filters'] : null,
                    'filter_values' => isset($context->filter['filter_values']) ? $context->filter['filter_values'] : null,
                );
            }
        }
    }
}