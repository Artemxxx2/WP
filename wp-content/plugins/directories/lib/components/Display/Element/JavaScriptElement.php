<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class JavaScriptElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('JavaScript', 'display element name', 'directories'),
            'description' => __('Renders raw JavaScript code', 'directories'),
            'default_settings' => array(
                'js' => '',
            ),
            'designable' => false,
            'icon' => 'far fa-file-code',
            'headingable' => false,
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'js' => array(
                '#description' => __('Enter raw JavaScript code without <script> tags.', 'directories'),
                '#type' => 'editor',
                '#language' => 'javascript',
                '#default_value' => $settings['js'],
                '#required' => true,
            ),
        );
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        return strlen($element['settings']['js']) ? array('raw' => '<script type="text/javascript">' . $element['settings']['js'] . '</script>') : null;
    }

    public function displayElementTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return $this->_application->H($element['settings']['js']);
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'html' => [
                'label' => 'JavaScript',
                'value' => '<code>' . $this->_application->H($settings['js']) . '</code>',
                'is_html' => true,
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}