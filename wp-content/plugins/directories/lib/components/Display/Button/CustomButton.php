<?php
namespace SabaiApps\Directories\Component\Display\Button;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field\Type\ILinkable;

class CustomButton extends AbstractButton
{    
    protected function _displayButtonInfo(Entity\Model\Bundle $bundle)
    {
        $info = array(
            'label' => __('Custom buttons', 'directories'),
            'default_settings' => array(
                '_label' => '',
                '_color' => 'secondary',
                '_icon' => '',
                'link_type' => 'current',
                'url' => null,
                'path' => null,
                'fragment' => null,
                'link_field' => null,
                'link_field_qstr' => null,
                'target' => '',
                'rel' => null,
            ),
            'multiple' => [],
            'weight' => 50,
        );
        foreach ($this->_application->Filter('entity_button_custom_button_num', range(1, 3), array($bundle)) as $num) {
            $info['multiple'][$num] = array(
                'default_checked' => $num === 1,
                'label' => sprintf(__('Custom button #%d', 'directories'), $num)
            );
        }
        
        return $info;
    }
    
    public function displayButtonSettingsForm(Entity\Model\Bundle $bundle, array $settings, array $parents = [])
    {
        $ret = array(
            'link_type' => array(
                '#type' => 'select',
                '#options' => array(
                    'current' => __('Link to current content URL', 'directories'),
                    'url' => __('Link to external URL', 'directories'),
                ),
                '#title' => __('Button link type', 'directories'),
                '#horizontal' => true,
                '#default_value' => $settings['link_type'],
                '#weight' => 1,
            ),
            'path' => array(
                '#title' => __('Extra URL path', 'directories'),
                '#type' => 'textfield',
                '#field_prefix' => '/',
                '#default_value' => $settings['path'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'current')
                    ),
                ),
                '#weight' => 2,
            ),
            'fragment' => array(
                '#title' => __('URL fragment identifier', 'directories'),
                '#description' => __('Add a fragment identifier to the link URL in order to link to a specific section of the page.', 'directories'),
                '#type' => 'textfield',
                '#field_prefix' => '#',
                '#default_value' => $settings['fragment'],
                '#horizontal' => true,
                '#states' => array(
                    'visible' => array(
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'current')
                    ),
                ),
                '#weight' => 3,
            ),
            'url' => array(
                '#type' => 'url',
                '#placeholder' => 'https://',
                '#default_value' => $settings['url'],
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'url'],
                    ],
                ],
                '#description' => $this->_application->System_Util_availableTags($this->_application->Entity_Tokens($bundle, true)),
                '#description_no_escape' => true,
                '#max_length' => 0,
                '#weight' => 2,
            ),
            'target' => [
                '#title' => __('Open link in', 'directories'),
                '#type' => 'select',
                '#options' => $this->_getLinkTargetOptions(),
                '#default_value' => $settings['target'],
                '#horizontal' => true,
                '#weight' => 10,
            ],
            'rel' => [
                '#title' => __('Link "rel" attribute', 'directories'),
                '#type' => 'checkboxes',
                '#options' => $this->_getLinkRelAttrOptions(),
                '#default_value' => $settings['rel'],
                '#horizontal' => true,
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'url'],
                    ],
                ],
                '#weight' => 11,
            ],
        );
        if (!empty($bundle->info['parent'])) {
            $ret['link_type']['#options']['parent'] = __('Link to parent page', 'directories'); 
        }

        if ($linkable_fields = $this->_application->Entity_Field_options($bundle, ['interface' => 'Field\Type\ILinkable', 'return_disabled' => true])) {
            $ret['link_type']['#options']['field'] = __('Link to URL of another field', 'directories');
            $ret['link_field'] = [
                '#type' => 'select',
                '#default_value' => $settings['link_field'],
                '#options' => $linkable_fields[0],
                '#options_disabled' => $linkable_fields[1],
                '#required' => function($form) use ($parents) { return in_array($form->getValue(array_merge($parents, ['link_type'])), ['field']); },
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'field'],
                    ],
                ],
                '#weight' => 2,
                '#horizontal' => true,
            ];
            $ret['link_field_qstr'] = [
                '#type' => 'textfield',
                '#default_value' => $settings['link_field_qstr'],
                '#field_prefix' => __('Additional query string', 'directories'),
                '#placeholder' => 'a=1&b=2&c=3',
                '#states' => [
                    'visible' => [
                        sprintf('select[name="%s[link_type]"]', $this->_application->Form_FieldName($parents)) => ['value' => 'field'],
                    ],
                ],
                '#weight' => 2,
                '#horizontal' => true,
            ];
        }
        
        return $ret;
    }

    protected function _getLinkTargetOptions()
    {
        return [
            '' => __('Current window', 'directories'),
            '_blank' => __('New window', 'directories'),
        ];
    }

    protected function _getLinkRelAttrOptions()
    {
        return [
            'nofollow' => __('Add "nofollow"', 'directories'),
            'external' => __('Add "external"', 'directories'),
            'ugc' => __('Add "ugc"', 'directories'),
            'sponsored' => __('Add "sponsored"', 'directories'),
        ];
    }
    
    public function displayButtonLink(Entity\Model\Bundle $bundle, Entity\Type\IEntity $entity, array $settings, $displayName)
    {
        switch ($settings['link_type']) {
            case 'current':
                if (empty($settings['path'])) {
                    $url = $this->_application->Entity_PermalinkUrl($entity, $settings['fragment']);
                } else {
                    $url = $this->_application->Entity_Url($entity, $settings['path'], [], $settings['fragment']);
                }
                break;
            case 'parent':
                if (!$parent = $this->_application->Entity_ParentEntity($entity)) return;

                if (empty($settings['path'])) {
                    $url = $this->_application->Entity_PermalinkUrl($parent, $settings['fragment']);
                } else {
                    $url = $this->_application->Entity_Url($parent, $settings['path'], [], $settings['fragment']);
                }
                break;
            case 'url':
                $url = $this->_application->Entity_Tokens_replace($settings['url'], $entity, true, false, true);
                break;
            case 'field':
                if (empty($settings['link_field'])
                    || (!$link_field = $this->_application->Entity_Field($entity, $settings['link_field']))
                    || (!$link_field_type = $this->_application->Field_Type($link_field->getFieldType(), true))
                    || !$link_field_type instanceof ILinkable
                    || (!$url = $link_field_type->fieldLinkableUrl($link_field, $entity))
                ) return;

                if (isset($settings['link_field_qstr']) && strlen($settings['link_field_qstr'])) {
                    $separator = strpos($url, '?') === false ? '?' : '&';
                    $url .= $separator . $settings['link_field_qstr'];
                }
                break;
            default:
                return;
        }
        $attr = [
            'class' => $settings['_class'],
            'style' => $settings['_style'],
        ];
        if (!empty($settings['target'])) {
            $attr['target'] = $settings['target'];
        }
        if (!empty($settings['rel'])) {
            $attr['rel'] = implode(' ', $settings['rel']);
        }

        return $this->_application->LinkTo($settings['_label'], ['script_url' => (string)$url], ['icon' => $settings['_icon']], $attr);
    }
}
