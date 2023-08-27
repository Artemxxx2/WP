<?php
namespace SabaiApps\Directories\Component\Entity\Helper;

use SabaiApps\Directories\Application;
use SabaiApps\Directories\Component\Entity\Model\Bundle;
use SabaiApps\Directories\Component\Field\Type\ISortable;

class SortsHelper
{    
    /**
     * @param Application $application
     * @param Bundle|string $bundle
     * @param string $lang
     * @return array
     */
    public function help(Application $application, $bundle, $lang = null)
    {
        $cache_id = $this->_getCacheId($application, is_string($bundle) ? $bundle : $bundle->name, $lang);
        if (!$ret = $application->getPlatform()->getCache($cache_id, 'entity_sorts')) {
            $ret = [];
            $bundle = $application->Entity_Bundle($bundle);
            foreach ($application->Entity_Field($bundle) as $field) {
                if ((!$field_type = $application->Field_Type($field->getFieldType(), true))
                    || !$field_type instanceof ISortable
                    || (false === $sort_options = $field_type->fieldSortableOptions($field))
                ) continue;

                $field_title = $field->getFieldLabel(true);
                if (is_array($sort_options)) {
                    foreach ($sort_options as $sort_option) {
                        $name = $field->getFieldName();
                        if (!empty($sort_option['args'])) {
                            $name .= ',' . implode(',', $sort_option['args']);
                        }
                        $ret[$name] = array(
                            'label' => sprintf(
                                isset($sort_option['label']) ? $sort_option['label'] : '%s',
                                isset($sort_option['sub_label']) ? $field_title . ' - ' . $sort_option['sub_label'] : $field_title
                            ),
                            'field_name' => $field->getFieldName(),
                            'field_type' => $field->getFieldType(),
                        );
                    }
                } else {
                    $ret[$field->getFieldName()] = array(
                        'label' => $field_title,
                        'field_name' => $field->getFieldName(),
                        'field_type' => $field->getFieldType(),
                    );
                }
            }
            $ret['random'] = array('label' => __('Random', 'directories'));
            $ret = $application->Filter('entity_sorts', $ret, [$bundle]);
            $application->getPlatform()->setCache($ret, $cache_id, 86400, 'entity_sorts');
        }
        return $ret;
    }

    protected function _getCacheId(Application $application, $bundleName, $lang = null)
    {
        if (!isset($lang)) {
            $lang = $application->getPlatform()->getCurrentLanguage();
        }
        return 'entity_sorts_' . $bundleName . '_' . $lang;
    }

    public function clearCache(Application $application, $bundleName)
    {
        $application->getPlatform()->clearCache('entity_sorts');
    }

    public function options(Application $application, $bundle, $html = false)
    {
        if ($sorts = $application->Entity_Sorts($bundle)) {
            if ($html) {
                foreach (array_keys($sorts) as $sort_name) {
                    $sorts[$sort_name] = $application->H($sorts[$sort_name]['label'])
                        . '<span class="' . DRTS_BS_PREFIX . 'text-muted" style="font-style:italic;"> - ' . $application->H($sort_name) . '</span>';
                }
            } else {
                foreach (array_keys($sorts) as $sort_name) {
                    $sorts[$sort_name] = $sorts[$sort_name]['label'];
                }
            }
        }
        return $sorts;
    }
}