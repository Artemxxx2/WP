<?php
namespace SabaiApps\Directories\Component\WordPressContent\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Form;
use SabaiApps\Directories\Component\Entity;

class PageSettingsFormHelper
{
    public function help(Application $application, Entity\Model\Bundle $bundle, array $parents = [])
    {
        if (empty($bundle->info['public'])) return;

        $page_slugs = $application->getPlatform()->getPageSlugs();

        $form = [
            '#js_ready' => array('$("#__FORM_ID__ select").toggleClass("' . DRTS_BS_PREFIX . 'form-control", true);'),
            '#submit' => [
                9 => [
                    [[$this, 'submitForm'], [$application, $bundle->name, $parents]],
                ],
            ],
            'page' => [
                '#type' => 'item',
                '#title' => __('Assign page', 'directories'),
                '#markup' => wp_dropdown_pages([
                    'depth' => 2,
                    'echo' => 0,
                    'show_option_none' => __('— Select page —', 'directories'),
                    'name' => $application->Form_FieldName(array_merge($parents, ['page'])),
                    'selected' => $application->getComponent('WordPressContent')->getBundleSingleItemPageId($bundle),
                ]),
                '#horizontal' => true,
                '#description' => sprintf(
                    $application->H(__('Shortcode %s can be used to customize the content of the page.', 'directories')),
                    '<code>[drts-entity]</code>'
                ),
                '#description_no_escape' => true,
                '#weight' => 20,
            ],
        ];
        if (empty($bundle->info['parent'])
            && empty($bundle->info['is_user'])
            && ($permalink = (array)$application->Entity_BundleTypeInfo($bundle, 'permalink'))
        ) {
            $current = isset($page_slugs[4][$bundle->name]) ? $page_slugs[4][$bundle->name] : [];
            if (isset($current['base'])) {
                $base = $current['base'];
            } else {
                if (is_array($permalink)
                    && isset($permalink['slug'])
                ) {
                    $base = strtolower($bundle->component) . '-' . $permalink['slug'];
                } else {
                    $base = str_replace(array('_', '--'), array('-', '-'), $bundle->type);
                }
            }
            $path = isset($current['path']) ? $current['path'] : '%slug%';
            
            if (!empty($bundle->info['is_taxonomy'])) {
                $paths = ['%slug%'];
                if (!empty($bundle->info['is_hierarchical'])) {
                    $paths[] = '%parent_term%/%slug%';
                }
            } else {
                $paths = array('%id%', '%slug%');
                if (!empty($bundle->info['taxonomies'])) {
                    foreach (array_keys($bundle->info['taxonomies']) as $taxonomy_type) {
                        $paths[] = '%' . $taxonomy_type . '%/%id%';
                        $paths[] = '%' . $taxonomy_type . '%/%slug%';
                    }
                }
            }
            $options = [];
            foreach ($paths as $_path) {
                $display_path = str_replace('/', ' / ', trim($_path, '/'));
                $selected = $_path === $path ? ' selected="selected"' : '';
                $options[] = '<option value="' . $_path . '"' . $selected . '>' . $display_path . '</option>';
            }
            $field_name_prefix = $application->Form_FieldName(array_merge($parents, ['permalink']));
            $form['permalink'] = array(
                '#element_validate' => array(
                    array(array($this, '_validatePermalinkSettings'), array($application, $bundle->name, isset($page_slugs[4]) ? $page_slugs[4] : []))
                ),
                '#tree' => true,
                'custom' => array(
                    '#title' => __('Enable custom permalink', 'directories'),
                    '#type' => 'checkbox',
                    '#default_value' => !empty($current),
                    '#horizontal' => true,
                    '#weight' => 10,
                ),
                'url' => array(
                    '#type' => 'item',
                    '#markup' => '<div class="' . DRTS_BS_PREFIX . 'form-inline"><code>' . rtrim($application->getPlatform()->getMainUrl(), '/') . '/</code>
<input class="' . DRTS_BS_PREFIX . 'form-control" name="' . $field_name_prefix . '[base]" type="text" size="20" value="' . $application->H($base) . '" />
<code>/</code>
<select class="' . DRTS_BS_PREFIX . 'form-control" name="' . $field_name_prefix . '[path]">' . implode(PHP_EOL, $options) . '</select></div>',
                    '#horizontal' => true,
                    '#states' => array(
                        'visible' => array(
                            'input[name="' . $field_name_prefix . '[custom]"]' => array('type' => 'checked', 'value' => true),
                        ),
                    ),
                    '#weight' => 11,
                ),
            );
        }
        
        return $form;
    }
    
    public function _validatePermalinkSettings(Form\Form $form, &$value, array $element, Application $application, $bundleName, array $current)
    {
        if (empty($value['custom'])) return;

        $value['base'] = strtolower(trim($value['base'], " \t\n\r\0\x0B/"));
        if (!strlen($value['base'])) {
            $form->setError(__('Permalink URL base may not be empty', 'directories'), $element['#name'] . '[url]');
            return;
        }
        if (!preg_match('#^[a-z0-9/_-]+$#', $value['base'])) {
            $form->setError(sprintf(__('Invalid permalink URL base: %s', 'directories'), $value['base']), $element['#name'] . '[url]');
            return;
        }

        $page_to_check = ($pos = strpos($value['base'], '/')) ? substr($value['base'], $pos) : $value['base'];
        if (get_page_by_path($page_to_check)) {
            $form->setError(sprintf(__('Page with path %s already exists', 'directories'), $value['base']), $element['#name'] . '[url]');
            return;
        }


        // Make sure the same slug is not used by multiple taxonomies.
        if ((!$bundle = $application->Entity_Bundle($bundleName)) // this should not happen
            || empty($bundle->info['is_taxonomy'])
        ) {
            return;
        }
        $current_lang = (string)$application->getPlatform()->getCurrentLanguage();
        $all_bases = [$current_lang => []];
        // Get currently configured base paths
        foreach ($current as $bundle_name => $permalink_setting) {
            $all_bases[$current_lang][$bundle_name] = $permalink_setting['base'];
        }
        // Overwrite those submitted
        $all_bases[$current_lang][$bundleName] = $value['base'];
        // Get from other languages
        foreach ($application->getPlatform()->getLanguages() as $lang) {
            if ($lang === $current_lang) continue;
            
            $pages_slugs = $application->getPlatform()->getPageSlugs($lang);
            if (!empty($pages_slugs[4])) {
                // Get currently configured base paths for this language
                foreach ($pages_slugs[4] as $bundle_name => $permalink_setting) {
                    $all_bases[$lang][$bundle_name] = $permalink_setting['base'];
                }
            }
        }
        // Check for duplicates
        foreach (array_keys($all_bases) as $lang) {
            if ($lang === $current_lang) {
                unset($all_bases[$lang][$bundleName]); // exclude self
            }
            if (($_bundle_name = array_search($value['base'], $all_bases[$lang]))
                && ($_bundle = $application->Entity_Bundle($_bundle_name))
                && !empty($_bundle->info['is_taxonomy'])
            ) {
                $form->setError(
                    sprintf(
                        __('Permalink URL base %s is already in use by %s.', 'directories'),
                        $value['base'],
                        $_bundle->getLabel('singular', $lang) . ($lang === $current_lang ? '' : ' (' . $lang . ')')
                    ),
                    $element['#name'] . '[url]'
                );
            }
        }
    }
    
    public function submitForm(Form\Form $form, Application $application, $bundleName, array $parents)
    {
        if (!$bundle = $application->Entity_Bundle($bundleName)) return; // this shouldn't fail

        $slugs = $application->System_Slugs();
        $slug_name = $bundle->group . '-' . $bundle->info['slug'];
        if (!isset($slugs[$bundle->component][$slug_name])) return;

        $slug = $slugs[$bundle->component][$slug_name];
        $page_slugs = $application->getPlatform()->getPageSlugs();
        $page_slug = $page_slugs[1][$slug['component']][$slug['parent']] . '/' . $slug['slug'];
        unset($page_slugs[4][$bundleName], $page_slugs[2][$page_slug], $page_slugs[5][$page_slug]['page_name']);
        $value = $form->getValue($parents);

        if (!empty($value['page'])
            && ($page = get_page($value['page']))
        ) {
            call_user_func_array([$application, 'WordPress_PageSettingsForm_saveSingle'], [$bundle, $slug_name, $page_slug, &$page_slugs, $page]);
        }

        if (!empty($value['permalink']['custom'])) {
            unset($value['permalink']['custom']);
            $path = $value['permalink']['path'] = trim($value['permalink']['path'], '/');
            if ($path === '%id%') {
                $regex = array(
                    array(
                        'regex' => preg_quote($value['permalink']['base']) . '/([0-9]+)',
                        'type' => 'id',
                    ),
                );
            } elseif ($path === '%slug%') {
                $regex = array(
                    array(
                        'regex' => preg_quote($value['permalink']['base']) . '/([^/]+)',
                        'type' => 'slug',
                    ),
                );
            } else {
                $type = strpos($path, '%id%') !== false ? 'id' : 'slug';
                $tags = array('%slug%' => '([^/]+)', '%id%' => '([0-9]+)');
                if (!empty($bundle->info['is_taxonomy'])) {
                    if (!empty($bundle->info['is_hierarchical'])) {
                        $tags['%parent_term%'] = '.+';
                    }
                } else {
                    foreach (array_keys($bundle->info['taxonomies']) as $taxonomy_type) {
                        $tags['%' . $taxonomy_type . '%'] = array(
                            'regex' => '.+',
                            'taxonomy' => $taxonomy_type,
                        );
                    }
                }
                foreach ($tags as $tag => $tag_info) {
                    if (strpos($value['permalink']['path'], $tag) === false) continue;

                    if (!is_array($tag_info)) {
                        $replace = $tag_info;
                    } else {
                        $replace = $tag_info['regex'];
                        if (isset($tag_info['taxonomy'])) {
                            $value['permalink']['taxonomies'][$tag] = $tag_info['taxonomy'];
                        }
                    }
                    $path = str_replace($tag, $replace, $path);
                }
                $regex = array(
                    array(
                        'regex' => preg_quote($value['permalink']['base']) . ($type === 'id' ? '/([0-9]+)' : '/([^/]+)'), // for entities without taxonomies
                        'type' => $type,
                    ),
                );
                if ($path) {
                    $regex[] = array(
                        'regex' => preg_quote($value['permalink']['base']) . '/' . ltrim(rtrim($path, '/'), '/'),
                        'type' => $type,
                    );
                }
            }
            $page_slugs[4][$bundle->name] = array(
                'component' => $bundle->component,
                'slug' => $slug_name,
                'parent' => isset($slug['parent']) ? $slug['parent'] : null,
                'regex' => $regex,
                empty($bundle->info['is_taxonomy']) ? 'post_type' : 'taxonomy' => $bundle->name,
                'base_route' => trim($bundle->getPath(), '/'),
            ) + $value['permalink'];
        }
        
        $application->getPlatform()->setPageSlugs($page_slugs);
    }
}