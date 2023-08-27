<?php
namespace SabaiApps\Directories\Component\Entity\FieldWidget;

use SabaiApps\Directories\Request;
use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermTaggingFieldWidget extends Field\Widget\AbstractWidget
{
    protected function _fieldWidgetInfo()
    {
        return array(
            'label' => __('Text input field', 'directories'),
            'field_types' => array('entity_terms'),
            'accept_multiple' => true,
            'default_settings' => array(
                'enhanced_ui' => true,
                'tagging' => true,
                'separator' => ',',
                'placeholder' => null,
            ),
        );
    }

    public function fieldWidgetSettingsForm($fieldType, Entity\Model\Bundle $bundle, array $settings, array $parents = [], array $rootParents = [])
    {
        return array(
            'enhanced_ui' => array(
                '#type' => 'checkbox',
                '#title' => __('Enable enhanced user interface', 'directories'),
                '#description' => sprintf(__('Check this to apply the jQuery %s plugin to this field to enable ajax auto-suggestion and more user friendly input method.', 'directories'), '<a href="http://ivaynberg.github.com/select2/">Select2</a>'),
                '#default_value' => $settings['enhanced_ui'],
                '#description_no_escape' => true,
            ),
            'tagging' => array(
                '#type' => 'checkbox',
                '#title' => __('Allow adding new items', 'directories'),
                '#description' => __('Check this to allow the user to add new items that do not currently exist in the list.', 'directories'),
                '#default_value' => $settings['tagging'],
            ),
            'separator' => array(
                '#type' => 'textfield',
                '#title' => __('Separator', 'directories'),
                '#description' => __('Enter a text string used to separate multiple terms.', 'directories'),
                '#default_value' => $settings['separator'],
                '#states' => array(
                    'visible' => array(
                        sprintf('input[name="%s[enhanced_ui]"]', $this->_application->Form_FieldName($parents)) => array(
                            'type' => 'checked', 
                            'value' => false,
                        ),
                    ),
                ),
                '#required' => array(array($this, 'isSeparatorRequired'), array($parents)),
                '#size' => 5,
            ),
            'placeholder' => [
                '#type' => 'textfield',
                '#title' => __('Placeholder', 'directories'),
                '#default_value' => $settings['placeholder'],
            ],
        );
    }
    
    public function isSeparatorRequired($form, $parents)
    {
        $values = $form->getValue($parents);
        return $values['enhanced_ui'] === false;
    }

    public function fieldWidgetForm(Field\IField $field, array $settings, $value = null, Entity\Type\IEntity $entity = null, array $parents = [], $language = null)
    {
        if (!$bundle = $field->getTaxonomyBundle()) return;
        
        $tagging = $settings['tagging'] && $this->_application->HasPermission('entity_assign_' . $bundle->name);
        if ($settings['enhanced_ui']) {
            return array(
                '#type' => 'autocomplete',
                '#multiple' => $field->getFieldMaxNumItems() !== 1,
                '#max_selection' => $field->getFieldMaxNumItems(),
                '#select2' => true,
                '#select2_ajax' => true,
                '#select2_ajax_url' => $this->_application->MainUrl(
                    '/_drts/entity/' . $bundle->type . '/query',
                    array('bundle' => $bundle->name, Request::PARAM_CONTENT_TYPE => 'json', 'language' => $language),
                    '',
                    '&'
                ),
                '#select2_item_id_key' => 'title',
                '#select2_tags' => $tagging,
                '#element_validate' => array(array(array($this, 'validateTerms'), array($bundle, $tagging))),
                '#default_value' => $value,
                '#default_options_callback' => array(array($this, '_getDefaultOptions'), array($bundle)),
                '#disabled' => !$this->_application->HasPermission('entity_assign_' . $bundle->name),
                '#placeholder' => $settings['placeholder'],
            );
        }
        
        if (isset($value)) {
            foreach (array_keys($value) as $key) {
                if (!$value[$key] instanceof Entity\Type\IEntity) continue;

                $value[$key] = $this->_application->Entity_Title($value[$key]);
            }
        }
        return array(
            '#type' => 'textfield',
            '#element_validate' => array(array(array($this, 'validateTerms'), array($bundle, $tagging))),
            '#separator' => $settings['separator'],
            '#max_selection' => $field->getFieldMaxNumItems(),
            '#default_value' => $value,
            '#disabled' => !$this->_application->HasPermission('entity_assign_' . $bundle->name),
            '#placeholder' => $settings['placeholder'],
            // Skip text validation if WordPress admin, since it comes as an array of IDs and already validated
            '#skip_validate_text' => $this->_application->getPlatform()->getName() === 'WordPress' && $this->_application->getPlatform()->isAdmin(),
        );
    }
    
    public function fieldWidgetSupports($fieldOrFieldType)
    {
        return is_object($fieldOrFieldType) // default field should already exists
            && ($bundle = $fieldOrFieldType->getTaxonomyBundle())
            && empty($bundle->info['is_hierarchical']);
    }
    
    public function _getDefaultOptions($defaultValue, array &$options, $bundle)
    {
        foreach (array_keys($defaultValue) as $key) {
            if (!$defaultValue[$key] instanceof Entity\Type\IEntity) continue;
            
            $options[$defaultValue[$key]->getTitle()] = $this->_application->Entity_Title($defaultValue[$key]);
            unset($defaultValue[$key]);
        }
        if (!empty($defaultValue)) {
            // IDs are term slugs
            foreach ($this->_application->Entity_Types_impl($bundle->entitytype_name)->entityTypeEntitiesBySlugs($bundle->name, $defaultValue) as $term) {
                $options[$term->getTitle()] = $this->_application->Entity_Title($term);
            }
        }
    }

    public function validateTerms($form, &$value, $element, $bundle, $tagging)
    {
        if (empty($value)) return;

        $terms = [];
        foreach ((array)$value as $term) {
            if (is_int($term)) continue; // Term ID passed for manual validation, ex. tagging from WP admin

            // For some reason values sometimes comes encoded
            $term = rawurldecode($term);

            $terms[$term] = $term;
        }
        if (empty($terms)) return;
        
        $entity_type_impl = $this->_application->Entity_Types_impl($bundle->entitytype_name);
        $value = [];
        foreach ($entity_type_impl->entityTypeEntitiesByTitles($bundle->name, $terms) as $term) {
            $value[$term->getId()] = $term->getTitle();
        }
        $new_terms = array_diff_key($terms, array_flip($value));
        $value = array_keys($value);
        
        if (empty($new_terms)) return; // no new terms
        
        // Check permission to create new tags
        if (!$tagging) {
            $form->setError(sprintf(
                __('The following %s do not exist: %s', 'directories'),
                strtolower($bundle->getLabel()),
                implode(', ', $new_terms)
            ), $element);
            return;
        }

        foreach ($new_terms as $new_term) {
            $term = $entity_type_impl->entityTypeCreateEntity(
                $bundle,
                array('title' => $new_term),
                $this->_application->getUser()->getIdentity()
            );
            $value[] = $term->getId();
        }
    }
}