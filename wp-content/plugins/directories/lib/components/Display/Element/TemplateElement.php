<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class TemplateElement extends AbstractElement
{
    private $_templates, $_cssLoaded = [];
    
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('Template', 'display element name', 'directories'),
            'description' => __('Load and display template file', 'directories'),
            'default_settings' => array(
                'template' => null,
            ),
            'icon' => 'fab fa-php',
            'designable' => ['margin', 'padding'],
        );
    }
    
    protected function _displayElementSupports(Entity\Model\Bundle $bundle, Display\Model\Display $display)
    {
        return $display->type === 'entity';
    }
    
    protected function _getTemplates(Entity\Model\Bundle $bundle)
    {
        if (!isset($this->_templates)) {
            $this->_templates = [];
            $file_name_prefix = $bundle->type . '-element_';
            foreach ($this->_application->getPlatform()->getCustomAssetsDir(false) as $assets_dir) {
                if (!is_dir($assets_dir)) continue;

                foreach (new \DirectoryIterator($assets_dir) as $finfo) {
                    if (!$finfo->isFile()
                        || strpos($finfo->getFilename(), $file_name_prefix) !== 0
                        || substr($finfo->getFilename(), -9) !== '.html.php'
                    ) continue;
                
                    $file_name = substr($finfo->getFilename(), 0, -9);
                    
                    // Is this an component specific template file?
                    if (($file_name_arr = explode('-', $file_name))
                        && count($file_name_arr) === 3
                    ) {
                        if ($file_name_arr[2] !== $bundle->component) continue;
                        
                        $file_name = $file_name_arr[0] . '-' . $file_name_arr[1]; // remove the component name part
                    }
                    
                    if (isset($this->_templates[$file_name])) continue;
                    
                    $this->_templates[$file_name] = ucwords(str_replace('_', ' ', substr($file_name, strlen($file_name_prefix))));
                }
            }
        }
        
        return $this->_templates;
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'template' => array(
                '#title' => __('Template file', 'directories'),
                '#type' => 'select',
                '#options' => array('' => __('— Select —', 'directories')) + $this->_getTemplates($bundle),
                '#horizontal' => true,
                '#default_value' => $settings['template'],
                '#required' => true,
                '#empty_value' => '',
                '#description' => sprintf(
                    $this->_application->H(__('See %s for details on creating template files.', 'directories')),
                    '<a href="https://directoriespro.com/documentation/advanced-customization/template-files.html" target="_blank">https://directoriespro.com/documentation/advanced-customization/template-files.html</a>'
                ),
                '#description_no_escape' => true,
            ),
        );
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $settings = $element['settings'];
        if (empty($settings['template'])
            || (!$template_path = $this->_templateExists($bundle, $settings['template']))
        ) return;
        
        $css_class = $this->_getCssClass($bundle, $settings);
        
        if (!isset($this->_cssLoaded[$settings['template']])) {
            // Load CSS file if any
            if ($css_url = $this->_cssExists($template_path)) {
                $this->_application->getPlatform()->addCssFile($css_url, $css_class, ['drts'], false);
                $this->_cssLoaded[$settings['template']] = true;
            } else {
                $this->_cssLoaded[$settings['template']] = false;
            }
        }

        ob_start();
        $this->_application->getTemplate()->includeFile($template_path, array('entity' => $var));
        $html = ob_get_clean();
        
        return [
            'html' => $html,
            'class' => $css_class,
        ];
    }
    
    protected function _templateExists(Entity\Model\Bundle $bundle, $templateName)
    {
        foreach (array($templateName . '-' . $bundle->component, $templateName) as $_template_name) {
            if ($template_path = $this->_application->getTemplate()->exists($_template_name)) {
                return $template_path;
            }
        }
        return false;
    }
    
    protected function _cssExists($templatePath)
    {
        $css_file = str_replace('.html.php', '.css', $templatePath);
        return file_exists($css_file) ? $this->_application->FileUrl($css_file) : false;
    }
    
    protected function _getCssClass(Entity\Model\Bundle $bundle, array $settings)
    {
        return 'drts-display-element-template-name-' . str_replace($bundle->type . '-element_', '', $settings['template']);
    }
    
    public function displayElementTitle(Entity\Model\Bundle $bundle, array $element)
    {
        if ('' === $title = parent::displayElementTitle($bundle, $element)) {
            $title = $this->_application->H($this->_getTemplateLabel($bundle, $element['settings']));
        }
        return $title;
    }
    
    protected function _getTemplateLabel(Entity\Model\Bundle $bundle, array $settings)
    {
        $templates = $this->_getTemplates($bundle);
        return isset($templates[$settings['template']]) ? $templates[$settings['template']] : '';
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'template' => [
                'label' => __('Template file', 'directories'),
                'value' => $settings['template'],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}