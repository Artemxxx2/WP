<?php
namespace SabaiApps\Directories\Component\Entity\Controller\Admin;

use SabaiApps\Directories\Context;
use SabaiApps\Directories\Component\Display\Controller\Admin\AbstractDisplays;

class Displays extends AbstractDisplays
{
    protected $_enableCSS = true, $_hideTabsIfSingle = false;
    
    protected function _getDisplays(Context $context)
    {
        return $this->Entity_Displays($context->bundle);
    }
    
    protected function _getSettingsForm(Context $context, array &$formStorage)
    {
        $form = parent::_getSettingsForm($context, $formStorage);
        foreach (array_keys($form['#displays']) as $default_display_name) {
            foreach ($form['#displays'][$default_display_name] as $display_name) {
                if (!$template = $this->Entity_Display_hasCustomTemplate($context->bundle, $display_name)) continue;

                $form[$default_display_name][$display_name]['#prefix'] = '<div class="' . DRTS_BS_PREFIX . 'alert ' . DRTS_BS_PREFIX . 'alert-warning">'
                    . sprintf(
                        $this->H(__('Template file for this display was found at %s. Display settings on this page are ignored.', 'directories')),
                        '<code>' . $this->H($template) . '</code>'
                    ) . '</div>';
            }
        }
        
        return $form;
    }
}