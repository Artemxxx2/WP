<?php
namespace SabaiApps\Directories\Component\Directory\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\System;

class ContentTypes extends System\Controller\Admin\AbstractSettings
{   
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $this->_submitable = false;        
        $form = array(
            '#directory' => $context->directory,
            'content' => array(
                '#type' => 'tableselect',
                '#options' => [],
                '#header' => array(
                    'name' => __('Name', 'directories'),
                    'type' => ucwords(__('Content type', 'directories')),
                    'links' => '',
                ),
                '#disabled' => true,
                '#class' => 'drts-data-table',
                '#row_attributes' => array(
                    '@all' => array(
                        'links' => array(
                            'style' => 'white-space:nowrap;text-align:' . ($this->getPlatform()->isRtl() ? 'left' : 'right') . ';',
                        ),
                    ),
                ),
            ),
        );
        $path = rtrim($context->getRoute(), '/') . '/';
        foreach ($this->Entity_Bundles_sort(null, 'Directory', $context->directory->name) as $bundle) {
            $info = $this->Entity_BundleTypeInfo($bundle);
            if (!empty($info['internal'])) continue;

            $form['content']['#options'][$bundle->name] = array(
                'name' => $this->H($bundle->getLabel('singular')) . ' <small>(' . $bundle->name . ')</small>',
                'type' => '<i class="drts-icon drts-icon-sm fa-fw ' . $info['icon'] . '"></i><span>' . $this->H($info['label_singular']) . ' <small>(' . $bundle->type . ')</small>' . '</span>',
                'links' => $this->DropdownButtonLinks(
                    $this->Entity_Bundle_adminLinks($bundle, $path . $bundle->name),
                    array('label' => true, 'right' => true, 'color' => 'outline-secondary', 'split' => true, 'btn' => false)
                ),
            );
        }
        
        return $form;
    }
}