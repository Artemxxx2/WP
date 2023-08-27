<?php
namespace SabaiApps\Directories\Component\Entity\FieldRenderer;

use SabaiApps\Directories\Component\Entity;
use SabaiApps\Directories\Component\Field;

class TermContentCountFieldRenderer extends Field\Renderer\AbstractRenderer
{    
    protected function _fieldRendererInfo()
    {
        return array(
            'field_types' => array($this->_name),
            'default_settings' => array(
                'content_bundle_type' => null,
            ),
            'inlineable' => true,
        );
    }
    
    protected function _fieldRendererSettingsForm(Field\IField $field, array $settings, array $parents = [])
    {
        $form = [];
        $options = [];
        foreach ($this->_application->Entity_TaxonomyContentBundleTypes($field->Bundle->type) as $bundle_type) {
            $bundle = $field->Bundle;
            $options[$bundle_type] = $this->_application->Entity_Bundle($bundle_type, $bundle->component, $bundle->group)->getLabel('singular');
        }
        $form['content_bundle_type'] = array(
            '#title' => __('Content type', 'directories'),
            '#type' => 'select',
            '#options' => $options,
            '#default_value' => $settings['content_bundle_type'],
        );
        
        return $form;
    }

    protected function _fieldRendererRenderField(Field\IField $field, array &$settings, Entity\Type\IEntity $entity, array $values, $more = 0)
    {
        if (!empty($settings['content_bundle_type'])
            && ($count = (int)$entity->getSingleFieldValue('entity_term_content_count', '_' . $settings['content_bundle_type']))
            && ($bundle = $field->Bundle)
            && ($content_bundle = $this->_application->Entity_Bundle($settings['content_bundle_type'], $bundle->component, $bundle->group))
        ) {
            return sprintf(_n($content_bundle->getLabel('count'), $content_bundle->getLabel('count2'), $count), $count);
        }
    }
}