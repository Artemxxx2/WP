<?php
namespace SabaiApps\Directories\Component\Entity\DisplayElement;

use SabaiApps\Directories\Component\Display;
use SabaiApps\Directories\Exception;
use SabaiApps\Directories\Component\Entity\Model\Bundle;

class FieldDisplayElement extends Display\Element\AbstractElement
{
    protected $_field = [];
    
    protected function _getField($bundle)
    {
        if (!$field = $this->_doGetField($bundle)) {
            throw new Exception\RuntimeException(sprintf('Invalid field for element %s, bundle %s', $this->_name, $bundle->name));
        }
        return $field;
    }
    
    protected function _doGetField($bundle)
    {
        $field_name = substr($this->_name, 13); // remove entity_field_ part
        return $this->_application->Entity_Field($bundle, $field_name);
    }
    
    protected function _displayElementSupports(Bundle $bundle, Display\Model\Display $display)
    {
        if ($display->type !== 'entity') return false;

        if (!empty($bundle->info['no_title'])
            && !empty($bundle->info['no_content'])
            && $this->_getField($bundle)->getFieldType() === 'entity_title'
        ) {
            // Do not enable since there is no way to render title
            return false;
        }

        return true;
    }
        
    protected function _displayElementSupportsAmp(Bundle $bundle, Display\Model\Display $display)
    {
        foreach (array_keys($this->_getRenderers($bundle)) as $renderer) {
            if ($this->_application->Field_Renderers_impl($renderer)->fieldRendererSupportsAmp($bundle)) {
                return true;
            }
        }
        return false;
    }
    
    protected function _displayElementInfo(Bundle $bundle)
    {
        $field = $this->_getField($bundle);
        $field_type = $this->_application->Field_Type($field->getFieldType());
        $label = $field->getFieldLabel();
        if (!strlen($label)) {
            $label = $field_type->fieldTypeInfo('label');
        }
        return array(
            'type' => ($element_type = $field_type->fieldTypeInfo('display_element_type')) ? $element_type : 'field',
            'label' => $label,
            'description' => sprintf(__('Field name: %s', 'directories'), $field->getFieldName()),
            'default_settings' => array(
                'label' => null,
                'label_custom' => null,
                'label_icon' => null,
                'label_icon_size' => null,
                'label_as_heading' => false,
                'renderer' => null,
                'renderer_settings' => [],
            ),
            'pre_render' => true,
            'icon' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('icon'),
            'headingable' => false,
            'cacheable' => $this->_application->Field_Type($field->getFieldType())->fieldTypeInfo('cacheable'),
            'designable' => ['margin', 'font'],
        );
    }
    
    protected function _getRenderers(Bundle $bundle)
    {
        $field_types = $this->_application->Field_Types();
        $field = $this->_getField($bundle);
        $renderers = (array)@$field_types[$field->getFieldType()]['renderers'];
        foreach (array_keys($renderers) as $renderer) {
            if ((!$field_renderer = $this->_application->Field_Renderers_impl($renderer, true))
                || !$field_renderer->fieldRendererSupports($bundle, $field)
            ) {
                unset($renderers[$renderer]);
                continue;
            }
        }
        return $renderers;
    }
    
    public function displayElementSettingsForm(Bundle $bundle, array $settings, Display\Model\Display $display, array $parents = [], $tab = null, $isEdit = false, array $submitValues = [])
    {
        if (!$renderers = $this->_getRenderers($bundle)) return;
        
        $field = $this->_getField($bundle);

        $form = $this->_application->Display_ElementLabelSettingsForm($settings, $parents) + array(
            'label_as_heading' => array(
                '#title' => __('Show label as heading', 'directories'),
                '#type' => 'checkbox',
                '#default_value' => !empty($settings['label_as_heading']),
                '#horizontal' => true,
                '#states' => array(
                    'invisible' => array(
                        sprintf('select[name="%s[label]"]', $this->_application->Form_FieldName($parents)) => array('value' => 'none'),
                    ),
                ),
                '#weight' => -2,
            ),
            'renderer_settings' => array(
                '#tree' => true,
            ),
        );
        if (isset($settings['renderer'])) {
            // Compat for <1.2.42
            if ($settings['renderer'] === 'photoslider') {
                $settings['renderer'] = 'slider_photos';
            }

            $renderer = $settings['renderer'];
        } else {
            $field_types = $this->_application->Field_Types();
            $field_type = $field->getFieldType();
            $renderer = isset($field_types[$field_type]['default_renderer']) ? $field_types[$field_type]['default_renderer'] : null;
        }
        $form['renderer'] = array(
            '#type' => 'select',
            '#title' => __('Field renderer', 'directories'),
            '#description' => __('A field renderer determines how the value of a field will be displayed.', 'directories'),
            '#options' => $renderers,
            '#weight' => -1,
            '#default_value' => $renderer,
            '#horizontal' => true,
            '#option_no_escape' => true,
        );
        foreach (array_keys($renderers) as $renderer) {
            $field_renderer = $this->_application->Field_Renderers_impl($renderer);
            $renderer_settings = (array)@$settings['renderer_settings'][$renderer] + (array)$field_renderer->fieldRendererInfo('default_settings');
            $renderer_settings_parents = $parents;
            $renderer_settings_parents[] = 'renderer_settings';
            $renderer_settings_parents[] = $renderer;
            if ($display->isAmp()) {
                $renderer_settings_form = $field_renderer->fieldRendererAmpSettingsForm($field, $renderer_settings, $renderer_settings_parents);
            } else {
                $renderer_settings_form = $field_renderer->fieldRendererSettingsForm($field, $renderer_settings, $renderer_settings_parents);
            }
            if ($renderer_settings_form) {          
                $form['renderer_settings'][$renderer] = $renderer_settings_form;
                foreach (array_keys($form['renderer_settings'][$renderer]) as $key) {
                    if (!isset($form['renderer_settings'][$renderer][$key]['#horizontal'])
                        && false === strpos($key, '#')
                    ) {
                        $form['renderer_settings'][$renderer][$key]['#horizontal'] = true;
                    }
                }
                $form['renderer_settings'][$renderer]['#states']['visible'] = array(
                    sprintf('[name="%s[renderer]"]', $this->_application->Form_FieldName($parents)) => array('value' => $renderer),
                );
            } else {
                $form['renderer_settings'][$renderer] = [];
            }
            if ($emptiable = $field_renderer->fieldRendererInfo('emptiable')) {
                $form['renderer_settings'][$renderer]['_render_empty'] = array(
                    '#type' => 'hidden',
                    '#value' => true,
                );
            } elseif ($field_renderer->fieldRendererInfo('no_imageable')) {
                $form['renderer_settings'][$renderer]['_render_empty'] = array(
                    '#type' => 'checkbox',
                    '#title' => __('Display "No Image" image if nothing to display', 'directories'),
                    '#default_value' => !empty($renderer_settings['_render_empty']),
                    '#horizontal' => true,
                    '#weight' => 300,
                );
            }
        }

        if ($field->getFieldData('disabled')) {
            $form['#header'] = [
                [
                    'level' => 'warning',
                    'message' => $this->_application->H(__('The field is disabled and will not be displayed.', 'directories')),
                ],
            ];
        }
        
        return $form;
    }

    public function displayElementRender(Bundle $bundle, array $element, $var)
    {
        $field = $this->_getField($bundle);
        if ($field->getFieldData('disabled')) return '';
        
        // Get renderer settings
        $settings = $element['settings'];
        if (empty($settings['renderer'])) {
            $this->_application->logWarning('No field renderer set for display element: ' . $element['element_id'] . ' (field: ' . $field->getFieldName() . ').');
            return '';
        }
        $renderer_settings = isset($settings['renderer_settings'][$settings['renderer']]) ? $settings['renderer_settings'][$settings['renderer']] : [];

        // Init renderer
        try {
            $renderer = $this->_application->Field_Renderers_impl($settings['renderer']);
            $renderer->fieldRendererInit($field, $renderer_settings);
        } catch (Exception\IException $e) {
            $this->_application->logError('Could not fetch or init field renderer for display element: ' . $element['element_id'] . ' (field: ' . $field->getFieldName() . '). Error: ' . $e->getMessage());
            return '';
        }

        // Render values
        $values = $var->getFieldValue($field->getFieldName());
        if (empty($values)) {
            if (empty($renderer_settings['_render_empty'])
                || $values === false // forced to not display, for example through conditional rules
            ) return '';

            $values = [];
        }
        $html = $this->_application->callHelper(
            'Entity_Field_renderBySettingsReference',
            [$var, $field->getFieldName(), $settings['renderer'], &$renderer_settings, $values]
        );

        // Nothing to show
        if ($html === '') return '';
        
        if (!is_array($html)) {
            $html = ['html' => $html, 'class' => ''];
        } else {
            if (!isset($html['class'])) $html['class'] = '';
        }
        
        // Link image?
        if (isset($html['url'])) {
            if (isset($html['target'])
                && $html['target'] === '_blank'
            ) {
                $html['attr']['onclick'] = 'window.open(\'' . $html['url'] . '\', \'_blank\'); return false;';
            } else {
                $html['attr']['onclick'] = 'location.href=\'' . $html['url'] . '\'; return false;';
            }
            unset($html['url'], $html['target']);
            $html['class'] .= ' drts-display-element-with-link';
        }

        $label = $this->_application->Display_ElementLabelSettingsForm_label($settings, $this->displayElementStringId('label', $element['_element_id']), $field->getFieldLabel(true));
        if (!strlen($label)) return $html;

        $label_class = 'drts-entity-field-label drts-entity-field-label-type-' . $settings['label'];
        if (!empty($settings['label_as_heading'])) {
            $label_class .= ' drts-display-element-header';
            $label = '<span>' . $label . '</span>';
        }
        $html['html'] = '<div class="' . $label_class . '">' . $label . '</div>'
            . '<div class="drts-entity-field-value">' . $html['html'] . '</div>';
        
        return $html;
    }
    
    public function displayElementIsInlineable(Bundle $bundle, array $settings)
    {
        if ($renderer = $this->_application->Field_Renderers_impl($settings['renderer'], true)) {
            return (bool)$renderer->fieldRendererInfo('inlineable');
        }
        parent::displayElementIsInlineable($bundle, $settings);
    }
    
    public function displayElementTitle(Bundle $bundle, array $element)
    {
        $field = $this->_getField($bundle);
        return $this->_application->Display_ElementLabelSettingsForm_label($element['settings'], null, $field->getFieldLabel());
    }
    
    public function displayElementIsPreRenderable(Bundle $bundle, array &$element)
    {
        $renderer = $element['settings']['renderer'];     
        if (!$renderer_impl = $this->_application->Field_Renderers_impl($renderer, true)) return false;
        
        $field = $this->_getField($bundle);
        $renderer_settings = isset($element['settings']['renderer_settings'][$renderer]) ? $element['settings']['renderer_settings'][$renderer] : [];  
        return $renderer_impl->fieldRendererIsPreRenderable($field, $renderer_settings);
    }
    
    public function displayElementPreRender(Bundle $bundle, array $element, &$var)
    {
        $renderer = $element['settings']['renderer'];    
        if (!$renderer_impl = $this->_application->Field_Renderers_impl($renderer, true)) return;
        
        $field = $this->_getField($bundle);
        $renderer_settings = isset($element['settings']['renderer_settings'][$renderer]) ? $element['settings']['renderer_settings'][$renderer] : [];    
        $renderer_impl->fieldRendererPreRender($field, $renderer_settings, $var['entities']);
    }
    
    public function displayElementOnSaved(Bundle $bundle, Display\Model\Element $element)
    {
        if (isset($element->data['settings']['label'])
            && in_array($element->data['settings']['label'], array('custom', 'custom_icon'))
        ) {
            $this->_registerString($element->data['settings']['label_custom'], 'label', $element->element_id);
        } else {
            $this->_unregisterString('label', $element->element_id);
        }
        $this->_unregisterString('label', $element->id); // for old versions
    }

    protected function _displayElementReadableInfo(Bundle $bundle, Display\Model\Element $element)
    {
        $settings = $element->data['settings'];
        $field = $this->_getField($bundle);
        $ret = [
            'field' => [
                'label' => __('Field name', 'directories'),
                'value' => $field->getFieldName(),
            ],
        ];
        if (isset($settings['renderer'])) {
            $renderers = $this->_getRenderers($bundle);
            if (isset($renderers[$settings['renderer']])) {
                $ret['field_renderer'] = [
                    'label' => __('Field renderer', 'directories'),
                    'value' => $renderers[$settings['renderer']] . ' (' . $settings['renderer'] . ')',
                ];
                if (isset($settings['renderer_settings'][$settings['renderer']])) {
                    $renderer_settings = $settings['renderer_settings'][$settings['renderer']];
                    if ($renderer = $this->_application->Field_Renderers_impl($settings['renderer'])) {
                        if ($default_settings = $renderer->fieldRendererInfo('default_settings')) {
                            $renderer_settings += $default_settings;
                        }
                        if ($readable_settings = $renderer->fieldRendererReadableSettings($field, $renderer_settings)) {
                            $ret += $readable_settings;
                        }
                    }
                }
            }
        }
        return [
            'settings' => ['value' => $ret],
        ];
    }

    public function displayElementIsDisabled(Bundle $bundle, array $settings)
    {
        return ($field = $this->_getField($bundle)) && $field->getFieldData('disabled');
    }
}
