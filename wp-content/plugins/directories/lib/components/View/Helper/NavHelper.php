<?php
namespace SabaiApps\Directories\Component\View\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\View\Mode\IMode;
use SabaiApps\Directories\Request;
use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Framework\Paginator\AbstractPaginator;

class NavHelper
{
    public function help(Application $application, Context $context, array $items, $isBottom = false)
    {
        $navs = $scripts = [];
        $color = isset($items['color']) ? $items['color'] : null;
        foreach (array_keys($items) as $i) { // navs
            if (!is_numeric($i)) continue;

            $has_item = false;
            foreach (array_keys($items[$i]) as $j) { // nav
                foreach (array_keys($items[$i][$j]) as $k) { // items
                    if (is_array($items[$i][$j][$k])) {
                        $item_name = $items[$i][$j][$k][0];
                        $item_options = $items[$i][$j][$k][1];
                    } else {
                        $item_name = $items[$i][$j][$k];
                        $item_options = [];
                    }
                    if ($this->_hasItem($application, $context, $item_name)) {
                        $items[$i][$j][$k] = $this->_getItem($application, $context, $item_name, $color, $item_options, $scripts);
                        $has_item = true;
                    } else {
                        unset($items[$i][$j][$k]);
                    }
                }
            }
            if ($has_item
                && ($_nav = $this->_nav($application, $items[$i], $color, $isBottom))
            ) {
                $navs[$i] = $_nav;
            }
        }
        if (empty($navs)) return '';

        $ret = implode(PHP_EOL, $navs);
        if (!empty($scripts)) {
            if (Request::isXhr()) {
                $ret .= '<script type="text/javascript">jQuery(function($) {';
            } else {
                $ret .= '<script type="text/javascript">document.addEventListener("DOMContentLoaded", function(event) { var $ = jQuery;';
            }
            $ret .= implode(PHP_EOL, $scripts);
            $ret .= '});</script>';
        }

        return $ret;
    }

    protected function _nav(Application $application, array $items, $color, $isBottom)
    {
        $navs = [];
        $has_nav = false;
        foreach (array_keys($items) as $i) {
            $_items = array_filter($items[$i]);
            if (empty($_items)) {
                // Hide on small size screen devices if empty
                $class = DRTS_BS_PREFIX . 'navbar-nav ' . DRTS_BS_PREFIX . 'd-none ' .  DRTS_BS_PREFIX . 'd-sm-block';
            } else {
                $has_nav = true;
                $class = DRTS_BS_PREFIX . 'navbar-nav';
            }
            $navs[] = '<div class="' . $class . '">' . implode(PHP_EOL, $_items) . '</div>';
        }
        if (!$has_nav) return;

        $class = empty($color) ? '' : $this->_getNavColorClass($color);
        return sprintf(
            '<div class="%1$snavbar %1$snavbar-expand-sm %1$s%6$s %1$s%2$s drts-view-nav %3$s %1$s%4$s">
    %5$s
</div>',
            DRTS_BS_PREFIX,
            $isBottom ? 'mt-2' : 'mb-2',
            $class ? $application->H($class) : '',
            $class ? 'p-2' : 'p-0',
            implode(PHP_EOL, $navs),
            $isBottom && count($navs) === 1 ? 'justify-content-center' : 'justify-content-between'
        );
    }

    protected function _getNavColorClass($color)
    {
        switch ($color) {
            case 'light':
                return DRTS_BS_PREFIX . 'navbar-light ' . DRTS_BS_PREFIX . 'bg-light ' . DRTS_BS_PREFIX . 'text-gray-dark';
            case 'dark':
                return DRTS_BS_PREFIX . 'navbar-dark ' . DRTS_BS_PREFIX . 'bg-dark ' . DRTS_BS_PREFIX . 'text-white';
            case 'info':
                return DRTS_BS_PREFIX . 'navbar-dark ' . DRTS_BS_PREFIX . 'bg-info ' . DRTS_BS_PREFIX . 'text-white';
            case 'primary':
                return DRTS_BS_PREFIX . 'navbar-dark ' . DRTS_BS_PREFIX . 'bg-primary ' . DRTS_BS_PREFIX . 'text-white';
            case 'secondary':
                return DRTS_BS_PREFIX . 'navbar-dark ' . DRTS_BS_PREFIX . 'bg-secondary ' . DRTS_BS_PREFIX . 'text-white';
            case 'success':
                return DRTS_BS_PREFIX . 'navbar-dark ' . DRTS_BS_PREFIX . 'bg-success ' . DRTS_BS_PREFIX . 'text-white';
            case 'warning':
                return DRTS_BS_PREFIX . 'navbar-dark ' . DRTS_BS_PREFIX . 'bg-warning ' . DRTS_BS_PREFIX . 'text-white';
            case 'danger':
                return DRTS_BS_PREFIX . 'navbar-dark ' . DRTS_BS_PREFIX . 'bg-danger ' . DRTS_BS_PREFIX . 'text-white';
            default:
                return '';
        }
    }

    protected function _hasItem(Application $application, Context $context, $name)
    {
        if (!is_string($name)) return true;

        switch ($name) {
            case 'sort':
                return isset($context->sorts) && count($context->sorts) > 1;
            case 'filter':
                return $application->getComponent('View')->isFilterable($context->bundle)
                    && (!empty($context->settings['filter']['show']) || (!empty($context->settings['map']['show']) && $context->view->viewModeInfo('mapable')));
            case 'filters':
                return !empty($context->filter['filters_applied_labels'])
                    && $application->getComponent('View')->isFilterable($context->bundle);
            case 'add':
                return $application->isComponentLoaded('FrontendSubmit')
                    && !empty($context->settings['other']['add']['show'])
                    && empty($context->settings['query']['user_id']);
            case 'pagination':
                return !empty($context->num_found)
                    && empty($context->settings['pagination']['type'])
                    && isset($context->paginator)
                    && $context->paginator->count() > 1;
            case 'load_more':
                return !empty($context->num_found)
                    && (!empty($context->settings['pagination']['type']) && $context->settings['pagination']['type'] === 'load_more')
                    && isset($context->paginator)
                    && $context->paginator->count() > 1
                    && $context->paginator->getElementCount() > $context->num_start + $context->num_shown - 1;
            case 'perpages':
                return !empty($context->num_found)
                    && !empty($context->perpage)
                    && (empty($context->settings['pagination']['type']) || $context->settings['pagination']['type'] === 'default')
                    && !empty($context->settings['pagination']['allow_perpage'])
                    && !empty($context->settings['pagination']['perpages']);
            case 'num':
                return !empty($context->num_found)
                    && !empty($context->settings['other']['num']);
            case 'layout_switch':
                return !empty($context->num_found) && !empty($context->settings['list_grid']) && empty($context->settings['list_no_row']);
            case 'status':
                return !empty($context->bundle->info['public'])
                    && !$application->getUser()->isAnonymous()
                    && empty($context->settings['query']['user_id']);
            default:
                return is_callable($name);
        }
    }

    protected function _getItem(Application $application, Context $context, $name, $color, array $options = [], array &$scripts = [])
    {
        if (!is_string($name)) return $name;

        switch ($name) {
            case 'sort':
                return $this->_sortByButton(
                    $application,
                    $name,
                    $color,
                    $context->sorts,
                    $context->sort,
                    $context->getContainer(),
                    $context->push_state ? $this->_getPushStateRoute($application, $context) : $context->getRoute(),
                    $context->url_params,
                    $context->push_state,
                    $options,
                    $scripts
                );
            case 'filter':
                return $this->_filterButton(
                    $application,
                    $name,
                    $color,
                    $context->bundle,
                    isset($context->settings['filter']['target']) ? $context->settings['filter']['target'] : $context->getContainer(),
                    !empty($context->settings['filter']['show']),
                    empty($context->bundle->info['parent']) && !empty($context->settings['filter']['show_modal']),
                    !empty($context->settings['filter']['shown']),
                    !empty($context->filter['form']),
                    empty($context->filter['filters_applied_labels']) ? [] : $context->filter['filters_applied_labels'],
                    $context->view,
                    $options,
                    $scripts
                );
            case 'filters':
                return $this->_filtersApplied(
                    $application,
                    $name,
                    $color,
                    $context->filter['filters_applied_labels'],
                    $options,
                    $scripts
                );
            case 'add':
                return $this->_addEntityButton(
                    $application,
                    $name,
                    $color,
                    $context->bundle,
                    $context->settings['other']['add'],
                    $context->entity ?: null,
                    $options,
                    $scripts
                );
            case 'pagination':
                return $this->_pagination(
                    $application,
                    $name,
                    $color,
                    $context->paginator,
                    $context->settings['pagination'],
                    $context->getContainer(),
                    $context->push_state ? $this->_getPushStateRoute($application, $context) : $context->getRoute(),
                    $context->url_params,
                    $context->push_state,
                    $options,
                    $scripts
                );
            case 'load_more':
                return $this->_loadMoreButton(
                    $application,
                    $name,
                    $color,
                    $context->paginator,
                    $context->settings['pagination'],
                    $context->getContainer(),
                    $context->getRoute(),
                    $context->url_params,
                    $options,
                    $scripts
                );
            case 'perpages':
                return $this->_perpages(
                    $application,
                    $name,
                    $color,
                    $context->perpage,
                    $context->settings['pagination']['perpages'],
                    $context->num_found,
                    $context->getContainer(),
                    $context->push_state ? $this->_getPushStateRoute($application, $context) : $context->getRoute(),
                    $context->url_params,
                    $context->push_state,
                    $options,
                    $scripts
                );
            case 'status':
                return $this->_status(
                    $application,
                    $name,
                    $color,
                    $context->bundle,
                    $context->getContainer(),
                    $context->push_state ? $this->_getPushStateRoute($application, $context) : $context->getRoute(),
                    $context->url_params,
                    $context->push_state,
                    $options,
                    $scripts
                );
            case 'layout_switch':
                return $this->_layoutSwitch(
                    $application,
                    $name,
                    $color,
                    $context->getContainer(),
                    $context->settings,
                    $options,
                    $scripts
                 );
            case 'num':
                return $this->_numResults(
                    $application,
                    $name,
                    $color,
                    $context->num_found,
                    $context->num_start,
                    $context->num_shown,
                    $context,
                    $options,
                    $scripts
                );
            default:
                return ($ret = call_user_func_array($name, [$context, $color, $options, &$scripts])) ? $ret : '';
        }
    }

    protected function _getPushStateRoute(Application $application, Context $context)
    {
        // Make sure taxonomy term permalink URL does not change
        if (isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->isTaxonomyTerm()
        ) {
            return $application->Entity_PermalinkUrl($GLOBALS['drts_entity']);
        }
        return $context->getRoute();
    }

    public function buttons(Application $application, $name, $color, array $links, array $btnOptions = [])
    {
        if (empty($links)) return '';

        $btnOptions += ['color' => $application->System_Util_btnColor($color), 'class' => '', 'label' => true, 'group' => true];
        $btnOptions['class'] .= ' ' . $this->itemClass($application, $name);

        return $application->ButtonLinks($links, $btnOptions);
    }

    public function dropdown(Application $application, $name, $color, array $links, array $btnOptions = [])
    {
        if (empty($links)) return '';

        $btnOptions += ['color' => $application->System_Util_btnColor($color), 'class' => ''];
        $btnOptions['class'] .= ' ' . $this->itemClass($application, $name);

        return $application->DropdownButtonLinks($links, $btnOptions);
    }

    protected function _sortLinks(Application $application, array $sorts, $currentSort, $container, $route, array $urlParams, $pushState = false)
    {
        $ret = [];
        $options = [
            'container' => $container,
            'cache' => true,
            'pushState' => $pushState,
            'target' => '.drts-view-entities-container',
        ];
        foreach (array_keys($sorts) as $key) {
            $attr = [
                'data-value' => $key,
                'class' => 'drts-view-entities-sort',
                'rel' => 'nofollow',
            ];
            if ($key === $currentSort) {
                $options['active'] = true;
                $attr['class'] .= ' drts-view-entities-sort-selected';
            } else {
                $options['active'] = false;
            }
            $ret[$key] = $application->LinkTo(
                is_array($sorts[$key]) ? $sorts[$key]['label'] : $sorts[$key],
                $application->Url($route, [$application->getPlatform()->getPageParam() => 1, 'sort' => $key] + $urlParams),
                $options,
                $attr
            );
        }

        return $ret;
    }

    protected function _sortByButton(Application $application, $name, $color, array $sorts, $currentSort, $container, $route, array $urlParams, $pushState = false, array $options = [], array &$scripts = [])
    {
        return $this->dropdown(
            $application,
            $name,
            $color,
            $this->_sortLinks($application, $sorts, $currentSort, $container, $route, $urlParams, $pushState),
            $options + ['format' => __('Sort by: %s', 'directories')]
        );
    }

    protected function _filterButton(Application $application, $name, $color, Entity\Model\Bundle $bundle, $filterTarget, $show, $showInModal, $shown, $hasForm, array $filtersApplied, IMode $view, array $options = [], array &$scripts = [])
    {
        if (!empty($options['show_filters_applied'])
            && !empty($filtersApplied)
        ) {
            $dropdown_menu = [];
            foreach ($filtersApplied as $remove_filter_name => $_remove_filter_labels) {
                foreach ($_remove_filter_labels as $remove_filter_value => $remove_filter_label) {
                    $dropdown_menu[] = sprintf(
                        '<button type="button" class="%1$sdropdown-item drts-view-remove-filter" data-filter-name="%2$s" data-filter-value="%3$s">%4$s <i class="fas fa-times-circle drts-clear"></i></button>',
                        DRTS_BS_PREFIX,
                        $application->H($remove_filter_name),
                        $application->H($remove_filter_value),
                        $remove_filter_label // HTML, so no need to escape
                    );
                }
            }
            $count = count($dropdown_menu);
            if ($count > 1) {
                $dropdown_menu[] = sprintf(
                    '<div class="%1$sdropdown-divider"></div>
<button type="button" class="%1$sdropdown-item drts-view-remove-filter">%2$s</button>',
                    DRTS_BS_PREFIX,
                    $application->H(__('Clear all', 'directories'))
                );
            }
        }

        $class = $application->System_Util_btnClass($options, $color);
        if (!$has_dropdown = !empty($dropdown_menu)) {
            $class .= ' ' . $this->itemClass($application, $name);
        }
        $buttons = [];
        $url = ['script_url' => '', 'fragment' => $filterTarget . '-view-filter-form'];
        $link_options = [
            'text' => $application->getComponent('View')->getConfig('filters', 'btn_label'),
            'icon' => $application->getComponent('View')->getConfig('filters', 'btn_icon'),
            'btn' => true,
        ];
        $link_options['text'] = $application->System_TranslateString($link_options['text'], 'nav_filter_btn_label', 'view');
        $link_text = $link_options['text'];
        unset($link_options['text']);
        if ($show
            && $hasForm
        ) {
            $class_collapse = $has_dropdown ? $class : $class . ' drts-view-nav-item-name-' . $name . '-collapse';
            $btn_label = sprintf(_x('Filter %s', 'filter items', 'directories'), $bundle->getLabel());
            if ($showInModal) {
                $buttons['collapse'] = $application->LinkTo($link_text, $url, ['container' => 'modal', 'modalSize' => 'xl'] + $link_options, [
                    'class' => $class_collapse,
                    'data-modal-title' => $btn_label,
                    'aria-label' => $btn_label,
                ]);
            } else {
                $buttons['collapse'] = $application->LinkTo($link_text, '', $link_options, [
                    'class' => $has_dropdown ? $class_collapse : $class_collapse . ' ' . DRTS_BS_PREFIX . 'd-inline-block',
                    'data-toggle' => DRTS_BS_PREFIX . 'collapse',
                    'data-target' => $url['fragment'],
                    'aria-expanded' => $shown ? 'true' : 'false',
                    'aria-label' => $btn_label,
                ]);
            }
        }
        // Allow top level bundles to show filters in modal when in full screen mode
        if (empty($bundle->info['parent'])
            && false !== $view->viewModeInfo('filter_fullscreen')
        ) {
            if (!isset($btn_label)) $btn_label = sprintf(_x('Filter %s', 'filter items', 'directories'), $bundle->getLabel());
            $buttons['modal'] = $application->LinkTo($link_text, $url, ['container' => 'modal', 'modalSize' => 'xl'] + $link_options, [
                'class' => $has_dropdown ? $class : $class . ' drts-view-nav-item-name-' . $name . '-modal',
                'data-modal-title' => $btn_label,
                'aria-label' => $btn_label,
            ]);
        }
        if ($has_dropdown) {
            foreach (array_keys($buttons) as $i) {
                $buttons[$i] = sprintf(
                    '<div class="%1$sbtn-group %2$s %3$s">
    %4$s<button type="button" class="%5$s %1$sdropdown-toggle %1$sdropdown-toggle-split" data-toggle="%1$sdropdown" aria-haspopup="true" aria-expanded="false"><span class="%1$ssr-only">Toggle Dropdown</span></button>
    <div class="%1$sdropdown-menu drts-view-entities-filters-applied">
        %6$s
    </div>
</div>',
                    DRTS_BS_PREFIX,
                    $this->itemClass($application, $name) . ' drts-view-nav-item-name-' . $name . '-' . $i,
                    $i === 'collapse' && !$showInModal ? DRTS_BS_PREFIX . 'd-inline-block' : '',
                    $buttons[$i],
                    $application->System_Util_btnClass($options, $color),
                    implode(PHP_EOL, $dropdown_menu)
                );
            }
        }

        return implode(PHP_EOL, $buttons);
    }

    protected function _addEntityButton(Application $application, $name, $color, Entity\Model\Bundle $bundle, $btnSettings, Entity\Type\IEntity $parentEntity = null, array $options = [], array &$scripts = [])
    {
        if (!$application->getUser()->isAnonymous()
            && !$application->Entity_IsRoutable($bundle, 'add', $parentEntity)
        ) return '';

        if (empty($bundle->info['parent'])) {
            $params = [
                'bundle' => $bundle->name,
            ];
            if (!empty($btnSettings['entity_reference_id'])
                && !empty($btnSettings['entity_reference_field'])
            ) {
                $params['entity_reference_id'] = $btnSettings['entity_reference_id'];
                $params['entity_reference_field'] = $btnSettings['entity_reference_field'];
            }
            $url = $application->Url('/' . $application->FrontendSubmit_AddEntitySlug($bundle), $params);
            $url = $application->Filter('view_nav_add_entity_button_url', $url, [$bundle]);

            return $this->_getAddEntityButton($application, $name, $color, $bundle, $btnSettings, $url, $options);
        }

        // Add child entity link requires a valid parent entity
        if (!isset($parentEntity)
            || $parentEntity->getBundleName() !== $bundle->info['parent']
        ) return '';

        $url = str_replace(':slug', $parentEntity->getSlug(), $bundle->getPath()) . '/add';
        return $this->_getAddEntityButton($application, $name, $color, $bundle, $btnSettings, $url, $options);
    }

    protected function _getAddEntityButton(Application $application, $name, $color, Entity\Model\Bundle $bundle, $settings, $url, array $options, array $attr = [])
    {
        $attr['class'] = $application->System_Util_btnClass($options, $color, true) . ' ' . $this->itemClass($application, $name);
        if (empty($settings['show_label'])) {
            $title = '';
            $attr['title'] = $bundle->getLabel('add');
            $attr['rel'] = 'sabaitooltip';
        } else {
            $title = $bundle->getLabel('add');
        }

        return $application->LinkTo(
            $title,
            $url,
            ['icon' => 'fa-fw fas fa-plus', 'btn' => true],
            $attr
        );
    }

    protected function _pagination(Application $application, $name, $color, AbstractPaginator $paginator, array $settings, $container, $route, array $urlParams, $pushState = false, array $options = [], array &$scripts = [])
    {
        return $application->PageNav(
            $container,
            $paginator,
            $application->Url($route, $urlParams),
            $options + [
                'target' => '.drts-view-entities-container',
                'scroll' => true,
                'cache' => true,
                'color' => $application->System_Util_btnColor($color),
                'class' => $this->itemClass($application, $name),
                'pushState' => $pushState,
                'append' => isset($settings['type']) && $settings['type'] === 'load_more' ? '.drts-entity' : null,
                'appendTo' => isset($settings['append_to']) ? $settings['append_to'] : null,
            ],
            isset($options['offset']) ? $options['offset'] : null
        );
    }

    protected function _loadMoreButton(Application $application, $name, $color, AbstractPaginator $paginator, array $settings, $container, $route, array $urlParams, array $options = [], array &$scripts = [])
    {
        $item_class = $this->itemClass($application, $name);
        return $application->LinkTo(
            isset($settings['load_more_label']) && strlen($settings['load_more_label']) ? $settings['load_more_label'] : __('Load More', 'directories'),
            $application->Url($route, [$application->getPlatform()->getPageParam() => $paginator->getCurrentPage() + 1] + $urlParams),
            $options + [
                'container' => $container,
                'target' => '.drts-view-entities-container',
                'scroll' => '.' . $item_class,
                'cache' => true,
                'pushState' => false,
                'append' => '.drts-view-entity-container',
                'appendTo' => isset($settings['append_to']) ? $settings['append_to'] : '.drts-view-entities',
            ],
            [
                'class' => $application->System_Util_btnClass($options + ['size' => 'lg'], $color, true) . ' ' . $item_class,
            ]
        );
    }

    protected function _perpages(Application $application, $name, $color, $perpage, array $perpages, $numFound, $container, $route, array $urlParams, $pushState = false, array $options = [], array &$scripts = [])
    {
        $perpage_links = $this->_perpageLinks($application, $perpage, $perpages, $numFound, $container, $route, $urlParams, $pushState);
        if (count($perpage_links) <= 1) return '';

        return $this->dropdown(
            $application,
            $name,
            $color,
            $perpage_links,
            $options + [
                'format' => _x('Show: %s', 'items per page', 'directories'),
            ]
        );
    }

    protected function _perPageLinks(Application $application, $perpage, array $perpages, $numFound, $container, $route, array $urlParams, $pushState = false)
    {
        $ret = [];
        $options = [
            'container' => $container,
            'cache' => true,
            'pushState' => $pushState,
            'target' => '.drts-view-entities-container',
            'scroll' => true,
        ];
        sort($perpages, SORT_NUMERIC);
        foreach ($perpages as $_perpage) {
            $_perpage = (int)$_perpage;
            if ($numFound < $_perpage) break;

            $attr = [
                'data-value' => $_perpage,
                'class' => 'drts-view-entities-perpage',
                'rel' => 'nofollow',
            ];
            if ($_perpage === $perpage) {
                $options['active'] = true;
                $attr['class'] .= ' drts-view-entities-perpage-selected';
            } else {
                $options['active'] = false;
            }
            $ret[$_perpage] = $application->LinkTo(
                $_perpage,
                $application->Url($route, [$application->getPlatform()->getPageParam() => 1, 'num' => $_perpage] + $urlParams),
                $options,
                $attr
            );
        }

        return $ret;
    }

    protected function _layoutSwitch(Application $application, $name, $color, $container, array $settings, array $options = [], array &$scripts = [])
    {
        if (!empty($settings['list_layout_switch_cookie']) && ($cookie = $application->System_Cookie($settings['list_layout_switch_cookie']))) {
            $selected = $cookie === 'grid' ? 'grid' : 'row';
        } else {
            $selected = !empty($settings['list_grid_default']) ? 'grid' : 'row';
        }
        $scripts[] = sprintf(
            '$(".drts-view-entities-layout-switch", "%3$s").off("click").on("click", ".%1$sbtn", function(e){
    var $this = $(this), container, layout = $this.data("layout");
    e.preventDefault();
    $this.parent().find(".%1$sbtn").each(function(){
        var $btn = $(this);
        $btn.toggleClass("%1$sactive", $btn.data("layout") === layout);
    });
    container = $this.closest("%3$s").find(".drts-view-entities-container");
    if (container.length) {
        container.find(".drts-view-entities-list-row, .drts-view-entities-list-grid")
            .toggleClass("drts-view-entities-list-row", layout === "row")
            .toggleClass("drts-view-entities-list-grid", layout === "grid")
            .find("> .drts-row").toggleClass("drts-y-gutter", layout === "grid");
    }
    var cookie_name = "%2$s-" + DRTS.cookieHash;
    if (cookie_name) $.cookie(cookie_name, layout, {path: DRTS.path, domain: DRTS.domain});
});',
            DRTS_BS_PREFIX,
            !empty($settings['list_layout_switch_cookie']) ? $settings['list_layout_switch_cookie'] : '',
            $container
        );
        return sprintf(
            '<div class="%1$sbtn-group %2$s drts-view-entities-layout-switch drts-form-switch %1$sd-none %1$sd-sm-block">
    <button type="button" class="%3$s%4$s" data-layout="row"><i class="fas fa-list"></i></button><button type="button" class="%3$s%5$s" data-layout="grid"><i class="fas fa-th-large"></i></button>
</div>',
            DRTS_BS_PREFIX,
            $this->itemClass($application, $name),
            $application->System_Util_btnClass($options, $color),
            $selected !== 'grid' ? ' ' . DRTS_BS_PREFIX . 'active' : '',
            $selected === 'grid' ? ' ' . DRTS_BS_PREFIX . 'active' : ''
        );
    }

    protected function _status(Application $application, $name, $color, Entity\Model\Bundle $bundle, $container, $route, array $urlParams, $pushState = false, array $options = [], array &$scripts = [])
    {
        return $this->dropdown(
            $application,
            $name,
            $color,
            $this->_statusLinks($application, $bundle, $container, $route, $urlParams, $pushState),
            $options + ['format' => __('Status: %s', 'directories')]
        );
    }

    protected function _statusLinks(Application $application, Entity\Model\Bundle $bundle, $container, $route, array $urlParams, $pushState = false)
    {
        $links = [];

        $options = [
            'container' => $container,
            'cache' => true,
            'pushState' => $pushState,
            'target' => '.drts-view-entities-container',
        ];
        $links[] = $application->LinkTo(
            __('All', 'directories'),
            $application->Url($route, ['status' => ''] + $urlParams),
            $options
        );
        $statuses = [
            'publish' => __('Published', 'directories'),
            'pending' => __('Pending', 'directories'),
            'draft' => __('Draft', 'directories'),
        ];
        if (!empty($bundle->info[ 'privatable'])) {
            $statuses['private'] = __('Private', 'directories');
        }
        if ($application->isComponentLoaded('Payment')
            && !empty($bundle->info['payment_enable'])
        ) {
            $statuses['expired'] = __('Expired', 'directories');
            $statuses['deactivated'] = __('Deactivated', 'directories');
            $statuses['expiring'] = __('Expiring', 'directories');
        }
        $current = isset($urlParams['status']) ? $urlParams['status'] : null;
        foreach ($statuses as $key => $label) {
            $links[] = $application->LinkTo(
                $label,
                $application->Url($route, ['status' => $key] + $urlParams),
                $current === $key ? ['active' => true] + $options : $options
            );
        }

        return $links;
    }

    protected function _numResults(Application $application, $name, $color, $numFound, $numStart, $numShown, Context $context, array $options = [], array &$scripts = [])
    {
        if (null === $text = $application->Filter('view_nav_num_results_text', null, [$color, $numFound, $numStart, $numShown, $context])) {
            $text = sprintf(
                $application->H(__('Showing %s - %s of %s', 'directories')),
                empty($context->settings['pagination']['type']) || $context->settings['pagination']['type'] !== 'load_more' ? $numStart : 1,
                $numStart + $numShown - 1,
                number_format($numFound)
            );
        }
        return '<span class="' . $application->H($this->itemClass($application, $name, true)) . '">' . $text . '</span>';
    }

    protected function _filtersApplied(Application $application, $name, $color, array $filtersApplied, array $options = [], array &$scripts = [])
    {
        $remove_filter_buttons = [];
        foreach ($filtersApplied as $remove_filter_name => $_remove_filter_labels) {
            foreach ($_remove_filter_labels as $remove_filter_value => $remove_filter_label) {
                $remove_filter_buttons[] = sprintf(
                    '<button type="button" class="%1$sbtn %1$sbtn-sm %1$sbtn-light %1$smb-1 drts-view-remove-filter" data-filter-name="%2$s" data-filter-value="%3$s">%4$s <i class="fas fa-times-circle drts-clear"></i></button>',
                    DRTS_BS_PREFIX,
                    $application->H($remove_filter_name),
                    $application->H($remove_filter_value),
                    $remove_filter_label // HTML, so no need to escape
                );
            }
        }
        if (!$count = count($remove_filter_buttons)) return '';

        if ($count > 1) {
            $remove_filter_buttons[] = sprintf(
                '<button type="button" class="%1$sbtn %1$sbtn-sm %1$sbtn-link drts-view-remove-filter %1$spx-0 %1$smb-1">%2$s</button>',
                DRTS_BS_PREFIX,
                $application->H(__('Clear all', 'directories'))
            );
        }
        $margin_class = $application->getPlatform()->isRtl() ? DRTS_BS_PREFIX . 'ml-1' : DRTS_BS_PREFIX . 'mr-1';
        return '<div class="' . $this->itemClass($application, $name) . ' drts-view-entities-filters-applied">'
            . '<span class="' . $margin_class . '">' . $application->H(__('Applied filters:', 'directories')) . '</span>'
            . implode(PHP_EOL, $remove_filter_buttons) . '</div>';
    }

    public function itemClass(Application $application, $name = null, $isText = false)
    {
        $margin_x_class = $application->getPlatform()->isRtl() ? DRTS_BS_PREFIX . 'ml-2' : DRTS_BS_PREFIX . 'mr-2';
        $class = DRTS_BS_PREFIX . 'nav-item ' . $margin_x_class . ' ' . DRTS_BS_PREFIX . 'mb-2 '
            . DRTS_BS_PREFIX . 'mb-sm-0 drts-view-nav-item';
        if (isset($name)) {
            $class .= ' drts-view-nav-item-name-' . $name;
        }
        if ($isText) {
            $class .= ' ' . DRTS_BS_PREFIX . 'navbar-text';
        }
        return $class;
    }
}
