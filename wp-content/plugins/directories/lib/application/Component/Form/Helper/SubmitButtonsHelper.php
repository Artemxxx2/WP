<?php
namespace SabaiApps\Directories\Component\Form\Helper;

use SabaiApps\Directories\Application;

class SubmitButtonsHelper
{    
    public function help(Application $application, array $buttons = null, array $options = [])
    {
        if (!isset($buttons)) {
            $buttons = array(array('#btn_label' => __('Submit', 'directories'), '#btn_color' => 'primary'));
        } else {
            if (empty($buttons)) return;
        }

        $margin = isset($options['margin']) ? $options['margin'] : DRTS_BS_PREFIX . 'mt-5';
        $submits = array(
            '#tree' => true,
            '#weight' => 99999,
            '#group' => false,
            '#prefix' => '<div class="drts-form-buttons ' . DRTS_BS_PREFIX . 'form-inline ' . $margin . '">',
            '#suffix' => '</div>',
        );
        
        // Add submit button and cancel link
        $margin_class = $application->getPlatform()->isRtl() ? DRTS_BS_PREFIX . 'ml-3' : DRTS_BS_PREFIX . 'mr-3';
        foreach ($buttons as $name => $button) {
            if (!isset($button['#attributes'])) {
                $button['#attributes'] = [];
            }
            $submits[$name] = $button + array('#type' => 'submit', '#value' => $name);
            if ($submits[$name]['#type'] !== 'submit') {
                if (!isset($submits[$name]['#tree'])) {
                    // Do not prefix with FORM_SUBMIT_BUTTON_NAME
                    $submits[$name]['#tree'] = false;
                }
                if (!isset($submits[$name]['#class'])) $submits[$name]['#class'] = '';
                $submits[$name]['#class'] .= ' ' . $margin_class;
                continue;
            }
            if (!isset($submits[$name]['#attributes']['class'])) $submits[$name]['#attributes']['class'] = '';
            $submits[$name]['#attributes']['class'] .= ' ' . $margin_class;
            if (isset($options['default_callback']) && !isset($submits[$name]['#submit'])) {
                $submits[$name]['#submit'] = array(
                    10 => array( // weight
                        $options['default_callback'],
                    ),
                );
                // Prevent callback called more than once
                unset($options['default_callback']);
            }
            
        }
        return $submits;
    }
}