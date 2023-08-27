<?php
namespace SabaiApps\Directories\Component\Entity\SystemTool;

use SabaiApps\Directories\Component\System\Tool\AbstractTool;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Exception;

class RecountSystemTool extends AbstractTool
{
    protected function _systemToolInfo()
    {
        return [
            'label' => __('Recount posts', 'directories'),
            'description' => __('This tool will recount the number of posts associated with each content item.', 'directories'),
            'weight' => 50,
        ];
    }

    public function systemToolSettingsForm(array $parents = [])
    {
        $form = [
            'type' => [
                '#type' => 'checkboxes',
                '#options' => [
                    'term' => __('Recount term posts', 'directories'),
                ],
                '#default_value' => ['term'],
            ],
        ];
        if ($this->_application->Entity_BundleTypes_children()) {
            $form['type']['#options']['child'] = __('Recount child posts', 'directories');
            $form['type']['#default_value'][] = 'child';
        }
        $form['num'] = [
            '#type' => 'number',
            '#title' => __('Number of records to process per request', 'directories'),
            '#horizontal' => true,
            '#default_value' => 50,
            '#min_value' => 1,
            '#integer' => true,
            '#required' => true,
        ];
        
        return $form;
    }

    public function systemToolInit(array $settings, array &$storage, array &$logs)
    {
        $ret = [];
        if (!empty($settings['type'])) {
            $langs = $this->_application->getPlatform()->getLanguages();
            if (in_array('term', $settings['type'])) {
                $this->_application->Entity_TaxonomyContentBundleTypes_clear(); // clear cache
                foreach ($this->_application->Entity_Bundles() as $bundle) {
                    if (empty($bundle->info['is_taxonomy'])) continue;

                    if (empty($langs)
                        || !$this->_application->getPlatform()->isTranslatable($bundle->entitytype_name, $bundle->name)
                    ) {
                        $ret['term-' . $bundle->name] = $this->_countTerms($bundle);
                    } else {
                        foreach ($langs as $lang) {
                            $ret['term-' . $bundle->name . '-' . $lang] = $this->_countTerms($bundle, $lang);
                        }
                    }
                }
            }
            if (in_array('child', $settings['type'])) {
                foreach ($this->_application->Entity_BundleTypes_children() as $bundle_type => $child_bundle_types) {
                    $bundle_type_info = $this->_application->Entity_BundleTypeInfo($bundle_type);
                    if (empty($langs)) {
                        $ret['child-' . $bundle_type] = $this->_countPosts($bundle_type_info);
                    } else {
                        foreach ($langs as $lang) {
                            $ret['child-' . $bundle_type . '-' . $lang] = $this->_countPosts($bundle_type_info, $lang);
                        }
                    }
                }
            }
        }
        
        return $ret;
    }

    protected function _countTerms(Bundle $bundle, $lang = null)
    {
        return $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)->count($lang);
    }

    protected function _countPosts(array $bundleTypeInfo, $lang = null)
    {
        return $this->_application->Entity_Query($bundleTypeInfo['entity_type'])
            ->fieldIs('bundle_type', $bundleTypeInfo['type'])
            ->fieldIs('status', $this->_application->Entity_Status($bundleTypeInfo['entity_type'], 'publish'))
            ->count($lang);
    }

    public function systemToolRunTask($task, array $settings, $iteration, $total, array &$storage, array &$logs)
    {
        $task_parts = explode('-', $task);
        switch ($task_parts[0]) {
            case 'term':
                if (empty($task_parts[1])
                    || (!$bundle = $this->_application->Entity_Bundle($task_parts[1]))
                ) return false;

                $lang = empty($task_parts[2]) ? null : $task_parts[2];
                $paginator = $this->_application->Entity_Query($bundle->entitytype_name, $bundle->name)
                    ->sortById()
                    ->paginate($settings['num'], 0, $lang)
                    ->setCurrentPage($iteration);
                if ($terms = $paginator->getElements()) {
                    foreach ($this->_application->Entity_TaxonomyContentBundleTypes($bundle->type) as $content_bundle_type) {
                        if (!$content_bundle = $this->_application->Entity_Bundle($content_bundle_type, $bundle->component, $bundle->group)) continue;

                        try {
                            $this->_application->Entity_UpdateTermContentCount($bundle->name, $terms, $content_bundle);
                        } catch (Exception\IException $e) {
                            $logs['error'][] = $e->getMessage();
                        }
                    }
                }

                $label = $bundle->getGroupLabel() . ' - ' . $bundle->getLabel();
                $offset = $paginator->getElementOffset();
                $logs['success'][] = sprintf(
                    'Recount term posts finished for %s (%d - %d)',
                    isset($lang) ? $label . '[' . $lang . ']' : $label,
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                );

                return $paginator->getElementLimit();

            case 'child':
                if (empty($task_parts[1])
                    || (!$bundle_type_info = $this->_application->Entity_BundleTypeInfo($task_parts[1]))
                ) return false;

                $lang = empty($task_parts[2]) ? null : $task_parts[2];
                $published_status = $this->_application->Entity_Status($bundle_type_info['entity_type'], 'publish');
                $paginator = $this->_application->Entity_Query($bundle_type_info['entity_type'])
                    ->fieldIs('bundle_type', $bundle_type_info['type'])
                    ->fieldIs('status', $published_status)
                    ->paginate($settings['num'], 0, $lang)
                    ->setCurrentPage($iteration);
                if ($posts = $paginator->getElements()) {
                    $parent_ids = array_keys($posts);
                    $children_count = [];
                    // Count the total number of published child posts grouped by parent post ID and child bundle type
                    $count = $this->_application->Entity_Query($bundle_type_info['entity_type'])
                        ->fieldIsIn('parent', $parent_ids)
                        ->fieldIs('status', $published_status)
                        ->fieldIsIn('bundle_type', $this->_application->Entity_BundleTypes_children($bundle_type_info['type']))
                        ->groupByField('parent')
                        ->groupByField('bundle_type')
                        ->count($lang);
                    if (!empty($count)) {
                        foreach (array_keys($count) as $parent_id) {
                            foreach ($count[$parent_id] as $child_bundle_type => $_count) {
                                if (!empty($_count)) {
                                    $children_count[(int)$parent_id][] = ['value' => $_count, 'child_bundle_type' => $child_bundle_type];
                                }
                            }
                        }
                    }
                    foreach (array_keys($posts) as $parent_id) {
                        try {
                            $this->_application->Entity_Save(
                                $posts[$parent_id],
                                ['entity_child_count' => isset($children_count[$parent_id]) ? $children_count[$parent_id] : false]
                            );
                        } catch (Exception\IException $e) {
                            $logs['error'][] = $e->getMessage();
                        }
                    }
                }

                $label = $bundle_type_info['type'];
                $offset = $paginator->getElementOffset();
                $logs['success'][] = sprintf(
                    'Recount child posts finished for %s (%d - %d)',
                    isset($lang) ? $label . '[' . $lang . ']' : $label,
                    $offset + 1,
                    $offset + $paginator->getElementLimit()
                );

                return $paginator->getElementLimit();

            default:
                return false;
        }
    }
}