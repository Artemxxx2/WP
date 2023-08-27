<?php
namespace SabaiApps\Directories\Component\Display\Element;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Display;

class TextElement extends AbstractElement
{
    protected function _displayElementInfo(Entity\Model\Bundle $bundle)
    {
        return array(
            'type' => 'content',
            'label' => _x('Text', 'display element name', 'directories'),
            'description' => __('Adds a block of custom text', 'directories'),
            'default_settings' => array(
                'text' => null,
                'htmlize' => true,
            ),
            'icon' => 'fas fa-bars',
            'designable' => ['margin', 'padding', 'font'],
        );
    }
    
    public function displayElementSettingsForm(Entity\Model\Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        return array(
            'text' => array(
                '#title' => __('Text content', 'directories'),
                '#type' => 'textarea',
                '#horizontal' => true,
                '#default_value' => $settings['text'],
                '#required' => true,
                '#description' => $display->type === 'entity' ? $this->_application->System_Util_availableTags($this->_application->Entity_Tokens($bundle, true)) : null,
                '#description_no_escape' => true,
            ),
            'htmlize' => [
                '#title' => __('Auto-convert to HTML', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['htmlize']),
                '#horizontal' => true,
            ],
        );
    }
    
    protected function _getTextFormType()
    {
        if ($this->_application->isComponentLoaded('WordPress')) {
            return 'wp_editor';
        } 
        return 'text';
    }
    
    public function displayElementRender(Entity\Model\Bundle $bundle, array $element, $var)
    {
        $text = $this->_translateString($element['settings']['text'], 'text', $element['_element_id']);
        if ($var instanceof Entity\Type\IEntity) {
            $text = $this->_application->Entity_Tokens_replace($text, $var, true);
        }

        if (!isset($element['settings']['htmlize']) || $element['settings']['htmlize']) {
            $text = $this->_application->Htmlize($text);
        }
        return $text;
    }
    
    public function displayElementTitle(Entity\Model\Bundle $bundle, array $element)
    {
        return $this->_application->H($element['settings']['text']);
    }

    protected function _displayElementReadableInfo(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $ret = [
            'text' => [
                'label' => __('Text content', 'directories'),
                'value' => $settings['text'],
            ],
        ];
        return ['settings' => ['value' => $ret]];
    }

    public function displayElementOnSaved(Entity\Model\Bundle $bundle, Display\Model\Element $element)
    {
        $this->_registerString($element->data['settings']['text'], 'text', $element->element_id);
        $this->_unregisterString('text', $element->id); // for old versions
    }
}
