<?php
namespace SabaiApps\Directories\Platform\WordPress;

use SabaiApps\Directories\Context;

class Template
{
    private static $_instance;
    private $_context, $_headHtml, $_jsHtml, $_pageIds, $_postTypes, $_skipInTheLoopCheck;
    
    public function __construct(Platform $platform)
    {
        // Fetch now otherwise will be cleared when widgets are rendered
        $this->_headHtml = $platform->getHeadHtml();
        $this->_jsHtml = $platform->getJsHtml();
        
        $page_slugs = $platform->getPageSlugs();
        $this->_pageIds = (array)$page_slugs[2];

        $current_theme = strtolower(wp_get_theme(get_template())->get('Name'));
        $this->_maybeAddThemeSpecificFilters($platform, $current_theme);

        if (!defined('DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK')) {
            define('DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK', apply_filters('drts_wordpress_skip_in_the_loop_check', in_array($current_theme, ['twenty twenty-two'])));
        }
        if (!defined('DRTS_WORDPRESS_FORCE_TAX_PAGE_TITLE')) {
            define('DRTS_WORDPRESS_FORCE_TAX_PAGE_TITLE', apply_filters('drts_wordpress_force_tax_page_title', in_array($current_theme, ['Newspaper', 'twenty twenty-two'])));
        }

        // For some themes, where for some reason the title of the first post
        // in archive on taxonomy page is used as page title
        if (DRTS_WORDPRESS_FORCE_TAX_PAGE_TITLE) {
            $this->_postTypes = $platform->getApplication()
                ->getComponent('WordPressContent')
                ->getPostTypeNames();
        }

        if (DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK) {
            if (is_bool(DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK)) {
                $this->_skipInTheLoopCheck = true;
            } elseif (is_string(DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK)) {
                if ($page_ids = explode(',', DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK)) {
                    $this->_skipInTheLoopCheck = array_map('intval', $page_ids);
                }
            } else {
                $this->_skipInTheLoopCheck = [(int)DRTS_WORDPRESS_SKIP_IN_THE_LOOP_CHECK];
            }
        }
    }

    protected function _maybeAddThemeSpecificFilters(Platform $platform, $theme)
    {
        switch ($theme) {
            case 'enfold':
                add_filter('avf_title_args', function($args) use ($platform) {
                    if (is_tax()
                        && isset($GLOBALS['wp_query'])
                        && ($term = $GLOBALS['wp_query']->get_queried_object())
                        && ($term_bundle = $platform->getApplication()->Entity_Bundle($term->taxonomy))
                    ) {
                        $args['title'] = sprintf($term_bundle->getLabel('page'), $term->name);
                    }
                    return $args;
                });
                break;
            default:
        }
    }
    
    public static function getInstance(Platform $platform)
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self($platform);
        }
        return self::$_instance;
    }
    
    public function setContext(Context $context)
    {
        $this->_context = $context;
        return $this;
    }

    public function render()
    {        
        add_action('wp_head', array($this, 'onWpHeadAction'));
        add_action('wp_footer', array($this, 'onWpFooterAction'), 99);
        add_filter('body_class', array($this, 'onBodyClassFilter'));
        
        // Hook with 3rd party breadcrumb plugins
        if (defined('WC_VERSION')) {
            add_filter('woocommerce_get_breadcrumb', array($this, 'onWoocommerceGetBreadcrumbFilter'));
        }
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_breadcrumb_links', array($this, 'onWpseoBreadcrumbLinksFilter'));
        }
        if (class_exists('\breadcrumb_navxt', false)) {
            add_action('bcn_after_fill', array($this, 'onBcnAfterFillAction'));
        }
        if (class_exists('\RankMath', false)) {
            add_filter('rank_math/frontend/breadcrumb/items', [$this, 'onRankmathFrontendBreadcrumbItemsAction'], 10, 2);
        }


        if (isset($this->_context)) {                
            add_filter('the_title', array($this, 'onTheTitleFilter'), PHP_INT_MAX - 1, 2);
        }
        return $this;
    }

    public function onWpHeadAction()
    {
        echo $this->_headHtml;
    }
    
    public function onWpFooterAction()
    {
        echo $this->_jsHtml;
    }
    
    public function onBodyClassFilter($classes)
    {
        if (isset($this->_context)) {
            $route = $this->_context->getRoute();
            $classes[] = 'drts-' . strtolower($route->controller_component . '-' . $route->controller);
        }
        return $classes;
    }
    
    public function onWoocommerceGetBreadcrumbFilter($crumbs)
    {
        if (isset($GLOBALS['drts_entity'])) {
            if ($GLOBALS['drts_entity']->getType() === 'user') {
                $crumbs[] = [
                    $GLOBALS['drts_entity']->getTitle(),
                    home_url($GLOBALS['wp']->request) // current URL
                ];
            } else {
                if ($page = $this->_getPostArchivePageForBreadcrumbs()) {
                    $home = array_shift($crumbs);
                }

                // Add taxonomy term crumbs?
                if ($GLOBALS['drts_entity']->getType() === 'post') {
                    if (($tax_bundle_type = apply_filters('drts_wordpress_wc_breadcrumbs_tax_type', null, $GLOBALS['drts_entity']))
                        && ($term = $GLOBALS['drts_entity']->getSingleFieldValue($tax_bundle_type))
                    ) {
                        array_unshift($crumbs, array($term->getTitle(), get_term_link($term->getId(), $term->getBundleName())));
                        // Add crumbs for parent terms if any
                        if ($parent_terms = get_ancestors($term->getId(), $term->getBundleName(), 'taxonomy')) {
                            foreach ($parent_terms as $term_id) {
                                $parent_term = get_term($term_id, $term->getBundleName());
                                if ($parent_term instanceof \WP_Term) {
                                    array_unshift($crumbs, array($parent_term->name, get_term_link($parent_term->term_id, $term->getBundleName())));
                                }
                            }
                        }
                    }
                } elseif ($GLOBALS['drts_entity']->getType() === 'term') {
                    // Remove taxonomy name
                    array_shift($crumbs);
                }

                if (isset($home)) {
                    // Add custom archive page
                    array_unshift($crumbs, array($page->post_title, get_permalink($page)));
                    // Add back home
                    array_unshift($crumbs, $home);
                }

                // WC does not seem to include parent post in crumbs, so add it if any
                if ($GLOBALS['drts_entity']->getType() === 'post') {
                    if (($parent_post_id = $GLOBALS['drts_entity']->getParentId())
                        && ($parent_post = get_post($parent_post_id))
                    ) {
                        $current_post = array_pop($crumbs);
                        $crumbs[] = [$parent_post->post_title, get_permalink($parent_post)];
                        $crumbs[] = $current_post;
                    }
                }

                // Add link to entity permalink if on an action page
                if (get_query_var('drts_action')) {
                    $current_post = array_pop($crumbs);
                    $crumbs[] = [$this->_getEntityTitle($GLOBALS['drts_entity']), $current_post[1]];
                    $crumbs[] = [$this->_context->getTitle()];
                }
            }
        }
        return $crumbs;
    }

    protected function _getEntityTitle($entity)
    {
        $title = $entity->getTitle();
        return strlen($title) ? $title : __('(no title)', 'directories');
    }
    
    public function onWpseoBreadcrumbLinksFilter($crumbs)
    {
        if (isset($GLOBALS['drts_entity'])) {
            if ($GLOBALS['drts_entity']->getType() === 'user') {
                array_push($crumbs, [
                    'url' => home_url($GLOBALS['wp']->request), // current URL
                    'text' => $GLOBALS['drts_entity']->getTitle(),
                ]);
            } else {
                if ($page = $this->_getPostArchivePageForBreadcrumbs()) {
                    $home = array_shift($crumbs);
                    // Add custom archive page
                    array_unshift($crumbs, ['url' => get_permalink($page), 'text' => $page->post_title]);
                    // Add back home
                    array_unshift($crumbs, $home);
                }
                // Add link to post permalink if on an action page
                if (get_query_var('drts_action')) {
                    $action_crumb = array_pop($crumbs);
                    array_push($crumbs, [
                        'url' => get_permalink($GLOBALS['drts_entity']->getId()),
                        'text' => $this->_getEntityTitle($GLOBALS['drts_entity']),
                    ]);
                    array_push($crumbs, $action_crumb);
                }
            }
        }
        return $crumbs;
    }

    public function onRankmathFrontendBreadcrumbItemsAction($crumbs, $class)
    {
        if (isset($GLOBALS['drts_entity'])) {
            if ($GLOBALS['drts_entity']->getType() === 'user') {
                array_push($crumbs, [
                    $GLOBALS['drts_entity']->getTitle(),
                    home_url($GLOBALS['wp']->request), // current URL
                    'hide_in_schema' => false,
                ]);
            } else {
                if ($page = $this->_getPostArchivePageForBreadcrumbs()) {
                    $home = array_shift($crumbs);
                    // Add custom archive page
                    array_unshift($crumbs, [
                        $page->post_title,
                        get_permalink($page),
                        'hide_in_schema' => false,
                    ]);
                    // Add back home
                    array_unshift($crumbs, $home);
                }

                // Add link to post permalink if on an action page
                if (get_query_var('drts_action')) {
                    $action_crumb = array_pop($crumbs);
                    array_push($crumbs, [
                        $this->_getEntityTitle($GLOBALS['drts_entity']),
                        get_permalink($GLOBALS['drts_entity']->getId()),
                        'hide_in_schema' => false,
                    ]);
                    array_push($crumbs, [$this->_context->getTitle(), null]);
                }
            }
        }
        return $crumbs;
    }
    
    public function onBcnAfterFillAction($bcnBreadcrumbTrail)
    {
        if (isset($GLOBALS['drts_entity'])) {
            if ($GLOBALS['drts_entity']->getType() === 'user') {
                array_unshift($bcnBreadcrumbTrail->breadcrumbs, new \bcn_breadcrumb($GLOBALS['drts_entity']->getTitle()));
            } else {
                if ($page = $this->_getPostArchivePageForBreadcrumbs()) {
                    $home = array_pop($bcnBreadcrumbTrail->breadcrumbs);
                    // Add custom archive page
                    $bcnBreadcrumbTrail->add(new \bcn_breadcrumb(
                        $page->post_title,
                        $bcnBreadcrumbTrail->opt['Hpost_page_template'],
                        array('post', 'post-page'),
                        get_permalink($page),
                        $page->ID
                    ));
                    // Add back home
                    $bcnBreadcrumbTrail->add($home);
                }
                // Add link to post permalink if on an action page
                if (get_query_var('drts_action')) {
                    $post_crumb = array_shift($bcnBreadcrumbTrail->breadcrumbs);
                    $post_crumb->set_title($this->_getEntityTitle($GLOBALS['drts_entity']));
                    $post_crumb->set_url(get_permalink($GLOBALS['drts_entity']->getId()));
                    array_unshift($bcnBreadcrumbTrail->breadcrumbs, $post_crumb);
                    array_unshift($bcnBreadcrumbTrail->breadcrumbs, new \bcn_breadcrumb($this->_context->getTitle()));
                }
            }
        }
    }

    protected function _getPostArchivePageForBreadcrumbs()
    {
        if (!$page_name = get_query_var('drts_parent_pagename')) {
            if ($GLOBALS['drts_entity']->getType() === 'term') {
                // Single taxonomy term page with same slug as single post page
                $page_name = get_query_var('drts_pagename');
            }
        }
        if ($page_name
            && ($page = get_page_by_path($page_name))
            && (get_option('show_on_front') !== 'page' || (int)get_option('page_on_front') !== $page->ID)
        ) {
            return $page;
        }
    }

    public function onTheTitleFilter($title, $pageId = null)
    {
        return $this->_isFilteringSabaiPage($pageId) ? $this->_context->getTitle(true) : $title;
    }
    
    private function _isFilteringSabaiPage($pageId)
    {
        if (!$pageId = (int)$pageId) return false;
        
        if (is_page()
            || is_tax()
        ) {
            if (!$this->_skipInTheLoopCheck
                || (is_array($this->_skipInTheLoopCheck) && !in_array($pageId, $this->_skipInTheLoopCheck))
            ) {
                if (!in_the_loop()) return false;
            }
            if (in_array($pageId, $this->_pageIds)) return true;

            if (is_tax()
                && defined('DRTS_WORDPRESS_FORCE_TAX_PAGE_TITLE')
                && DRTS_WORDPRESS_FORCE_TAX_PAGE_TITLE
                && in_array(get_post_type($pageId), $this->_postTypes)
                && !wp_get_post_parent_id($pageId)
            ) {
                return true;
            }

            return false;
        }
        return isset($GLOBALS['drts_entity'])
            && $GLOBALS['drts_entity']->getId() === $pageId;
    }
}