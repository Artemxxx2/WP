<?php
namespace SabaiApps\Directories\Component\DirectoryPro\SystemWidget;

use SabaiApps\Directories\Component\System\Widget\AbstractWidget;

class FiltersSystemWidget extends AbstractWidget
{    
    protected $_cacheable = false;
    
    protected function _systemWidgetInfo()
    {
        return [
            'title' => __('Filter Form', 'directories-pro'),
            'summary' => __('Displays a filter form.', 'directories-pro'),
        ];
    }
    
    protected function _getWidgetSettings(array $settings)
    {
        $directory_options = $filter_groups = [];
        foreach ($this->_application->getModel('Directory', 'Directory')->fetch() as $directory) {
            if (!$this->_application->Directory_Types_impl($directory->type, true)) continue; // make sure the directory type is active
            
            $directory_options[$directory->name] = $directory->getLabel();
        }
        if (empty($directory_options)) return;
        
        $directory_option_keys = array_keys($directory_options);
        return [
            'directory' => [
                '#title' => __('Select directory', 'directories-pro'),
                '#options' => $directory_options,
                '#type' => count($directory_options) <= 1 ? 'hidden' : 'select',
                '#default_value' => array_shift($directory_option_keys),
            ],
            'hide_on_mobile' => [
                '#title' => __('Hide on mobile', 'directories-pro'),
                '#type' => 'checkbox',
                '#default_value' => false,
            ],
        ];
    }
    
    protected function _getWidgetContent(array $settings)
    {       
        if (!isset($settings['directory'])
            || !strlen($settings['directory'])
            || !isset($GLOBALS['drts_view_entites_context'])
            || !isset($GLOBALS['drts_view_entites_context']['bundle'])
            || (!empty($settings['hide_on_mobile']) && $this->_application->isMobile())
        ) return;

        $context = $GLOBALS['drts_view_entites_context'];
        if (!empty($context['bundle']->info['parent'])
            || !empty($context['bundle']->info['internal'])
        ) return;

        if ($sep_pos = strpos($settings['directory'], ',')) { // for compat with <v1.3.38
            $directory_name = substr($settings['directory'], 0, $sep_pos);
        } else {
            $directory_name = $settings['directory'];
        }

        if ($directory_name !== $GLOBALS['drts_view_entites_context']['bundle']->group) return;

        $view = $this->_application->getModel('View', 'View')
            ->bundleName_is($context['bundle']->name)
            ->default_is(1)
            ->fetchOne();
        if (!$view) return;

        $filter_group = empty($view->data['settings']['filter']['display']) ? 'default' : $view->data['settings']['filter']['display'];

        $container = isset($context['filter_target']) ? $context['filter_target'] : $context['container'];
        $form = $this->_application->View_FilterForm(
            $context['bundle']->name,
            $context['query'],
            $filter_group,
            array(
                'container' => $container,
                'filters' => $context['filters'], 
                'values' => $context['filter_values'],
                'url' => $this->_application->Url($context['route'], $context['url_params']),
                'push_state' => true,
            )
        );
        if (!$form) return;

        if (!empty($context['filter_show'])) {
            $form['#header'][] = [
                'level' => 'danger',
                'message' => 'The filter form in this widget will not work properly if the filler form is also enabled in the main content section of the page. Please disable the one in the main content section by following the instructions in the documentation <a href="https://directoriespro.com/documentation/getting-started/adding-widgets.html#directories-filter-form" target="_blank" rel="noopener">here</a>.',
                'no_escape' => true,
            ];
        }
        
        $form['#js_ready'][] = 'DRTS.init("#__FORM_ID__");';

        if (!$filter_form = $this->_application->View_FilterForm_render($this->_application->Form_Build($form, true, $context['filter_values']), null, true)) return;

        return '<div id="' . substr($container, 1) . '-view-filter-form' . '" class="drts-view-filter-form-external">' . $filter_form . '</div>';
    }
}