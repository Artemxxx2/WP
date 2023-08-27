<?php
namespace SabaiApps\Directories\Component\WordPress\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;

class PageSettingsFormHelper
{    
    public function help(Application $application, array $slugs, array $parents = [])
    {        
        $form = array(
            '#js_ready' => array('$("#__FORM_ID__ select").toggleClass("' . DRTS_BS_PREFIX . 'form-control", true);'),
            '#submit' => array(
                9 => array( // weight
                    array(array($this, 'submitForm'), array($application, $slugs, $parents)),
                ),
            ),
        );
        
        $page_slugs = $application->getPlatform()->getPageSlugs();
        $weight = 0;
        uasort($slugs, function($a, $b) { return empty($a['parent']) && empty($b['parent']) ? 0 : (empty($a['parent']) ? -1 : 1);});
        foreach (array_keys($slugs) as $slug_name) {
            $slug = $slugs[$slug_name];
            if (!empty($slug['parent'])) continue;

            $current_id = (null !== $current_slug = @$page_slugs[1][$slug['component']][$slug_name]) && isset($page_slugs[2][$current_slug]) ? $page_slugs[2][$current_slug] : null;
            $form[$slug_name]['#title'] = $slug['admin_title'];
            $form[$slug_name]['#horizontal'] = true;
            $form[$slug_name]['#weight'] = isset($slug['weight']) ? $slug['weight'] : $weight + 10;
            $form[$slug_name]['id'] = array(
                '#type' => 'item',
                '#markup' => wp_dropdown_pages(array(
                    'depth' => 1,
                    'echo' => 0,
                    'show_option_none' => __('— Select page —', 'directories'),
                    'name' => $application->Form_FieldName(array_merge($parents, array($slug_name, 'id'))),
                    'selected' => $current_id,
                )),
            );
            if (!isset($slug['required']) || $slug['required']) {
                $form[$slug_name]['#display_required'] = true;
                $form[$slug_name]['id']['#element_validate'] = [
                    [[$this, '_validatePageSettings'], [$slug_name, empty($slug['parent']), $parents]],
                ];
            }
            if (!empty($slug['wp_shortcode'])) {
                if (is_array($slug['wp_shortcode'])) {
                    $shortcode = '[' . $slug['wp_shortcode'][0] . $application->Attr($slug['wp_shortcode'][1]) . ']';
                } else {
                    $shortcode = '[' . $slug['wp_shortcode'] . ']';
                }
                $form[$slug_name]['id']['#description'] = sprintf(
                    $application->H(__('Shortcode %s can be used to customize the content of the page.', 'directories')),
                    '<code>' . $shortcode . '</code>'
                );
                $form[$slug_name]['id']['#description_no_escape'] = true;
            }
        }
        
        return $form;
    }
    
    public function _validatePageSettings(Form\Form $form, &$value, $element, $slug, $isRoot, $parents)
    {
        if ($isRoot
            && !$form->getValue(array_merge($parents, array($slug, 'id')))
        ) {
            $form->setError(__('Please select a page', 'directories'), $element);
        }
    }
    
    public function submitForm(Form\Form $form, Application $application, $slugs, $parents)
    {
        $this->_save($application, (array)$form->getValue($parents), $slugs, $application->getPlatform()->getPageSlugs());
    }

    protected function _save(Application $application, array $values, $slugs, $pageSlugs)
    {
        // Save pages
        $main_url = strtok($application->getPlatform()->getMainUrl(), '?');
        foreach ($slugs as $slug_name => $slug) {
            $old_slug = null;
            if (isset($pageSlugs[1][$slug['component']][$slug_name])) {
                $old_slug = $pageSlugs[1][$slug['component']][$slug_name];
                unset($pageSlugs[1][$slug['component']][$slug_name]);
            }

            if (!empty($slug['parent'])) {
                if (!isset($pageSlugs[1][$slug['component']][$slug['parent']]) // no valid parent
                    || empty($slug['bundle_type'])
                    || (!$bundle = $application->Entity_Bundle($slug['bundle_type'], $slug['component'], isset($slug['bundle_group']) ? $slug['bundle_group'] : ''))
                ) {
                    if (isset($old_slug)) {
                        unset($pageSlugs[0][$old_slug], $pageSlugs[5][$old_slug]);
                    }
                    continue;
                }

                // Save taxonomy or post type and slug
                $new_slug = $pageSlugs[1][$slug['component']][$slug['parent']] . '/' . $slug['slug'];
                $this->saveSingle($application, $bundle, $slug_name, $new_slug, $pageSlugs);
                continue;
            }

            if (empty($values[$slug_name])) continue;

            $value = $values[$slug_name];
            if (empty($value['id'])
                || (!$page = get_page($value['id']))
            ) {
                // No page, save slug info only
                if (isset($old_slug)) {
                    unset($pageSlugs[0][$old_slug], $pageSlugs[2][$old_slug]);
                }
                $new_slug = $slug['slug'];
                $pageSlugs[1][$slug['component']][$slug_name] = $new_slug;
                continue;
            }
            $new_slug = str_replace($main_url, '', strtok(get_permalink($page->ID), '?'));
            if (strpos($new_slug, 'index.php') === 0) {
                $new_slug = substr($new_slug, strlen('index.php'));
            }
            $new_slug = trim($new_slug, '/');
            // Set post name as slug if the selected page is the front page
            if ($new_slug === '') {
                $new_slug = $page->post_name;
            }
            $pageSlugs[0][$new_slug] = $new_slug;
            $pageSlugs[1][$slug['component']][$slug_name] = $new_slug;
            $pageSlugs[2][$new_slug] = $value['id'];
        }

        // Clear slugs that do not exist or no longer a sabai page slug
        $valid_slugs = [];
        if (!empty($pageSlugs[1])) {
            foreach (array_keys($pageSlugs[1]) as $component_name) {
                if (!$application->isComponentLoaded($component_name)) {
                    unset($pageSlugs[1][$component_name]);
                    continue;
                }
                foreach ($pageSlugs[1][$component_name] as $slug) {
                    $valid_slugs[$slug] = $slug;
                }
            }
        }
        $pageSlugs[0] = empty($pageSlugs[0]) ? [] : array_intersect_key($pageSlugs[0], $valid_slugs); // slugs
        $pageSlugs[2] = empty($pageSlugs[2]) ? [] : array_intersect_key($pageSlugs[2], $valid_slugs); // ids
        if (!empty($pageSlugs[5])) {
            $pageSlugs[5] = array_intersect_key($pageSlugs[5], $valid_slugs); // post type slugs
        }

        $application->getPlatform()->setPageSlugs($pageSlugs);

        // Upgrade all ISlug components since slugs have been updated
        $application->System_Component_upgradeAll(array_keys($application->System_Slugs()));

        // Reload main routes
        $application->getComponent('System')->reloadAllRoutes(true);
    }

    /**
     * Reload page settings without submitting form
     * @param Application $application
     */
    public function reload(Application $application)
    {
        if (!$slugs = $application->System_Slugs(null, 'Directory')) return;

        $_slugs = [];
        foreach (array_keys($slugs) as $component_name) {
            $_slugs += $slugs[$component_name];
        }
        $slugs = $_slugs;
        $page_slugs = $application->getPlatform()->getPageSlugs();
        $values = [];
        foreach (array_keys($slugs) as $slug_name) {
            $slug = $slugs[$slug_name];
            if (!empty($slug['parent'])) continue;

            if ((null !== $current_slug = @$page_slugs[1][$slug['component']][$slug_name])
                && isset($page_slugs[2][$current_slug])
            ) {
                $values[$slug_name]['id'] = $page_slugs[2][$current_slug];
            }
        }
        $this->_save($application, $values, $slugs, $page_slugs);
    }

    public function saveSingle(Application $application, $bundle, $slugName, $slug, array &$pageSlugs, $page = null)
    {
        $pageSlugs[0][$slug] = $slug;
        $pageSlugs[1][$bundle->component][$slugName] = $slug;
        if (isset($page)) {
            $main_url = strtok($application->getPlatform()->getMainUrl(), '?');
            $page_name = str_replace($main_url, '', strtok(get_permalink($page->ID), '?'));
            if (strpos($page_name, 'index.php') === 0) {
                $page_name = substr($page_name, strlen('index.php'));
            }
            $page_name = trim($page_name, '/');
            $pageSlugs[2][$slug] = $page->ID;
        } else {
            // Make sure not to remove previously assigned page name
            $page_name = isset($pageSlugs[5][$slug]['page_name']) ? $pageSlugs[5][$slug]['page_name'] : null;
        }

        if (empty($bundle->info['is_user'])) {
            $taxonomy_or_post_type = !empty($bundle->info['is_taxonomy']) ? 'taxonomy' : 'post_type';

            // Remove old setting
            if (!empty($pageSlugs[5])) {
                foreach (array_keys($pageSlugs[5]) as $_slug) {
                    if (isset($pageSlugs[5][$_slug][$taxonomy_or_post_type])
                        && $pageSlugs[5][$_slug][$taxonomy_or_post_type] === $bundle->name
                    ) {
                        unset($pageSlugs[5][$_slug]);
                    }
                }
            }
        } else {
            // Remove old setting
            if (!empty($pageSlugs[5])) {
                foreach (array_keys($pageSlugs[5]) as $_slug) {
                    if (!empty($pageSlugs[5][$_slug]['is_user'])) {
                        unset($pageSlugs[5][$_slug]);
                    }
                }
            }
        }


        // Save taxonomy or post type and slug
        $pageSlugs[5][$slug] = array(
            $taxonomy_or_post_type => $bundle->name,
            'bundle_type' => $bundle->type,
            'bundle_group' => $bundle->group,
            'component' => $bundle->component,
            'is_child' => !empty($bundle->info['parent']),
            'is_user' => !empty($bundle->info['is_user']),
        );
        if (isset($taxonomy_or_post_type)) {
            $pageSlugs[5][$slug][$taxonomy_or_post_type] = $bundle->name;
        }
        if (isset($page_name)) {
            $pageSlugs[5][$slug]['page_name'] = $page_name;
        }
    }
}