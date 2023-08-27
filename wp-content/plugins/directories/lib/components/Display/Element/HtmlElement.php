<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class HtmlElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('HTML', 'display element name', 'directories'),
            'description' => __('Renders raw HTML', 'directories'),
            'default_settings' => array(
                'html' => '',
            ),
            'icon' => 'fas fa-code',
            'designable' => ['margin', 'padding'],
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'html' => array(
                '#type' => 'editor',
                '#language' => 'xml',
                '#default_value' => $settings['html'],
                '#required' => true,
            ),
        );
    }

    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        return $element['settings']['html'];
    }

    public function displayElementTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return $this->_application->H($element['settings']['html']);
    }

    public function displayElementReadableSettings(Entity\Model\Bundle $bundle, array $settings)
    {
        $ret = [
            'html' => [
                'label' => 'HTML',
                'value' => '<code>' . $this->_application->H($settings['html']) . '</code>',
                'is_html' => true,
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }
}